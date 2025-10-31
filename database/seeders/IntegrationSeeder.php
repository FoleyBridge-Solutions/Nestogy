<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Integration\Models\Integration;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Integration Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 3-10 integrations per company
            Integration::factory()
                ->count(rand(3, 10))
                ->for($company)
                ->create();
        }

        $this->command->info('Integration Seeder completed!');
    }
}
