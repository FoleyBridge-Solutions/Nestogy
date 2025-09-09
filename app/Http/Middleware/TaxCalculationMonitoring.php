<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tax Calculation Monitoring Middleware
 * 
 * Monitors tax calculation requests for performance, errors, and reliability.
 * Provides automatic error detection, performance tracking, and alerting.
 */
class TaxCalculationMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only monitor tax calculation endpoints
        if (!$this->shouldMonitor($request)) {
            return $next($request);
        }
        
        $startTime = microtime(true);
        $monitoringId = uniqid('tax_monitor_');
        
        // Track request start
        $this->logRequestStart($request, $monitoringId);
        
        $response = null;
        $exception = null;
        
        try {
            $response = $next($request);
            
            // Check for application-level errors in JSON responses
            $this->checkResponseForErrors($response, $request, $monitoringId);
            
        } catch (\Exception $e) {
            $exception = $e;
            
            // Log the exception
            $this->logException($e, $request, $monitoringId);
            
            // Re-throw to maintain normal error handling
            throw $e;
            
        } finally {
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            // Log request completion
            $this->logRequestCompletion($request, $response, $duration, $monitoringId, $exception);
            
            // Update performance metrics
            $this->updatePerformanceMetrics($request, $duration, $exception === null);
            
            // Check for performance issues
            $this->checkPerformanceThresholds($duration, $request);
        }
        
        return $response;
    }
    
    /**
     * Determine if this request should be monitored
     */
    protected function shouldMonitor(Request $request): bool
    {
        $taxEndpoints = [
            'api/tax-engine',
            'api/voip-tax',
            'admin/tax',
        ];
        
        foreach ($taxEndpoints as $endpoint) {
            if (str_starts_with($request->path(), $endpoint)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log the start of a tax calculation request
     */
    protected function logRequestStart(Request $request, string $monitoringId): void
    {
        Log::info('Tax calculation request started', [
            'monitoring_id' => $monitoringId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_size' => strlen($request->getContent()),
            'timestamp' => now()->toISOString(),
        ]);
    }
    
    /**
     * Check response for application-level errors
     */
    protected function checkResponseForErrors(Response $response, Request $request, string $monitoringId): void
    {
        if ($response->headers->get('content-type') === 'application/json') {
            $content = $response->getContent();
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                // Check for API error responses
                if (isset($data['success']) && $data['success'] === false) {
                    $this->logApplicationError($data, $request, $monitoringId);
                }
                
                // Check for calculation errors
                if (isset($data['error']) || isset($data['calculation_error'])) {
                    $this->logCalculationError($data, $request, $monitoringId);
                }
                
                // Check for performance warnings
                if (isset($data['performance']) && isset($data['performance']['calculation_time_ms'])) {
                    $calculationTime = $data['performance']['calculation_time_ms'];
                    if ($calculationTime > 5000) { // 5 seconds
                        $this->logPerformanceWarning($calculationTime, $request, $monitoringId);
                    }
                }
            }
        }
    }
    
    /**
     * Log application-level errors
     */
    protected function logApplicationError(array $data, Request $request, string $monitoringId): void
    {
        Log::warning('Tax calculation application error', [
            'monitoring_id' => $monitoringId,
            'endpoint' => $request->path(),
            'error' => $data['error'] ?? 'Unknown error',
            'error_details' => $data['details'] ?? null,
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
            'request_data' => $this->sanitizeRequestData($request->all()),
        ]);
        
        // Increment error counter
        $this->incrementErrorCounter('application_error', $request->path());
    }
    
    /**
     * Log calculation-specific errors
     */
    protected function logCalculationError(array $data, Request $request, string $monitoringId): void
    {
        Log::error('Tax calculation error', [
            'monitoring_id' => $monitoringId,
            'endpoint' => $request->path(),
            'calculation_error' => $data['calculation_error'] ?? $data['error'],
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
            'request_data' => $this->sanitizeRequestData($request->all()),
        ]);
        
        // Increment calculation error counter
        $this->incrementErrorCounter('calculation_error', $request->path());
        
        // Check if this is a critical error pattern
        $this->checkForCriticalErrorPatterns($data, $request);
    }
    
    /**
     * Log performance warnings
     */
    protected function logPerformanceWarning(float $calculationTime, Request $request, string $monitoringId): void
    {
        Log::warning('Tax calculation performance warning', [
            'monitoring_id' => $monitoringId,
            'endpoint' => $request->path(),
            'calculation_time_ms' => $calculationTime,
            'threshold_exceeded' => '5000ms',
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
        ]);
        
        // Track slow calculations
        $this->incrementSlowCalculationCounter($request->path());
    }
    
    /**
     * Log exceptions
     */
    protected function logException(\Exception $exception, Request $request, string $monitoringId): void
    {
        Log::error('Tax calculation exception', [
            'monitoring_id' => $monitoringId,
            'endpoint' => $request->path(),
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
            'request_data' => $this->sanitizeRequestData($request->all()),
        ]);
        
        // Increment exception counter
        $this->incrementErrorCounter('exception', get_class($exception));
    }
    
    /**
     * Log request completion
     */
    protected function logRequestCompletion(Request $request, ?Response $response, float $duration, string $monitoringId, ?\Exception $exception): void
    {
        $status = $exception ? 'error' : 'completed';
        $httpStatus = $response ? $response->getStatusCode() : ($exception ? 500 : 200);
        
        Log::info('Tax calculation request completed', [
            'monitoring_id' => $monitoringId,
            'endpoint' => $request->path(),
            'status' => $status,
            'http_status' => $httpStatus,
            'duration_ms' => round($duration, 2),
            'response_size' => $response ? strlen($response->getContent()) : 0,
            'user_id' => auth()->id(),
            'company_id' => auth()->user()->company_id ?? null,
        ]);
    }
    
    /**
     * Update performance metrics in cache
     */
    protected function updatePerformanceMetrics(Request $request, float $duration, bool $success): void
    {
        $endpoint = $this->getEndpointCategory($request->path());
        $cacheKey = "tax_metrics:{$endpoint}";
        $ttl = 3600; // 1 hour
        
        Cache::lock("tax_metrics_lock:{$endpoint}", 10)->block(5, function () use ($cacheKey, $duration, $success, $ttl) {
            $metrics = Cache::get($cacheKey, [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_duration_ms' => 0,
                'min_duration_ms' => PHP_FLOAT_MAX,
                'max_duration_ms' => 0,
                'last_updated' => now()->toISOString(),
            ]);
            
            $metrics['total_requests']++;
            
            if ($success) {
                $metrics['successful_requests']++;
            } else {
                $metrics['failed_requests']++;
            }
            
            $metrics['total_duration_ms'] += $duration;
            $metrics['min_duration_ms'] = min($metrics['min_duration_ms'], $duration);
            $metrics['max_duration_ms'] = max($metrics['max_duration_ms'], $duration);
            $metrics['avg_duration_ms'] = $metrics['total_duration_ms'] / $metrics['total_requests'];
            $metrics['success_rate'] = ($metrics['successful_requests'] / $metrics['total_requests']) * 100;
            $metrics['last_updated'] = now()->toISOString();
            
            Cache::put($cacheKey, $metrics, $ttl);
        });
    }
    
    /**
     * Check performance thresholds and alert if necessary
     */
    protected function checkPerformanceThresholds(float $duration, Request $request): void
    {
        $thresholds = [
            'critical' => 10000, // 10 seconds
            'warning' => 5000,   // 5 seconds
        ];
        
        if ($duration > $thresholds['critical']) {
            $this->triggerAlert('critical_performance', [
                'endpoint' => $request->path(),
                'duration_ms' => $duration,
                'threshold_ms' => $thresholds['critical'],
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id ?? null,
            ]);
        } elseif ($duration > $thresholds['warning']) {
            $this->triggerAlert('performance_warning', [
                'endpoint' => $request->path(),
                'duration_ms' => $duration,
                'threshold_ms' => $thresholds['warning'],
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id ?? null,
            ]);
        }
    }
    
    /**
     * Check for critical error patterns
     */
    protected function checkForCriticalErrorPatterns(array $errorData, Request $request): void
    {
        $criticalPatterns = [
            'database connection',
            'timeout',
            'memory',
            'api rate limit',
            'authentication failed',
        ];
        
        $errorMessage = strtolower($errorData['error'] ?? $errorData['calculation_error'] ?? '');
        
        foreach ($criticalPatterns as $pattern) {
            if (str_contains($errorMessage, $pattern)) {
                $this->triggerAlert('critical_error_pattern', [
                    'pattern' => $pattern,
                    'error_message' => $errorMessage,
                    'endpoint' => $request->path(),
                    'user_id' => auth()->id(),
                    'company_id' => auth()->user()->company_id ?? null,
                ]);
                break;
            }
        }
    }
    
    /**
     * Increment error counters
     */
    protected function incrementErrorCounter(string $errorType, string $context): void
    {
        $cacheKey = "tax_errors:{$errorType}:{$context}";
        Cache::increment($cacheKey, 1);
        Cache::expire($cacheKey, 3600); // 1 hour TTL
        
        // Check if error rate is too high
        $errorCount = Cache::get($cacheKey, 0);
        if ($errorCount > 10) { // More than 10 errors of this type in an hour
            $this->triggerAlert('high_error_rate', [
                'error_type' => $errorType,
                'context' => $context,
                'error_count' => $errorCount,
                'time_window' => '1 hour',
            ]);
        }
    }
    
    /**
     * Increment slow calculation counter
     */
    protected function incrementSlowCalculationCounter(string $endpoint): void
    {
        $cacheKey = "tax_slow_calculations:{$endpoint}";
        Cache::increment($cacheKey, 1);
        Cache::expire($cacheKey, 3600); // 1 hour TTL
    }
    
    /**
     * Trigger monitoring alerts
     */
    protected function triggerAlert(string $alertType, array $data): void
    {
        Log::critical("Tax system alert: {$alertType}", $data);
        
        // Here you could integrate with external monitoring services
        // like Slack, PagerDuty, email notifications, etc.
        
        // Store alert in cache for dashboard display
        $alertId = uniqid('alert_');
        Cache::put("tax_alert:{$alertId}", [
            'type' => $alertType,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], 86400); // 24 hours
    }
    
    /**
     * Sanitize request data for logging
     */
    protected function sanitizeRequestData(array $data): array
    {
        // Remove sensitive information
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'credit_card'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]';
            }
        }
        
        return $data;
    }
    
    /**
     * Get endpoint category for metrics grouping
     */
    protected function getEndpointCategory(string $path): string
    {
        if (str_contains($path, 'calculate-bulk')) {
            return 'bulk_calculation';
        } elseif (str_contains($path, 'preview')) {
            return 'preview';
        } elseif (str_contains($path, 'calculate')) {
            return 'single_calculation';
        } elseif (str_contains($path, 'profile')) {
            return 'profile_lookup';
        } elseif (str_contains($path, 'admin')) {
            return 'admin_operation';
        }
        
        return 'other';
    }
}