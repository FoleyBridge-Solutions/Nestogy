<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FinancialSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Financial Seeder (Composite)...');
        
        // This is a composite seeder that calls other financial seeders
        // Individual financial seeders are called directly from DevDatabaseSeeder
        // This seeder exists for backwards compatibility
        
        $this->command->info('✓ Financial seeder completed (composite)');
    }
}
