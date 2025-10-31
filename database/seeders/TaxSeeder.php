<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating taxes for company: {$company->name}");

            // Create standard tax rates
            $taxRates = [
                ['name' => 'No Tax', 'percent' => 0.00],
                ['name' => 'Sales Tax', 'percent' => 8.25],
                ['name' => 'VAT Standard', 'percent' => 20.00],
                ['name' => 'VAT Reduced', 'percent' => 10.00],
                ['name' => 'GST', 'percent' => 10.00],
                ['name' => 'State Sales Tax', 'percent' => 6.00],
                ['name' => 'Local Sales Tax', 'percent' => 2.25],
                ['name' => 'Telecom Tax', 'percent' => 5.00],
                ['name' => 'Digital Services Tax', 'percent' => 7.50],
                ['name' => 'Luxury Tax', 'percent' => 15.00],
            ];

            foreach ($taxRates as $taxData) {
                Tax::factory()
                    ->for($company)
                    ->create($taxData);
            }

            // Create some random additional tax rates
            Tax::factory()
                ->count(5)
                ->for($company)
                ->create();

            $this->command->info("Completed taxes for company: {$company->name}");
        }

        $this->command->info('Tax Seeder completed!');
    }
}
