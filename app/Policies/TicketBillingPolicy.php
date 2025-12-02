<?php

namespace App\Policies;

use App\Domains\Core\Models\User;
use App\Domains\Ticket\Models\Ticket;

class TicketBillingPolicy
{
    /**
     * Determine whether the user can view billing settings.
     */
    public function viewSettings(User $user): bool
    {
        return $user->hasPermission('billing.settings.view')
            || $user->hasPermission('billing.settings.manage')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can manage billing settings.
     */
    public function manageSettings(User $user): bool
    {
        return $user->hasPermission('billing.settings.manage')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can process pending tickets in bulk.
     */
    public function processPendingTickets(User $user): bool
    {
        return $user->hasPermission('billing.tickets.process')
            || $user->hasPermission('billing.settings.manage')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can generate an invoice from a ticket.
     */
    public function generateInvoice(User $user, Ticket $ticket): bool
    {
        // Must have permission
        if (!$user->hasPermission('billing.tickets.generate') && !$user->isAdmin()) {
            return false;
        }

        // Must be same company
        if ($ticket->company_id !== $user->company_id) {
            return false;
        }

        // Ticket must be billable
        if (!$ticket->billable) {
            return false;
        }

        // Ticket must not already be invoiced
        if ($ticket->invoice_id) {
            return false;
        }

        // Additional role-based rules
        if ($user->hasRole('technician')) {
            // Technicians can only bill their own assigned tickets
            return $ticket->assigned_to === $user->id 
                || $ticket->created_by === $user->id;
        }

        if ($user->hasRole('manager')) {
            // Managers can bill any ticket for their clients
            // (In future: check if client is assigned to manager)
            return true;
        }

        // Admins and users with explicit permission can bill any ticket
        return true;
    }

    /**
     * Determine whether the user can approve a billing invoice.
     */
    public function approveInvoice(User $user): bool
    {
        return $user->hasPermission('billing.tickets.approve')
            || $user->hasRole('manager')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can void/cancel a generated invoice.
     */
    public function voidInvoice(User $user): bool
    {
        return $user->hasPermission('billing.tickets.void')
            || $user->hasRole('manager')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can view billing reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermission('billing.reports.view')
            || $user->hasRole('manager')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can run dry-run previews.
     */
    public function runDryRun(User $user): bool
    {
        return $user->hasPermission('billing.tickets.process')
            || $user->hasPermission('billing.settings.view')
            || $user->hasRole('manager')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can modify billing amount.
     */
    public function modifyAmount(User $user): bool
    {
        return $user->hasPermission('billing.tickets.modify')
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can view audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return $user->hasPermission('billing.audit.view')
            || $user->hasRole('manager')
            || $user->isAdmin();
    }
}
