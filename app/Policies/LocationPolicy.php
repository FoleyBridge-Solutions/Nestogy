<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can always view locations
        if ($user->isAdmin()) {
            return true;
        }

        return $user->can('clients.locations.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Location $location): bool
    {
        // Admins can always view locations from their company
        if ($user->isAdmin() && $this->sameCompany($user, $location)) {
            return true;
        }

        return $user->can('clients.locations.view') && $this->sameCompany($user, $location);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admins can always create locations
        if ($user->isAdmin()) {
            return true;
        }

        return $user->can('clients.locations.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Location $location): bool
    {
        // Admins can always update locations from their company
        if ($user->isAdmin() && $this->sameCompany($user, $location)) {
            return true;
        }

        return $user->can('clients.locations.manage') && $this->sameCompany($user, $location);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Location $location): bool
    {
        // Admins can always delete locations from their company
        if ($user->isAdmin() && $this->sameCompany($user, $location)) {
            return true;
        }

        return $user->can('clients.locations.manage') && $this->sameCompany($user, $location);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Location $location): bool
    {
        // Admins can always restore locations from their company
        if ($user->isAdmin() && $this->sameCompany($user, $location)) {
            return true;
        }

        return $user->can('clients.locations.manage') && $this->sameCompany($user, $location);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Location $location): bool
    {
        // Admins can always force delete locations from their company
        if ($user->isAdmin() && $this->sameCompany($user, $location)) {
            return true;
        }

        return $user->can('clients.locations.manage') && $this->sameCompany($user, $location);
    }

    /**
     * Check if user and location belong to the same company
     */
    private function sameCompany(User $user, Location $location): bool
    {
        return $user->company_id === $location->company_id;
    }
}
