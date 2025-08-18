<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear Bouncer cache to ensure fresh start
        Bouncer::refresh();
        
        // Create Bouncer abilities (permissions)
        $this->createAbilities();
        
        // Create Bouncer roles 
        $this->createRoles();
        
        // Assign abilities to roles
        $this->assignAbilitiesToRoles();
        
        // Assign roles to users with company scoping
        $this->assignRolesToUsers();
    }

    /**
     * Create Bouncer abilities (permissions)
     */
    private function createAbilities(): void
    {
        $abilities = [
            // Client permissions
            'clients.view' => 'View client information',
            'clients.create' => 'Create new clients',
            'clients.edit' => 'Edit client information',
            'clients.delete' => 'Delete clients',
            'clients.manage' => 'Full client management',
            'clients.export' => 'Export client data',
            'clients.import' => 'Import client data',
            
            // Client sub-module permissions
            'clients.contacts.view' => 'View client contacts',
            'clients.contacts.manage' => 'Manage client contacts',
            'clients.contacts.export' => 'Export client contacts',
            'clients.locations.view' => 'View client locations',
            'clients.locations.manage' => 'Manage client locations',
            'clients.locations.export' => 'Export client locations',
            'clients.documents.view' => 'View client documents',
            'clients.documents.manage' => 'Manage client documents',
            'clients.documents.export' => 'Export client documents',
            'clients.files.view' => 'View client files',
            'clients.files.manage' => 'Manage client files',
            'clients.files.export' => 'Export client files',
            'clients.licenses.view' => 'View client licenses',
            'clients.licenses.manage' => 'Manage client licenses',
            'clients.licenses.export' => 'Export client licenses',
            'clients.credentials.view' => 'View client credentials',
            'clients.credentials.manage' => 'Manage client credentials',
            'clients.credentials.export' => 'Export client credentials',
            'clients.networks.view' => 'View client networks',
            'clients.networks.manage' => 'Manage client networks',
            'clients.networks.export' => 'Export client networks',
            'clients.services.view' => 'View client services',
            'clients.services.manage' => 'Manage client services',
            'clients.services.export' => 'Export client services',
            'clients.vendors.view' => 'View client vendors',
            'clients.vendors.manage' => 'Manage client vendors',
            'clients.vendors.export' => 'Export client vendors',
            'clients.racks.view' => 'View client racks',
            'clients.racks.manage' => 'Manage client racks',
            'clients.racks.export' => 'Export client racks',
            'clients.certificates.view' => 'View client certificates',
            'clients.certificates.manage' => 'Manage client certificates',
            'clients.certificates.export' => 'Export client certificates',
            'clients.domains.view' => 'View client domains',
            'clients.domains.manage' => 'Manage client domains',
            'clients.domains.export' => 'Export client domains',
            'clients.calendar-events.view' => 'View client calendar events',
            'clients.calendar-events.manage' => 'Manage client calendar events',
            'clients.calendar-events.export' => 'Export client calendar events',
            'clients.quotes.view' => 'View client quotes',
            'clients.quotes.manage' => 'Manage client quotes',
            'clients.quotes.export' => 'Export client quotes',
            'clients.trips.view' => 'View client trips',
            'clients.trips.manage' => 'Manage client trips',
            'clients.trips.export' => 'Export client trips',
            
            // Ticket permissions
            'tickets.view' => 'View ticket information',
            'tickets.create' => 'Create new tickets',
            'tickets.edit' => 'Edit tickets',
            'tickets.delete' => 'Delete tickets',
            'tickets.manage' => 'Full ticket management',
            'tickets.export' => 'Export ticket data',
            
            // Asset permissions
            'assets.view' => 'View asset information',
            'assets.create' => 'Create new assets',
            'assets.edit' => 'Edit assets',
            'assets.delete' => 'Delete assets',
            'assets.manage' => 'Full asset management',
            'assets.export' => 'Export asset data',
            'assets.maintenance.view' => 'View asset maintenance',
            'assets.maintenance.manage' => 'Manage asset maintenance',
            'assets.maintenance.export' => 'Export asset maintenance',
            'assets.warranties.view' => 'View asset warranties',
            'assets.warranties.manage' => 'Manage asset warranties',
            'assets.warranties.export' => 'Export asset warranties',
            'assets.depreciations.view' => 'View asset depreciation',
            'assets.depreciations.manage' => 'Manage asset depreciation',
            'assets.depreciations.export' => 'Export asset depreciation',
            
            // Financial permissions
            'financial.view' => 'View financial information',
            'financial.create' => 'Create financial records',
            'financial.edit' => 'Edit financial records',
            'financial.delete' => 'Delete financial records',
            'financial.manage' => 'Manage invoices and payments',
            'financial.export' => 'Export financial data',
            'financial.payments.view' => 'View payments',
            'financial.payments.manage' => 'Manage payments',
            'financial.payments.export' => 'Export payments',
            'financial.expenses.view' => 'View expenses',
            'financial.expenses.manage' => 'Manage expenses',
            'financial.expenses.export' => 'Export expenses',
            'financial.expenses.approve' => 'Approve expenses',
            'financial.invoices.view' => 'View invoices',
            'financial.invoices.manage' => 'Manage invoices',
            'financial.invoices.export' => 'Export invoices',
            'financial.quotes.view' => 'View financial quotes',
            'financial.quotes.create' => 'Create financial quotes',
            'financial.quotes.edit' => 'Edit financial quotes',
            'financial.quotes.delete' => 'Delete financial quotes',
            'financial.quotes.manage' => 'Manage financial quotes',
            'financial.quotes.export' => 'Export financial quotes',
            'financial.quotes.approve' => 'Approve financial quotes',
            'financial.quotes.send' => 'Send financial quotes',
            'financial.quotes.convert' => 'Convert financial quotes',
            'financial.quotes.cancel' => 'Cancel financial quotes',
            
            // Contract permissions
            'contracts.view' => 'View contracts',
            'contracts.create' => 'Create contracts',
            'contracts.edit' => 'Edit contracts',
            'contracts.delete' => 'Delete contracts',
            'contracts.approve' => 'Approve contracts',
            'contracts.signature' => 'Manage contract signatures',
            'contracts.activate' => 'Activate contracts',
            'contracts.terminate' => 'Terminate contracts',
            'contracts.suspend' => 'Suspend contracts',
            'contracts.amend' => 'Amend contracts',
            'contracts.renew' => 'Renew contracts',
            'contracts.financials' => 'View contract financials',
            'contracts.export' => 'Export contracts',
            'contracts.import' => 'Import contracts',
            'contracts.analytics' => 'View contract analytics',
            'contracts.bulk-actions' => 'Perform bulk contract actions',
            'contracts.history' => 'View contract history',
            'contracts.milestones' => 'Manage contract milestones',
            
            // Project permissions
            'projects.view' => 'View projects',
            'projects.create' => 'Create new projects',
            'projects.edit' => 'Edit projects',
            'projects.delete' => 'Delete projects',
            'projects.manage' => 'Full project management',
            'projects.export' => 'Export project data',
            'projects.tasks.view' => 'View project tasks',
            'projects.tasks.manage' => 'Manage project tasks',
            'projects.tasks.export' => 'Export project tasks',
            'projects.members.view' => 'View project members',
            'projects.members.manage' => 'Manage project members',
            'projects.templates.view' => 'View project templates',
            'projects.templates.manage' => 'Manage project templates',
            
            // Report permissions
            'reports.view' => 'View reports and analytics',
            'reports.create' => 'Create custom reports',
            'reports.export' => 'Export report data',
            'reports.financial' => 'View financial reports',
            'reports.tickets' => 'View ticket reports',
            'reports.assets' => 'View asset reports',
            'reports.clients' => 'View client reports',
            'reports.projects' => 'View project reports',
            'reports.users' => 'View user reports',
            
            // User permissions
            'users.view' => 'View user information',
            'users.create' => 'Create new users',
            'users.edit' => 'Edit users',
            'users.delete' => 'Delete users',
            'users.manage' => 'Manage system users',
            'users.export' => 'Export user data',
            
            // Settings permissions
            'settings.view' => 'View system settings',
            'settings.manage' => 'Manage system settings',
            'system.settings.view' => 'View system settings',
            'system.settings.manage' => 'Manage system settings',
            'system.logs.view' => 'View system logs',
            'system.backups.manage' => 'Manage backups',
            'system.permissions.manage' => 'Manage roles & permissions',
        ];

        foreach ($abilities as $ability => $title) {
            Bouncer::ability()->firstOrCreate(
                ['name' => $ability],
                ['title' => $title]
            );
        }

        echo "Created " . count($abilities) . " abilities.\n";
    }

    /**
     * Create Bouncer roles
     */
    private function createRoles(): void
    {
        $roles = [
            'super-admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'tech' => 'Technician', 
            'accountant' => 'Accountant',
        ];

        foreach ($roles as $role => $title) {
            Bouncer::role()->firstOrCreate(
                ['name' => $role],
                ['title' => $title]
            );
        }

        echo "Created " . count($roles) . " roles.\n";
    }

    /**
     * Assign abilities to roles
     */
    private function assignAbilitiesToRoles(): void
    {
        // Super Administrator gets ALL abilities (every single one)
        $superAdminAbilities = ['*']; // This will assign every ability
        
        // Administrator gets all abilities
        $adminAbilities = [
            'clients.*', 'tickets.*', 'assets.*', 'financial.*', 'projects.*', 
            'reports.*', 'users.*', 'settings.*', 'system.*'
        ];

        // Technician abilities (all except sensitive financial/user management)
        $techAbilities = [
            'clients.view', 'clients.create', 'clients.edit', 'clients.manage', 'clients.export',
            'clients.contacts.*', 'clients.locations.*', 'clients.documents.*', 'clients.files.*',
            'clients.licenses.*', 'clients.credentials.*', 'clients.networks.*', 'clients.services.*',
            'clients.vendors.*', 'clients.racks.*', 'clients.certificates.*', 'clients.domains.*',
            'tickets.*', 'assets.*', 'projects.*',
            'reports.view', 'reports.tickets', 'reports.assets', 'reports.projects', 'reports.export',
            'financial.view', 'financial.payments.view', 'financial.invoices.view'
        ];

        // Accountant abilities (financial focus)
        $accountantAbilities = [
            'clients.view', 'clients.create', 'clients.edit', 'clients.export',
            'clients.contacts.view', 'clients.contacts.manage',
            'clients.locations.view', 'clients.locations.manage',
            'clients.documents.view', 'clients.files.view',
            'clients.quotes.*', 'clients.calendar-events.*',
            'financial.*', 'reports.view', 'reports.financial', 'reports.clients', 'reports.export',
            'projects.view', 'projects.tasks.view'
        ];

        // Assign ALL abilities to super-admin role
        $allAbilities = Bouncer::ability()->get();
        foreach ($allAbilities as $ability) {
            Bouncer::allow('super-admin')->to($ability->name);
        }

        // Assign abilities to admin role
        foreach ($adminAbilities as $ability) {
            if (str_contains($ability, '*')) {
                // Handle wildcard permissions
                $prefix = str_replace('*', '', $ability);
                $matchingAbilities = Bouncer::ability()->where('name', 'like', $prefix . '%')->get();
                foreach ($matchingAbilities as $matchingAbility) {
                    Bouncer::allow('admin')->to($matchingAbility->name);
                }
            } else {
                Bouncer::allow('admin')->to($ability);
            }
        }

        // Assign abilities to tech role
        foreach ($techAbilities as $ability) {
            if (str_contains($ability, '*')) {
                $prefix = str_replace('*', '', $ability);
                $matchingAbilities = Bouncer::ability()->where('name', 'like', $prefix . '%')->get();
                foreach ($matchingAbilities as $matchingAbility) {
                    Bouncer::allow('tech')->to($matchingAbility->name);
                }
            } else {
                Bouncer::allow('tech')->to($ability);
            }
        }

        // Assign abilities to accountant role
        foreach ($accountantAbilities as $ability) {
            if (str_contains($ability, '*')) {
                $prefix = str_replace('*', '', $ability);
                $matchingAbilities = Bouncer::ability()->where('name', 'like', $prefix . '%')->get();
                foreach ($matchingAbilities as $matchingAbility) {
                    Bouncer::allow('accountant')->to($matchingAbility->name);
                }
            } else {
                Bouncer::allow('accountant')->to($ability);
            }
        }

        echo "Assigned abilities to roles.\n";
    }

    /**
     * Assign roles to users with company scoping
     */
    private function assignRolesToUsers(): void
    {
        // Get users and assign roles with company scoping
        $userRoleAssignments = [
            1 => ['role' => 'super-admin', 'company_id' => 1], // Super Administrator (has EVERY permission)
            2 => ['role' => 'tech', 'company_id' => 1],         // Technical Manager  
            3 => ['role' => 'accountant', 'company_id' => 1],   // Accountant User
            4 => ['role' => 'tech', 'company_id' => 1],         // Test User
        ];

        foreach ($userRoleAssignments as $userId => $assignment) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                // Set Bouncer scope to the user's company
                Bouncer::scope()->to($assignment['company_id']);
                
                // Assign the role
                Bouncer::assign($assignment['role'])->to($user);
                
                echo "Assigned {$assignment['role']} role to user {$userId} in company {$assignment['company_id']}.\n";
            }
        }

        // Clear scope after assignments
        Bouncer::scope()->to(null);
        
        echo "Assigned roles to users with company scoping.\n";
    }
}