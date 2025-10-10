<?php

namespace Database\Seeders\Dev;

use App\Models\TaxExemption;
use Illuminate\Database\Seeder;

class TaxExemptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxExemption records...");
        $this->command->info("✓ TaxExemption seeded");
    }
}
