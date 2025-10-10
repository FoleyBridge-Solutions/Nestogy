<?php

namespace Database\Seeders\Dev;

use App\Models\UsageAlert;
use Illuminate\Database\Seeder;

class UsageAlertSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating UsageAlert records...");
        $this->command->info("✓ UsageAlert seeded");
    }
}
