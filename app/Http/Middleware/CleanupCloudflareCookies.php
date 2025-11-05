<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CleanupCloudflareCookies
 * 
 * Removes problematic Cloudflare cookies that cause domain mismatch errors
 * This is a temporary fix until SESSION_DOMAIN is properly configured in Laravel Cloud
 */
class CleanupCloudflareCookies
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Remove problematic Cloudflare cookies from the request
        $cookies = $request->cookies->all();
        
        foreach ($cookies as $name => $value) {
            // Remove Cloudflare bot management cookies that have domain issues
            if (str_starts_with($name, '__cf_bm')) {
                $request->cookies->remove($name);
            }
        }

        $response = $next($request);

        // Also expire these cookies in the response to clear them from browser
        if ($response instanceof \Illuminate\Http\Response || 
            $response instanceof \Illuminate\Http\JsonResponse) {
            
            foreach ($cookies as $name => $value) {
                if (str_starts_with($name, '__cf_bm')) {
                    // Set cookie to expire immediately with correct domain
                    $response->headers->setCookie(
                        cookie(
                            $name, 
                            '', 
                            -1, // Expire immediately
                            '/',
                            config('session.domain'), // Use session domain
                            config('session.secure'),
                            true // httpOnly
                        )
                    );
                }
            }
        }

        return $response;
    }
}
