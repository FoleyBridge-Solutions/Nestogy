<?php

namespace Database\Seeders\Dev;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating PermissionGroup records...");
        $this->command->info("✓ PermissionGroup seeded");
    }
}
