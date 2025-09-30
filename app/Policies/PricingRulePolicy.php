<?php

namespace App\Policies;

use App\Models\PricingRule;
use App\Models\User;

class PricingRulePolicy
{
    /**
     * Determine whether the user can view any pricing rules.
     */
    public function viewAny(User $user): bool
    {
        return $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can view the pricing rule.
     */
    public function view(User $user, PricingRule $pricingRule): bool
    {
        return $this->belongsToUserCompany($user, $pricingRule) &&
               $user->getRole() >= User::ROLE_ACCOUNTANT;
    }

    /**
     * Determine whether the user can create pricing rules.
     */
    public function create(User $user): bool
    {
        return $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can update the pricing rule.
     */
    public function update(User $user, PricingRule $pricingRule): bool
    {
        return $this->belongsToUserCompany($user, $pricingRule) &&
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can delete the pricing rule.
     */
    public function delete(User $user, PricingRule $pricingRule): bool
    {
        return $this->belongsToUserCompany($user, $pricingRule) &&
               $user->getRole() >= User::ROLE_TECH;
    }

    /**
     * Determine whether the user can restore the pricing rule.
     */
    public function restore(User $user, PricingRule $pricingRule): bool
    {
        return $this->belongsToUserCompany($user, $pricingRule) &&
               $user->getRole() >= User::ROLE_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the pricing rule.
     */
    public function forceDelete(User $user, PricingRule $pricingRule): bool
    {
        return $this->belongsToUserCompany($user, $pricingRule) &&
               $user->getRole() >= User::ROLE_ADMIN;
    }

    /**
     * Check if the pricing rule belongs to the user's company.
     */
    protected function belongsToUserCompany(User $user, PricingRule $pricingRule): bool
    {
        return $pricingRule->company_id === $user->company_id;
    }
}
