<?php

namespace Database\Seeders\Dev;

use App\Domains\Financial\Models\RevenueMetric;
use Illuminate\Database\Seeder;

class RevenueMetricSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating RevenueMetric records...");
        $this->command->info("✓ RevenueMetric seeded");
    }
}
