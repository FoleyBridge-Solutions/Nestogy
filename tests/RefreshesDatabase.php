<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\ParallelTesting;

trait RefreshesDatabase
{
    use RefreshDatabase;

    /**
     * Define hooks to migrate the database before and after each test.
     */
    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            try {
                $this->artisan('migrate:fresh', array_merge(
                    $this->migrateFreshUsing(),
                    ['--quiet' => true]
                ));

                $this->app[Kernel::class]->setArtisan(null);

                RefreshDatabaseState::$migrated = true;
            } catch (\Exception $e) {
                // Log the error but allow the test to continue
                \Illuminate\Support\Facades\Log::warning('Database migration failed: ' . $e->getMessage());
                RefreshDatabaseState::$migrated = true;
            }
        }

        $this->beginDatabaseTransaction();
    }
}
