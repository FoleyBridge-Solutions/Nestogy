<?php

namespace App\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Models\InAppNotification;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationService
{
    public function notifyTicketCreated(Ticket $ticket): void
    {
        $recipients = $this->getRecipientsForEvent('ticket_created', $ticket);

        foreach ($recipients as $user) {
            $prefs = NotificationPreference::getOrCreateForUser($user);

            if ($prefs->shouldSendInApp('ticket_created')) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'name' => 'Ticket Created',
                    'type' => 'ticket_created',
                    'title' => 'New Ticket Created',
                    'message' => "Ticket #{$ticket->number}: {$ticket->subject}",
                    'link' => route('tickets.show', $ticket->id),
                    'icon' => 'fas fa-ticket-alt',
                    'color' => 'blue',
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    public function notifyTicketAssigned(Ticket $ticket, User $assignedTo): void
    {
        $prefs = NotificationPreference::getOrCreateForUser($assignedTo);

        if ($prefs->shouldSendInApp('ticket_assigned')) {
            InAppNotification::create([
                'user_id' => $assignedTo->id,
                'name' => 'Ticket Assigned',
                'type' => 'ticket_assigned',
                'title' => 'Ticket Assigned to You',
                'message' => "Ticket #{$ticket->number}: {$ticket->subject}",
                'link' => route('tickets.show', $ticket->id),
                'icon' => 'fas fa-user-check',
                'color' => 'green',
                'ticket_id' => $ticket->id,
            ]);
        }
    }

    public function notifyTicketStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        $recipients = $this->getRecipientsForEvent('ticket_status_changed', $ticket);

        foreach ($recipients as $user) {
            $prefs = NotificationPreference::getOrCreateForUser($user);

            if ($prefs->shouldSendInApp('ticket_status_changed')) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'name' => 'Ticket Status Changed',
                    'type' => 'ticket_status_changed',
                    'title' => 'Ticket Status Updated',
                    'message' => "Ticket #{$ticket->number} changed from {$oldStatus} to {$newStatus}",
                    'link' => route('tickets.show', $ticket->id),
                    'icon' => 'fas fa-exchange-alt',
                    'color' => 'yellow',
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    public function notifyTicketResolved(Ticket $ticket): void
    {
        $recipients = $this->getRecipientsForEvent('ticket_resolved', $ticket);

        foreach ($recipients as $user) {
            $prefs = NotificationPreference::getOrCreateForUser($user);

            if ($prefs->shouldSendInApp('ticket_resolved')) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'name' => 'Ticket Resolved',
                    'type' => 'ticket_resolved',
                    'title' => 'Ticket Resolved',
                    'message' => "Ticket #{$ticket->number}: {$ticket->subject}",
                    'link' => route('tickets.show', $ticket->id),
                    'icon' => 'fas fa-check-circle',
                    'color' => 'green',
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    public function notifyTicketCommentAdded(Ticket $ticket, $comment): void
    {
        $recipients = $this->getRecipientsForEvent('ticket_comment_added', $ticket);

        foreach ($recipients as $user) {
            if ($user->id === ($comment->author_id ?? null)) {
                continue;
            }

            $prefs = NotificationPreference::getOrCreateForUser($user);

            if ($prefs->shouldSendInApp('ticket_comment_added')) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'name' => 'Ticket Comment Added',
                    'type' => 'ticket_comment_added',
                    'title' => 'New Comment Added',
                    'message' => "New comment on Ticket #{$ticket->number}",
                    'link' => route('tickets.show', $ticket->id),
                    'icon' => 'fas fa-comment',
                    'color' => 'purple',
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    public function notifySLABreachWarning(Ticket $ticket, int $hoursRemaining): void
    {
        $recipients = $this->getRecipientsForEvent('sla_breach_warning', $ticket);

        foreach ($recipients as $user) {
            $prefs = NotificationPreference::getOrCreateForUser($user);

            if ($prefs->shouldSendInApp('sla_breach_warning')) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'name' => 'SLA Breach Warning',
                    'type' => 'sla_breach_warning',
                    'title' => 'SLA Breach Warning',
                    'message' => "Ticket #{$ticket->number} - {$hoursRemaining}h remaining",
                    'link' => route('tickets.show', $ticket->id),
                    'icon' => 'fas fa-exclamation-triangle',
                    'color' => 'yellow',
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    public function notifySLABreached(Ticket $ticket, int $hoursOverdue): void
    {
        $recipients = $this->getRecipientsForEvent('sla_breached', $ticket);

        foreach ($recipients as $user) {
            $prefs = NotificationPreference::getOrCreateForUser($user);

            if ($prefs->shouldSendInApp('sla_breached')) {
                InAppNotification::create([
                    'user_id' => $user->id,
                    'name' => 'SLA Breached',
                    'type' => 'sla_breached',
                    'title' => 'SLA BREACHED - Critical',
                    'message' => "Ticket #{$ticket->number} - {$hoursOverdue}h overdue",
                    'link' => route('tickets.show', $ticket->id),
                    'icon' => 'fas fa-exclamation-circle',
                    'color' => 'red',
                    'ticket_id' => $ticket->id,
                ]);
            }
        }
    }

    protected function getRecipientsForEvent(string $eventType, Ticket $ticket): array
    {
        $recipients = collect();

        if ($ticket->created_by) {
            $recipients->push(User::find($ticket->created_by));
        }

        if ($ticket->assigned_to) {
            $recipients->push(User::find($ticket->assigned_to));
        }

        $watchers = $ticket->watchers()->with('user')->get()->pluck('user');
        $recipients = $recipients->merge($watchers);

        if (in_array($eventType, ['sla_breach_warning', 'sla_breached'])) {
            $managers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager']);
            })->where('company_id', $ticket->company_id)->get();
            
            $recipients = $recipients->merge($managers);
        }

        return $recipients->filter()->unique('id')->values()->all();
    }
}
