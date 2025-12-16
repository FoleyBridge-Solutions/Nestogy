<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent client portal users from accessing admin routes
 * and admin users from being redirected from client portal
 */
class ClientPortalGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $isClientPortalRoute = $request->is('client-portal') || $request->is('client-portal/*');

        // If on client portal routes and authenticated as web user, logout web guard
        if ($isClientPortalRoute && auth('web')->check()) {
            auth('web')->logout();
        }

        // If on admin routes and authenticated as client, logout client guard
        if (! $isClientPortalRoute && auth('client')->check()) {
            auth('client')->logout();
        }

        return $next($request);
    }
}
