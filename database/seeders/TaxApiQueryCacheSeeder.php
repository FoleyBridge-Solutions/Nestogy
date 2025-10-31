<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Tax\Models\TaxApiQueryCache;
use Illuminate\Database\Seeder;

class TaxApiQueryCacheSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Tax API Query Cache Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating tax API query cache for company: {$company->name}");

            // Create 50-100 cached queries per company (simulating API call history)
            TaxApiQueryCache::factory()
                ->count(rand(50, 100))
                ->for($company)
                ->create();

            $this->command->info("Completed tax API query cache for company: {$company->name}");
        }

        $this->command->info('Tax API Query Cache Seeder completed!');
    }
}
