<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * AuditLogMiddleware
 *
 * Logs all user actions, data changes, and security events.
 * Tracks request/response details for comprehensive auditing.
 */
class AuditLogMiddleware
{
    /**
     * Routes/actions to exclude from logging
     */
    protected array $excludedRoutes = [
        'login',
        'logout',
        'password.request',
        'password.email',
        'password.reset',
        'verification.*',
        'sanctum/csrf-cookie',
    ];

    /**
     * Sensitive fields to exclude from request body logging
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'cvv',
        'ssn',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        // Log the request/response if not excluded
        if ($this->shouldLog($request)) {
            $this->logRequest($request, $response, $startTime);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged.
     */
    protected function shouldLog(Request $request): bool
    {
        // Don't log if user is not authenticated (unless it's a security event)
        if (! Auth::check() && ! $this->isSecurityEvent($request)) {
            return false;
        }

        // Check if route is excluded
        $routeName = Route::currentRouteName();
        if ($routeName) {
            foreach ($this->excludedRoutes as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    return false;
                }
            }
        }

        // Don't log GET requests to assets or static files
        if ($request->isMethod('GET') && $this->isStaticAsset($request)) {
            return false;
        }

        return true;
    }

    /**
     * Check if request is for a static asset.
     */
    protected function isStaticAsset(Request $request): bool
    {
        $path = $request->path();
        $staticExtensions = ['js', 'css', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), $staticExtensions);
    }

    /**
     * Check if this is a security-related event.
     */
    protected function isSecurityEvent(Request $request): bool
    {
        $securityRoutes = ['login', 'logout', 'password.*', 'verification.*'];
        $routeName = Route::currentRouteName();

        if ($routeName) {
            foreach ($securityRoutes as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    return true;
                }
            }
        }

        // Check for suspicious activity
        return $this->isSuspiciousRequest($request);
    }

    /**
     * Check if request appears suspicious.
     */
    protected function isSuspiciousRequest(Request $request): bool
    {
        // Check for SQL injection patterns
        $sqlPatterns = [
            '/union.*select/i',
            '/select.*from.*where/i',
            '/insert.*into.*values/i',
            '/delete.*from/i',
            '/drop.*table/i',
            '/script.*>/i',
            '/<.*iframe/i',
        ];

        $input = json_encode($request->all());
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request and response.
     */
    protected function logRequest(Request $request, Response $response, float $startTime): void
    {
        $executionTime = microtime(true) - $startTime;
        $routeName = Route::currentRouteName() ?? $request->path();

        // Determine event type
        $eventType = $this->determineEventType($request);

        // Prepare request data
        $requestData = [
            'user_id' => Auth::id(),
            'company_id' => session('company_id'),
            'event_type' => $eventType,
            'action' => $this->getActionName($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => session()->getId(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_headers' => $this->getFilteredHeaders($request),
            'request_body' => $this->getFilteredRequestBody($request),
            'response_status' => $response->getStatusCode(),
            'execution_time' => $executionTime,
            'severity' => $this->determineSeverity($request, $response),
            'metadata' => $this->getMetadata($request, $response),
        ];

        // Add model information if applicable
        if ($model = $this->getAffectedModel($request)) {
            $requestData['model_type'] = get_class($model);
            $requestData['model_id'] = $model->getKey();
        }

        AuditLog::create($requestData);
    }

    /**
     * Determine the event type based on the request.
     */
    protected function determineEventType(Request $request): string
    {
        $method = $request->method();
        $routeName = Route::currentRouteName();

        // Check for specific routes
        if ($routeName) {
            if (str_contains($routeName, 'login')) {
                return AuditLog::EVENT_LOGIN;
            }
            if (str_contains($routeName, 'logout')) {
                return AuditLog::EVENT_LOGOUT;
            }
            if (str_contains($routeName, 'api.')) {
                return AuditLog::EVENT_API;
            }
        }

        // Check for suspicious activity
        if ($this->isSuspiciousRequest($request)) {
            return AuditLog::EVENT_SECURITY;
        }

        // Determine by HTTP method
        return match ($method) {
            'POST' => AuditLog::EVENT_CREATE,
            'PUT', 'PATCH' => AuditLog::EVENT_UPDATE,
            'DELETE' => AuditLog::EVENT_DELETE,
            default => AuditLog::EVENT_ACCESS,
        };
    }

    /**
     * Get a human-readable action name.
     */
    protected function getActionName(Request $request): string
    {
        $routeName = Route::currentRouteName();
        if ($routeName) {
            return str_replace('.', ' ', $routeName);
        }

        $method = $request->method();
        $path = $request->path();

        return strtolower($method).' '.$path;
    }

    /**
     * Get filtered request headers (remove sensitive data).
     */
    protected function getFilteredHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        $filtered = [];

        $sensitiveHeaders = ['authorization', 'cookie', 'x-csrf-token', 'x-xsrf-token'];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $filtered[$key] = '[REDACTED]';
            } else {
                $filtered[$key] = is_array($value) ? implode(', ', $value) : $value;
            }
        }

        return $filtered;
    }

    /**
     * Get filtered request body (remove sensitive data).
     */
    protected function getFilteredRequestBody(Request $request): array
    {
        $data = $request->all();

        return $this->filterSensitiveData($data);
    }

    /**
     * Recursively filter sensitive data from arrays.
     */
    protected function filterSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterSensitiveData($value);
            } elseif ($this->isSensitiveField($key)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Check if a field name is sensitive.
     */
    protected function isSensitiveField(string $field): bool
    {
        foreach ($this->sensitiveFields as $sensitive) {
            if (stripos($field, $sensitive) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine severity based on response and request.
     */
    protected function determineSeverity(Request $request, Response $response): string
    {
        $status = $response->getStatusCode();

        // Security events are always at least warning
        if ($this->isSuspiciousRequest($request)) {
            return AuditLog::SEVERITY_CRITICAL;
        }

        // Determine by status code
        if ($status >= 500) {
            return AuditLog::SEVERITY_ERROR;
        } elseif ($status >= 400) {
            return AuditLog::SEVERITY_WARNING;
        }

        return AuditLog::SEVERITY_INFO;
    }

    /**
     * Get additional metadata for the log entry.
     */
    protected function getMetadata(Request $request, Response $response): array
    {
        $metadata = [
            'route_name' => Route::currentRouteName(),
            'route_action' => Route::currentRouteAction(),
            'middleware' => Route::current()?->gatherMiddleware() ?? [],
            'response_size' => strlen($response->getContent()),
            'memory_usage' => memory_get_peak_usage(true) / 1024 / 1024, // MB
        ];

        // Add route parameters
        if ($route = Route::current()) {
            $metadata['route_parameters'] = $route->parameters();
        }

        // Add validation errors if any
        if ($response->getStatusCode() === 422) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['errors'])) {
                $metadata['validation_errors'] = $content['errors'];
            }
        }

        return $metadata;
    }

    /**
     * Get the affected model from the request if applicable.
     */
    protected function getAffectedModel(Request $request)
    {
        $route = Route::current();
        if (! $route) {
            return null;
        }

        // Check route parameters for common model bindings
        $parameters = $route->parameters();
        foreach ($parameters as $key => $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                return $value;
            }
        }

        return null;
    }
}
