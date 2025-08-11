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
        // Run essential seeders for v1
        $this->call([
            SubscriptionPlansSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            UserSettingsSeeder::class,
            RolesAndPermissionsSeeder::class,
            ClientSeeder::class,
        ]);
    }
}
