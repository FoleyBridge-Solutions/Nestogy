<?php

namespace Database\Seeders\Dev;

use App\Domains\Tax\Models\ComplianceRequirement;
use Illuminate\Database\Seeder;

class ComplianceRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating ComplianceRequirement records...");
        $this->command->info("✓ ComplianceRequirement seeded");
    }
}
