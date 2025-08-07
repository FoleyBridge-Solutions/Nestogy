<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

/**
 * RememberTokenMiddleware
 * 
 * Handles "Remember Me" functionality by checking for valid remember tokens
 * and automatically logging in users with valid tokens.
 */
class RememberTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process if user is not already authenticated
        if (!Auth::check()) {
            $this->attemptRememberLogin($request);
        }

        return $next($request);
    }

    /**
     * Attempt to log in user using remember token.
     */
    protected function attemptRememberLogin(Request $request): void
    {
        $rememberToken = $request->cookie(Auth::guard()->getRecallerName());

        if (!$rememberToken) {
            return;
        }

        // Parse the remember token
        $segments = explode('|', $rememberToken);
        if (count($segments) !== 2) {
            $this->clearRememberCookie();
            return;
        }

        [$userId, $token] = $segments;

        // Find user by ID
        $user = User::find($userId);
        if (!$user) {
            $this->clearRememberCookie();
            return;
        }

        // Check if user is active
        if (!$user->isActive()) {
            $this->clearRememberCookie();
            return;
        }

        // Verify the remember token
        if (!$this->verifyRememberToken($user, $token)) {
            $this->clearRememberCookie();
            return;
        }

        // Log the user in
        Auth::login($user, true);

        // Log successful remember login
        $this->logRememberLogin($user, $request);

        // Regenerate remember token for security
        $this->regenerateRememberToken($user);
    }

    /**
     * Verify the remember token against stored hash.
     */
    protected function verifyRememberToken(User $user, string $token): bool
    {
        // Check Laravel's built-in remember token
        if ($user->getRememberToken() && hash_equals($user->getRememberToken(), $token)) {
            return true;
        }

        // Check custom remember token in user settings
        if ($user->userSetting && $user->userSetting->remember_me_token) {
            return hash_equals($user->userSetting->remember_me_token, hash('sha256', $token));
        }

        return false;
    }

    /**
     * Regenerate remember token for security.
     */
    protected function regenerateRememberToken(User $user): void
    {
        $newToken = \Str::random(60);
        
        // Update Laravel's remember token
        $user->setRememberToken($newToken);
        $user->save();

        // Update custom remember token in user settings
        if ($user->userSetting) {
            $user->userSetting->update([
                'remember_me_token' => hash('sha256', $newToken)
            ]);
        }

        // Set new cookie
        $recaller = $user->id . '|' . $newToken;
        Cookie::queue(
            Auth::guard()->getRecallerName(),
            $recaller,
            Auth::guard()->getRememberTokenCookieTimeout()
        );
    }

    /**
     * Clear remember cookie.
     */
    protected function clearRememberCookie(): void
    {
        Cookie::queue(Cookie::forget(Auth::guard()->getRecallerName()));
    }

    /**
     * Log successful remember login.
     */
    protected function logRememberLogin(User $user, Request $request): void
    {
        \Log::info('User logged in via remember token', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}