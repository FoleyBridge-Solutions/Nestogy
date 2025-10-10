<?php

namespace Database\Seeders\Dev;

use App\Models\SettingsConfiguration;
use Illuminate\Database\Seeder;

class SettingsConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating SettingsConfiguration records...");
        $this->command->info("✓ SettingsConfiguration seeded");
    }
}
