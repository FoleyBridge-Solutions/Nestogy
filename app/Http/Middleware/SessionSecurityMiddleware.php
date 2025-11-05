<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * SessionSecurityMiddleware - OPTIMIZED VERSION
 * 
 * Stores security data in Cache (server-side) instead of Session (client-side)
 * This prevents session bloat and 400 header size errors while maintaining security
 * 
 * Features:
 * - Session timeout detection
 * - Session hijacking prevention via fingerprinting
 * - Concurrent session limiting
 * - Zero session storage (all in server-side cache)
 */
class SessionSecurityMiddleware
{
    /**
     * Request-level cache for settings to prevent duplicate lookups
     */
    private static $settingsCache = [];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for unauthenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $sessionId = session()->getId();
        
        // Use Cache key instead of Session storage
        $cacheKey = "session_security_{$sessionId}";

        try {
            // Get or create security data in CACHE (not session)
            $securityData = Cache::get($cacheKey, [
                'fingerprint' => null,
                'last_activity' => null,
                'session_start' => time(),
                'last_regeneration' => time(),
            ]);

            // Check session timeout (using cached data)
            if (!$this->checkSessionTimeout($securityData)) {
                $this->logSecurityEvent($user, 'session_timeout', $request);
                Cache::forget($cacheKey);
                Auth::logout();
                
                return redirect()->route('login')
                    ->with('error', 'Your session has expired due to inactivity.');
            }

            // Check fingerprint (using cached data)
            if (!$this->checkFingerprint($request, $securityData)) {
                $this->logSecurityEvent($user, 'session_hijacking_detected', $request);
                Cache::forget($cacheKey);
                Auth::logout();
                
                return redirect()->route('login')
                    ->with('error', 'Session security violation detected.');
            }

            // Check concurrent sessions (already using Cache)
            if (!$this->checkConcurrentSessions($user, $sessionId)) {
                $this->logSecurityEvent($user, 'concurrent_session_limit', $request);
                Cache::forget($cacheKey);
                Auth::logout();
                
                return redirect()->route('login')
                    ->with('error', 'Maximum concurrent sessions exceeded.');
            }

            // Update activity timestamp in CACHE (not session)
            $securityData['last_activity'] = time();
            
            // Store in Cache for session lifetime (e.g., 2 hours)
            $lifetime = config('session.lifetime', 120);
            Cache::put($cacheKey, $securityData, now()->addMinutes($lifetime));

        } catch (\Exception $e) {
            // Log error but don't block the request
            Log::warning("SessionSecurityMiddleware error", [
                'user_id' => $user->id ?? null,
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            // Allow request to proceed on errors (fail open for availability)
        }

        return $next($request);
    }

    /**
     * Check if session has timed out due to inactivity
     */
    protected function checkSessionTimeout(array $securityData): bool
    {
        if (!isset($securityData['last_activity'])) {
            return true; // First request, allow
        }

        // Get timeout from config (in minutes), convert to seconds
        $timeoutMinutes = config('session.lifetime', 120);
        $timeout = $timeoutMinutes * 60;
        
        $idleTime = time() - $securityData['last_activity'];

        return $idleTime <= $timeout;
    }

    /**
     * Check session fingerprint to detect hijacking
     */
    protected function checkFingerprint(Request $request, array &$securityData): bool
    {
        $fingerprint = $this->generateFingerprint($request);

        if (!isset($securityData['fingerprint'])) {
            // First time, store the fingerprint
            $securityData['fingerprint'] = $fingerprint;
            return true;
        }

        // Compare fingerprints - if changed, it's suspicious
        // Note: We use a simple comparison because users typically don't change
        // browsers/devices mid-session
        if ($fingerprint !== $securityData['fingerprint']) {
            // Allow for mobile users with changing IPs
            if ($this->isMobileDevice($request)) {
                // For mobile, we're more lenient as IPs can change
                // Just log it but allow
                Log::info("Mobile user fingerprint changed", [
                    'old' => substr($securityData['fingerprint'], 0, 8),
                    'new' => substr($fingerprint, 0, 8),
                ]);
                $securityData['fingerprint'] = $fingerprint;
                return true;
            }
            
            return false; // Desktop/non-mobile with changed fingerprint = suspicious
        }

        return true;
    }

    /**
     * Generate lightweight session fingerprint
     * Uses only user agent to avoid IP-based issues with mobile users
     */
    protected function generateFingerprint(Request $request): string
    {
        $components = [
            'user_agent' => $request->userAgent() ?: 'unknown',
            'accept_language' => $request->header('Accept-Language', 'unknown'),
        ];

        return hash('sha256', json_encode($components));
    }

    /**
     * Check if request is from a mobile device
     */
    protected function isMobileDevice(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?: '');
        
        $mobileKeywords = [
            'mobile', 'android', 'iphone', 'ipad', 'ipod', 
            'blackberry', 'windows phone', 'opera mini'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check concurrent session limits
     */
    protected function checkConcurrentSessions($user, string $currentSessionId): bool
    {
        // Get max concurrent sessions from config
        $maxSessions = config('security.session.max_concurrent', 3);
        
        if ($maxSessions <= 0) {
            return true; // No limit
        }

        $userSessionsKey = "user_sessions_{$user->id}";
        $activeSessions = Cache::get($userSessionsKey, []);

        // Clean expired sessions
        $timeout = config('session.lifetime', 120) * 60;
        $activeSessions = array_filter($activeSessions, function ($session) use ($timeout) {
            return isset($session['last_activity']) &&
                   (time() - $session['last_activity']) < $timeout;
        });

        // Add/update current session
        $activeSessions[$currentSessionId] = [
            'id' => $currentSessionId,
            'ip' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?: 'unknown', 0, 100),
            'last_activity' => time(),
            'created_at' => $activeSessions[$currentSessionId]['created_at'] ?? time(),
        ];

        // Check limit
        if (count($activeSessions) > $maxSessions) {
            // Keep only the most recent sessions by last activity
            uasort($activeSessions, fn($a, $b) => $b['last_activity'] <=> $a['last_activity']);
            $activeSessions = array_slice($activeSessions, 0, $maxSessions, true);

            // Check if current session survived the cut
            if (!isset($activeSessions[$currentSessionId])) {
                return false; // Current session was removed (too many sessions)
            }
        }

        // Store back in cache with TTL
        $lifetime = config('session.lifetime', 120);
        Cache::put($userSessionsKey, $activeSessions, now()->addMinutes($lifetime));

        return true;
    }

    /**
     * Log security events for audit trail
     */
    protected function logSecurityEvent($user, string $event, Request $request): void
    {
        try {
            Log::warning("Session security event: {$event}", [
                'user_id' => $user->id ?? null,
                'user_email' => $user->email ?? null,
                'event' => $event,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
        } catch (\Exception $e) {
            // Don't let logging errors break the flow
            Log::error("Failed to log security event", ['error' => $e->getMessage()]);
        }
    }
}
