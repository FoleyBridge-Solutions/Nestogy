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
        // Run essential system seeders only - no demo data
        $this->call([
            // Core system setup
            SubscriptionPlansSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            UserSettingsSeeder::class,
            RolesAndPermissionsSeeder::class, // Bouncer-based permissions
            
            // System configuration (essential for functionality)
            CategorySeeder::class,
            TaxSeeder::class,
            
            // System templates (not demo data)
            TicketTemplateSeeder::class,
            ProjectTemplateSeeder::class,
            DocumentTemplateSeeder::class,
            
            // CRM/Marketing default data
            LeadSourceSeeder::class,
        ]);
    }
}
