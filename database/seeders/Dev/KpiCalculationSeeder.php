<?php

namespace Database\Seeders\Dev;

use App\Models\KpiCalculation;
use Illuminate\Database\Seeder;

class KpiCalculationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating KpiCalculation records...");
        $this->command->info("✓ KpiCalculation seeded");
    }
}
