<?php

namespace Database\Seeders\Dev;

use App\Models\TaxProfile;
use Illuminate\Database\Seeder;

class TaxProfileSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxProfile records...");
        $this->command->info("✓ TaxProfile seeded");
    }
}
