<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RemoteControlPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Adds Remote Control permissions for asset management
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating Remote Control Permissions...');

        // Define remote control permissions
        $permissions = [
            [
                'name' => 'assets.remote.view',
                'title' => 'View Remote Asset Info',
                'description' => 'View processes and services on remote assets',
            ],
            [
                'name' => 'assets.remote.execute',
                'title' => 'Execute Remote Commands',
                'description' => 'Execute commands and control services on remote assets',
            ],
            [
                'name' => 'assets.remote.terminal',
                'title' => 'Use Remote Terminal',
                'description' => 'Access PowerShell/CMD terminal on remote assets',
            ],
            [
                'name' => 'assets.remote.reboot',
                'title' => 'Reboot Remote Assets',
                'description' => 'Reboot remote devices',
            ],
        ];

        foreach ($permissions as $permission) {
            // Create ability using Bouncer
            Bouncer::ability()->firstOrCreate(
                ['name' => $permission['name']],
                [
                    'title' => $permission['title'],
                    'entity_type' => null,
                    'only_owned' => false,
                ]
            );

            $this->command->info("✓ Created: {$permission['name']} - {$permission['title']}");
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('✅ Remote Control Permissions created successfully!');
    }

    /**
     * Assign remote control permissions to default roles
     */
    protected function assignPermissionsToRoles(): void
    {
        $this->command->info('📋 Assigning permissions to roles...');

        // Super Admin gets all permissions
        if (Bouncer::role()->where('name', 'super-admin')->exists()) {
            Bouncer::allow('super-admin')->to([
                'assets.remote.view',
                'assets.remote.execute',
                'assets.remote.terminal',
                'assets.remote.reboot',
            ]);
            $this->command->info('✓ Assigned all remote control permissions to: super-admin');
        }

        // Admin gets all permissions
        if (Bouncer::role()->where('name', 'admin')->exists()) {
            Bouncer::allow('admin')->to([
                'assets.remote.view',
                'assets.remote.execute',
                'assets.remote.terminal',
                'assets.remote.reboot',
            ]);
            $this->command->info('✓ Assigned all remote control permissions to: admin');
        }

        // Tech/Engineer gets view and execute (no terminal or reboot)
        if (Bouncer::role()->where('name', 'tech')->exists()) {
            Bouncer::allow('tech')->to([
                'assets.remote.view',
                'assets.remote.execute',
            ]);
            $this->command->info('✓ Assigned limited remote control permissions to: tech');
        }

        // Manager gets view only
        if (Bouncer::role()->where('name', 'manager')->exists()) {
            Bouncer::allow('manager')->to('assets.remote.view');
            $this->command->info('✓ Assigned view-only remote control permission to: manager');
        }

        // User gets view only
        if (Bouncer::role()->where('name', 'user')->exists()) {
            Bouncer::allow('user')->to('assets.remote.view');
            $this->command->info('✓ Assigned view-only remote control permission to: user');
        }
    }
}
