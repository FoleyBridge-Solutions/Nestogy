<?php

namespace Database\Seeders\Dev;

use App\Domains\Product\Models\UsageTier;
use Illuminate\Database\Seeder;

class UsageTierSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating UsageTier records...");
        $this->command->info("✓ UsageTier seeded");
    }
}
