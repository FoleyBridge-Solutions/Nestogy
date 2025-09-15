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
        // Use production seeder by default (no test data)
        $this->call([
            ProductionDatabaseSeeder::class,
        ]);
    }
}
