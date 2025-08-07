<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * ApiKeyMiddleware
 * 
 * Validates API key authentication for API endpoints.
 * Supports multiple API key formats and validation methods.
 */
class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $scope  Optional scope requirement for the API key
     */
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (!$apiKey) {
            return $this->unauthorizedResponse('API key is required');
        }

        $keyData = $this->validateApiKey($apiKey);

        if (!$keyData) {
            $this->logFailedAttempt($request, $apiKey);
            return $this->unauthorizedResponse('Invalid API key');
        }

        // Check if key is active
        if (!$keyData['is_active']) {
            return $this->unauthorizedResponse('API key is inactive');
        }

        // Check expiration
        if ($this->isKeyExpired($keyData)) {
            return $this->unauthorizedResponse('API key has expired');
        }

        // Check scope if required
        if ($scope && !$this->hasScope($keyData, $scope)) {
            return $this->forbiddenResponse('Insufficient API key permissions');
        }

        // Check rate limits for this API key
        if (!$this->checkKeyRateLimit($keyData)) {
            return $this->rateLimitResponse($keyData);
        }

        // Attach API key data to request
        $request->attributes->set('api_key', $keyData);
        $request->attributes->set('api_key_id', $keyData['id']);

        // Log successful API key usage
        $this->logApiKeyUsage($request, $keyData);

        return $next($request);
    }

    /**
     * Extract API key from request.
     */
    protected function extractApiKey(Request $request): ?string
    {
        // Check Authorization header (Bearer token)
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        // Check X-API-Key header
        $apiKeyHeader = $request->header('X-API-Key');
        if ($apiKeyHeader) {
            return $apiKeyHeader;
        }

        // Check query parameter (less secure, should be discouraged)
        if (config('security.api_keys.allow_query_param', false)) {
            $queryKey = $request->query('api_key');
            if ($queryKey) {
                return $queryKey;
            }
        }

        return null;
    }

    /**
     * Validate API key and return key data.
     */
    protected function validateApiKey(string $apiKey): ?array
    {
        // Check cache first
        $cacheKey = 'api_key:' . substr($apiKey, 0, 8);
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            // Verify the full key matches
            if ($cached && Hash::check($apiKey, $cached['key_hash'])) {
                return $cached;
            }
            return null;
        }

        // Look up in database (you'll need to create an ApiKey model)
        $keyData = $this->findApiKeyInDatabase($apiKey);
        
        if ($keyData) {
            // Cache for performance (without the actual key)
            $cacheData = $keyData;
            $cacheData['key_hash'] = Hash::make($apiKey);
            Cache::put($cacheKey, $cacheData, now()->addMinutes(5));
            
            return $keyData;
        }

        // Cache negative result to prevent database hammering
        Cache::put($cacheKey, false, now()->addMinutes(1));
        
        return null;
    }

    /**
     * Find API key in database.
     * This is a placeholder - you'll need to implement based on your ApiKey model
     */
    protected function findApiKeyInDatabase(string $apiKey): ?array
    {
        // Example implementation - adjust based on your actual model
        // $apiKeyModel = \App\Models\ApiKey::where('key', hash('sha256', $apiKey))
        //     ->where('is_active', true)
        //     ->first();
        
        // For now, return a mock implementation
        // In production, this should query your api_keys table
        $mockKeys = config('security.api_keys.keys', []);
        
        foreach ($mockKeys as $id => $keyData) {
            if (Hash::check($apiKey, $keyData['hash'])) {
                return array_merge($keyData, ['id' => $id]);
            }
        }
        
        return null;
    }

    /**
     * Check if API key has expired.
     */
    protected function isKeyExpired(array $keyData): bool
    {
        if (!isset($keyData['expires_at'])) {
            return false;
        }

        return now()->isAfter($keyData['expires_at']);
    }

    /**
     * Check if API key has required scope.
     */
    protected function hasScope(array $keyData, string $requiredScope): bool
    {
        if (!isset($keyData['scopes'])) {
            return false;
        }

        $scopes = is_array($keyData['scopes']) ? $keyData['scopes'] : explode(',', $keyData['scopes']);
        
        // Check for exact scope or wildcard
        return in_array($requiredScope, $scopes) || in_array('*', $scopes);
    }

    /**
     * Check rate limit for API key.
     */
    protected function checkKeyRateLimit(array $keyData): bool
    {
        $keyId = $keyData['id'];
        $limit = $keyData['rate_limit'] ?? config('security.api_keys.default_rate_limit', 1000);
        $window = $keyData['rate_limit_window'] ?? 3600; // 1 hour default

        $key = 'api_key_rate_limit:' . $keyId;
        $current = Cache::get($key, 0);

        if ($current >= $limit) {
            return false;
        }

        Cache::increment($key);
        
        // Set expiration on first request
        if ($current === 0) {
            Cache::put($key, 1, $window);
        }

        return true;
    }

    /**
     * Log failed API key attempt.
     */
    protected function logFailedAttempt(Request $request, string $apiKey): void
    {
        // Log only first 8 characters of the key for security
        $maskedKey = substr($apiKey, 0, 8) . '...';
        
        AuditLog::logSecurity('Invalid API Key Attempt', [
            'masked_key' => $maskedKey,
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ], AuditLog::SEVERITY_WARNING);

        // Track failed attempts by IP
        $ip = $request->ip();
        $failKey = 'api_key_fails:' . $ip;
        $fails = Cache::increment($failKey);
        
        if ($fails === 1) {
            Cache::put($failKey, 1, now()->addMinutes(15));
        }
        
        // Block IP after too many failed attempts
        if ($fails >= config('security.api_keys.max_failed_attempts', 10)) {
            Cache::put('ip_blocked_' . $ip, true, now()->addHours(1));
        }
    }

    /**
     * Log successful API key usage.
     */
    protected function logApiKeyUsage(Request $request, array $keyData): void
    {
        // Update last used timestamp
        $this->updateLastUsed($keyData['id']);

        // Log usage for analytics
        AuditLog::logApi('API Key Usage', [
            'key_id' => $keyData['id'],
            'key_name' => $keyData['name'] ?? 'Unknown',
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Update last used timestamp for API key.
     */
    protected function updateLastUsed(string $keyId): void
    {
        // In production, update the api_keys table
        // \App\Models\ApiKey::where('id', $keyId)->update(['last_used_at' => now()]);
        
        // For now, just cache it
        Cache::put('api_key_last_used:' . $keyId, now(), now()->addDay());
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401)->header('WWW-Authenticate', 'Bearer');
    }

    /**
     * Forbidden response.
     */
    protected function forbiddenResponse(string $message): Response
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
        ], 403);
    }

    /**
     * Rate limit response.
     */
    protected function rateLimitResponse(array $keyData): Response
    {
        $window = $keyData['rate_limit_window'] ?? 3600;
        $resetTime = now()->addSeconds($window)->timestamp;

        return response()->json([
            'error' => 'Rate limit exceeded',
            'message' => 'API key rate limit exceeded. Please try again later.',
            'limit' => $keyData['rate_limit'] ?? config('security.api_keys.default_rate_limit', 1000),
            'reset' => $resetTime,
        ], 429)->header('X-RateLimit-Reset', $resetTime);
    }
}