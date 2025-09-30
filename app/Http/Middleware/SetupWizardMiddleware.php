<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetupWizardMiddleware
 *
 * Redirects users to the setup wizard if no companies exist in the system.
 * This ensures that the ERP system is properly initialized with at least one company
 * before allowing access to the main application.
 */
class SetupWizardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for setup wizard routes, API routes, and asset requests
        if ($this->shouldSkipCheck($request)) {
            return $next($request);
        }

        // Check if any companies exist
        if (! Company::exists()) {
            // Redirect to setup wizard
            return redirect()->route('setup.wizard.index');
        }

        return $next($request);
    }

    /**
     * Determine if the setup check should be skipped for this request.
     */
    protected function shouldSkipCheck(Request $request): bool
    {
        // Skip for setup wizard routes
        if ($request->routeIs('setup.*')) {
            return true;
        }

        // Skip for API routes
        if ($request->is('api/*')) {
            return true;
        }

        // Skip for Livewire routes
        if ($request->is('livewire/*')) {
            return true;
        }

        // Skip for assets and static files
        if ($request->is('assets/*', 'images/*', 'css/*', 'js/*', 'storage/*', 'build/*')) {
            return true;
        }

        // Skip for health check route
        if ($request->is('up')) {
            return true;
        }

        // Skip for auth routes that don't require companies
        $authRoutes = [
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
            'signup.*',
            'security.*',
        ];

        foreach ($authRoutes as $route) {
            if ($request->routeIs($route)) {
                return true;
            }
        }

        return false;
    }
}
