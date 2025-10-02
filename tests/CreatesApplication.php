<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // CRITICAL: Ensure domain routes are registered for tests
        // The bootstrap process should register them, but we ensure it here
        // to handle cases where RefreshDatabase clears the router
        $this->ensureDomainRoutesRegistered($app);

        return $app;
    }

    protected function ensureDomainRoutesRegistered(Application $app): void
    {
        // Check if routes are already registered
        if (\Illuminate\Support\Facades\Route::has('clients.index')) {
            return; // Routes already registered
        }

        try {
            $routeManager = $app->make(\App\Domains\Core\Services\DomainRouteManager::class);
            $routeManager->registerDomainRoutes();
        } catch (\Exception $e) {
            // Log the error but don't fail - some tests might not need routes
            if (method_exists($this, 'markTestSkipped')) {
                // We're in a test context, log the error
                error_log("Failed to register domain routes: " . $e->getMessage());
            }
        }
    }
}