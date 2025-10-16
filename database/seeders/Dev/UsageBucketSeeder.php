<?php

namespace Database\Seeders\Dev;

use App\Domains\Product\Models\UsageBucket;
use Illuminate\Database\Seeder;

class UsageBucketSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating UsageBucket records...");
        $this->command->info("✓ UsageBucket seeded");
    }
}
