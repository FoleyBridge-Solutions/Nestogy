<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Exception Handling Middleware
 * 
 * Provides additional context and logging for requests that result in exceptions.
 * Works in conjunction with the global exception handler.
 */
class ExceptionHandlingMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // Add request ID for tracking
        $requestId = $request->header('X-Request-ID') ?? uniqid('req_');
        $request->headers->set('X-Request-ID', $requestId);

        // Set start time for performance monitoring
        $startTime = microtime(true);

        try {
            $response = $next($request);
            
            // Log successful requests in debug mode
            if (config('app.debug') && config('app.log_successful_requests', false)) {
                $this->logSuccessfulRequest($request, $response, $startTime);
            }
            
            return $response;
            
        } catch (Throwable $exception) {
            // Add additional context to the exception
            $this->addExceptionContext($exception, $request, $startTime);
            
            // Re-throw to let the global handler process it
            throw $exception;
        }
    }

    /**
     * Add additional context to the exception
     */
    private function addExceptionContext(Throwable $exception, Request $request, float $startTime): void
    {
        $context = [
            'request_id' => $request->header('X-Request-ID'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms',
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
        ];

        // Add route information if available
        if ($request->route()) {
            $context['route'] = [
                'name' => $request->route()->getName(),
                'action' => $request->route()->getActionName(),
                'parameters' => $request->route()->parameters(),
            ];
        }

        // Add sanitized request data for POST/PUT/PATCH requests
        if (!$request->isMethod('GET')) {
            $context['request_data'] = $this->sanitizeRequestData($request->all());
        }

        // Add session information if available
        if ($request->hasSession()) {
            $context['session'] = [
                'id' => $request->session()->getId(),
                'csrf_token' => $request->session()->token(),
            ];
        }

        // Store context in exception if it supports it
        if (method_exists($exception, 'setContext')) {
            $exception->setContext($context);
        }
    }

    /**
     * Log successful requests for debugging
     */
    private function logSuccessfulRequest(Request $request, Response $response, float $startTime): void
    {
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::debug('Request completed', [
            'request_id' => $request->header('X-Request-ID'),
            'method' => $request->method(),
            'url' => $request->url(),
            'status_code' => $response->getStatusCode(),
            'execution_time' => $executionTime . 'ms',
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
        ]);
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'stripe_token', 'card_number', 'cvv', 'pin',
            'secret', 'private_key', 'access_token', 'refresh_token',
            'ssn', 'social_security_number', 'credit_card', 'bank_account',
        ];

        return $this->recursivelyRedactSensitiveData($data, $sensitiveKeys);
    }

    /**
     * Recursively redact sensitive data from arrays
     */
    private function recursivelyRedactSensitiveData(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->recursivelyRedactSensitiveData($value, $sensitiveKeys);
            }
        }

        return $data;
    }
}