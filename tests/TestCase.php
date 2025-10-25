<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Set up the test environment.
     * 
     * Overridden to refresh route name lookups after facade cache is cleared.
     * Routes persist in the Router singleton but the Route facade's name lookup
     * cache gets cleared by Facade::clearResolvedInstances(), so we rebuild it.
     *
     * @return void
     */
    protected function setUpTheTestEnvironment(): void
    {
        parent::setUpTheTestEnvironment();

        // Refresh route name lookups after facade cache is cleared
        // This rebuilds the Route facade's internal route name cache without
        // needing to re-register routes or re-require route files
        $this->app['router']->getRoutes()->refreshNameLookups();
    }
}