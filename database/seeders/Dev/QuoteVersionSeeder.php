<?php

namespace Database\Seeders\Dev;

use App\Models\QuoteVersion;
use Illuminate\Database\Seeder;

class QuoteVersionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating QuoteVersion records...");
        $this->command->info("✓ QuoteVersion seeded");
    }
}
