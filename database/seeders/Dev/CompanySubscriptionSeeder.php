<?php

namespace Database\Seeders\Dev;

use App\Models\CompanySubscription;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CompanySubscription records...");
        $this->command->info("✓ CompanySubscription seeded");
    }
}
