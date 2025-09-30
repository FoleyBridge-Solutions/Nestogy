<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base API Client for Tax Services
 *
 * Provides common functionality for all tax API integrations including:
 * - Response caching to prevent duplicate calls
 * - Rate limiting and retry logic
 * - Error handling and logging
 * - Performance monitoring
 */
abstract class BaseApiClient
{
    protected int $companyId;

    protected string $provider;

    protected array $config;

    protected array $rateLimits;

    public function __construct(int $companyId, string $provider, array $config = [])
    {
        $this->companyId = $companyId;
        $this->provider = $provider;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->rateLimits = $this->getRateLimits();
    }

    /**
     * Get default configuration for this API client
     */
    protected function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 1000, // milliseconds
            'cache_days' => 30,
            'enable_caching' => true,
            'enable_rate_limiting' => true,
            'log_requests' => true,
        ];
    }

    /**
     * Get rate limits for this API provider
     */
    abstract protected function getRateLimits(): array;

    /**
     * Make an API request with caching and rate limiting
     */
    protected function makeRequest(
        string $queryType,
        array $parameters,
        callable $apiCall,
        ?int $cacheDays = null
    ): array {
        $cacheDays = $cacheDays ?? $this->config['cache_days'];

        // Check cache first
        if ($this->config['enable_caching']) {
            $cached = TaxApiQueryCache::findCachedResponse(
                $this->companyId,
                $this->provider,
                $queryType,
                $parameters
            );

            if ($cached) {
                $this->logCacheHit($queryType, $parameters);

                return $cached->api_response;
            }
        }

        // Check rate limits
        if ($this->config['enable_rate_limiting'] && ! $this->checkRateLimit($queryType)) {
            $errorMessage = "Rate limit exceeded for {$this->provider} {$queryType}";
            $this->logRateLimit($queryType, $parameters, $errorMessage);

            TaxApiQueryCache::cacheError(
                $this->companyId,
                $this->provider,
                $queryType,
                $parameters,
                $errorMessage,
                TaxApiQueryCache::STATUS_RATE_LIMITED,
                1 // Cache rate limit errors for 1 day
            );

            throw new Exception($errorMessage);
        }

        // Make the API call with retry logic
        $startTime = microtime(true);
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->config['retry_attempts']) {
            $attempts++;

            try {
                $response = $apiCall();
                $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

                $this->logSuccessfulRequest($queryType, $parameters, $responseTime, $attempts);

                // Cache successful response
                if ($this->config['enable_caching']) {
                    TaxApiQueryCache::cacheResponse(
                        $this->companyId,
                        $this->provider,
                        $queryType,
                        $parameters,
                        $response,
                        $responseTime,
                        $cacheDays
                    );
                }

                return $response;

            } catch (Exception $e) {
                $lastException = $e;
                $this->logFailedRequest($queryType, $parameters, $e, $attempts);

                // If this isn't the last attempt, wait before retrying
                if ($attempts < $this->config['retry_attempts']) {
                    usleep($this->config['retry_delay'] * 1000); // Convert to microseconds
                }
            }
        }

        // All attempts failed - cache the error
        $errorMessage = $lastException ? $lastException->getMessage() : 'Unknown API error';

        if ($this->config['enable_caching']) {
            TaxApiQueryCache::cacheError(
                $this->companyId,
                $this->provider,
                $queryType,
                $parameters,
                $errorMessage
            );
        }

        throw new Exception("API request failed after {$attempts} attempts: {$errorMessage}");
    }

    /**
     * Check if we're within rate limits for a query type
     */
    protected function checkRateLimit(string $queryType): bool
    {
        if (! isset($this->rateLimits[$queryType])) {
            return true; // No rate limit defined
        }

        $limit = $this->rateLimits[$queryType];
        $window = $limit['window'] ?? 60; // Default to 1 minute window
        $maxRequests = $limit['max_requests'] ?? 100;

        // Count recent requests within the time window
        $recentRequests = TaxApiQueryCache::where('company_id', $this->companyId)
            ->where('api_provider', $this->provider)
            ->where('query_type', $queryType)
            ->where('api_called_at', '>=', now()->subSeconds($window))
            ->count();

        return $recentRequests < $maxRequests;
    }

    /**
     * Create HTTP client with common configuration
     */
    protected function createHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->config['timeout'])
            ->retry($this->config['retry_attempts'], $this->config['retry_delay']);
    }

    /**
     * Log cache hit
     */
    protected function logCacheHit(string $queryType, array $parameters): void
    {
        if ($this->config['log_requests']) {
            Log::info('Tax API cache hit', [
                'provider' => $this->provider,
                'query_type' => $queryType,
                'company_id' => $this->companyId,
                'parameters_hash' => TaxApiQueryCache::generateQueryHash($parameters),
            ]);
        }
    }

    /**
     * Log successful API request
     */
    protected function logSuccessfulRequest(string $queryType, array $parameters, float $responseTime, int $attempts): void
    {
        if ($this->config['log_requests']) {
            Log::info('Tax API request successful', [
                'provider' => $this->provider,
                'query_type' => $queryType,
                'company_id' => $this->companyId,
                'response_time_ms' => round($responseTime, 2),
                'attempts' => $attempts,
                'parameters_hash' => TaxApiQueryCache::generateQueryHash($parameters),
            ]);
        }
    }

    /**
     * Log failed API request
     */
    protected function logFailedRequest(string $queryType, array $parameters, Exception $exception, int $attempt): void
    {
        Log::warning('Tax API request failed', [
            'provider' => $this->provider,
            'query_type' => $queryType,
            'company_id' => $this->companyId,
            'attempt' => $attempt,
            'error' => $exception->getMessage(),
            'parameters_hash' => TaxApiQueryCache::generateQueryHash($parameters),
        ]);
    }

    /**
     * Log rate limit exceeded
     */
    protected function logRateLimit(string $queryType, array $parameters, string $message): void
    {
        Log::warning('Tax API rate limit exceeded', [
            'provider' => $this->provider,
            'query_type' => $queryType,
            'company_id' => $this->companyId,
            'message' => $message,
            'parameters_hash' => TaxApiQueryCache::generateQueryHash($parameters),
        ]);
    }

    /**
     * Get API health metrics
     */
    public function getHealthMetrics(): array
    {
        $stats = TaxApiQueryCache::where('company_id', $this->companyId)
            ->where('api_provider', $this->provider)
            ->where('api_called_at', '>=', now()->subHours(24))
            ->selectRaw('
                query_type,
                status,
                COUNT(*) as request_count,
                AVG(response_time_ms) as avg_response_time,
                MIN(response_time_ms) as min_response_time,
                MAX(response_time_ms) as max_response_time
            ')
            ->groupBy(['query_type', 'status'])
            ->get()
            ->groupBy('query_type');

        $metrics = [];
        foreach ($stats as $queryType => $queryStats) {
            $successCount = $queryStats->where('status', TaxApiQueryCache::STATUS_SUCCESS)->sum('request_count');
            $errorCount = $queryStats->where('status', TaxApiQueryCache::STATUS_ERROR)->sum('request_count');
            $rateLimitCount = $queryStats->where('status', TaxApiQueryCache::STATUS_RATE_LIMITED)->sum('request_count');
            $totalCount = $successCount + $errorCount + $rateLimitCount;

            $metrics[$queryType] = [
                'total_requests' => $totalCount,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'rate_limit_count' => $rateLimitCount,
                'success_rate' => $totalCount > 0 ? round(($successCount / $totalCount) * 100, 2) : 0,
                'avg_response_time' => $queryStats->where('status', TaxApiQueryCache::STATUS_SUCCESS)->avg('avg_response_time'),
            ];
        }

        return [
            'provider' => $this->provider,
            'company_id' => $this->companyId,
            'period' => '24_hours',
            'query_types' => $metrics,
            'overall' => [
                'total_requests' => array_sum(array_column($metrics, 'total_requests')),
                'success_rate' => count($metrics) > 0 ? round(array_sum(array_column($metrics, 'success_rate')) / count($metrics), 2) : 0,
                'avg_response_time' => count($metrics) > 0 ? round(array_sum(array_column($metrics, 'avg_response_time')) / count($metrics), 2) : 0,
            ],
        ];
    }

    /**
     * Clean up old cache entries for this provider
     */
    public function cleanupCache(): int
    {
        return TaxApiQueryCache::where('company_id', $this->companyId)
            ->where('api_provider', $this->provider)
            ->expired()
            ->delete();
    }
}
