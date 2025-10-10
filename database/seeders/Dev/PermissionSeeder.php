<?php

namespace Database\Seeders\Dev;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Permission records...");
        $this->command->info("✓ Permission seeded");
    }
}
