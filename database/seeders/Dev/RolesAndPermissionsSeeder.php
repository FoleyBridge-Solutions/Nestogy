<?php

namespace Database\Seeders\Dev;

use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating roles and permissions with Bouncer...');

        // Clear Bouncer cache to ensure fresh start
        Bouncer::refresh();

        // Create abilities (permissions)
        $this->createAbilities();

        // Create roles
        $this->createRoles();

        // Assign abilities to roles
        $this->assignAbilitiesToRoles();

        $this->command->info('Roles and permissions created successfully.');
    }

    /**
     * Create Bouncer abilities (permissions)
     */
    private function createAbilities(): void
    {
        $this->command->info('  Creating abilities...');

        $abilities = [
            // Client management
            'clients.*' => 'Full client management access',
            'clients.view' => 'View clients',
            'clients.create' => 'Create clients',
            'clients.edit' => 'Edit clients',
            'clients.delete' => 'Delete clients',

            // Asset management
            'assets.*' => 'Full asset management access',
            'assets.view' => 'View assets',
            'assets.create' => 'Create assets',
            'assets.edit' => 'Edit assets',
            'assets.delete' => 'Delete assets',

            // Ticket management
            'tickets.*' => 'Full ticket management access',
            'tickets.view' => 'View tickets',
            'tickets.create' => 'Create tickets',
            'tickets.edit' => 'Edit tickets',
            'tickets.delete' => 'Delete tickets',
            'tickets.assign' => 'Assign tickets',
            'tickets.close' => 'Close tickets',

            // Financial management
            'financial.*' => 'Full financial management access',
            'financial.view' => 'View financial data',
            'financial.invoices.create' => 'Create invoices',
            'financial.invoices.edit' => 'Edit invoices',
            'financial.invoices.delete' => 'Delete invoices',
            'financial.invoices.send' => 'Send invoices',
            'financial.payments.create' => 'Record payments',
            'financial.payments.edit' => 'Edit payments',
            'financial.payments.delete' => 'Delete payments',
            'financial.expenses.create' => 'Create expenses',
            'financial.expenses.approve' => 'Approve expenses',
            'financial.reports' => 'View financial reports',

            // Contract management
            'contracts.*' => 'Full contract management access',
            'contracts.view' => 'View contracts',
            'contracts.create' => 'Create contracts',
            'contracts.edit' => 'Edit contracts',
            'contracts.delete' => 'Delete contracts',
            'contracts.approve' => 'Approve contracts',

            // Project management
            'projects.*' => 'Full project management access',
            'projects.view' => 'View projects',
            'projects.create' => 'Create projects',
            'projects.edit' => 'Edit projects',
            'projects.delete' => 'Delete projects',
            'projects.manage' => 'Manage project tasks',

            // Lead management
            'leads.*' => 'Full lead management access',
            'leads.view' => 'View leads',
            'leads.create' => 'Create leads',
            'leads.edit' => 'Edit leads',
            'leads.delete' => 'Delete leads',
            'leads.convert' => 'Convert leads to clients',

            // Marketing
            'marketing.*' => 'Full marketing access',
            'marketing.campaigns' => 'Manage marketing campaigns',
            'marketing.emails' => 'Send marketing emails',
            'marketing.analytics' => 'View marketing analytics',

            // Knowledge Base
            'knowledge.*' => 'Full knowledge base access',
            'knowledge.view' => 'View knowledge base',
            'knowledge.create' => 'Create KB articles',
            'knowledge.edit' => 'Edit KB articles',
            'knowledge.delete' => 'Delete KB articles',

            // Reports
            'reports.*' => 'Full reports access',
            'reports.view' => 'View reports',
            'reports.create' => 'Create custom reports',
            'reports.export' => 'Export reports',

            // User management
            'users.*' => 'Full user management access',
            'users.view' => 'View users',
            'users.create' => 'Create users',
            'users.edit' => 'Edit users',
            'users.delete' => 'Delete users',
            'users.roles' => 'Manage user roles',

            // Settings
            'settings.*' => 'Full settings access',
            'settings.view' => 'View settings',
            'settings.edit' => 'Edit settings',
            'settings.company' => 'Manage company settings',
            'settings.integrations' => 'Manage integrations',

            // System administration
            'system.*' => 'Full system administration',
            'system.backup' => 'Manage backups',
            'system.logs' => 'View system logs',
            'system.audit' => 'View audit logs',
            'system.maintenance' => 'Perform system maintenance',
        ];

        foreach ($abilities as $name => $title) {
            Bouncer::ability()->firstOrCreate(['name' => $name], ['title' => $title]);
        }

        $this->command->info('  ✓ Abilities created');
    }

    /**
     * Create roles
     */
    private function createRoles(): void
    {
        $this->command->info('  Creating roles...');

        $roles = [
            'super-admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'tech' => 'Technician',
            'accountant' => 'Accountant',
            'sales' => 'Sales Representative',
            'marketing' => 'Marketing Specialist',
            'client' => 'Client User',
        ];

        foreach ($roles as $name => $title) {
            Bouncer::role()->firstOrCreate(['name' => $name], ['title' => $title]);
        }

        $this->command->info('  ✓ Roles created');
    }

    /**
     * Assign abilities to roles
     */
    private function assignAbilitiesToRoles(): void
    {
        $this->command->info('  Assigning abilities to roles...');

        // Super Admin - Gets everything
        $superAdmin = Bouncer::role()->where('name', 'super-admin')->first();
        Bouncer::allow($superAdmin)->everything();

        // Admin - Gets everything except system administration
        $admin = Bouncer::role()->where('name', 'admin')->first();
        Bouncer::allow($admin)->to([
            'clients.*',
            'assets.*',
            'tickets.*',
            'financial.*',
            'contracts.*',
            'projects.*',
            'leads.*',
            'marketing.*',
            'knowledge.*',
            'reports.*',
            'users.*',
            'settings.*',
        ]);

        // Technician - Technical work focused
        $tech = Bouncer::role()->where('name', 'tech')->first();
        Bouncer::allow($tech)->to([
            'clients.view',
            'assets.*',
            'tickets.*',
            'projects.view',
            'projects.manage',
            'knowledge.view',
            'knowledge.create',
            'knowledge.edit',
            'reports.view',
            'reports.tickets',
            'reports.assets',
        ]);

        // Accountant - Financial focused
        $accountant = Bouncer::role()->where('name', 'accountant')->first();
        Bouncer::allow($accountant)->to([
            'clients.view',
            'financial.*',
            'contracts.view',
            'contracts.financials',
            'reports.view',
            'reports.financial',
            'reports.export',
        ]);

        // Sales - Sales and lead focused
        $sales = Bouncer::role()->where('name', 'sales')->first();
        Bouncer::allow($sales)->to([
            'clients.view',
            'clients.create',
            'clients.edit',
            'leads.*',
            'contracts.view',
            'contracts.create',
            'projects.view',
            'financial.quotes.create',
            'financial.quotes.edit',
            'financial.quotes.send',
            'reports.view',
            'reports.clients',
        ]);

        // Marketing - Marketing focused
        $marketing = Bouncer::role()->where('name', 'marketing')->first();
        Bouncer::allow($marketing)->to([
            'clients.view',
            'leads.view',
            'leads.create',
            'leads.edit',
            'marketing.*',
            'knowledge.view',
            'knowledge.create',
            'knowledge.edit',
            'reports.view',
        ]);

        // Client - Limited client portal access
        $client = Bouncer::role()->where('name', 'client')->first();
        Bouncer::allow($client)->to([
            'tickets.view',
            'tickets.create',
            'assets.view',
            'financial.invoices.view',
            'contracts.view',
            'knowledge.view',
        ]);

        $this->command->info('  ✓ Abilities assigned to roles');
    }
}
