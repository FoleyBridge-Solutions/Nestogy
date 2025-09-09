<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoVerifyEmailWithoutSMTP
{
    /**
     * Handle an incoming request.
     * 
     * Automatically marks admin users as email verified when SMTP is not configured
     * to prevent email verification lockout during initial setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated and SMTP is not configured
        if ($request->user() && 
            (config('mail.mailer') === 'log' || !config('mail.host')) &&
            !$request->user()->hasVerifiedEmail() &&
            $request->user()->isAdmin()) {
            
            // Auto-verify admin users when SMTP is not available
            $request->user()->markEmailAsVerified();
        }

        return $next($request);
    }
}