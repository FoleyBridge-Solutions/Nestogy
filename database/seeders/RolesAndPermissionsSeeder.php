<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create basic permissions
        $permissions = [
            ['name' => 'View Clients', 'slug' => 'clients.view', 'domain' => 'clients', 'action' => 'view', 'description' => 'View client information'],
            ['name' => 'Manage Clients', 'slug' => 'clients.manage', 'domain' => 'clients', 'action' => 'manage', 'description' => 'Create, edit, delete clients'],
            ['name' => 'View Tickets', 'slug' => 'tickets.view', 'domain' => 'tickets', 'action' => 'view', 'description' => 'View ticket information'],
            ['name' => 'Manage Tickets', 'slug' => 'tickets.manage', 'domain' => 'tickets', 'action' => 'manage', 'description' => 'Create, edit, delete tickets'],
            ['name' => 'View Assets', 'slug' => 'assets.view', 'domain' => 'assets', 'action' => 'view', 'description' => 'View asset information'],
            ['name' => 'Manage Assets', 'slug' => 'assets.manage', 'domain' => 'assets', 'action' => 'manage', 'description' => 'Create, edit, delete assets'],
            ['name' => 'View Financial', 'slug' => 'financial.view', 'domain' => 'financial', 'action' => 'view', 'description' => 'View financial information'],
            ['name' => 'Manage Financial', 'slug' => 'financial.manage', 'domain' => 'financial', 'action' => 'manage', 'description' => 'Manage invoices and payments'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'domain' => 'reports', 'action' => 'view', 'description' => 'View reports and analytics'],
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'domain' => 'users', 'action' => 'manage', 'description' => 'Manage system users'],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'domain' => 'settings', 'action' => 'manage', 'description' => 'Manage system settings'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore(array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create basic roles
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full system access', 'level' => 3, 'is_system' => true],
            ['name' => 'Technician', 'slug' => 'tech', 'description' => 'Technical support staff', 'level' => 2, 'is_system' => true],
            ['name' => 'Accountant', 'slug' => 'accountant', 'description' => 'Financial management staff', 'level' => 1, 'is_system' => true],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore(array_merge($role, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}