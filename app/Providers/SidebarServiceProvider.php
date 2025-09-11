<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SidebarConfigProvider;
use Illuminate\Support\Facades\Event;

class SidebarServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the SidebarConfigProvider as a singleton
        $this->app->singleton(SidebarConfigProvider::class, function ($app) {
            return new SidebarConfigProvider();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Allow modules/packages to register sidebar sections dynamically
        Event::listen('sidebar.register', function ($context, $key, $section) {
            app(SidebarConfigProvider::class)->registerSection($context, $key, $section);
        });
        
        // Allow providers defined in config to register sections
        $this->registerConfiguredProviders();
        
        // Share sidebar config with all views (optional)
        $this->app['view']->composer('*', function ($view) {
            $view->with('sidebarProvider', app(SidebarConfigProvider::class));
        });
    }
    
    /**
     * Register sidebar providers from configuration
     */
    protected function registerConfiguredProviders(): void
    {
        $providers = config('sidebar.providers', []);
        
        foreach ($providers as $providerClass) {
            if (class_exists($providerClass)) {
                $provider = new $providerClass();
                
                if (method_exists($provider, 'registerSidebar')) {
                    $provider->registerSidebar(app(SidebarConfigProvider::class));
                }
            }
        }
    }
    
    /**
     * Helper method to register a sidebar section from any service provider
     * 
     * Usage in other providers:
     * app(SidebarServiceProvider::class)->registerSection('main', 'custom', [...]);
     */
    public function registerSection(string $context, string $key, array $section): void
    {
        app(SidebarConfigProvider::class)->registerSection($context, $key, $section);
    }
    
    /**
     * Helper to register multiple sections at once
     */
    public function registerSections(string $context, array $sections): void
    {
        foreach ($sections as $key => $section) {
            $this->registerSection($context, $key, $section);
        }
    }
}