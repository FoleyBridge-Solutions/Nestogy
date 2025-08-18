<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permission groups
        $this->createPermissionGroups();
        
        // Create permissions
        $this->createPermissions();
        
        // Create roles
        $this->createRoles();
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Create permission groups for organization.
     */
    private function createPermissionGroups(): void
    {
        $groups = [
            ['name' => 'Client Management', 'slug' => 'client-management', 'sort_order' => 10],
            ['name' => 'Asset Management', 'slug' => 'asset-management', 'sort_order' => 20],
            ['name' => 'Financial Management', 'slug' => 'financial-management', 'sort_order' => 30],
            ['name' => 'Project Management', 'slug' => 'project-management', 'sort_order' => 40],
            ['name' => 'Ticket System', 'slug' => 'ticket-system', 'sort_order' => 50],
            ['name' => 'Reports & Analytics', 'slug' => 'reports-analytics', 'sort_order' => 60],
            ['name' => 'User Management', 'slug' => 'user-management', 'sort_order' => 70],
            ['name' => 'System Administration', 'slug' => 'system-administration', 'sort_order' => 80],
        ];

        foreach ($groups as $group) {
            PermissionGroup::firstOrCreate(
                ['slug' => $group['slug']],
                $group
            );
        }
    }

    /**
     * Create all permissions.
     */
    private function createPermissions(): void
    {
        $permissions = $this->getPermissionDefinitions();

        foreach ($permissions as $domain => $domainPermissions) {
            foreach ($domainPermissions as $permission) {
                $group = PermissionGroup::where('slug', $permission['group'])->first();
                
                Permission::firstOrCreate(
                    ['slug' => $permission['slug']],
                    [
                        'name' => $permission['name'],
                        'domain' => $domain,
                        'action' => $permission['action'],
                        'description' => $permission['description'] ?? null,
                        'is_system' => $permission['is_system'] ?? true,
                        'group_id' => $group?->id,
                    ]
                );
            }
        }
    }

    /**
     * Get all permission definitions.
     */
    private function getPermissionDefinitions(): array
    {
        return [
            Permission::DOMAIN_CLIENTS => [
                ['name' => 'View Clients', 'slug' => 'clients.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Create Clients', 'slug' => 'clients.create', 'action' => 'create', 'group' => 'client-management'],
                ['name' => 'Edit Clients', 'slug' => 'clients.edit', 'action' => 'edit', 'group' => 'client-management'],
                ['name' => 'Delete Clients', 'slug' => 'clients.delete', 'action' => 'delete', 'group' => 'client-management'],
                ['name' => 'Manage Clients', 'slug' => 'clients.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Clients', 'slug' => 'clients.export', 'action' => 'export', 'group' => 'client-management'],
                ['name' => 'Import Clients', 'slug' => 'clients.import', 'action' => 'import', 'group' => 'client-management'],
                
                // Client sub-modules
                ['name' => 'View Client Contacts', 'slug' => 'clients.contacts.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Contacts', 'slug' => 'clients.contacts.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Contacts', 'slug' => 'clients.contacts.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Locations', 'slug' => 'clients.locations.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Locations', 'slug' => 'clients.locations.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Locations', 'slug' => 'clients.locations.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Documents', 'slug' => 'clients.documents.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Documents', 'slug' => 'clients.documents.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Documents', 'slug' => 'clients.documents.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Files', 'slug' => 'clients.files.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Files', 'slug' => 'clients.files.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Files', 'slug' => 'clients.files.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Licenses', 'slug' => 'clients.licenses.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Licenses', 'slug' => 'clients.licenses.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Licenses', 'slug' => 'clients.licenses.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Credentials', 'slug' => 'clients.credentials.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Credentials', 'slug' => 'clients.credentials.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Credentials', 'slug' => 'clients.credentials.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Networks', 'slug' => 'clients.networks.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Networks', 'slug' => 'clients.networks.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Networks', 'slug' => 'clients.networks.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Services', 'slug' => 'clients.services.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Services', 'slug' => 'clients.services.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Services', 'slug' => 'clients.services.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Vendors', 'slug' => 'clients.vendors.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Vendors', 'slug' => 'clients.vendors.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Vendors', 'slug' => 'clients.vendors.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Racks', 'slug' => 'clients.racks.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Racks', 'slug' => 'clients.racks.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Racks', 'slug' => 'clients.racks.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Certificates', 'slug' => 'clients.certificates.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Certificates', 'slug' => 'clients.certificates.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Certificates', 'slug' => 'clients.certificates.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Domains', 'slug' => 'clients.domains.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Domains', 'slug' => 'clients.domains.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Domains', 'slug' => 'clients.domains.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Calendar Events', 'slug' => 'clients.calendar-events.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Calendar Events', 'slug' => 'clients.calendar-events.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Calendar Events', 'slug' => 'clients.calendar-events.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Quotes', 'slug' => 'clients.quotes.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Quotes', 'slug' => 'clients.quotes.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Quotes', 'slug' => 'clients.quotes.export', 'action' => 'export', 'group' => 'client-management'],
                
                ['name' => 'View Client Trips', 'slug' => 'clients.trips.view', 'action' => 'view', 'group' => 'client-management'],
                ['name' => 'Manage Client Trips', 'slug' => 'clients.trips.manage', 'action' => 'manage', 'group' => 'client-management'],
                ['name' => 'Export Client Trips', 'slug' => 'clients.trips.export', 'action' => 'export', 'group' => 'client-management'],
            ],

            Permission::DOMAIN_ASSETS => [
                ['name' => 'View Assets', 'slug' => 'assets.view', 'action' => 'view', 'group' => 'asset-management'],
                ['name' => 'Create Assets', 'slug' => 'assets.create', 'action' => 'create', 'group' => 'asset-management'],
                ['name' => 'Edit Assets', 'slug' => 'assets.edit', 'action' => 'edit', 'group' => 'asset-management'],
                ['name' => 'Delete Assets', 'slug' => 'assets.delete', 'action' => 'delete', 'group' => 'asset-management'],
                ['name' => 'Manage Assets', 'slug' => 'assets.manage', 'action' => 'manage', 'group' => 'asset-management'],
                ['name' => 'Export Assets', 'slug' => 'assets.export', 'action' => 'export', 'group' => 'asset-management'],
                
                ['name' => 'View Asset Maintenance', 'slug' => 'assets.maintenance.view', 'action' => 'view', 'group' => 'asset-management'],
                ['name' => 'Manage Asset Maintenance', 'slug' => 'assets.maintenance.manage', 'action' => 'manage', 'group' => 'asset-management'],
                ['name' => 'Export Asset Maintenance', 'slug' => 'assets.maintenance.export', 'action' => 'export', 'group' => 'asset-management'],
                
                ['name' => 'View Asset Warranties', 'slug' => 'assets.warranties.view', 'action' => 'view', 'group' => 'asset-management'],
                ['name' => 'Manage Asset Warranties', 'slug' => 'assets.warranties.manage', 'action' => 'manage', 'group' => 'asset-management'],
                ['name' => 'Export Asset Warranties', 'slug' => 'assets.warranties.export', 'action' => 'export', 'group' => 'asset-management'],
                
                ['name' => 'View Asset Depreciation', 'slug' => 'assets.depreciations.view', 'action' => 'view', 'group' => 'asset-management'],
                ['name' => 'Manage Asset Depreciation', 'slug' => 'assets.depreciations.manage', 'action' => 'manage', 'group' => 'asset-management'],
                ['name' => 'Export Asset Depreciation', 'slug' => 'assets.depreciations.export', 'action' => 'export', 'group' => 'asset-management'],
            ],

            Permission::DOMAIN_FINANCIAL => [
                ['name' => 'View Financial Data', 'slug' => 'financial.view', 'action' => 'view', 'group' => 'financial-management'],
                ['name' => 'Create Financial Records', 'slug' => 'financial.create', 'action' => 'create', 'group' => 'financial-management'],
                ['name' => 'Edit Financial Records', 'slug' => 'financial.edit', 'action' => 'edit', 'group' => 'financial-management'],
                ['name' => 'Delete Financial Records', 'slug' => 'financial.delete', 'action' => 'delete', 'group' => 'financial-management'],
                ['name' => 'Manage Financial Data', 'slug' => 'financial.manage', 'action' => 'manage', 'group' => 'financial-management'],
                ['name' => 'Export Financial Data', 'slug' => 'financial.export', 'action' => 'export', 'group' => 'financial-management'],
                
                ['name' => 'View Payments', 'slug' => 'financial.payments.view', 'action' => 'view', 'group' => 'financial-management'],
                ['name' => 'Manage Payments', 'slug' => 'financial.payments.manage', 'action' => 'manage', 'group' => 'financial-management'],
                ['name' => 'Export Payments', 'slug' => 'financial.payments.export', 'action' => 'export', 'group' => 'financial-management'],
                
                ['name' => 'View Expenses', 'slug' => 'financial.expenses.view', 'action' => 'view', 'group' => 'financial-management'],
                ['name' => 'Manage Expenses', 'slug' => 'financial.expenses.manage', 'action' => 'manage', 'group' => 'financial-management'],
                ['name' => 'Export Expenses', 'slug' => 'financial.expenses.export', 'action' => 'export', 'group' => 'financial-management'],
                ['name' => 'Approve Expenses', 'slug' => 'financial.expenses.approve', 'action' => 'approve', 'group' => 'financial-management'],
                
                ['name' => 'View Invoices', 'slug' => 'financial.invoices.view', 'action' => 'view', 'group' => 'financial-management'],
                ['name' => 'Manage Invoices', 'slug' => 'financial.invoices.manage', 'action' => 'manage', 'group' => 'financial-management'],
                ['name' => 'Export Invoices', 'slug' => 'financial.invoices.export', 'action' => 'export', 'group' => 'financial-management'],
            ],

            Permission::DOMAIN_PROJECTS => [
                ['name' => 'View Projects', 'slug' => 'projects.view', 'action' => 'view', 'group' => 'project-management'],
                ['name' => 'Create Projects', 'slug' => 'projects.create', 'action' => 'create', 'group' => 'project-management'],
                ['name' => 'Edit Projects', 'slug' => 'projects.edit', 'action' => 'edit', 'group' => 'project-management'],
                ['name' => 'Delete Projects', 'slug' => 'projects.delete', 'action' => 'delete', 'group' => 'project-management'],
                ['name' => 'Manage Projects', 'slug' => 'projects.manage', 'action' => 'manage', 'group' => 'project-management'],
                ['name' => 'Export Projects', 'slug' => 'projects.export', 'action' => 'export', 'group' => 'project-management'],
                
                ['name' => 'View Project Tasks', 'slug' => 'projects.tasks.view', 'action' => 'view', 'group' => 'project-management'],
                ['name' => 'Manage Project Tasks', 'slug' => 'projects.tasks.manage', 'action' => 'manage', 'group' => 'project-management'],
                ['name' => 'Export Project Tasks', 'slug' => 'projects.tasks.export', 'action' => 'export', 'group' => 'project-management'],
                
                ['name' => 'View Project Members', 'slug' => 'projects.members.view', 'action' => 'view', 'group' => 'project-management'],
                ['name' => 'Manage Project Members', 'slug' => 'projects.members.manage', 'action' => 'manage', 'group' => 'project-management'],
                
                ['name' => 'View Project Templates', 'slug' => 'projects.templates.view', 'action' => 'view', 'group' => 'project-management'],
                ['name' => 'Manage Project Templates', 'slug' => 'projects.templates.manage', 'action' => 'manage', 'group' => 'project-management'],
            ],

            Permission::DOMAIN_TICKETS => [
                ['name' => 'View Tickets', 'slug' => 'tickets.view', 'action' => 'view', 'group' => 'ticket-system'],
                ['name' => 'Create Tickets', 'slug' => 'tickets.create', 'action' => 'create', 'group' => 'ticket-system'],
                ['name' => 'Edit Tickets', 'slug' => 'tickets.edit', 'action' => 'edit', 'group' => 'ticket-system'],
                ['name' => 'Delete Tickets', 'slug' => 'tickets.delete', 'action' => 'delete', 'group' => 'ticket-system'],
                ['name' => 'Manage Tickets', 'slug' => 'tickets.manage', 'action' => 'manage', 'group' => 'ticket-system'],
                ['name' => 'Export Tickets', 'slug' => 'tickets.export', 'action' => 'export', 'group' => 'ticket-system'],
            ],

            Permission::DOMAIN_REPORTS => [
                ['name' => 'View Reports', 'slug' => 'reports.view', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'View Financial Reports', 'slug' => 'reports.financial', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'View Ticket Reports', 'slug' => 'reports.tickets', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'View Asset Reports', 'slug' => 'reports.assets', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'View Client Reports', 'slug' => 'reports.clients', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'View Project Reports', 'slug' => 'reports.projects', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'View User Reports', 'slug' => 'reports.users', 'action' => 'view', 'group' => 'reports-analytics'],
                ['name' => 'Export Reports', 'slug' => 'reports.export', 'action' => 'export', 'group' => 'reports-analytics'],
            ],

            Permission::DOMAIN_USERS => [
                ['name' => 'View Users', 'slug' => 'users.view', 'action' => 'view', 'group' => 'user-management'],
                ['name' => 'Create Users', 'slug' => 'users.create', 'action' => 'create', 'group' => 'user-management'],
                ['name' => 'Edit Users', 'slug' => 'users.edit', 'action' => 'edit', 'group' => 'user-management'],
                ['name' => 'Delete Users', 'slug' => 'users.delete', 'action' => 'delete', 'group' => 'user-management'],
                ['name' => 'Manage Users', 'slug' => 'users.manage', 'action' => 'manage', 'group' => 'user-management'],
                ['name' => 'Export Users', 'slug' => 'users.export', 'action' => 'export', 'group' => 'user-management'],
            ],

            Permission::DOMAIN_SYSTEM => [
                ['name' => 'View System Settings', 'slug' => 'system.settings.view', 'action' => 'view', 'group' => 'system-administration'],
                ['name' => 'Manage System Settings', 'slug' => 'system.settings.manage', 'action' => 'manage', 'group' => 'system-administration'],
                ['name' => 'View System Logs', 'slug' => 'system.logs.view', 'action' => 'view', 'group' => 'system-administration'],
                ['name' => 'Manage Backups', 'slug' => 'system.backups.manage', 'action' => 'manage', 'group' => 'system-administration'],
                ['name' => 'Manage Roles & Permissions', 'slug' => 'system.permissions.manage', 'action' => 'manage', 'group' => 'system-administration'],
            ],
        ];
    }

    /**
     * Create default system roles.
     */
    private function createRoles(): void
    {
        $roles = [
            [
                'name' => 'Accountant',
                'slug' => Role::SLUG_ACCOUNTANT,
                'description' => 'Financial management and basic client operations',
                'level' => Role::LEVEL_ACCOUNTANT,
                'is_system' => true,
            ],
            [
                'name' => 'Technician',
                'slug' => Role::SLUG_TECHNICIAN,
                'description' => 'Technical operations, tickets, and asset management',
                'level' => Role::LEVEL_TECHNICIAN,
                'is_system' => true,
            ],
            [
                'name' => 'Administrator',
                'slug' => Role::SLUG_ADMIN,
                'description' => 'Full system access and management capabilities',
                'level' => Role::LEVEL_ADMIN,
                'is_system' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }

    /**
     * Assign permissions to roles.
     */
    private function assignPermissionsToRoles(): void
    {
        $accountantRole = Role::where('slug', Role::SLUG_ACCOUNTANT)->first();
        $technicianRole = Role::where('slug', Role::SLUG_TECHNICIAN)->first();
        $adminRole = Role::where('slug', Role::SLUG_ADMIN)->first();

        // Accountant permissions
        $accountantPermissions = [
            // Clients - view and basic operations
            'clients.view', 'clients.create', 'clients.edit', 'clients.export',
            'clients.contacts.view', 'clients.contacts.manage',
            'clients.locations.view', 'clients.locations.manage',
            'clients.documents.view', 'clients.files.view',
            'clients.quotes.view', 'clients.quotes.manage',
            'clients.calendar-events.view', 'clients.calendar-events.manage',
            
            // Financial - full access
            'financial.view', 'financial.create', 'financial.edit', 'financial.manage', 'financial.export',
            'financial.payments.view', 'financial.payments.manage', 'financial.payments.export',
            'financial.expenses.view', 'financial.expenses.manage', 'financial.expenses.export',
            'financial.invoices.view', 'financial.invoices.manage', 'financial.invoices.export',
            
            // Reports - financial focus
            'reports.view', 'reports.financial', 'reports.clients', 'reports.export',
            
            // Basic project view
            'projects.view', 'projects.tasks.view',
        ];

        // Technician permissions
        $technicianPermissions = [
            // Clients - full access except deletion
            'clients.view', 'clients.create', 'clients.edit', 'clients.manage', 'clients.export',
            'clients.contacts.view', 'clients.contacts.manage', 'clients.contacts.export',
            'clients.locations.view', 'clients.locations.manage', 'clients.locations.export',
            'clients.documents.view', 'clients.documents.manage', 'clients.documents.export',
            'clients.files.view', 'clients.files.manage', 'clients.files.export',
            'clients.licenses.view', 'clients.licenses.manage', 'clients.licenses.export',
            'clients.credentials.view', 'clients.credentials.manage', 'clients.credentials.export',
            'clients.networks.view', 'clients.networks.manage', 'clients.networks.export',
            'clients.services.view', 'clients.services.manage', 'clients.services.export',
            'clients.vendors.view', 'clients.vendors.manage', 'clients.vendors.export',
            'clients.racks.view', 'clients.racks.manage', 'clients.racks.export',
            'clients.certificates.view', 'clients.certificates.manage', 'clients.certificates.export',
            'clients.domains.view', 'clients.domains.manage', 'clients.domains.export',
            
            // Assets - full access
            'assets.view', 'assets.create', 'assets.edit', 'assets.manage', 'assets.export',
            'assets.maintenance.view', 'assets.maintenance.manage', 'assets.maintenance.export',
            'assets.warranties.view', 'assets.warranties.manage', 'assets.warranties.export',
            'assets.depreciations.view', 'assets.depreciations.manage', 'assets.depreciations.export',
            
            // Tickets - full access
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.manage', 'tickets.export',
            
            // Projects - full access except deletion
            'projects.view', 'projects.create', 'projects.edit', 'projects.manage', 'projects.export',
            'projects.tasks.view', 'projects.tasks.manage', 'projects.tasks.export',
            'projects.members.view', 'projects.members.manage',
            'projects.templates.view', 'projects.templates.manage',
            
            // Reports - technical focus
            'reports.view', 'reports.tickets', 'reports.assets', 'reports.projects', 'reports.export',
            
            // Basic financial view
            'financial.view', 'financial.payments.view', 'financial.invoices.view',
        ];

        // Admin gets all permissions
        $adminPermissions = Permission::all()->pluck('slug')->toArray();

        // Assign permissions
        if ($accountantRole) {
            $accountantRole->syncPermissions($accountantPermissions);
        }

        if ($technicianRole) {
            $technicianRole->syncPermissions($technicianPermissions);
        }

        if ($adminRole) {
            $adminRole->syncPermissions($adminPermissions);
        }
    }
}