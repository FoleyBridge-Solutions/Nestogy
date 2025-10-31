<?php

namespace App\Policies;

use App\Domains\Core\Models\User;
use App\Domains\Integration\Models\RmmIntegration;

class RmmIntegrationPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user): ?bool
    {
        // Super admins have unrestricted access
        if ($user->isA('super-admin')) {
            return true;
        }

        return null; // Fall through to specific permission checks
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins and technicians can view integrations in their company
        return $user->isA('admin') || $user->isA('technician');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RmmIntegration $rmmIntegration): bool
    {
        // Must be same company
        if ($user->company_id !== $rmmIntegration->company_id) {
            return false;
        }

        // Admins and technicians can view
        return $user->isA('admin') || $user->isA('technician');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admins and technicians can create integrations
        return $user->isA('admin') || $user->isA('technician');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RmmIntegration $rmmIntegration): bool
    {
        // Must be same company
        if ($user->company_id !== $rmmIntegration->company_id) {
            return false;
        }

        // Admins and technicians can update
        return $user->isA('admin') || $user->isA('technician');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RmmIntegration $rmmIntegration): bool
    {
        // Must be same company
        if ($user->company_id !== $rmmIntegration->company_id) {
            return false;
        }

        // Only admins can delete integrations
        return $user->isA('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RmmIntegration $rmmIntegration): bool
    {
        return $user->isA('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RmmIntegration $rmmIntegration): bool
    {
        return $user->isA('super-admin');
    }
}
