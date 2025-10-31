<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Quote;
use App\Domains\Financial\Models\QuoteApproval;
use Illuminate\Database\Seeder;

class QuoteApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Quote Approval Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating quote approvals for company: {$company->name}");

            $quotes = Quote::where('company_id', $company->id)->get();
            $users = User::where('company_id', $company->id)->pluck('id')->toArray();

            if ($quotes->isEmpty() || empty($users)) {
                $this->command->warn("No quotes or users found for company: {$company->name}. Skipping.");
                continue;
            }

            // Create approvals for 60-70% of quotes
            $quoteCount = (int) ($quotes->count() * rand(60, 70) / 100);
            $selectedQuotes = $quotes->random(min($quoteCount, $quotes->count()));

            foreach ($selectedQuotes as $quote) {
                QuoteApproval::factory()
                    ->for($company)
                    ->for($quote)
                    ->create([
                        'approved_by' => fake()->randomElement($users),
                    ]);
            }

            $this->command->info("Completed quote approvals for company: {$company->name}");
        }

        $this->command->info('Quote Approval Seeder completed!');
    }
}
