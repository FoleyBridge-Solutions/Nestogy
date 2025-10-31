<?php

namespace Database\Seeders;

use App\Domains\Collections\Models\DunningCampaign;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class DunningCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Dunning Campaign Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating dunning campaigns for company: {$company->name}");

            // Create 3-5 dunning campaigns per company with different escalation levels
            DunningCampaign::factory()
                ->count(rand(3, 5))
                ->for($company)
                ->create();

            $this->command->info("Completed dunning campaigns for company: {$company->name}");
        }

        $this->command->info('Dunning Campaign Seeder completed!');
    }
}
