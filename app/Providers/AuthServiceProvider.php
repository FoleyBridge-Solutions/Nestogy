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
        // Add model policies here as needed
        \App\Models\Client::class => \App\Policies\ClientPolicy::class,
        \App\Models\Ticket::class => \App\Policies\TicketPolicy::class,
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
    }

    /**
     * Define role-based authorization gates.
     */
    protected function defineRoleGates(): void
    {
        // Admin role gate
        Gate::define('admin', function (User $user) {
            return $user->isAdmin();
        });

        // Tech role gate (includes admin)
        Gate::define('tech', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
        });

        // Accountant role gate (includes tech and admin)
        Gate::define('accountant', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_ACCOUNTANT;
        });

        // Specific role checks
        Gate::define('is-admin', function (User $user) {
            return $user->getRole() === UserSetting::ROLE_ADMIN;
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
        // User management
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('create-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('edit-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('delete-users', function (User $user) {
            return $user->isAdmin();
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
            return $user->isAdmin();
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
        // System settings
        Gate::define('manage-settings', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('view-system-logs', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('manage-companies', function (User $user) {
            return $user->isAdmin();
        });

        // Database operations
        Gate::define('backup-database', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('restore-database', function (User $user) {
            return $user->isAdmin();
        });

        // Integration management
        Gate::define('manage-integrations', function (User $user) {
            return $user->isAdmin();
        });

        // Advanced reporting
        Gate::define('view-all-reports', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('export-data', function (User $user) {
            return $user->getRole() >= UserSetting::ROLE_TECH;
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
            ],
            'Technical Management' => [
                'manage-technical' => 'Manage Technical',
                'view-technical-reports' => 'View Technical Reports',
                'manage-assets' => 'Manage Assets',
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