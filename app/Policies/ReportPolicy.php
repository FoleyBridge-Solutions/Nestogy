<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view reports.
     */
    public function view(User $user): bool
    {
        return $user->can('reports.view');
    }

    /**
     * Determine whether the user can export reports.
     */
    public function export(User $user): bool
    {
        return $user->can('reports.export');
    }

    /**
     * Determine whether the user can view financial reports.
     */
    public function viewFinancial(User $user): bool
    {
        return $user->canAny([
            'reports.financial',
            'financial.view',
        ]);
    }

    /**
     * Determine whether the user can view ticket reports.
     */
    public function viewTickets(User $user): bool
    {
        return $user->canAny([
            'reports.tickets',
            'tickets.view',
        ]);
    }

    /**
     * Determine whether the user can view asset reports.
     */
    public function viewAssets(User $user): bool
    {
        return $user->canAny([
            'reports.assets',
            'assets.view',
        ]);
    }

    /**
     * Determine whether the user can view client reports.
     */
    public function viewClients(User $user): bool
    {
        return $user->canAny([
            'reports.clients',
            'clients.view',
        ]);
    }

    /**
     * Determine whether the user can view project reports.
     */
    public function viewProjects(User $user): bool
    {
        return $user->canAny([
            'reports.projects',
            'projects.view',
        ]);
    }

    /**
     * Determine whether the user can view user reports.
     */
    public function viewUsers(User $user): bool
    {
        return $user->canAny([
            'reports.users',
            'users.view',
        ]);
    }

    /**
     * Determine whether the user can create custom reports.
     */
    public function createCustom(User $user): bool
    {
        return $user->canAny([
            'reports.view',
            'system.settings.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can schedule reports.
     */
    public function schedule(User $user): bool
    {
        return $user->can('reports.export') || $user->isAdmin();
    }

    /**
     * Determine whether the user can access dashboard analytics.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->can('reports.view');
    }

    /**
     * Determine whether the user can view revenue reports.
     */
    public function viewRevenue(User $user): bool
    {
        return $user->canAny([
            'reports.financial',
            'financial.view',
            'financial.manage',
        ]);
    }

    /**
     * Determine whether the user can view expense reports.
     */
    public function viewExpenses(User $user): bool
    {
        return $user->canAny([
            'reports.financial',
            'financial.expenses.view',
            'financial.expenses.approve',
        ]);
    }

    /**
     * Determine whether the user can view profit/loss reports.
     */
    public function viewProfitLoss(User $user): bool
    {
        return $user->canAny([
            'reports.financial',
            'financial.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can view time tracking reports.
     */
    public function viewTimeTracking(User $user): bool
    {
        return $user->canAny([
            'reports.tickets',
            'tickets.view',
            'projects.view',
        ]);
    }

    /**
     * Determine whether the user can view performance reports.
     */
    public function viewPerformance(User $user): bool
    {
        return $user->canAny([
            'reports.users',
            'users.manage',
            'tickets.manage',
            'projects.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can view maintenance reports.
     */
    public function viewMaintenance(User $user): bool
    {
        return $user->canAny([
            'reports.assets',
            'assets.maintenance.view',
            'assets.view',
        ]);
    }

    /**
     * Determine whether the user can view warranty reports.
     */
    public function viewWarranty(User $user): bool
    {
        return $user->canAny([
            'reports.assets',
            'assets.warranties.view',
            'assets.view',
        ]);
    }

    /**
     * Determine whether the user can view depreciation reports.
     */
    public function viewDepreciation(User $user): bool
    {
        return $user->canAny([
            'reports.assets',
            'assets.depreciations.view',
            'assets.view',
        ]);
    }

    /**
     * Determine whether the user can view SLA reports.
     */
    public function viewSla(User $user): bool
    {
        return $user->canAny([
            'reports.tickets',
            'tickets.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can view client satisfaction reports.
     */
    public function viewSatisfaction(User $user): bool
    {
        return $user->canAny([
            'reports.clients',
            'clients.manage',
            'tickets.manage',
        ]);
    }

    /**
     * Determine whether the user can view utilization reports.
     */
    public function viewUtilization(User $user): bool
    {
        return $user->canAny([
            'reports.users',
            'users.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can access advanced analytics.
     */
    public function viewAdvancedAnalytics(User $user): bool
    {
        return $user->canAny([
            'reports.export',
            'system.settings.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can configure report settings.
     */
    public function configureSettings(User $user): bool
    {
        return $user->can('system.settings.manage') || $user->isAdmin();
    }

    /**
     * Determine whether the user can access audit reports.
     */
    public function viewAudit(User $user): bool
    {
        return $user->can('system.logs.view') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view system health reports.
     */
    public function viewSystemHealth(User $user): bool
    {
        return $user->can('system.settings.view') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view company-wide reports.
     */
    public function viewCompanyWide(User $user): bool
    {
        return $user->canAny([
            'reports.export',
            'system.settings.manage',
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can share reports with others.
     */
    public function share(User $user): bool
    {
        return $user->can('reports.export');
    }

    /**
     * Determine whether the user can automate report generation.
     */
    public function automate(User $user): bool
    {
        return $user->can('reports.export') || $user->isAdmin();
    }
}
