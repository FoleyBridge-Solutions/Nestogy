<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Silber\Bouncer\BouncerFacade as Bouncer;

/**
 * SetBouncerScope Middleware
 * 
 * Ensures Bouncer scope is properly set for every authenticated request
 * to enable company-based multi-tenancy in authorization.
 */
class SetBouncerScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set Bouncer scope if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->company_id) {
                Bouncer::scope()->to($user->company_id);
            }
        }

        return $next($request);
    }
}