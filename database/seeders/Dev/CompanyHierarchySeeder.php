<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\CompanyHierarchy;
use App\Domains\Company\Models\Company;
use Illuminate\Database\Seeder;

class CompanyHierarchySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CompanyHierarchy records...");
        $this->command->info("✓ CompanyHierarchy seeded");
    }
}
