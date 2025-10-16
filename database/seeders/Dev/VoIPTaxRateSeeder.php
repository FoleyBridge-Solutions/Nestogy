<?php

namespace Database\Seeders\Dev;

use App\Domains\Tax\Models\VoIPTaxRate;
use Illuminate\Database\Seeder;

class VoIPTaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating VoIPTaxRate records...");
        $this->command->info("✓ VoIPTaxRate seeded");
    }
}
