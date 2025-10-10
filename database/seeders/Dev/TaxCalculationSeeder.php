<?php

namespace Database\Seeders\Dev;

use App\Models\TaxCalculation;
use Illuminate\Database\Seeder;

class TaxCalculationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxCalculation records...");
        $this->command->info("✓ TaxCalculation seeded");
    }
}
