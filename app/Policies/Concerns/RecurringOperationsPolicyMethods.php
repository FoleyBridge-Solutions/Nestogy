<?php

namespace App\Policies\Concerns;

use App\Models\Recurring;
use App\Models\User;

trait RecurringOperationsPolicyMethods
{
    public function duplicate(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    public function generateInvoice(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.generate')
            && $recurring->company_id === $user->company_id;
    }

    public function pauseResume(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    public function createFromQuote(User $user): bool
    {
        return $user->can('financial.recurring.manage')
            && $user->can('financial.quotes.view');
    }

    public function overrideCalculations(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.override')
            && $recurring->company_id === $user->company_id
            && ($user->isAdmin() || $user->isManager());
    }
}
