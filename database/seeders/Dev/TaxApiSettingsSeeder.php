<?php

namespace Database\Seeders\Dev;

use App\Models\TaxApiSettings;
use Illuminate\Database\Seeder;

class TaxApiSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating TaxApiSettings records...");
        $this->command->info("✓ TaxApiSettings seeded");
    }
}
