<?php

namespace Database\Seeders;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySubscription;
use Illuminate\Database\Seeder;

class CompanySubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Company Subscription Seeder...');

        $companies = Company::where('id', '>', 1)->get();

        foreach ($companies as $company) {
            // Each company gets 1 subscription
            CompanySubscription::factory()
                ->for($company)
                ->create();
        }

        $this->command->info('Company Subscription Seeder completed!');
    }
}
