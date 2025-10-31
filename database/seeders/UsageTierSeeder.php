<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\UsageTier;
use Illuminate\Database\Seeder;

class UsageTierSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Usage Tier Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating usage tiers for company: {$company->name}");

            // Create 10-15 usage tiers per company with different pricing models
            UsageTier::factory()
                ->count(rand(10, 15))
                ->for($company)
                ->create();

            $this->command->info("Completed usage tiers for company: {$company->name}");
        }

        $this->command->info('Usage Tier Seeder completed!');
    }
}
