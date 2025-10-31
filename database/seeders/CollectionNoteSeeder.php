<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Collections\Models\CollectionNote;
use App\Domains\Collections\Models\DunningAction;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class CollectionNoteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Collection Note Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating collection notes for company: {$company->name}");

            $actions = DunningAction::where('company_id', $company->id)->get();

            if ($actions->isEmpty()) {
                $this->command->warn("No dunning actions found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 1-3 notes per dunning action
            foreach ($actions as $action) {
                $noteCount = rand(1, 3);

                for ($i = 0; $i < $noteCount; $i++) {
                    CollectionNote::factory()
                        ->for($company)
                        ->for($action->client)
                        ->create([
                            'dunning_action_id' => $action->id,
                        ]);
                }
            }

            $this->command->info("Completed collection notes for company: {$company->name}");
        }

        $this->command->info('Collection Note Seeder completed!');
    }
}
