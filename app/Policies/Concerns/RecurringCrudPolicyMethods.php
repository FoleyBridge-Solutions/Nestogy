<?php

namespace App\Policies\Concerns;

use App\Models\Recurring;
use App\Models\User;

trait RecurringCrudPolicyMethods
{
    public function viewAny(User $user): bool
    {
        return $user->can('financial.recurring.view');
    }

    public function view(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.view')
            && $recurring->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('financial.recurring.manage');
    }

    public function update(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    public function delete(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    public function restore(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id;
    }

    public function forceDelete(User $user, Recurring $recurring): bool
    {
        return $user->can('financial.recurring.manage')
            && $recurring->company_id === $user->company_id
            && $user->isAdmin();
    }
}
