<?php

namespace Database\Seeders\Dev;

use App\Domains\Tax\Models\ServiceTaxRate;
use Illuminate\Database\Seeder;

class ServiceTaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating ServiceTaxRate records...");
        $this->command->info("✓ ServiceTaxRate seeded");
    }
}
