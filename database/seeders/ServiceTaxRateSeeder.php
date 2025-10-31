<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\ServiceTaxRate;
use Illuminate\Database\Seeder;

class ServiceTaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Service Tax Rate Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating service tax rates for company: {$company->name}");

            // Create 20-30 service tax rates per company
            ServiceTaxRate::factory()
                ->count(rand(20, 30))
                ->for($company)
                ->create();

            $this->command->info("Completed service tax rates for company: {$company->name}");
        }

        $this->command->info('Service Tax Rate Seeder completed!');
    }
}
