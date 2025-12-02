<?php

namespace App\Policies;

use App\Domains\Financial\Models\BankTransaction;
use App\Domains\Core\Models\User;

class BankTransactionPolicy
{
    /**
     * Determine whether the user can view any bank transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('financial.bank-transactions.view');
    }

    /**
     * Determine whether the user can view the bank transaction.
     */
    public function view(User $user, BankTransaction $bankTransaction): bool
    {
        // User can view if they have permission and transaction belongs to their company
        return $user->hasPermission('financial.bank-transactions.view')
            && $bankTransaction->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create bank transactions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('financial.bank-transactions.manage');
    }

    /**
     * Determine whether the user can update the bank transaction.
     */
    public function update(User $user, BankTransaction $bankTransaction): bool
    {
        // User can update if they have permission and transaction belongs to their company
        return $user->hasPermission('financial.bank-transactions.manage')
            && $bankTransaction->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can delete the bank transaction.
     */
    public function delete(User $user, BankTransaction $bankTransaction): bool
    {
        // User can delete if they have permission and transaction belongs to their company
        return $user->hasPermission('financial.bank-transactions.manage')
            && $bankTransaction->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can reconcile bank transactions.
     */
    public function reconcile(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->hasPermission('financial.bank-transactions.reconcile')
            && $bankTransaction->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can ignore/exclude bank transactions.
     */
    public function ignore(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->hasPermission('financial.bank-transactions.manage')
            && $bankTransaction->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can export bank transactions.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('financial.bank-transactions.export');
    }
}
