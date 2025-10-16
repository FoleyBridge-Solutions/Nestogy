<?php

namespace App\Livewire\Concerns;

use App\Domains\Core\Models\User;

trait WithAuthenticatedUser
{
    public User $user;

    public int $companyId;

    /**
     * Boot the trait - called automatically by Livewire
     */
    public function bootWithAuthenticatedUser(): void
    {
        $this->user = auth()->user();
        $this->companyId = $this->user->company_id;
    }

    /**
     * Get the authenticated user
     */
    protected function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get the company ID
     */
    protected function getCompanyId(): int
    {
        return $this->companyId;
    }

    /**
     * Get the user ID
     */
    protected function getUserId(): int
    {
        return $this->user->id;
    }

    /**
     * Check if user has permission
     */
    protected function userCan(string $permission): bool
    {
        return $this->user->can($permission);
    }

    /**
     * Check if user has role
     */
    protected function userHasRole(string $role): bool
    {
        return $this->user->hasRole($role);
    }
}
