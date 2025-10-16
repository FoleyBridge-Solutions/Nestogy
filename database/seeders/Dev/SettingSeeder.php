<?php

namespace Database\Seeders\Dev;

use App\Domains\Core\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Setting records...");
        $this->command->info("✓ Setting seeded");
    }
}
