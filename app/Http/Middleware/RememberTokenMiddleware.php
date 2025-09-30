<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * RememberTokenMiddleware
 *
 * Handles "Remember Me" functionality by checking for valid remember tokens
 * and automatically logging in users with valid tokens.
 * Supports multiple guards (web and client).
 */
class RememberTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        // Determine which guard to use
        $guard = $guard ?: $this->getGuardFromRoute($request);

        // Only process if user is not already authenticated for this guard
        if (! Auth::guard($guard)->check()) {
            $this->attemptRememberLogin($request, $guard);
        }

        return $next($request);
    }

    /**
     * Determine the guard based on the route.
     */
    protected function getGuardFromRoute(Request $request): string
    {
        // Check if we're in the client portal
        if ($request->is('client-portal/*')) {
            return 'client';
        }

        return 'web';
    }

    /**
     * Attempt to log in user using remember token.
     */
    protected function attemptRememberLogin(Request $request, string $guard): void
    {
        $authGuard = Auth::guard($guard);
        $rememberToken = $request->cookie($authGuard->getRecallerName());

        if (! $rememberToken) {
            return;
        }

        // Parse the remember token
        $segments = explode('|', $rememberToken);
        if (count($segments) !== 2) {
            $this->clearRememberCookie($guard);

            return;
        }

        [$userId, $token] = $segments;

        // Find user by ID based on guard
        $user = $this->findUserByGuard($userId, $guard);
        if (! $user) {
            $this->clearRememberCookie($guard);

            return;
        }

        // Check if user/contact is active
        if (! $this->isUserActive($user, $guard)) {
            $this->clearRememberCookie($guard);

            return;
        }

        // Verify the remember token
        if (! $this->verifyRememberToken($user, $token, $guard)) {
            $this->clearRememberCookie($guard);

            return;
        }

        // Log the user in
        $authGuard->login($user, true);

        // Log successful remember login
        $this->logRememberLogin($user, $request, $guard);

        // Regenerate remember token for security
        $this->regenerateRememberToken($user, $guard);
    }

    /**
     * Find user based on guard type.
     */
    protected function findUserByGuard($userId, string $guard)
    {
        if ($guard === 'client') {
            return Contact::find($userId);
        }

        return User::find($userId);
    }

    /**
     * Check if user/contact is active based on guard.
     */
    protected function isUserActive($user, string $guard): bool
    {
        if ($guard === 'client') {
            // For contacts, check if they can access the portal
            return $user instanceof Contact && $user->canAccessPortal();
        }

        // For regular users
        return $user instanceof User && $user->isActive();
    }

    /**
     * Verify the remember token against stored hash.
     */
    protected function verifyRememberToken($user, string $token, string $guard): bool
    {
        // Check Laravel's built-in remember token
        if ($user->getRememberToken() && hash_equals($user->getRememberToken(), $token)) {
            return true;
        }

        // For regular users, check custom remember token in user settings
        if ($guard === 'web' && $user instanceof User) {
            if ($user->userSetting && $user->userSetting->remember_me_token) {
                return hash_equals($user->userSetting->remember_me_token, hash('sha256', $token));
            }
        }

        return false;
    }

    /**
     * Regenerate remember token for security.
     */
    protected function regenerateRememberToken($user, string $guard): void
    {
        $newToken = \Str::random(60);

        // Update Laravel's remember token
        $user->setRememberToken($newToken);
        $user->save();

        // For regular users, update custom remember token in user settings
        if ($guard === 'web' && $user instanceof User && $user->userSetting) {
            $user->userSetting->update([
                'remember_me_token' => hash('sha256', $newToken),
            ]);
        }

        // Set new cookie
        $recaller = $user->id.'|'.$newToken;
        Cookie::queue(
            Auth::guard($guard)->getRecallerName(),
            $recaller,
            Auth::guard($guard)->getRememberTokenCookieTimeout()
        );
    }

    /**
     * Clear remember cookie.
     */
    protected function clearRememberCookie(string $guard): void
    {
        Cookie::queue(Cookie::forget(Auth::guard($guard)->getRecallerName()));
    }

    /**
     * Log successful remember login.
     */
    protected function logRememberLogin($user, Request $request, string $guard): void
    {
        $modelType = $guard === 'client' ? 'Contact' : 'User';

        \Log::info($modelType.' logged in via remember token', [
            'guard' => $guard,
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
