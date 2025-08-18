<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('financial.invoices.view');
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // User can view if they have permission and invoice belongs to their company
        return $user->can('financial.invoices.view') 
            && $invoice->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->can('financial.invoices.manage');
    }

    /**
     * Determine whether the user can update the invoice.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // User can update if they have permission and invoice belongs to their company
        return $user->can('financial.invoices.manage')
            && $invoice->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can delete the invoice.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // User can delete if they have permission and invoice belongs to their company
        return $user->can('financial.invoices.manage')
            && $invoice->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can restore the invoice.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->can('financial.invoices.manage')
            && $invoice->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can permanently delete the invoice.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->can('financial.invoices.manage')
            && $invoice->company_id === $user->company_id
            && $user->isAdmin();
    }

    /**
     * Determine whether the user can send the invoice.
     */
    public function send(User $user, Invoice $invoice): bool
    {
        return $user->can('financial.invoices.manage')
            && $invoice->company_id === $user->company_id;
    }

    /**
     * Determine whether the user can export invoices.
     */
    public function export(User $user): bool
    {
        return $user->can('financial.invoices.export');
    }
}