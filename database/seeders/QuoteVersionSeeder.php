<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\Quote;
use App\Domains\Financial\Models\QuoteVersion;
use Illuminate\Database\Seeder;

class QuoteVersionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Quote Version Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating quote versions for company: {$company->name}");

            $quotes = Quote::where('company_id', $company->id)->get();

            if ($quotes->isEmpty()) {
                $this->command->warn("No quotes found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create 1-3 versions for 30-40% of quotes
            $quoteCount = (int) ($quotes->count() * rand(30, 40) / 100);
            $selectedQuotes = $quotes->random(min($quoteCount, $quotes->count()));

            foreach ($selectedQuotes as $quote) {
                $versionCount = rand(1, 3);

                QuoteVersion::factory()
                    ->count($versionCount)
                    ->for($company)
                    ->for($quote)
                    ->create();
            }

            $this->command->info("Completed quote versions for company: {$company->name}");
        }

        $this->command->info('Quote Version Seeder completed!');
    }
}
