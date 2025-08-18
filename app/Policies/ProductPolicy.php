<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can manage pricing for products.
     */
    public function managePricing(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can manage inventory for products.
     */
    public function manageInventory(User $user, Product $product): bool
    {
        return $this->belongsToUserCompany($user, $product) && 
               $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can import/export products.
     */
    public function importExport(User $user): bool
    {
        return $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Check if the product belongs to the user's company.
     */
    protected function belongsToUserCompany(User $user, Product $product): bool
    {
        return $product->company_id === $user->company_id;
    }
}