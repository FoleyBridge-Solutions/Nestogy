<?php

namespace App\Policies;

use App\Models\ProductBundle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductBundlePolicy
{
    /**
     * Determine whether the user can view any product bundles.
     */
    public function viewAny(User $user): bool
    {
        return $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can view the product bundle.
     */
    public function view(User $user, ProductBundle $productBundle): bool
    {
        return $this->belongsToUserCompany($user, $productBundle) && 
               $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can create product bundles.
     */
    public function create(User $user): bool
    {
        return $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can update the product bundle.
     */
    public function update(User $user, ProductBundle $productBundle): bool
    {
        return $this->belongsToUserCompany($user, $productBundle) && 
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can delete the product bundle.
     */
    public function delete(User $user, ProductBundle $productBundle): bool
    {
        return $this->belongsToUserCompany($user, $productBundle) && 
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can restore the product bundle.
     */
    public function restore(User $user, ProductBundle $productBundle): bool
    {
        return $this->belongsToUserCompany($user, $productBundle) && 
               $user->getRole() >= User::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the product bundle.
     */
    public function forceDelete(User $user, ProductBundle $productBundle): bool
    {
        return $this->belongsToUserCompany($user, $productBundle) && 
               $user->getRole() >= User::ROLE_ADMIN;
    }

    /**
     * Check if the product bundle belongs to the user's company.
     */
    protected function belongsToUserCompany(User $user, ProductBundle $productBundle): bool
    {
        return $productBundle->company_id === $user->company_id;
    }
}