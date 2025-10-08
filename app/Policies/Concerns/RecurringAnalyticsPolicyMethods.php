<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait RecurringAnalyticsPolicyMethods
{
    public function bulkOperations(User $user): bool
    {
        return $user->can('financial.recurring.bulk');
    }

    public function viewAnalytics(User $user): bool
    {
        return $user->can('financial.recurring.analytics');
    }

    public function export(User $user): bool
    {
        return $user->can('financial.recurring.export');
    }

    public function testAutomation(User $user): bool
    {
        return $user->can('financial.recurring.automation')
            && ($user->isAdmin() || $user->isManager());
    }

    public function viewRevenueForecast(User $user): bool
    {
        return $user->can('financial.recurring.forecast')
            && ($user->isAdmin() || $user->isManager());
    }

    public function manageAutomation(User $user): bool
    {
        return $user->can('financial.recurring.automation')
            && ($user->isAdmin() || $user->isManager());
    }
}
