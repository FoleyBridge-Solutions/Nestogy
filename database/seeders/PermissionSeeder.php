<?php

namespace Database\Seeders;

use App\Domains\Core\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Permission Seeder...');

        // Note: RolesAndPermissionsSeeder likely handles this
        // Create additional granular permissions if needed
        
        $existingCount = Permission::count();
        
        if ($existingCount > 0) {
            $this->command->info("Permissions already seeded by RolesAndPermissionsSeeder ($existingCount found)");
        } else {
            // Create basic permissions if RolesAndPermissionsSeeder didn't run
            Permission::factory()
                ->count(50)
                ->create();
        }

        $this->command->info('Permission Seeder completed!');
    }
}
