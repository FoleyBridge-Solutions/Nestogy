<?php

namespace App\Http\Middleware\Portal;

use App\Models\Client;
use App\Models\ClientPortalSession;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Portal Authentication Middleware
 *
 * Handles portal-specific authentication including:
 * - Session validation and management
 * - Client authentication verification
 * - Session security checks
 * - Session activity tracking
 * - Automatic session cleanup
 */
class PortalAuthMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        // Get session ID from header or request parameter
        $sessionId = $this->getSessionId($request);

        if (! $sessionId) {
            return $this->unauthorizedResponse('Portal session required');
        }

        // Validate and retrieve session
        $session = $this->validateSession($sessionId, $request);

        if (! $session) {
            return $this->unauthorizedResponse('Invalid or expired session');
        }

        // Attach session and client to request
        $request->merge(['portal_session' => $session]);
        $request->setUserResolver(function () use ($session) {
            return $session->client;
        });

        // Update session activity
        $this->updateSessionActivity($session, $request);

        $response = $next($request);

        // Log activity for security audit
        $this->logActivity($session, $request, $response);

        return $response;
    }

    /**
     * Get session ID from request
     */
    private function getSessionId(Request $request): ?string
    {
        // Check multiple sources for session ID
        $sessionId = $request->header('X-Portal-Session');

        if (! $sessionId) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
                $sessionId = substr($authHeader, 7);
            }
        }

        return $sessionId
            ?? $request->input('session_id')
            ?? $request->cookie('portal_session');
    }

    /**
     * Validate portal session
     */
    private function validateSession(string $sessionId, Request $request): ?ClientPortalSession
    {
        try {
            $session = ClientPortalSession::with(['client.portalAccess'])
                ->where('id', $sessionId)
                ->where('is_active', true)
                ->first();

            if (! $session) {
                return null;
            }

            // Check if session is expired
            if ($session->expires_at && $session->expires_at->isPast()) {
                $this->invalidateSession($session, 'expired');

                return null;
            }

            // Verify client is still active
            if (! $session->client || $session->client->status !== 'active') {
                $this->invalidateSession($session, 'client_inactive');

                return null;
            }

            // Check portal access permissions
            if (! $session->client->portalAccess || ! $session->client->portalAccess->is_enabled) {
                $this->invalidateSession($session, 'access_disabled');

                return null;
            }

            // Security checks
            if (! $this->performSecurityChecks($session, $request)) {
                $this->invalidateSession($session, 'security_violation');

                return null;
            }

            return $session;

        } catch (\Exception $e) {
            Log::error('Portal session validation error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return null;
        }
    }

    /**
     * Perform security checks on session
     */
    private function performSecurityChecks(ClientPortalSession $session, Request $request): bool
    {
        // IP address validation (if enabled)
        $portalAccess = $session->client->portalAccess;

        if ($portalAccess->ip_restrictions && ! empty($portalAccess->allowed_ips)) {
            $clientIp = $request->ip();
            $allowedIps = $portalAccess->allowed_ips;

            if (! in_array($clientIp, $allowedIps) && ! $this->isIpInRange($clientIp, $allowedIps)) {
                Log::warning('Portal access from unauthorized IP', [
                    'session_id' => $session->id,
                    'client_id' => $session->client_id,
                    'client_ip' => $clientIp,
                    'allowed_ips' => $allowedIps,
                ]);

                return false;
            }
        }

        // Time-based restrictions
        if ($portalAccess->time_restrictions) {
            $now = Carbon::now($portalAccess->timezone ?? 'UTC');
            $currentTime = $now->format('H:i');
            $currentDay = $now->dayOfWeek; // 0 = Sunday, 6 = Saturday

            $restrictions = $portalAccess->allowed_hours ?? [];

            if (! empty($restrictions)) {
                $dayKey = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'][$currentDay];
                $allowedHours = $restrictions[$dayKey] ?? null;

                if ($allowedHours && isset($allowedHours['start'], $allowedHours['end'])) {
                    if ($currentTime < $allowedHours['start'] || $currentTime > $allowedHours['end']) {
                        Log::info('Portal access outside allowed hours', [
                            'session_id' => $session->id,
                            'client_id' => $session->client_id,
                            'current_time' => $currentTime,
                            'allowed_hours' => $allowedHours,
                        ]);

                        return false;
                    }
                }
            }
        }

        // Rate limiting check (basic)
        if ($this->isRateLimited($session, $request)) {
            return false;
        }

        // Check for suspicious activity patterns
        if ($this->detectSuspiciousActivity($session, $request)) {
            return false;
        }

        return true;
    }

    /**
     * Check if IP is in allowed ranges
     */
    private function isIpInRange(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if (strpos($range, '/') !== false) {
                // CIDR notation
                if ($this->ipInCidr($ip, $range)) {
                    return true;
                }
            } elseif (strpos($range, '-') !== false) {
                // IP range notation
                if ($this->ipInRange($ip, $range)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }

    /**
     * Check if IP is in range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$start, $end] = explode('-', $range);

        return ip2long($ip) >= ip2long($start) && ip2long($ip) <= ip2long($end);
    }

    /**
     * Check for rate limiting
     */
    private function isRateLimited(ClientPortalSession $session, Request $request): bool
    {
        $cacheKey = "portal_rate_limit:{$session->client_id}:".$request->ip();
        $attempts = cache()->get($cacheKey, 0);

        // Allow 100 requests per minute per client/IP combination
        if ($attempts > 100) {
            Log::warning('Portal rate limit exceeded', [
                'session_id' => $session->id,
                'client_id' => $session->client_id,
                'ip' => $request->ip(),
                'attempts' => $attempts,
            ]);

            return true;
        }

        cache()->put($cacheKey, $attempts + 1, now()->addMinute());

        return false;
    }

    /**
     * Detect suspicious activity patterns
     */
    private function detectSuspiciousActivity(ClientPortalSession $session, Request $request): bool
    {
        // Check for rapid IP changes
        if ($session->ip_address !== $request->ip()) {
            $recentSessions = ClientPortalSession::where('client_id', $session->client_id)
                ->where('created_at', '>=', now()->subHours(1))
                ->distinct('ip_address')
                ->count('ip_address');

            if ($recentSessions > 5) { // More than 5 different IPs in 1 hour
                Log::warning('Suspicious IP activity detected', [
                    'session_id' => $session->id,
                    'client_id' => $session->client_id,
                    'current_ip' => $request->ip(),
                    'session_ip' => $session->ip_address,
                    'recent_ip_count' => $recentSessions,
                ]);

                return true;
            }
        }

        // Check for unusual user agent changes
        $currentUserAgent = $request->userAgent();
        if ($session->user_agent !== $currentUserAgent) {
            // Allow some flexibility for legitimate user agent variations
            $similarity = similar_text($session->user_agent, $currentUserAgent, $percent);
            if ($percent < 70) { // Less than 70% similarity
                Log::info('User agent change detected', [
                    'session_id' => $session->id,
                    'client_id' => $session->client_id,
                    'original_ua' => $session->user_agent,
                    'current_ua' => $currentUserAgent,
                    'similarity' => $percent,
                ]);
                // Don't block, but log for analysis
            }
        }

        return false;
    }

    /**
     * Update session activity
     */
    private function updateSessionActivity(ClientPortalSession $session, Request $request): void
    {
        try {
            $session->update([
                'last_activity' => Carbon::now(),
                'page_views' => $session->page_views + 1,
                'current_page' => $request->path(),
            ]);

            // Update risk score based on activity
            $this->updateRiskScore($session, $request);

        } catch (\Exception $e) {
            Log::error('Failed to update session activity', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update session risk score
     */
    private function updateRiskScore(ClientPortalSession $session, Request $request): void
    {
        $riskScore = $session->risk_score ?? 0;

        // Decrease risk score for normal activity
        if ($session->last_activity && $session->last_activity->diffInMinutes(Carbon::now()) > 5) {
            $riskScore = max(0, $riskScore - 1);
        }

        // Increase risk score for suspicious patterns
        if ($session->ip_address !== $request->ip()) {
            $riskScore += 5;
        }

        $session->update(['risk_score' => min(100, $riskScore)]);
    }

    /**
     * Invalidate session
     */
    private function invalidateSession(ClientPortalSession $session, string $reason): void
    {
        try {
            $session->update([
                'is_active' => false,
                'ended_at' => Carbon::now(),
                'end_reason' => $reason,
            ]);

            Log::info('Portal session invalidated', [
                'session_id' => $session->id,
                'client_id' => $session->client_id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to invalidate session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log activity for security audit
     */
    private function logActivity(ClientPortalSession $session, Request $request, $response): void
    {
        try {
            $statusCode = $response instanceof JsonResponse ? $response->getStatusCode() : 200;

            Log::info('Portal activity', [
                'session_id' => $session->id,
                'client_id' => $session->client_id,
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $statusCode,
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to log portal activity', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'PORTAL_AUTH_REQUIRED',
            'timestamp' => Carbon::now()->toISOString(),
        ], 401);
    }
}
