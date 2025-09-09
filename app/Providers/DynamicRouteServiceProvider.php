<?php

namespace App\Providers;

use App\Domains\Contract\Services\DynamicContractRouteService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;

class DynamicRouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        parent::boot();

        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Load dynamic contract routes
            Route::middleware('web')
                ->group(base_path('routes/dynamic-contracts.php'));

            // Register dynamic contract routes
            $this->registerDynamicContractRoutes();
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('dynamic-contracts', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Register dynamic contract routes
     */
    protected function registerDynamicContractRoutes(): void
    {
        // Only register dynamic routes if not in console (to avoid issues during migration)
        if (!app()->runningInConsole()) {
            try {
                $routeService = app(DynamicContractRouteService::class);
                $routeService->registerAllRoutes();
            } catch (\Exception $e) {
                // Silently fail if database is not available (during initial setup)
                if (!str_contains($e->getMessage(), 'Connection refused') && 
                    !str_contains($e->getMessage(), 'Table') &&
                    !str_contains($e->getMessage(), 'database')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Register dynamic routes for specific company
     */
    public function registerCompanyRoutes(int $companyId): void
    {
        $routeService = app(DynamicContractRouteService::class);
        $routeService->registerCompanyRoutes($companyId);
    }
}