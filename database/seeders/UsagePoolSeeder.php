<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\UsagePool;
use Illuminate\Database\Seeder;

class UsagePoolSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Usage Pool Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating usage pools for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 1-2 usage pools for 30-40% of clients
            $clientCount = (int) ($clients->count() * rand(30, 40) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                UsagePool::factory()
                    ->count(rand(1, 2))
                    ->for($company)
                    ->for($client)
                    ->create();
            }

            $this->command->info("Completed usage pools for company: {$company->name}");
        }

        $this->command->info('Usage Pool Seeder completed!');
    }
}
