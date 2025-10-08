<?php

namespace App\Policies\Concerns;

use App\Models\Recurring;
use App\Models\User;

trait RecurringTaxReportPolicyMethods
{
    public function viewTaxCalculations(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.view')
            && $recurring->company_id === $user->company_id;
    }

    public function manageTaxSettings(User $user): bool
    {
        return $user->can('financial.recurring.tax')
            && ($user->isAdmin() || $user->isManager());
    }

    public function viewUsageReports(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.reports')
            && $recurring->company_id === $user->company_id;
    }

    public function viewBillingHistory(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.history')
            && $recurring->company_id === $user->company_id;
    }
}
