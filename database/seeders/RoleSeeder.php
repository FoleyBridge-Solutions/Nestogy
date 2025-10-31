<?php

namespace Database\Seeders;

use App\Domains\Core\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Role Seeder...');

        // Note: RolesAndPermissionsSeeder likely handles this
        
        $existingCount = Role::count();
        
        if ($existingCount > 0) {
            $this->command->info("Roles already seeded by RolesAndPermissionsSeeder ($existingCount found)");
        } else {
            // Create basic roles if RolesAndPermissionsSeeder didn't run
            Role::factory()
                ->count(10)
                ->create();
        }

        $this->command->info('Role Seeder completed!');
    }
}
