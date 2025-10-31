<?php

namespace Database\Seeders;

use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\UsageBucket;
use App\Domains\Product\Models\UsagePool;
use Illuminate\Database\Seeder;

class UsageBucketSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Usage Bucket Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating usage buckets for company: {$company->name}");

            $clients = Client::where('company_id', $company->id)->get();
            $pools = UsagePool::where('company_id', $company->id)->pluck('id')->toArray();

            if ($clients->isEmpty()) {
                $this->command->warn("No clients found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 2-4 usage buckets for each client with usage pools
            foreach ($clients as $client) {
                // 40% of clients get usage buckets
                if (rand(1, 100) > 40) continue;

                $bucketCount = rand(2, 4);

                for ($i = 0; $i < $bucketCount; $i++) {
                    UsageBucket::factory()
                        ->for($company)
                        ->for($client)
                        ->create([
                            'usage_pool_id' => !empty($pools) && rand(0, 1) ? fake()->randomElement($pools) : null,
                        ]);
                }
            }

            $this->command->info("Completed usage buckets for company: {$company->name}");
        }

        $this->command->info('Usage Bucket Seeder completed!');
    }
}
