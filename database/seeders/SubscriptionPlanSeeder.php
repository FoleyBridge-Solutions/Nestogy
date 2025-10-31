<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Product\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Subscription Plan Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Create 5-10 subscription plans per company
            SubscriptionPlan::factory()
                ->count(rand(5, 10))
                ->for($company)
                ->create();
        }

        $this->command->info('Subscription Plan Seeder completed!');
    }
}
