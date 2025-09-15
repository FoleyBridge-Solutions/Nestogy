<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionDatabaseSeeder extends Seeder
{
    /**
     * Seed the production database with essential system data only.
     */
    public function run(): void
    {
        $this->call([
            // Core system setup
            SubscriptionPlansSeeder::class,

            // System permissions structure (roles only, no user assignments)
            RolesAndPermissionsSeeder::class,

            // System configuration
            CategorySeeder::class,
            TaxSeeder::class,

            // System templates
            TicketTemplateSeeder::class,
            ProjectTemplateSeeder::class,
            DocumentTemplateSeeder::class,

            // CRM/Marketing defaults
            LeadSourceSeeder::class,
        ]);
    }
}