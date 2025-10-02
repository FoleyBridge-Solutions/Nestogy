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

        // Register domain routes for testing
        try {
            $routeManager = $app->make(\App\Domains\Core\Services\DomainRouteManager::class);
            $routeManager->registerDomainRoutes();
        } catch (\Exception $e) {
            // If DomainRouteManager fails, manually load critical route files
            $domainRouteFiles = glob(app_path('Domains/*/routes.php'));
            foreach ($domainRouteFiles as $routeFile) {
                require_once $routeFile;
            }
        }

        return $app;
    }
}