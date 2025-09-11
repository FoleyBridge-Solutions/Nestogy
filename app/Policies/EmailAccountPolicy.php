<?php

namespace App\Policies;

use App\Domains\Email\Models\EmailAccount;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmailAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailAccount $emailAccount): bool
    {
        return $user->company_id === $emailAccount->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailAccount $emailAccount): bool
    {
        return $user->company_id === $emailAccount->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailAccount $emailAccount): bool
    {
        return $user->company_id === $emailAccount->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmailAccount $emailAccount): bool
    {
        return $user->id === $emailAccount->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmailAccount $emailAccount): bool
    {
        return $user->id === $emailAccount->user_id;
    }
}
