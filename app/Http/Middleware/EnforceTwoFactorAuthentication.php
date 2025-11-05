<?php

namespace App\Http\Middleware;

use App\Helpers\ConfigHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceTwoFactorAuthentication
 *
 * Enforces two-factor authentication based on company security settings.
 * Redirects users to 2FA setup if required but not enabled.
 */
class EnforceTwoFactorAuthentication
{
    /**
     * Routes that should be excluded from 2FA enforcement
     */
    protected array $except = [
        'user/two-factor-authentication',
        'user/confirmed-two-factor-authentication',
        'user/two-factor-qr-code',
        'user/two-factor-secret-key',
        'user/two-factor-recovery-codes',
        'two-factor-challenge',
        'logout',
        'user/profile-information',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if user is not authenticated
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip if route is in exception list
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // Get 2FA settings from database
        $companyId = $user->company_id;
        $twoFactorEnabled = ConfigHelper::securitySetting($companyId, 'authentication', 'two_factor_enabled', true);
        $twoFactorRequired = ConfigHelper::securitySetting($companyId, 'authentication', 'two_factor_required', false);

        // If 2FA is not enabled in settings, allow through
        if (!$twoFactorEnabled) {
            return $next($request);
        }

        // If 2FA is required but user hasn't enabled it, redirect to setup
        if ($twoFactorRequired && !$user->two_factor_secret) {
            // Store intended URL
            if (!$request->ajax() && !$request->expectsJson()) {
                session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('profile.show')
                ->with('error', 'Two-factor authentication is required for your account. Please enable it to continue.');
        }

        return $next($request);
    }

    /**
     * Determine if the request has a URI that should pass through 2FA enforcement.
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}
