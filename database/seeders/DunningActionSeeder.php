<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Collections\Models\DunningAction;
use App\Domains\Collections\Models\DunningCampaign;
use App\Domains\Collections\Models\DunningSequence;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Invoice;
use Illuminate\Database\Seeder;

class DunningActionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Dunning Action Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating dunning actions for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $campaigns = DunningCampaign::where('company_id', $company->id)->get();
            $sequences = DunningSequence::where('company_id', $company->id)->pluck('id')->toArray();

            if ($clients->isEmpty() || $campaigns->isEmpty() || empty($sequences)) {
                $this->command->warn("Skipping company {$company->name}: missing clients/campaigns/sequences");
                continue;
            }

            // Create dunning actions for 20-30% of clients
            $clientCount = (int) ($clients->count() * rand(20, 30) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                // Get one overdue invoice for this client
                $invoice = Invoice::where('company_id', $company->id)
                    ->where('client_id', $client->id)
                    ->where('status', 'unpaid')
                    ->first();

                if (!$invoice) continue;

                // Create 1-3 actions per selected client
                $actionCount = rand(1, 3);

                for ($i = 0; $i < $actionCount; $i++) {
                    DunningAction::factory()
                        ->for($company)
                        ->for($client)
                        ->create([
                            'dunning_campaign_id' => fake()->randomElement($campaigns)->id,
                            'dunning_sequence_id' => fake()->randomElement($sequences),
                            'invoice_id' => $invoice->id,
                        ]);
                }
            }

            $this->command->info("Completed dunning actions for company: {$company->name}");
        }

        $this->command->info('Dunning Action Seeder completed!');
    }
}
