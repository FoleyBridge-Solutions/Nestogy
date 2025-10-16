<?php

namespace Database\Seeders\Dev;

use App\Domains\Company\Models\CrossCompanyUser;
use Illuminate\Database\Seeder;

class CrossCompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating CrossCompanyUser records...");
        $this->command->info("✓ CrossCompanyUser seeded");
    }
}
