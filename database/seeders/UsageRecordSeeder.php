<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\UsageBucket;
use App\Domains\Product\Models\UsageRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UsageRecordSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Usage Record Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating usage records for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $buckets = UsageBucket::where('company_id', $company->id)->pluck('id')->toArray();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create usage records for 50-60% of clients
            $clientCount = (int) ($clients->count() * rand(50, 60) / 100);
            $selectedClients = $clients->random(min($clientCount, $clients->count()));

            foreach ($selectedClients as $client) {
                // Create 50-200 usage records per client over the past 60 days
                $recordCount = rand(50, 200);

                for ($i = 0; $i < $recordCount; $i++) {
                    UsageRecord::factory()
                        ->for($company)
                        ->for($client)
                        ->create([
                            'usage_bucket_id' => !empty($buckets) && rand(0, 1) ? fake()->randomElement($buckets) : null,
                            'usage_date' => Carbon::now()->subDays(rand(0, 60)),
                        ]);
                }
            }

            $this->command->info("Completed usage records for company: {$company->name}");
        }

        $this->command->info('Usage Record Seeder completed!');
    }
}
