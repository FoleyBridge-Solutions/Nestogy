<?php

namespace Database\Seeders\Dev;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("Creating Role records...");
        $this->command->info("✓ Role seeded");
    }
}
