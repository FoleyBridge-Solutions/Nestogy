<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Financial\Models\QuoteTemplate;
use Illuminate\Database\Seeder;

class QuoteTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Quote Template Seeder...');

        // Skip root company
        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            $this->command->info("Creating quote templates for company: {$company->name}");

            // Create 3-7 quote templates per company
            QuoteTemplate::factory()
                ->count(rand(3, 7))
                ->for($company)
                ->create();

            $this->command->info("Completed quote templates for company: {$company->name}");
        }

        $this->command->info('Quote Template Seeder completed!');
    }
}
