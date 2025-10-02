<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait RefreshesDatabase
{
    use RefreshDatabase {
        RefreshDatabase::refreshTestDatabase as parentRefreshTestDatabase;
    }

    protected function refreshTestDatabase(): void
    {
        $this->parentRefreshTestDatabase();

        // Re-register domain routes after database refresh
        // RefreshDatabase can cause the app to refresh, clearing routes
        if (method_exists($this, 'ensureDomainRoutesRegistered')) {
            $this->ensureDomainRoutesRegistered($this->app);
        } else {
            // Fallback: directly register routes
            try {
                $routeManager = app(\App\Domains\Core\Services\DomainRouteManager::class);
                $routeManager->registerDomainRoutes();
            } catch (\Exception $e) {
                // Ignore errors - some tests might not need routes
            }
        }
    }
}
