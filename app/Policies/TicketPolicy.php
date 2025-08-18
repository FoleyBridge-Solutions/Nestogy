<?php

namespace App\Policies;

use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view tickets
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Users can view tickets from their company
        return $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create tickets
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Admins can update any ticket in their company
        if ($user->isAdmin() && $user->company_id === $ticket->company_id) {
            return true;
        }

        // Techs can update tickets assigned to them or created by them
        if ($user->isTech() && $user->company_id === $ticket->company_id) {
            return $ticket->assigned_to === $user->id || $ticket->created_by === $user->id;
        }

        // Users can update their own tickets
        return $user->id === $ticket->created_by && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Only admins and managers can delete tickets
        return $user->hasRole(['admin', 'manager']) && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        // Only admins and managers can restore tickets
        return $user->hasRole(['admin', 'manager']) && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        // Only admins can permanently delete tickets
        return $user->isAdmin() && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can add a reply to the ticket.
     */
    public function addReply(User $user, Ticket $ticket): bool
    {
        // Users can reply to tickets from their company
        return $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can assign the ticket.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        // Only admins and managers can assign tickets
        return $user->hasRole(['admin', 'manager']) && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can update the ticket status.
     */
    public function updateStatus(User $user, Ticket $ticket): bool
    {
        // Admins and managers can update any ticket status
        if ($user->hasRole(['admin', 'manager']) && $user->company_id === $ticket->company_id) {
            return true;
        }

        // Technicians can update status of tickets assigned to them
        if ($user->hasRole('technician') && $ticket->assigned_to === $user->id) {
            return true;
        }

        // Users can update status of their own tickets
        return $user->id === $ticket->created_by && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can update the ticket priority.
     */
    public function updatePriority(User $user, Ticket $ticket): bool
    {
        // Only admins, managers, and technicians can update priority
        return $user->hasRole(['admin', 'manager', 'technician']) && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can schedule the ticket.
     */
    public function schedule(User $user, Ticket $ticket): bool
    {
        // Only admins, managers, and technicians can schedule tickets
        return $user->hasRole(['admin', 'manager', 'technician']) && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can add watchers to the ticket.
     */
    public function addWatcher(User $user, Ticket $ticket): bool
    {
        // Users can add watchers to tickets from their company
        return $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can merge tickets.
     */
    public function merge(User $user, Ticket $ticket): bool
    {
        // Only admins and managers can merge tickets
        return $user->hasRole(['admin', 'manager']) && $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can upload attachments.
     */
    public function uploadAttachment(User $user, Ticket $ticket): bool
    {
        // Users can upload attachments to tickets from their company
        return $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can generate PDF.
     */
    public function generatePdf(User $user, Ticket $ticket): bool
    {
        // Users can generate PDF for tickets from their company
        return $user->company_id === $ticket->company_id;
    }

    /**
     * Determine whether the user can export tickets.
     */
    public function exportCsv(User $user): bool
    {
        // Only admins and managers can export tickets
        return $user->hasRole(['admin', 'manager']);
    }
}