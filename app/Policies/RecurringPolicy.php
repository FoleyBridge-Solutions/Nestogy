<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Recurring;
use Illuminate\Auth\Access\Response;

class RecurringPolicy
{
    /**
     * Determine whether the user can view any recurring billing records.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('financial.recurring.view');
    }

    /**
     * Determine whether the user can view the recurring billing record.
     */
    public function view(User $user, Recurring $recurring): bool
    {
        // User can view if they have permission and recurring belongs to their company
        return $user->can('financial.recurring.view') 
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create recurring billing records.
     */
    public function create(User $user): bool
    {
        return $user->can('financial.recurring.manage');
    }

    /**
     * Determine whether the user can update the recurring billing record.
     */
    public function update(User $user, Recurring $recurring): bool
    {
        // User can update if they have permission and recurring belongs to their company
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can delete the recurring billing record.
     */
    public function delete(User $user, Recurring $recurring): bool
    {
        // User can delete if they have permission and recurring belongs to their company
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can restore the recurring billing record.
     */
    public function restore(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can permanently delete the recurring billing record.
     */
    public function forceDelete(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id
            && $user->isAdmin();
    }

    /**
     * Determine whether the user can duplicate recurring billing records.
     */
    public function duplicate(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can generate invoices from recurring billing records.
     */
    public function generateInvoice(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.generate')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can pause/resume recurring billing records.
     */
    public function pauseResume(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can process VoIP usage data.
     */
    public function processUsage(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.usage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can manage service tiers.
     */
    public function manageServiceTiers(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can apply contract escalations.
     */
    public function applyEscalation(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.escalations')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can calculate prorations and make adjustments.
     */
    public function manageAdjustments(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.adjustments')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkOperations(User $user): bool
    {
        return $user->can('financial.recurring.bulk');
    }

    /**
     * Determine whether the user can access analytics and reports.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->can('financial.recurring.analytics');
    }

    /**
     * Determine whether the user can export recurring billing data.
     */
    public function export(User $user): bool
    {
        return $user->can('financial.recurring.export');
    }

    /**
     * Determine whether the user can create recurring billing from quotes.
     */
    public function createFromQuote(User $user): bool
    {
        return $user->can('financial.recurring.manage')
            && $user->can('financial.quotes.view');
    }

    /**
     * Determine whether the user can test automation features.
     */
    public function testAutomation(User $user): bool
    {
        return $user->can('financial.recurring.automation')
            && ($user->isAdmin() || $user->isManager());
    }

    /**
     * Determine whether the user can view tax calculations and previews.
     */
    public function viewTaxCalculations(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.view')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can manage VoIP tax settings.
     */
    public function manageTaxSettings(User $user): bool
    {
        return $user->can('financial.recurring.tax')
            && ($user->isAdmin() || $user->isManager());
    }

    /**
     * Determine whether the user can view usage summaries and reports.
     */
    public function viewUsageReports(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.reports')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can access revenue forecasting.
     */
    public function viewRevenueForecast(User $user): bool
    {
        return $user->can('financial.recurring.forecast')
            && ($user->isAdmin() || $user->isManager());
    }

    /**
     * Determine whether the user can manage automation schedules.
     */
    public function manageAutomation(User $user): bool
    {
        return $user->can('financial.recurring.automation')
            && ($user->isAdmin() || $user->isManager());
    }

    /**
     * Determine whether the user can view detailed billing history.
     */
    public function viewBillingHistory(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.history')
            && $recurring->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can override system calculations.
     */
    public function overrideCalculations(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.override')
            && $recurring->company_id === $user->company_id
            && ($user->isAdmin() || $user->isManager());
    }