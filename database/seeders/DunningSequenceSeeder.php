<?php

namespace Database\Seeders;

use App\Domains\Collections\Models\DunningCampaign;
use App\Domains\Collections\Models\DunningSequence;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class DunningSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Dunning Sequence Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating dunning sequences for company: {$company->name}");

            $campaigns = DunningCampaign::where('company_id', $company->id)->get();

            if ($campaigns->isEmpty()) {
                $this->command->warn("No campaigns found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 3-7 sequences per campaign
            foreach ($campaigns as $campaign) {
                DunningSequence::factory()
                    ->count(rand(3, 7))
                    ->for($company)
                    ->for($campaign)
                    ->create();
            }

            $this->command->info("Completed dunning sequences for company: {$company->name}");
        }

        $this->command->info('Dunning Sequence Seeder completed!');
    }
}
