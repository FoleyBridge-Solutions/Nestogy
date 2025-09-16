<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\URL;
use App\Models\AuditLog;

/**
 * ForceHttpsMiddleware
 * 
 * Forces HTTPS connections for secure routes and handles
 * HTTP to HTTPS redirections with proper security headers.
 */
class ForceHttpsMiddleware
{
    /**
     * Paths that should always use HTTPS
     */
    protected array $securePaths = [
        'login',
        'register',
        'password/*',
        'admin/*',
        'api/*',
        'payment/*',
        'checkout/*',
        'account/*',
        'profile/*',
    ];

    /**
     * Paths that can use HTTP (exceptions)
     */
    protected array $insecurePaths = [
        'health',
        'status',
        '.well-known/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $mode  'all', 'selective', or null for config default
     */
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        // Skip in local development unless explicitly enabled
        if (app()->environment('local') && !config('security.force_https.local', false)) {
            return $next($request);
        }

        // Determine enforcement mode
        $mode = $mode ?? config('security.force_https.mode', 'selective');

        // Check if HTTPS should be enforced
        if ($this->shouldEnforceHttps($request, $mode)) {
            if (!$request->secure()) {
                return $this->redirectToHttps($request);
            }
        }

        // Force secure URLs in the application
        if ($request->secure() || $this->shouldEnforceHttps($request, $mode)) {
            URL::forceScheme('https');
        }

        $response = $next($request);

        // Add security headers for HTTPS connections
        if ($request->secure()) {
            $this->addSecurityHeaders($response);
        }

        return $response;
    }

    /**
     * Determine if HTTPS should be enforced for this request.
     */
    protected function shouldEnforceHttps(Request $request, string $mode): bool
    {
        // Check if explicitly disabled
        if (!config('security.force_https.enabled', true)) {
            return false;
        }

        // Check for insecure path exceptions
        if ($this->isInsecurePath($request)) {
            return false;
        }

        // Handle different modes
        switch ($mode) {
            case 'all':
                return true;
                
            case 'selective':
                return $this->isSecurePath($request);
                
            case 'none':
                return false;
                
            default:
                return $this->isSecurePath($request);
        }
    }

    /**
     * Check if the current path requires HTTPS.
     */
    protected function isSecurePath(Request $request): bool
    {
        $path = trim($request->path(), '/');
        
        foreach ($this->securePaths as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if the current path is explicitly allowed to use HTTP.
     */
    protected function isInsecurePath(Request $request): bool
    {
        $path = trim($request->path(), '/');
        
        foreach ($this->insecurePaths as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if a path matches a pattern (supports wildcards).
     */
    protected function matchesPattern(string $path, string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(['*', '/'], ['[^/]*', '\/'], $pattern);
        $regex = '/^' . $regex . '$/i';
        
        return preg_match($regex, $path);
    }

    /**
     * Redirect to HTTPS version of the URL.
     */
    protected function redirectToHttps(Request $request): Response
    {
        // Use Laravel's built-in secure redirect - this uses APP_URL configuration
        // and doesn't rely on user-controlled host headers
        $redirectResponse = redirect()->secure($request->getRequestUri(), 301);
        
        // Log security redirect
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'https_redirect',
            'model_type' => 'security',
            'details' => [
                'from_url' => $request->fullUrl(),
                'to_url' => $redirectResponse->getTargetUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ]
        ]);
        
        return $redirectResponse;
    }

    /**
     * Add security headers to HTTPS responses.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        $headers = [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin'
        ];

        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }
    }
}
