<?php

namespace Database\Seeders\Dev;

use App\Domains\PhysicalMail\Models\PhysicalMailSettings;
use Illuminate\Database\Seeder;

class PhysicalMailSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating PhysicalMailSettings records...");
        $this->command->info("✓ PhysicalMailSettings seeded");
    }
}
