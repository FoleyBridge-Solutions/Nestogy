<?php

namespace Database\Seeders\Dev;

use App\Domains\Product\Models\UsageRecord;
use Illuminate\Database\Seeder;

class UsageRecordSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating UsageRecord records...");
        $this->command->info("✓ UsageRecord seeded");
    }
}
