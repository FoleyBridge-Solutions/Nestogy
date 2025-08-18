<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ConfigurationValidationService;
use App\Services\NavigationService;
use App\Services\VoIPTaxService;
use App\Services\VoIPUsageService;
use App\Services\VoIPTieredPricingService;
use App\Services\ClaudePTYService;
use App\Http\ViewComposers\NavigationComposer;
use App\Http\ViewComposers\ClientViewComposer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Silber\Bouncer\BouncerFacade as Bouncer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the configuration validation service
        $this->app->singleton(ConfigurationValidationService::class, function ($app) {
            return new ConfigurationValidationService();
        });

        // Register the navigation service
        $this->app->singleton(NavigationService::class, function ($app) {
            return new NavigationService();
        });

        // Register VoIP services with proper dependency injection
        $this->app->bind(VoIPTaxService::class, function ($app) {
            $service = new VoIPTaxService();
            if (Auth::check() && Auth::user()->company_id) {
                $service->setCompanyId(Auth::user()->company_id);
            }
            return $service;
        });

        $this->app->bind(VoIPUsageService::class, function ($app) {
            return new VoIPUsageService();
        });

        $this->app->bind(VoIPTieredPricingService::class, function ($app) {
            return new VoIPTieredPricingService();
        });

        // Register Claude PTY service as singleton for session management
        $this->app->singleton(ClaudePTYService::class, function ($app) {
            return new ClaudePTYService();
        });

        // Register Tax Profile Service with company context
        $this->app->bind(\App\Services\TaxEngine\TaxProfileService::class, function ($app) {
            $service = new \App\Services\TaxEngine\TaxProfileService();
            if (Auth::check() && Auth::user()->company_id) {
                $service->setCompanyId(Auth::user()->company_id);
            }
            return $service;
        });

        // Register Tax Engine Router with company context
        $this->app->bind(\App\Services\TaxEngine\TaxEngineRouter::class, function ($app) {
            $service = new \App\Services\TaxEngine\TaxEngineRouter();
            if (Auth::check() && Auth::user()->company_id) {
                $service->setCompanyId(Auth::user()->company_id);
            }
            return $service;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Run configuration validation in non-production environments
        if ($this->app->environment(['local', 'development', 'staging'])) {
            $this->validateConfiguration();
        }

        // Set default timezone from configuration
        if ($timezone = config('nestogy.company.default_timezone')) {
            date_default_timezone_set($timezone);
        }

        // Configure upload directories
        $this->configureUploadDirectories();

        // Register view composers
        $this->registerViewComposers();
        
        // Configure Bouncer
        $this->configureBouncer();
    }

    /**
     * Validate application configuration
     */
    protected function validateConfiguration(): void
    {
        try {
            $validator = $this->app->make(ConfigurationValidationService::class);
            $isValid = $validator->validate();

            if (!$isValid) {
                $errors = $validator->getErrors();
                Log::error('Configuration validation failed during boot', ['errors' => $errors]);
                
                // In console, show errors
                if ($this->app->runningInConsole()) {
                    foreach ($errors as $error) {
                        $this->app->make('log')->error("Config Error: {$error}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to run configuration validation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Register view composers
     */
    protected function registerViewComposers(): void
    {
        // Inject navigation data into the main app layout
        View::composer('layouts.app', NavigationComposer::class);
        
        // Inject selected client data into client views
        View::composer('clients.*', ClientViewComposer::class);
    }

    /**
     * Configure upload directories
     */
    protected function configureUploadDirectories(): void
    {
        $uploadPaths = config('uploads.paths', []);
        
        foreach ($uploadPaths as $key => $path) {
            $fullPath = storage_path('app/' . $path);
            
            if (!file_exists($fullPath)) {
                try {
                    mkdir($fullPath, 0755, true);
                } catch (\Exception $e) {
                    Log::warning("Failed to create upload directory: {$fullPath}", ['error' => $e->getMessage()]);
                }
            }
        }
    }
    
    /**
     * Configure Bouncer for multi-tenancy
     */
    protected function configureBouncer(): void
    {
        // Set custom table names
        \Bouncer::tables([
            'abilities' => 'bouncer_abilities',
            'assigned_roles' => 'bouncer_assigned_roles', 
            'permissions' => 'bouncer_permissions',
            'roles' => 'bouncer_roles',
        ]);
        
        // Note: Bouncer scope is set per-request via SetBouncerScope middleware
    }
}
