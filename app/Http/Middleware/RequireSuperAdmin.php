<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireSuperAdmin
 *
 * Middleware that ensures only super-admin users from Company 1
 * can access protected routes.
 */
class RequireSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user is a super-admin with cross-tenant access
        if (! $user->canAccessCrossTenant()) {
            abort(403, 'Access denied. Super-admin privileges required.');
        }

        return $next($request);
    }
}
