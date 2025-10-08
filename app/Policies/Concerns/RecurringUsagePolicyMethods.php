<?php

namespace App\Policies\Concerns;

use App\Models\Recurring;
use App\Models\User;

trait RecurringUsagePolicyMethods
{
    public function processUsage(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.usage')
            && $recurring->company_id === $user->company_id;
    }

    public function manageServiceTiers(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    public function applyEscalation(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.escalations')
            && $recurring->company_id === $user->company_id;
    }

    public function manageAdjustments(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.adjustments')
            && $recurring->company_id === $user->company_id;
    }
}
