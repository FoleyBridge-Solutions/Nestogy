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
            // Note: RolesAndPermissionsSeeder will create roles but won't assign to users
            RolesAndPermissionsSeeder::class,

            // Company-specific seeders are NOT included here since they require
            // company_id=1 to exist. These will be run after company creation:
            // - CategorySeeder
            // - TaxSeeder
            // - TicketTemplateSeeder
            // - ProjectTemplateSeeder
            // - DocumentTemplateSeeder
            // - LeadSourceSeeder
            // - QuickActionsSeeder
        ]);
    }
}
