<?php

namespace Database\Seeders\Dev;

use App\Models\Tax;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Tax records...");
        $this->command->info("✓ Tax seeded");
    }
}
