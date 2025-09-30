<?php

namespace App\Providers;

use App\Domains\PhysicalMail\Services\CompanyAwarePostGridClient;
use App\Domains\PhysicalMail\Services\PhysicalMailService;
use App\Domains\PhysicalMail\Services\PostGridClient;
use Illuminate\Support\ServiceProvider;

class PhysicalMailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind PostGridClient to use company-aware version when in request context
        $this->app->bind(PostGridClient::class, function ($app) {
            // If we're in a request context with an authenticated user
            if (auth()->check() && auth()->user()->company_id) {
                try {
                    return new CompanyAwarePostGridClient(auth()->user()->company_id);
                } catch (\Exception $e) {
                    // Fall back to regular client with env config
                    return new PostGridClient;
                }
            }

            // Default to env config for console commands, jobs, etc.
            return new PostGridClient;
        });

        // Register PhysicalMailService as singleton
        $this->app->singleton(PhysicalMailService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
