<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ConfigurationValidationService;
use Illuminate\Support\Facades\Log;

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
}
