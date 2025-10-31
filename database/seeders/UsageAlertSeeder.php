<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\UsageAlert;
use App\Domains\Product\Models\UsageBucket;
use Illuminate\Database\Seeder;

class UsageAlertSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Usage Alert Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating usage alerts for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $buckets = UsageBucket::where('company_id', $company->id)->pluck('id')->toArray();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create usage alerts for 20-30% of clients
            $clientCount = (int) ($clients->count() * rand(20, 30) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                // Create 1-3 alerts per client
                $alertCount = rand(1, 3);

                for ($i = 0; $i < $alertCount; $i++) {
                    UsageAlert::factory()
                        ->for($company)
                        ->for($client)
                        ->create([
                            'usage_bucket_id' => !empty($buckets) && rand(0, 1) ? fake()->randomElement($buckets) : null,
                        ]);
                }
            }

            $this->command->info("Completed usage alerts for company: {$company->name}");
        }

        $this->command->info('Usage Alert Seeder completed!');
    }
}
