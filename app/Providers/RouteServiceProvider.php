<?php

namespace App\Providers;

use App\Domains\Core\Services\DomainRouteManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Call parent to set up Laravel's routing mechanism
        parent::register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Register domain routes using the routes() helper method
        // This integrates with Laravel's routing system properly
        $this->routes(function () {
            $this->registerDomainRoutes();
        });
    }

    /**
     * Register domain routes using the Domain Route Manager
     */
    protected function registerDomainRoutes(): void
    {
        try {
            $routeManager = $this->app->make(DomainRouteManager::class);
            $routeManager->registerDomainRoutes();
        } catch (\Exception $e) {
            // Fail in local and testing environments so we catch route registration errors
            if ($this->app->environment(['local', 'testing'])) {
                throw $e;
            }
            
            // Log error but don't fail the application in production
            logger()->error('Failed to register domain routes in RouteServiceProvider', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email').$request->ip());
        });
    }
}
