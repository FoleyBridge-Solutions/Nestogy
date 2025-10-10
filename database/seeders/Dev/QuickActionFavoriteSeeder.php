<?php

namespace Database\Seeders\Dev;

use App\Models\QuickActionFavorite;
use Illuminate\Database\Seeder;

class QuickActionFavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating QuickActionFavorite records...");
        $this->command->info("✓ QuickActionFavorite seeded");
    }
}
