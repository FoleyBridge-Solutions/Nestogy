<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiSecurityMiddleware
 *
 * Comprehensive API security middleware that handles rate limiting,
 * API versioning, request validation, and security monitoring.
 */
class ApiSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if API is enabled
        if (! config('security.api.enabled', true)) {
            return $this->apiDisabledResponse();
        }

        // Validate API version
        if (! $this->validateApiVersion($request)) {
            return $this->invalidVersionResponse($request);
        }

        // Check rate limits
        if (! $this->checkRateLimit($request)) {
            return $this->rateLimitExceededResponse($request);
        }

        // Validate request format
        if (! $this->validateRequestFormat($request)) {
            return $this->invalidFormatResponse($request);
        }

        // Check for suspicious patterns
        if ($this->detectSuspiciousActivity($request)) {
            return $this->suspiciousActivityResponse($request);
        }

        // Add API security headers
        $response = $next($request);
        $this->addApiSecurityHeaders($response);

        // Log API access
        $this->logApiAccess($request, $response);

        return $response;
    }

    /**
     * Validate API version.
     */
    protected function validateApiVersion(Request $request): bool
    {
        $requestedVersion = $this->getApiVersion($request);
        $supportedVersions = config('security.api.supported_versions', ['v1']);
        $deprecatedVersions = config('security.api.deprecated_versions', []);

        if (! in_array($requestedVersion, $supportedVersions)) {
            return false;
        }

        // Add deprecation warning for deprecated versions
        if (in_array($requestedVersion, $deprecatedVersions)) {
            $request->attributes->set('api_deprecation_warning', true);
        }

        $request->attributes->set('api_version', $requestedVersion);

        return true;
    }

    /**
     * Get API version from request.
     */
    protected function getApiVersion(Request $request): string
    {
        // Check URL path (e.g., /api/v1/...)
        $path = $request->path();
        if (preg_match('/api\/(v\d+)/', $path, $matches)) {
            return $matches[1];
        }

        // Check Accept header
        $accept = $request->header('Accept');
        if (preg_match('/application\/vnd\.api\+(v\d+)/', $accept, $matches)) {
            return $matches[1];
        }

        // Check custom header
        $version = $request->header('X-API-Version');
        if ($version) {
            return $version;
        }

        // Default version
        return config('security.api.default_version', 'v1');
    }

    /**
     * Check rate limits.
     */
    protected function checkRateLimit(Request $request): bool
    {
        $key = $this->getRateLimitKey($request);
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = config('security.api.rate_limit_decay', 1);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return true;
    }

    /**
     * Get rate limit key.
     */
    protected function getRateLimitKey(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            return 'api_rate_limit:user:'.$user->id;
        }

        // For unauthenticated requests, use IP
        return 'api_rate_limit:ip:'.$request->ip();
    }

    /**
     * Get max attempts based on user type.
     */
    protected function getMaxAttempts(Request $request): int
    {
        $user = $request->user();

        if ($user) {
            // Check for custom rate limits
            if (method_exists($user, 'getApiRateLimit')) {
                return $user->getApiRateLimit();
            }

            // Check user role
            $role = $user->getRole();
            $roleLimits = config('security.api.rate_limits_by_role', [
                3 => 1000, // Admin
                2 => 500,  // Tech
                1 => 100,  // Accountant
            ]);

            return $roleLimits[$role] ?? config('security.api.rate_limit_authenticated', 60);
        }

        return config('security.api.rate_limit_unauthenticated', 30);
    }

    /**
     * Validate request format.
     */
    protected function validateRequestFormat(Request $request): bool
    {
        // Check Content-Type for POST/PUT/PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->header('Content-Type');

            if (! $contentType || ! str_contains($contentType, 'application/json')) {
                return false;
            }

            // Validate JSON format
            if ($request->getContent() && ! $request->isJson()) {
                return false;
            }
        }

        // Check Accept header
        $accept = $request->header('Accept');
        if ($accept && ! str_contains($accept, 'application/json') && ! str_contains($accept, '*/*')) {
            return false;
        }

        return true;
    }

    /**
     * Detect suspicious activity.
     */
    protected function detectSuspiciousActivity(Request $request): bool
    {
        $ip = $request->ip();

        // Check if IP is temporarily blocked
        if (Cache::get('ip_blocked_'.$ip)) {
            return true;
        }

        // Check for rapid endpoint scanning
        if ($this->detectEndpointScanning($request)) {
            return true;
        }

        // Check for abnormal request patterns
        if ($this->detectAbnormalPatterns($request)) {
            return true;
        }

        return false;
    }

    /**
     * Detect endpoint scanning.
     */
    protected function detectEndpointScanning(Request $request): bool
    {
        $ip = $request->ip();
        $key = 'api_endpoints_accessed:'.$ip;
        $threshold = config('security.api.endpoint_scan_threshold', 20);
        $window = config('security.api.endpoint_scan_window', 60); // seconds

        $endpoints = Cache::get($key, []);
        $endpoint = $request->method().':'.$request->path();

        if (! in_array($endpoint, $endpoints)) {
            $endpoints[] = $endpoint;
            Cache::put($key, $endpoints, $window);
        }

        return count($endpoints) > $threshold;
    }

    /**
     * Detect abnormal request patterns.
     */
    protected function detectAbnormalPatterns(Request $request): bool
    {
        // Check for unusually large payloads
        $maxSize = config('security.api.max_payload_size', 1048576); // 1MB
        if ($request->header('Content-Length') > $maxSize) {
            return true;
        }

        // Check for suspicious user agents
        $userAgent = strtolower($request->userAgent() ?? '');
        $suspiciousAgents = ['sqlmap', 'nikto', 'scanner', 'havij', 'acunetix'];

        foreach ($suspiciousAgents as $agent) {
            if (str_contains($userAgent, $agent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add API security headers.
     */
    protected function addApiSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        // Add rate limit headers
        $request = request();
        $key = $this->getRateLimitKey($request);
        $maxAttempts = $this->getMaxAttempts($request);

        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $maxAttempts));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $response->headers->set('X-RateLimit-Reset', RateLimiter::availableIn($key));
        }

        // Add deprecation warning if applicable
        if ($request->attributes->get('api_deprecation_warning')) {
            $response->headers->set('X-API-Deprecation-Warning', 'This API version is deprecated and will be removed in future releases.');
        }
    }

    /**
     * Log API access.
     */
    protected function logApiAccess(Request $request, Response $response): void
    {
        $metadata = [
            'api_version' => $request->attributes->get('api_version'),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'response_time' => microtime(true) - LARAVEL_START,
            'response_size' => strlen($response->getContent()),
            'rate_limit_remaining' => $response->headers->get('X-RateLimit-Remaining'),
        ];

        AuditLog::logApi('API Access', $metadata, $response->getStatusCode());
    }

    /**
     * API disabled response.
     */
    protected function apiDisabledResponse(): Response
    {
        return response()->json([
            'error' => 'API is temporarily disabled',
            'message' => 'The API is currently undergoing maintenance. Please try again later.',
        ], 503);
    }

    /**
     * Invalid version response.
     */
    protected function invalidVersionResponse(Request $request): Response
    {
        $requestedVersion = $this->getApiVersion($request);
        $supportedVersions = config('security.api.supported_versions', ['v1']);

        return response()->json([
            'error' => 'Invalid API version',
            'message' => "Version '{$requestedVersion}' is not supported.",
            'supported_versions' => $supportedVersions,
        ], 400);
    }

    /**
     * Rate limit exceeded response.
     */
    protected function rateLimitExceededResponse(Request $request): Response
    {
        $key = $this->getRateLimitKey($request);
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Invalid format response.
     */
    protected function invalidFormatResponse(Request $request): Response
    {
        return response()->json([
            'error' => 'Invalid request format',
            'message' => 'The request must be in JSON format with appropriate headers.',
            'required_headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ], 400);
    }

    /**
     * Suspicious activity response.
     */
    protected function suspiciousActivityResponse(Request $request): Response
    {
        $ip = $request->ip();

        AuditLog::logSecurity('Suspicious API Activity', [
            'ip' => $ip,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ], AuditLog::SEVERITY_CRITICAL);

        // Block IP temporarily
        Cache::put('ip_blocked_'.$ip, true, now()->addHours(1));

        return response()->json([
            'error' => 'Access denied',
            'message' => 'Suspicious activity detected.',
        ], 403);
    }
}
