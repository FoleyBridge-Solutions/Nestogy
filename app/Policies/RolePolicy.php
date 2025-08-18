<?php

namespace App\Policies;

use App\Models\User;
use Silber\Bouncer\BouncerFacade as Bouncer;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view') || $user->can('settings.roles.view');
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user): bool
    {
        return $user->can('users.view') || $user->can('settings.roles.view');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->can('settings.roles.create') || $user->can('users.manage');
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user): bool
    {
        return $user->can('settings.roles.edit') || $user->can('users.manage');
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user): bool
    {
        return $user->can('settings.roles.delete') || $user->can('users.manage');
    }
}
