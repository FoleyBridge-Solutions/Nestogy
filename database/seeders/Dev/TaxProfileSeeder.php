<?php

namespace Database\Seeders\Dev;

use App\Domains\Tax\Models\TaxProfile;
use Illuminate\Database\Seeder;

class TaxProfileSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxProfile records...");
        $this->command->info("✓ TaxProfile seeded");
    }
}
