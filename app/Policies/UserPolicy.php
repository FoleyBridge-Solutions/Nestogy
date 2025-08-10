<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Check permission and company scope
        return $user->hasPermission('users.view') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update their own profile (basic info)
        if ($user->id === $model->id) {
            return true;
        }

        // Check permission and company scope for managing other users
        return $user->hasPermission('users.edit') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.delete') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Users cannot force delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can export users.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('users.export');
    }

    /**
     * Determine whether the user can update user passwords.
     */
    public function updatePassword(User $user, User $model): bool
    {
        // Users can update their own password
        if ($user->id === $model->id) {
            return true;
        }

        // Admin users can update other users' passwords
        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can update user roles.
     */
    public function updateRole(User $user, User $model): bool
    {
        // Users cannot change their own role
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can update user status.
     */
    public function updateStatus(User $user, User $model): bool
    {
        // Users cannot change their own status
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can archive users.
     */
    public function archive(User $user, User $model): bool
    {
        // Users cannot archive themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('users.edit') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can view user activity logs.
     */
    public function viewActivity(User $user, User $model): bool
    {
        // Users can view their own activity
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can manage user permissions.
     */
    public function managePermissions(User $user, User $model): bool
    {
        // Users cannot manage their own permissions
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasAnyPermission([
            'users.manage',
            'system.permissions.manage'
        ]) && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can assign roles to users.
     */
    public function assignRoles(User $user, User $model): bool
    {
        // Users cannot assign roles to themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasAnyPermission([
            'users.manage',
            'system.permissions.manage'
        ]) && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can impersonate other users.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Users cannot impersonate themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Only super admins can impersonate
        return $user->hasPermission('system.permissions.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can view user settings.
     */
    public function viewSettings(User $user, User $model): bool
    {
        // Users can view their own settings
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.view') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can update user settings.
     */
    public function updateSettings(User $user, User $model): bool
    {
        // Users can update their own settings
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can reset two-factor authentication.
     */
    public function resetTwoFactor(User $user, User $model): bool
    {
        // Users can reset their own 2FA
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can view sensitive user information.
     */
    public function viewSensitive(User $user, User $model): bool
    {
        // Users can view their own sensitive info
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Determine whether the user can manage API tokens for users.
     */
    public function manageApiTokens(User $user, User $model): bool
    {
        // Users can manage their own API tokens
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('users.manage') && $this->sameCompany($user, $model);
    }

    /**
     * Check if user and model belong to same company.
     */
    private function sameCompany(User $user, User $model): bool
    {
        return $user->company_id === $model->company_id;
    }
}