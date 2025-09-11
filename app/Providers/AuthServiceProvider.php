<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\UserSetting;

/**
 * AuthServiceProvider
 * 
 * Handles authentication policies and gates for role-based access control.
 * Defines permissions for different user roles and system operations.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Domain model policies
        \App\Models\Client::class => \App\Policies\ClientPolicy::class,
        \App\Models\Contact::class => \App\Policies\ContactPolicy::class,
        \App\Models\Location::class => \App\Policies\LocationPolicy::class,
        \App\Models\Ticket::class => \App\Policies\TicketPolicy::class,
        \App\Models\Asset::class => \App\Policies\AssetPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Domains\Project\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\Invoice::class => \App\Policies\InvoicePolicy::class,
        \App\Models\Quote::class => \App\Policies\QuotePolicy::class,
        \App\Models\Recurring::class => \App\Policies\RecurringPolicy::class,
        \App\Domains\Client\Models\ClientITDocumentation::class => \App\Policies\ClientITDocumentationPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\ProductBundle::class => \App\Policies\ProductBundlePolicy::class,
        \App\Models\PricingRule::class => \App\Policies\PricingRulePolicy::class,
        \App\Domains\Contract\Models\ContractTemplate::class => \App\Policies\ContractTemplatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define role-based gates
        $this->defineRoleGates();
        
        // Define feature-based gates
        $this->defineFeatureGates();
        
        // Define administrative gates
        $this->defineAdminGates();

        // Define new permission-based gates
        $this->definePermissionGates();

        // Define quote-specific gates
        $this->defineQuoteGates();

        // Define recurring billing gates
        $this->defineRecurringGates();

        // Define approval workflow gates
        $this->defineApprovalGates();

        // Define export permission gates
        $this->defineExportGates();

        // Define company and team gates
        $this->defineCompanyGates();

        // Define security and audit gates
        $this->defineSecurityGates();

        // Define product management gates
        $this->defineProductGates();
    }

    /**
     * Define role-based authorization gates.
     */
    protected function defineRoleGates(): void
    {
        // Admin role gate (tenant admin)
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        // Super admin role gate (platform operator)
        Gate::define('super-admin', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Any admin role gate (tenant or super)
        Gate::define('any-admin', function (User $user) {
            return $user->isAnyAdmin();
        });

        // Tech role gate (includes admin and super admin)
        Gate::define('tech', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH || $user->isSuperAdmin();
        });

        // Accountant role gate (includes tech, admin, and super admin)
        Gate::define('accountant', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_ACCOUNTANT || $user->isSuperAdmin();
        });

        // Specific role checks
        Gate::define('is-admin', function (User $user) {
            return $user->getRole() === UserSetting::ROLE_ADMIN;
        });

        Gate::define('is-super-admin', function (User $user) {
            return $user->getRole() === UserSetting::ROLE_SUPER_ADMIN;
        });

        Gate::define('is-tech', function (User $user) {
            return $user->getRole() === UserSetting::ROLE_TECH;
        });

        Gate::define('is-accountant', function (User $user) {
            return $user->getRole() === UserSetting::ROLE_ACCOUNTANT;
        });
    }

    /**
     * Define feature-based authorization gates.
     */
    protected function defineFeatureGates(): void
    {
        // User management (tenant admins can manage users in their tenant)
        Gate::define('manage-users', function (User $user) {
            return $user->isAnyAdmin();
        });

        Gate::define('create-users', function (User $user) {
            return $user->isAnyAdmin();
        });

        Gate::define('edit-users', function (User $user) {
            return $user->isAnyAdmin();
        });

        Gate::define('delete-users', function (User $user) {
            return $user->isAnyAdmin();
        });

        // Client management
        Gate::define('manage-clients', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        Gate::define('create-clients', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        Gate::define('edit-clients', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        Gate::define('delete-clients', function (User $user) {
            return $user->isSuperAdmin(); // Only super admins can delete clients
        });

        // Ticket management
        Gate::define('manage-tickets', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_ACCOUNTANT;
        });

        Gate::define('create-tickets', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_ACCOUNTANT;
        });

        Gate::define('assign-tickets', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        Gate::define('close-tickets', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        // Financial management
        Gate::define('manage-finances', function (User $user) {
            return $user->userSetting && $user->userSetting->hasFinancialDashboard();
        });

        Gate::define('view-financial-reports', function (User $user) {
            return $user->userSetting && $user->userSetting->hasFinancialDashboard();
        });

        Gate::define('manage-invoices', function (User $user) {
            return $user->userSetting && $user->userSetting->hasFinancialDashboard();
        });

        // Technical management
        Gate::define('manage-technical', function (User $user) {
            return $user->userSetting && $user->userSetting->hasTechnicalDashboard();
        });

        Gate::define('view-technical-reports', function (User $user) {
            return $user->userSetting && $user->userSetting->hasTechnicalDashboard();
        });

        Gate::define('manage-assets', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });
    }

    /**
     * Define administrative authorization gates.
     */
    protected function defineAdminGates(): void
    {
        // System settings (super admin only for platform settings)
        Gate::define('manage-settings', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('view-system-logs', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('manage-companies', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Database operations (super admin only)
        Gate::define('backup-database', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('restore-database', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Integration management (super admin only)
        Gate::define('manage-integrations', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Advanced reporting (super admin can view all tenant reports)
        Gate::define('view-all-reports', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('export-data', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        // Cross-tenant and subscription management gates
        Gate::define('manage-subscriptions', function (User $user) {
            return $user->canAccessCrossTenant();
        });

        Gate::define('access-cross-tenant', function (User $user) {
            return $user->canAccessCrossTenant();
        });

        Gate::define('manage-subscription-plans', function (User $user) {
            return $user->canAccessCrossTenant();
        });

        Gate::define('impersonate-tenant', function (User $user) {
            return $user->canAccessCrossTenant();
        });
    }

    /**
     * Define permission-based authorization gates.
     */
    protected function definePermissionGates(): void
    {
        // Dynamic permission gates
        Gate::define('has-permission', function (User $user, string $permission) {
            return $user->hasPermission($permission);
        });

        Gate::define('has-any-permission', function (User $user, array $permissions) {
            return $user->hasAnyPermission($permissions);
        });

        Gate::define('has-all-permissions', function (User $user, array $permissions) {
            return $user->hasAllPermissions($permissions);
        });

        // Domain access gates
        Gate::define('access-domain', function (User $user, string $domain) {
            return $user->canAccessDomain($domain);
        });

        Gate::define('perform-action', function (User $user, string $domain, string $action) {
            return $user->canPerformAction($domain, $action);
        });

        // Role hierarchy gates (using new system)
        Gate::define('has-role-level', function (User $user, int $level) {
            return $user->getRoleLevel() >= $level;
        });

        Gate::define('is-company-admin', function (User $user) {
            return $user->hasRole('admin') && $user->company_id;
        });
    }

    /**
     * Define quote-specific authorization gates.
     */
    protected function defineQuoteGates(): void
    {
        // Quote management gates
        Gate::define('manage-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.manage');
        });

        Gate::define('create-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.manage');
        });

        Gate::define('view-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.view');
        });

        Gate::define('approve-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.approve');
        });

        Gate::define('send-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.manage');
        });

        Gate::define('convert-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.manage') &&
                   $user->hasPermission('financial.invoices.manage');
        });

        // Quote workflow gates
        Gate::define('approve-quotes-manager', function (User $user) {
            return $user->hasPermission('financial.quotes.approve') &&
                   ($user->hasRole('manager') || $user->hasRole('executive') || $user->hasRole('admin'));
        });

        Gate::define('approve-quotes-executive', function (User $user) {
            return $user->hasPermission('financial.quotes.approve') &&
                   ($user->hasRole('executive') || $user->hasRole('admin'));
        });

        Gate::define('approve-quotes-finance', function (User $user) {
            return $user->hasPermission('financial.quotes.approve') &&
                   ($user->hasRole('finance') || $user->hasRole('executive') || $user->hasRole('admin'));
        });

        // Quote template gates
        Gate::define('manage-quote-templates', function (User $user) {
            return $user->hasPermission('financial.quotes.templates');
        });

        Gate::define('use-quote-templates', function (User $user) {
            return $user->hasPermission('financial.quotes.manage');
        });

        // VoIP configuration gates
        Gate::define('manage-voip-config', function (User $user) {
            return $user->hasPermission('financial.quotes.manage') &&
                   $user->hasPermission('voip.configuration');
        });

        // Quote export gates
        Gate::define('export-quotes', function (User $user) {
            return $user->hasPermission('financial.quotes.export');
        });

        // Quote analytics gates
        Gate::define('view-quote-analytics', function (User $user) {
            return $user->hasPermission('financial.quotes.analytics') ||
                   $user->hasPermission('reports.financial');
        });
    }

    /**
     * Define recurring billing authorization gates.
     */
    protected function defineRecurringGates(): void
    {
        // Recurring billing management gates
        Gate::define('manage-recurring', function (User $user) {
            return $user->hasPermission('financial.recurring.manage');
        });

        Gate::define('create-recurring', function (User $user) {
            return $user->hasPermission('financial.recurring.manage');
        });

        Gate::define('view-recurring', function (User $user) {
            return $user->hasPermission('financial.recurring.view');
        });

        Gate::define('generate-recurring-invoices', function (User $user) {
            return $user->hasPermission('financial.recurring.generate');
        });

        Gate::define('process-recurring-usage', function (User $user) {
            return $user->hasPermission('financial.recurring.usage');
        });

        Gate::define('manage-recurring-escalations', function (User $user) {
            return $user->hasPermission('financial.recurring.escalations');
        });

        Gate::define('manage-recurring-adjustments', function (User $user) {
            return $user->hasPermission('financial.recurring.adjustments');
        });

        // VoIP-specific recurring billing gates
        Gate::define('manage-voip-tiers', function (User $user) {
            return $user->hasPermission('financial.recurring.manage') &&
                   $user->hasPermission('voip.configuration');
        });

        Gate::define('process-voip-usage', function (User $user) {
            return $user->hasPermission('financial.recurring.usage') &&
                   $user->hasPermission('voip.billing');
        });

        Gate::define('manage-voip-tax', function (User $user) {
            return $user->hasPermission('financial.recurring.tax') &&
                   ($user->isAdmin() || $user->isManager());
        });

        // Recurring billing bulk operations
        Gate::define('bulk-recurring-operations', function (User $user) {
            return $user->hasPermission('financial.recurring.bulk');
        });

        // Recurring billing analytics and reports
        Gate::define('view-recurring-analytics', function (User $user) {
            return $user->hasPermission('financial.recurring.analytics');
        });

        Gate::define('view-recurring-reports', function (User $user) {
            return $user->hasPermission('financial.recurring.reports');
        });

        Gate::define('export-recurring', function (User $user) {
            return $user->hasPermission('financial.recurring.export');
        });

        Gate::define('view-revenue-forecast', function (User $user) {
            return $user->hasPermission('financial.recurring.forecast') &&
                   ($user->isAdmin() || $user->isManager());
        });

        // Recurring billing automation gates
        Gate::define('manage-recurring-automation', function (User $user) {
            return $user->hasPermission('financial.recurring.automation') &&
                   ($user->isAdmin() || $user->isManager());
        });

        Gate::define('test-recurring-automation', function (User $user) {
            return $user->hasPermission('financial.recurring.automation') &&
                   ($user->isAdmin() || $user->isManager());
        });

        // Recurring billing history and audit
        Gate::define('view-recurring-history', function (User $user) {
            return $user->hasPermission('financial.recurring.history');
        });

        Gate::define('override-recurring-calculations', function (User $user) {
            return $user->hasPermission('financial.recurring.override') &&
                   ($user->isAdmin() || $user->isManager());
        });

        // Quote to recurring conversion
        Gate::define('convert-quotes-to-recurring', function (User $user) {
            return $user->hasPermission('financial.recurring.manage') &&
                   $user->hasPermission('financial.quotes.view');
        });
    }

    /**
     * Define approval workflow gates.
     */
    protected function defineApprovalGates(): void
    {
        // Quote approval workflows
        Gate::define('approve-quotes-any-level', function (User $user) {
            return $user->hasPermission('financial.quotes.approve');
        });

        // Financial approval workflows
        Gate::define('approve-expenses', function (User $user) {
            return $user->hasPermission('financial.expenses.approve');
        });

        Gate::define('approve-payments', function (User $user) {
            return $user->hasAnyPermission([
                'financial.payments.manage',
                'financial.expenses.approve'
            ]);
        });

        Gate::define('approve-budgets', function (User $user) {
            return $user->hasPermission('financial.manage') || $user->isAdmin();
        });

        // Project approval workflows
        Gate::define('approve-project-changes', function (User $user) {
            return $user->hasPermission('projects.manage');
        });

        Gate::define('approve-time-entries', function (User $user) {
            return $user->hasAnyPermission([
                'projects.manage',
                'tickets.manage'
            ]);
        });

        // Asset approval workflows
        Gate::define('approve-asset-disposal', function (User $user) {
            return $user->hasPermission('assets.manage') || $user->isAdmin();
        });

        Gate::define('approve-maintenance-schedules', function (User $user) {
            return $user->hasPermission('assets.maintenance.manage');
        });

        // User management approvals
        Gate::define('approve-user-access', function (User $user) {
            return $user->hasAnyPermission([
                'users.manage',
                'system.permissions.manage'
            ]);
        });
    }

    /**
     * Define export permission gates.
     */
    protected function defineExportGates(): void
    {
        // General export gates
        Gate::define('export-any-data', function (User $user) {
            return $user->hasAnyPermission([
                'clients.export',
                'assets.export',
                'financial.export',
                'projects.export',
                'reports.export',
                'users.export'
            ]);
        });

        Gate::define('export-sensitive-data', function (User $user) {
            return $user->hasAnyPermission([
                'financial.export',
                'users.export',
                'reports.export'
            ]) || $user->isAdmin();
        });

        // Domain-specific export gates
        Gate::define('export-client-data', function (User $user) {
            return $user->hasAnyPermission([
                'clients.export',
                'clients.contacts.export',
                'clients.locations.export',
                'clients.documents.export'
            ]);
        });

        Gate::define('export-financial-data', function (User $user) {
            return $user->hasAnyPermission([
                'financial.export',
                'financial.payments.export',
                'financial.expenses.export',
                'financial.invoices.export',
                'financial.quotes.export'
            ]);
        });

        Gate::define('export-asset-data', function (User $user) {
            return $user->hasAnyPermission([
                'assets.export',
                'assets.maintenance.export',
                'assets.warranties.export',
                'assets.depreciations.export'
            ]);
        });

        Gate::define('export-project-data', function (User $user) {
            return $user->hasAnyPermission([
                'projects.export',
                'projects.tasks.export'
            ]);
        });

        Gate::define('export-reports', function (User $user) {
            return $user->hasPermission('reports.export');
        });

        // Bulk export operations
        Gate::define('bulk-export', function (User $user) {
            return $user->hasPermission('reports.export') || $user->isAdmin();
        });

        Gate::define('scheduled-exports', function (User $user) {
            return $user->hasPermission('reports.export');
        });
    }

    /**
     * Define company and team-based gates.
     */
    protected function defineCompanyGates(): void
    {
        // Company scoping gates
        Gate::define('same-company', function (User $user, $model) {
            if (method_exists($model, 'company_id')) {
                return $user->company_id === $model->company_id;
            }
            return false;
        });

        Gate::define('manage-company-settings', function (User $user) {
            return $user->hasAnyPermission([
                'system.settings.manage',
                'users.manage'
            ]) || $user->isAdmin();
        });

        // Team-based gates for projects
        Gate::define('manage-team', function (User $user, $project = null) {
            if (!$project) {
                return $user->hasPermission('projects.members.manage');
            }

            // Project managers can manage their team
            if ($project->manager_id === $user->id) {
                return true;
            }

            return $user->hasPermission('projects.manage');
        });
    }

    /**
     * Define security and audit gates.
     */
    protected function defineSecurityGates(): void
    {
        // Sensitive operation gates
        Gate::define('access-sensitive-data', function (User $user) {
            return $user->hasAnyPermission([
                'financial.manage',
                'users.manage',
                'system.settings.manage'
            ]) || $user->isAdmin();
        });

        Gate::define('view-audit-logs', function (User $user) {
            return $user->hasPermission('system.logs.view') || $user->isAdmin();
        });

        Gate::define('impersonate-users', function (User $user) {
            return $user->hasPermission('system.permissions.manage') && $user->isAdmin();
        });

        Gate::define('bypass-restrictions', function (User $user) {
            return $user->hasPermission('system.permissions.manage') && $user->isAdmin();
        });
    }

    /**
     * Define super admin gate for development/maintenance.
     */
    protected function defineSuperAdminGate(): void
    {
        Gate::define('super-admin', function (User $user) {
            // This could be based on a specific user ID, email, or special flag
            return $user->isAdmin() && in_array($user->email, [
                'admin@nestogy.com',
                'support@foleybridge.com'
            ]);
        });
    }

    /**
     * Define product management authorization gates.
     */
    protected function defineProductGates(): void
    {
        // Product management gates
        Gate::define('access', function (User $user, string $resource) {
            if ($resource === 'products') {
                return $user->getRole() >= User::ROLE_ACCOUNTANT;
            }
            if ($resource === 'settings') {
                return $user->isAdmin() || $user->hasPermission('settings.manage');
            }
            return false;
        });

        Gate::define('create', function (User $user, string $resource) {
            if ($resource === 'products') {
                return $user->getRole() >= User::ROLE_TECH;
            }
            return false;
        });

        Gate::define('update', function (User $user, string $resource) {
            if ($resource === 'products') {
                return $user->getRole() >= User::ROLE_TECH;
            }
            return false;
        });

        Gate::define('delete', function (User $user, string $resource) {
            if ($resource === 'products') {
                return $user->getRole() >= User::ROLE_TECH;
            }
            return false;
        });

        // Product-specific capabilities
        Gate::define('manage-products', function (User $user) {
            return $user->getRole() >= User::ROLE_ACCOUNTANT;
        });

        Gate::define('manage-product-pricing', function (User $user) {
            return $user->getRole() >= User::ROLE_TECH;
        });

        Gate::define('manage-product-inventory', function (User $user) {
            return $user->getRole() >= User::ROLE_ACCOUNTANT;
        });

        Gate::define('import-export-products', function (User $user) {
            return $user->getRole() >= User::ROLE_TECH;
        });

        // Bundle management gates
        Gate::define('manage-bundles', function (User $user) {
            return $user->getRole() >= User::ROLE_TECH;
        });

        // Pricing rule management gates
        Gate::define('manage-pricing-rules', function (User $user) {
            return $user->getRole() >= User::ROLE_TECH;
        });
    }

    /**
     * Get all available permissions grouped by category.
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'User Management' => [
                'manage-users' => 'Manage Users',
                'create-users' => 'Create Users',
                'edit-users' => 'Edit Users',
                'delete-users' => 'Delete Users',
            ],
            'Client Management' => [
                'manage-clients' => 'Manage Clients',
                'create-clients' => 'Create Clients',
                'edit-clients' => 'Edit Clients',
                'delete-clients' => 'Delete Clients',
            ],
            'Ticket Management' => [
                'manage-tickets' => 'Manage Tickets',
                'create-tickets' => 'Create Tickets',
                'assign-tickets' => 'Assign Tickets',
                'close-tickets' => 'Close Tickets',
            ],
            'Financial Management' => [
                'manage-finances' => 'Manage Finances',
                'view-financial-reports' => 'View Financial Reports',
                'manage-invoices' => 'Manage Invoices',
                'manage-quotes' => 'Manage Quotes',
                'approve-quotes' => 'Approve Quotes',
                'export-quotes' => 'Export Quotes',
                'manage-recurring' => 'Manage Recurring Billing',
                'view-recurring' => 'View Recurring Billing',
                'generate-recurring-invoices' => 'Generate Recurring Invoices',
                'process-recurring-usage' => 'Process VoIP Usage Data',
                'manage-recurring-escalations' => 'Manage Contract Escalations',
                'manage-recurring-adjustments' => 'Manage Billing Adjustments',
                'bulk-recurring-operations' => 'Bulk Recurring Operations',
                'view-recurring-analytics' => 'View Recurring Analytics',
                'export-recurring' => 'Export Recurring Data',
                'manage-recurring-automation' => 'Manage Billing Automation',
            ],
            'Technical Management' => [
                'manage-technical' => 'Manage Technical',
                'view-technical-reports' => 'View Technical Reports',
                'manage-assets' => 'Manage Assets',
            ],
            'Product Management' => [
                'manage-products' => 'Manage Products',
                'manage-product-pricing' => 'Manage Product Pricing',
                'manage-product-inventory' => 'Manage Product Inventory',
                'import-export-products' => 'Import/Export Products',
                'manage-bundles' => 'Manage Product Bundles',
                'manage-pricing-rules' => 'Manage Pricing Rules',
            ],
            'System Administration' => [
                'manage-settings' => 'Manage Settings',
                'view-system-logs' => 'View System Logs',
                'manage-companies' => 'Manage Companies',
                'backup-database' => 'Backup Database',
                'restore-database' => 'Restore Database',
                'manage-integrations' => 'Manage Integrations',
            ],
        ];
    }
}