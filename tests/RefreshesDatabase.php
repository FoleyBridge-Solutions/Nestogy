<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;

trait RefreshesDatabase
{
    use RefreshDatabase;

    /**
     * Define hooks to migrate the database before and after each test.
     */
    protected function refreshTestDatabase(): void
    {
        // Mark migrations as already complete to prevent re-running them
        // Migrations are run once by run-tests.php before all tests
        if (! RefreshDatabaseState::$migrated) {
            RefreshDatabaseState::$migrated = true;
        }

        // Start a transaction for each test
        $this->beginDatabaseTransaction();
    }
}
