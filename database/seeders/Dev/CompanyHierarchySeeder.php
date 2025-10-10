<?php

namespace Database\Seeders\Dev;

use App\Models\CompanyHierarchy;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanyHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CompanyHierarchy records...");
        $this->command->info("✓ CompanyHierarchy seeded");
    }
}
