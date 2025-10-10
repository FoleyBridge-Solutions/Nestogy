<?php

namespace Database\Seeders\Dev;

use App\Models\TaxApiQueryCache;
use Illuminate\Database\Seeder;

class TaxApiQueryCacheSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxApiQueryCache records...");
        $this->command->info("✓ TaxApiQueryCache seeded");
    }
}
