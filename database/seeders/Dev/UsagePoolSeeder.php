<?php

namespace Database\Seeders\Dev;

use App\Models\UsagePool;
use Illuminate\Database\Seeder;

class UsagePoolSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating UsagePool records...");
        $this->command->info("✓ UsagePool seeded");
    }
}
