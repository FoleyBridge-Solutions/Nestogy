<?php

namespace App\Policies;

use App\Domains\Financial\Models\PlaidItem;
use App\Domains\Core\Models\User;

class PlaidItemPolicy
{
    /**
     * Determine whether the user can view any bank connections.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('financial.bank-connections.view');
    }

    /**
     * Determine whether the user can view the bank connection.
     */
    public function view(User $user, PlaidItem $plaidItem): bool
    {
        // User can view if they have permission and bank connection belongs to their company
        return $user->hasPermission('financial.bank-connections.view')
            && $plaidItem->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create bank connections.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('financial.bank-connections.manage');
    }

    /**
     * Determine whether the user can update the bank connection.
     */
    public function update(User $user, PlaidItem $plaidItem): bool
    {
        // User can update if they have permission and bank connection belongs to their company
        return $user->hasPermission('financial.bank-connections.manage')
            && $plaidItem->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can delete the bank connection.
     */
    public function delete(User $user, PlaidItem $plaidItem): bool
    {
        // User can delete if they have permission and bank connection belongs to their company
        return $user->hasPermission('financial.bank-connections.manage')
            && $plaidItem->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can sync transactions for the bank connection.
     */
    public function sync(User $user, PlaidItem $plaidItem): bool
    {
        return $user->hasPermission('financial.bank-connections.sync')
            && $plaidItem->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can export bank connections.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('financial.bank-connections.export');
    }
}
