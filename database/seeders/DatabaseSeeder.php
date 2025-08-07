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
        // Run seeders in the correct order to respect foreign key constraints
        $this->call([
            CompanySeeder::class,
            UserSeeder::class,
            UserSettingsSeeder::class,
            CategorySeeder::class,
            AccountSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
