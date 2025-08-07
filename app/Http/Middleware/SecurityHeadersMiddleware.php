<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeadersMiddleware
 * 
 * Adds security headers to HTTP responses to protect against various attacks
 * including XSS, clickjacking, MIME sniffing, and more.
 */
class SecurityHeadersMiddleware
{
    /**
     * Default security headers
     */
    protected array $defaultHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        $this->addSecurityHeaders($response, $request);

        // Add Content Security Policy
        $this->addContentSecurityPolicy($response, $request);

        // Add Strict Transport Security for HTTPS
        if ($request->secure()) {
            $this->addStrictTransportSecurity($response);
        }

        // Remove potentially dangerous headers
        $this->removeUnsafeHeaders($response);

        return $response;
    }

    /**
     * Add security headers to response.
     */
    protected function addSecurityHeaders(Response $response, Request $request): void
    {
        $headers = array_merge(
            $this->defaultHeaders,
            config('security.headers.custom', [])
        );

        foreach ($headers as $header => $value) {
            if ($value !== false && $value !== null) {
                $response->headers->set($header, $value);
            }
        }

        // Add additional headers based on response type
        $this->addContentTypeSpecificHeaders($response);
    }

    /**
     * Add Content Security Policy header.
     */
    protected function addContentSecurityPolicy(Response $response, Request $request): void
    {
        // Skip CSP for certain routes if needed
        if ($this->shouldSkipCSP($request)) {
            return;
        }

        $csp = $this->buildContentSecurityPolicy($request);
        
        if ($csp) {
            // Use Report-Only mode in development
            if (app()->environment('local', 'development')) {
                $response->headers->set('Content-Security-Policy-Report-Only', $csp);
            } else {
                $response->headers->set('Content-Security-Policy', $csp);
            }
        }
    }

    /**
     * Build Content Security Policy string.
     */
    protected function buildContentSecurityPolicy(Request $request): string
    {
        $policies = config('security.headers.csp', [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
            'style-src' => ["'self'", "'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'media-src' => ["'self'"],
            'object-src' => ["'none'"],
            'frame-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'upgrade-insecure-requests' => [],
        ]);

        // Add nonce for inline scripts if enabled
        if (config('security.headers.csp_nonce', false)) {
            $nonce = $this->generateNonce();
            $request->attributes->set('csp-nonce', $nonce);
            
            // Add nonce to script-src
            $policies['script-src'][] = "'nonce-{$nonce}'";
            
            // Remove unsafe-inline if nonce is used
            $policies['script-src'] = array_diff($policies['script-src'], ["'unsafe-inline'"]);
        }

        // Build CSP string
        $cspParts = [];
        foreach ($policies as $directive => $sources) {
            if (!empty($sources)) {
                $cspParts[] = $directive . ' ' . implode(' ', $sources);
            } else {
                $cspParts[] = $directive;
            }
        }

        return implode('; ', $cspParts);
    }

    /**
     * Generate a nonce for CSP.
     */
    protected function generateNonce(): string
    {
        return base64_encode(random_bytes(16));
    }

    /**
     * Check if CSP should be skipped for this request.
     */
    protected function shouldSkipCSP(Request $request): bool
    {
        // Skip for API routes
        if ($request->is('api/*')) {
            return true;
        }

        // Skip for specific routes
        $skipRoutes = config('security.headers.csp_skip_routes', []);
        foreach ($skipRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add Strict Transport Security header.
     */
    protected function addStrictTransportSecurity(Response $response): void
    {
        $maxAge = config('security.headers.hsts_max_age', 31536000); // 1 year
        $includeSubdomains = config('security.headers.hsts_include_subdomains', true);
        $preload = config('security.headers.hsts_preload', false);

        $value = "max-age={$maxAge}";
        
        if ($includeSubdomains) {
            $value .= '; includeSubDomains';
        }
        
        if ($preload) {
            $value .= '; preload';
        }

        $response->headers->set('Strict-Transport-Security', $value);
    }

    /**
     * Add content type specific headers.
     */
    protected function addContentTypeSpecificHeaders(Response $response): void
    {
        $contentType = $response->headers->get('Content-Type');

        // Add X-Content-Type-Options for all responses
        if (!$response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        // Add specific headers for JSON responses
        if (str_contains($contentType, 'application/json')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        }

        // Add specific headers for HTML responses
        if (str_contains($contentType, 'text/html')) {
            // Ensure X-Frame-Options is set
            if (!$response->headers->has('X-Frame-Options')) {
                $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            }
        }

        // Add specific headers for file downloads
        if ($response->headers->has('Content-Disposition')) {
            $response->headers->set('X-Download-Options', 'noopen');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }
    }

    /**
     * Remove potentially unsafe headers.
     */
    protected function removeUnsafeHeaders(Response $response): void
    {
        $unsafeHeaders = config('security.headers.remove', [
            'X-Powered-By',
            'Server',
            'X-AspNet-Version',
            'X-AspNetMvc-Version',
        ]);

        foreach ($unsafeHeaders as $header) {
            $response->headers->remove($header);
        }
    }

    /**
     * Get CSP nonce for views.
     */
    public static function getNonce(Request $request): ?string
    {
        return $request->attributes->get('csp-nonce');
    }
}