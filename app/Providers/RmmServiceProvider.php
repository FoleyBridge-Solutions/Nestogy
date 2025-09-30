<?php

namespace App\Providers;

use App\Domains\Integration\Services\RmmServiceFactory;
use Illuminate\Support\ServiceProvider;

/**
 * RMM Service Provider
 *
 * Registers RMM integration services and bindings.
 */
class RmmServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the RMM factory as a singleton
        $this->app->singleton('rmm.factory', function () {
            return new RmmServiceFactory;
        });

        // Register factory binding for dependency injection
        $this->app->bind(RmmServiceFactory::class, function () {
            return app('rmm.factory');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
