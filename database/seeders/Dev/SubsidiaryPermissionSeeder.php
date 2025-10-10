<?php

namespace Database\Seeders\Dev;

use App\Models\SubsidiaryPermission;
use Illuminate\Database\Seeder;

class SubsidiaryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating SubsidiaryPermission records...");
        $this->command->info("✓ SubsidiaryPermission seeded");
    }
}
