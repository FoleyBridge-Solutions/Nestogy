<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\VoIPTaxRate;
use Illuminate\Database\Seeder;

class VoIPTaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting VoIP Tax Rate Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating VoIP tax rates for company: {$company->name}");

            // Create 15-25 VoIP tax rates per company (federal, state, local, regulatory fees)
            VoIPTaxRate::factory()
                ->count(rand(15, 25))
                ->for($company)
                ->create();

            $this->command->info("Completed VoIP tax rates for company: {$company->name}");
        }

        $this->command->info('VoIP Tax Rate Seeder completed!');
    }
}
