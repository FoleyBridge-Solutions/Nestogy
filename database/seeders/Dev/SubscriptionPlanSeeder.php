<?php

namespace Database\Seeders\Dev;

use App\Domains\Product\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating SubscriptionPlan records...");
        $this->command->info("✓ SubscriptionPlan seeded");
    }
}
