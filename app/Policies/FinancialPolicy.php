<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class FinancialPolicy
{
    /**
     * Determine whether the user can view financial data.
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('financial.view');
    }

    /**
     * Determine whether the user can create financial records.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('financial.create');
    }

    /**
     * Determine whether the user can edit financial records.
     */
    public function edit(User $user): bool
    {
        return $user->hasPermission('financial.edit');
    }

    /**
     * Determine whether the user can delete financial records.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermission('financial.delete');
    }

    /**
     * Determine whether the user can manage financial data.
     */
    public function manage(User $user): bool
    {
        return $user->hasPermission('financial.manage');
    }

    /**
     * Determine whether the user can export financial data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('financial.export');
    }

    // Payment-specific permissions
    /**
     * Determine whether the user can view payments.
     */
    public function viewPayments(User $user): bool
    {
        return $user->hasPermission('financial.payments.view');
    }

    /**
     * Determine whether the user can manage payments.
     */
    public function managePayments(User $user): bool
    {
        return $user->hasPermission('financial.payments.manage');
    }

    /**
     * Determine whether the user can export payments.
     */
    public function exportPayments(User $user): bool
    {
        return $user->hasPermission('financial.payments.export');
    }

    /**
     * Determine whether the user can process online payments.
     */
    public function processPayments(User $user): bool
    {
        return $user->hasPermission('financial.payments.manage');
    }

    // Expense-specific permissions
    /**
     * Determine whether the user can view expenses.
     */
    public function viewExpenses(User $user): bool
    {
        return $user->hasPermission('financial.expenses.view');
    }

    /**
     * Determine whether the user can manage expenses.
     */
    public function manageExpenses(User $user): bool
    {
        return $user->hasPermission('financial.expenses.manage');
    }

    /**
     * Determine whether the user can export expenses.
     */
    public function exportExpenses(User $user): bool
    {
        return $user->hasPermission('financial.expenses.export');
    }

    /**
     * Determine whether the user can approve expenses.
     */
    public function approveExpenses(User $user): bool
    {
        return $user->hasPermission('financial.expenses.approve');
    }

    /**
     * Determine whether the user can reject expenses.
     */
    public function rejectExpenses(User $user): bool
    {
        return $user->hasPermission('financial.expenses.approve');
    }

    /**
     * Determine whether the user can submit expenses for approval.
     */
    public function submitExpenses(User $user): bool
    {
        return $user->hasPermission('financial.expenses.manage');
    }

    // Invoice-specific permissions
    /**
     * Determine whether the user can view invoices.
     */
    public function viewInvoices(User $user): bool
    {
        return $user->hasPermission('financial.invoices.view');
    }

    /**
     * Determine whether the user can manage invoices.
     */
    public function manageInvoices(User $user): bool
    {
        return $user->hasPermission('financial.invoices.manage');
    }

    /**
     * Determine whether the user can export invoices.
     */
    public function exportInvoices(User $user): bool
    {
        return $user->hasPermission('financial.invoices.export');
    }

    /**
     * Determine whether the user can send invoices.
     */
    public function sendInvoices(User $user): bool
    {
        return $user->hasPermission('financial.invoices.manage');
    }

    /**
     * Determine whether the user can generate invoice PDFs.
     */
    public function generateInvoicePdf(User $user): bool
    {
        return $user->hasPermission('financial.invoices.view');
    }

    /**
     * Determine whether the user can update invoice status.
     */
    public function updateInvoiceStatus(User $user): bool
    {
        return $user->hasPermission('financial.invoices.manage');
    }

    /**
     * Determine whether the user can add payments to invoices.
     */
    public function addInvoicePayments(User $user): bool
    {
        return $user->hasPermission('financial.payments.manage');
    }

    /**
     * Determine whether the user can manage invoice items.
     */
    public function manageInvoiceItems(User $user): bool
    {
        return $user->hasPermission('financial.invoices.manage');
    }

    /**
     * Determine whether the user can copy invoices.
     */
    public function copyInvoices(User $user): bool
    {
        return $user->hasPermission('financial.invoices.manage');
    }

    // Special workflow permissions
    /**
     * Determine whether the user can approve financial workflows.
     */
    public function approveWorkflows(User $user): bool
    {
        return $user->hasAnyPermission([
            'financial.expenses.approve',
            'financial.manage'
        ]);
    }

    /**
     * Determine whether the user can view financial reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasAnyPermission([
            'financial.view',
            'reports.financial'
        ]);
    }

    /**
     * Determine whether the user can access sensitive financial data.
     */
    public function viewSensitiveData(User $user): bool
    {
        return $user->hasAnyPermission([
            'financial.manage',
            'financial.expenses.approve'
        ]) || $user->isAdmin();
    }

    /**
     * Determine whether the user can configure financial settings.
     */
    public function configureSettings(User $user): bool
    {
        return $user->hasPermission('financial.manage') || $user->isAdmin();
    }
}