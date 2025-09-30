<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * SessionSecurityMiddleware
 *
 * Enforces session security policies including timeout, concurrent session limits,
 * session fingerprinting, and hijacking prevention.
 */
class SessionSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $sessionId = Session::getId();

        // Check session timeout
        if (! $this->checkSessionTimeout()) {
            return $this->handleSessionTimeout($request);
        }

        // Check concurrent sessions
        if (! $this->checkConcurrentSessions($user, $sessionId)) {
            return $this->handleConcurrentSessionViolation($request);
        }

        // Check session fingerprint
        if (! $this->checkSessionFingerprint($request)) {
            return $this->handleSessionHijacking($request);
        }

        // Update session activity
        $this->updateSessionActivity();

        // Regenerate session ID periodically for security
        $this->regenerateSessionIfNeeded();

        return $next($request);
    }

    /**
     * Check if session has timed out.
     */
    protected function checkSessionTimeout(): bool
    {
        $lastActivity = Session::get('last_activity');
        if (! $lastActivity) {
            return true;
        }

        $timeout = config('security.session.timeout', 1800); // 30 minutes default
        $idleTime = time() - $lastActivity;

        if ($idleTime > $timeout) {
            return false;
        }

        // Check absolute timeout (maximum session duration)
        $sessionStart = Session::get('session_start');
        if ($sessionStart) {
            $absoluteTimeout = config('security.session.absolute_timeout', 86400); // 24 hours default
            if (time() - $sessionStart > $absoluteTimeout) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check concurrent session limits.
     */
    protected function checkConcurrentSessions($user, string $currentSessionId): bool
    {
        $maxSessions = config('security.session.max_concurrent', 3);
        if ($maxSessions <= 0) {
            return true; // No limit
        }

        // Get all active sessions for the user
        $userSessionsKey = 'user_sessions_'.$user->id;
        $activeSessions = Cache::get($userSessionsKey, []);

        // Remove expired sessions
        $activeSessions = array_filter($activeSessions, function ($session) {
            return isset($session['last_activity']) &&
                   (time() - $session['last_activity']) < config('security.session.timeout', 1800);
        });

        // Add current session if not exists
        if (! isset($activeSessions[$currentSessionId])) {
            $activeSessions[$currentSessionId] = [
                'id' => $currentSessionId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'last_activity' => time(),
                'created_at' => time(),
            ];
        }

        // Check if limit exceeded
        if (count($activeSessions) > $maxSessions) {
            // Remove oldest sessions
            uasort($activeSessions, function ($a, $b) {
                return $a['created_at'] <=> $b['created_at'];
            });

            $activeSessions = array_slice($activeSessions, -$maxSessions, null, true);

            // Check if current session is still in the list
            if (! isset($activeSessions[$currentSessionId])) {
                return false;
            }
        }

        // Update cache
        Cache::put($userSessionsKey, $activeSessions, now()->addDay());

        return true;
    }

    /**
     * Check session fingerprint to detect hijacking.
     */
    protected function checkSessionFingerprint(Request $request): bool
    {
        $fingerprint = $this->generateFingerprint($request);
        $storedFingerprint = Session::get('session_fingerprint');

        if (! $storedFingerprint) {
            // First time, store the fingerprint
            Session::put('session_fingerprint', $fingerprint);

            return true;
        }

        // Compare fingerprints
        if ($fingerprint !== $storedFingerprint) {
            // Check if it's an allowed change (e.g., IP change for mobile users)
            if ($this->isAllowedFingerprintChange($request, $fingerprint, $storedFingerprint)) {
                Session::put('session_fingerprint', $fingerprint);

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Generate session fingerprint.
     */
    protected function generateFingerprint(Request $request): string
    {
        $components = [
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
        ];

        // Include IP in fingerprint if strict mode is enabled
        if (config('security.session.strict_ip_check', false)) {
            $components['ip'] = $request->ip();
        } else {
            // Use IP subnet for more flexibility
            $ip = $request->ip();
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                // Use /24 subnet for IPv4
                $parts = explode('.', $ip);
                $components['ip_subnet'] = $parts[0].'.'.$parts[1].'.'.$parts[2];
            } else {
                // For IPv6, use first 64 bits
                $components['ip_subnet'] = substr($ip, 0, 19);
            }
        }

        return hash('sha256', json_encode($components));
    }

    /**
     * Check if fingerprint change is allowed.
     */
    protected function isAllowedFingerprintChange(Request $request, string $newFingerprint, string $oldFingerprint): bool
    {
        // Allow if user is on a mobile device and only IP changed
        if ($this->isMobileDevice($request)) {
            // Generate fingerprint without IP
            $fingerprintWithoutIp = $this->generateFingerprintWithoutIp($request);
            $oldFingerprintWithoutIp = Session::get('session_fingerprint_no_ip');

            if ($fingerprintWithoutIp === $oldFingerprintWithoutIp) {
                Session::put('session_fingerprint_no_ip', $fingerprintWithoutIp);

                return true;
            }
        }

        return false;
    }

    /**
     * Generate fingerprint without IP component.
     */
    protected function generateFingerprintWithoutIp(Request $request): string
    {
        $components = [
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
        ];

        return hash('sha256', json_encode($components));
    }

    /**
     * Check if request is from a mobile device.
     */
    protected function isMobileDevice(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent());
        $mobileKeywords = ['mobile', 'android', 'iphone', 'ipad', 'windows phone', 'blackberry'];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update session activity timestamp.
     */
    protected function updateSessionActivity(): void
    {
        Session::put('last_activity', time());

        // Set session start time if not set
        if (! Session::has('session_start')) {
            Session::put('session_start', time());
        }

        // Update user's active sessions in cache
        if (Auth::check()) {
            $user = Auth::user();
            $sessionId = Session::getId();
            $userSessionsKey = 'user_sessions_'.$user->id;
            $activeSessions = Cache::get($userSessionsKey, []);

            if (isset($activeSessions[$sessionId])) {
                $activeSessions[$sessionId]['last_activity'] = time();
                Cache::put($userSessionsKey, $activeSessions, now()->addDay());
            }
        }
    }

    /**
     * Regenerate session ID periodically.
     */
    protected function regenerateSessionIfNeeded(): void
    {
        $lastRegeneration = Session::get('last_regeneration', 0);
        $regenerationInterval = config('security.session.regeneration_interval', 3600); // 1 hour default

        if (time() - $lastRegeneration > $regenerationInterval) {
            Session::regenerate();
            Session::put('last_regeneration', time());
        }
    }

    /**
     * Handle session timeout.
     */
    protected function handleSessionTimeout(Request $request): Response
    {
        $user = Auth::user();

        AuditLog::logSecurity('Session Timeout', [
            'user_id' => $user->id,
            'email' => $user->email,
            'session_id' => Session::getId(),
            'ip_address' => $request->ip(),
            'last_activity' => Session::get('last_activity'),
        ], AuditLog::SEVERITY_INFO);

        Auth::logout();
        Session::invalidate();

        return redirect()->route('login')
            ->with('error', 'Your session has expired. Please log in again.');
    }

    /**
     * Handle concurrent session violation.
     */
    protected function handleConcurrentSessionViolation(Request $request): Response
    {
        $user = Auth::user();

        AuditLog::logSecurity('Concurrent Session Limit Exceeded', [
            'user_id' => $user->id,
            'email' => $user->email,
            'session_id' => Session::getId(),
            'ip_address' => $request->ip(),
            'max_sessions' => config('security.session.max_concurrent', 3),
        ], AuditLog::SEVERITY_WARNING);

        Auth::logout();
        Session::invalidate();

        return redirect()->route('login')
            ->with('error', 'Maximum concurrent sessions exceeded. Please log in again.');
    }

    /**
     * Handle potential session hijacking.
     */
    protected function handleSessionHijacking(Request $request): Response
    {
        $user = Auth::user();

        AuditLog::logSecurity('Potential Session Hijacking', [
            'user_id' => $user->id,
            'email' => $user->email,
            'session_id' => Session::getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_fingerprint' => Session::get('session_fingerprint'),
            'new_fingerprint' => $this->generateFingerprint($request),
        ], AuditLog::SEVERITY_CRITICAL);

        // Invalidate all sessions for this user
        $userSessionsKey = 'user_sessions_'.$user->id;
        Cache::forget($userSessionsKey);

        Auth::logout();
        Session::invalidate();

        return redirect()->route('login')
            ->with('error', 'Security violation detected. Please log in again.');
    }
}
