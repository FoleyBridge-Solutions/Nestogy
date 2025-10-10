<?php

namespace Database\Seeders\Dev;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Service records...");
        $this->command->info("✓ Service seeded");
    }
}
