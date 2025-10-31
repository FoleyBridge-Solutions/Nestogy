<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\ClientCreditApplication;
use Illuminate\Database\Seeder;

class CreditApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Credit Application Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating credit applications for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create credit applications for 10-20% of clients
            $clientCount = (int) ($clients->count() * rand(10, 20) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                ClientCreditApplication::factory()
                    ->for($company)
                    ->for($client)
                    ->create();
            }

            $this->command->info("Completed credit applications for company: {$company->name}");
        }

        $this->command->info('Credit Application Seeder completed!');
    }
}
