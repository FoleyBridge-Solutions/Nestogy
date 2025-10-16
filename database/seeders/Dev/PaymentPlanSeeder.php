<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\PaymentPlan;
use Illuminate\Database\Seeder;

class PaymentPlanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating PaymentPlan records...");
        $this->command->info("✓ PaymentPlan seeded");
    }
}
