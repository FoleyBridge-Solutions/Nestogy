<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\PricingRule;
use Illuminate\Database\Seeder;

class PricingRuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Pricing Rule Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 10-20 pricing rules per company
            PricingRule::factory()
                ->count(rand(10, 20))
                ->for($company)
                ->create();
        }

        $this->command->info('Pricing Rule Seeder completed!');
    }
}
