<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\TaxApiSettings;
use Illuminate\Database\Seeder;

class TaxApiSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax API Settings Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tax API settings for company: {$company->name}");

            // Create 1-2 tax API settings per company (e.g., one for Avalara, one for TaxJar)
            TaxApiSettings::factory()
                ->count(rand(1, 2))
                ->for($company)
                ->create();

            $this->command->info("Completed tax API settings for company: {$company->name}");
        }

        $this->command->info('Tax API Settings Seeder completed!');
    }
}
