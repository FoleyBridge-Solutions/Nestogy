<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check environment to determine which seeder to run
        if (app()->environment('local', 'development', 'testing')) {
            // Development/Local/Testing: Run dev seeder with test data
            $this->call([
                DevDatabaseSeeder::class,
            ]);
        } else {
            // Production/Staging: Run essential seeders only
            $this->call([
                SubscriptionPlansSeeder::class,
                RolesAndPermissionsSeeder::class,
            ]);
        }
    }
}
