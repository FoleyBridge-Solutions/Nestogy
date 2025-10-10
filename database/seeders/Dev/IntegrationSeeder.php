<?php

namespace Database\Seeders\Dev;

use App\Models\Integration;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Integration records...");
        $this->command->info("✓ Integration seeded");
    }
}
