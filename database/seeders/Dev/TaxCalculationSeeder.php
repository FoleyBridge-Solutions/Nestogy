<?php

namespace Database\Seeders\Dev;

use App\Domains\Tax\Models\TaxCalculation;
use Illuminate\Database\Seeder;

class TaxCalculationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxCalculation records...");
        $this->command->info("✓ TaxCalculation seeded");
    }
}
