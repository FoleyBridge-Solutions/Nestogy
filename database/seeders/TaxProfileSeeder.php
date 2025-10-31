<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\TaxProfile;
use Illuminate\Database\Seeder;

class TaxProfileSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax Profile Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tax profiles for company: {$company->name}");

            // Create default tax profiles using the model's built-in method
            TaxProfile::createDefaultProfiles($company->id);

            // Create 5-10 additional custom tax profiles
            TaxProfile::factory()
                ->count(rand(5, 10))
                ->for($company)
                ->create();

            $this->command->info("Completed tax profiles for company: {$company->name}");
        }

        $this->command->info('Tax Profile Seeder completed!');
    }
}
