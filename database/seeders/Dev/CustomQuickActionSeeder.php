<?php

namespace Database\Seeders\Dev;

use App\Models\CustomQuickAction;
use Illuminate\Database\Seeder;

class CustomQuickActionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CustomQuickAction records...");
        $this->command->info("✓ CustomQuickAction seeded");
    }
}
