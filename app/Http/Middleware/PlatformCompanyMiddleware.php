<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * PlatformCompanyMiddleware
 *
 * Restricts access to routes that should only be accessible by users
 * from the platform company (Company ID 1). This is used for features
 * like subscription plan management, tenant oversight, etc.
 */
class PlatformCompanyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();

        // Check if user is authenticated
        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return redirect()->route('login');
        }

        // Check if user belongs to the platform company (Company 1)
        $platformCompanyId = config('saas.platform_company_id', 1);

        if ($user->company_id !== $platformCompanyId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied. This feature is only available to platform administrators.',
                ], 403);
            }

            abort(403, 'Access denied. This feature is only available to platform administrators.');
        }

        return $next($request);
    }
}
