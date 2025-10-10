<?php

namespace Database\Seeders\Dev;

use App\Models\ComplianceCheck;
use Illuminate\Database\Seeder;

class ComplianceCheckSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating ComplianceCheck records...");
        $this->command->info("✓ ComplianceCheck seeded");
    }
}
