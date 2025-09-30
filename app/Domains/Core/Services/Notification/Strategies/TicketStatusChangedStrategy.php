<?php

namespace App\Domains\Core\Services\Notification\Strategies;

use App\Domains\Core\Services\Notification\Contracts\NotificationStrategyInterface;
use App\Domains\Ticket\Models\Ticket;

/**
 * Ticket Status Changed Notification Strategy
 * 
 * Handles notifications when a ticket's status changes.
 * Different recipients and messaging based on status transitions.
 */
class TicketStatusChangedStrategy implements NotificationStrategyInterface
{
    /**
     * Execute the notification strategy for status changes.
     */
    public function execute(Ticket $ticket, array $eventData = []): array
    {
        if (!$this->shouldExecute($ticket, $eventData)) {
            return ['skipped' => true, 'reason' => 'Strategy execution conditions not met'];
        }

        $recipients = $this->getRecipients($ticket, $eventData);
        $subject = $this->getSubject($ticket, $eventData);
        $message = $this->getMessage($ticket, $eventData);
        $notificationData = $this->getNotificationData($ticket, $eventData);

        return [
            'event_type' => $this->getEventType(),
            'priority' => $this->getPriority(),
            'recipients' => $recipients,
            'subject' => $subject,
            'message' => $message,
            'data' => $notificationData,
            'channels' => $this->getChannelsForStatus($eventData['new_status'] ?? $ticket->status),
        ];
    }

    /**
     * Get the event type this strategy handles.
     */
    public function getEventType(): string
    {
        return 'ticket_status_changed';
    }

    /**
     * Determine recipients for status change notifications.
     */
    public function getRecipients(Ticket $ticket, array $eventData = []): array
    {
        $recipients = [
            'email' => [],
            'slack' => [],
            'sms' => [],
        ];

        $newStatus = $eventData['new_status'] ?? $ticket->status;
        $oldStatus = $eventData['old_status'] ?? '';

        // Always notify assignee
        if ($ticket->assignee) {
            $recipients['email'][] = $ticket->assignee;
            $recipients['slack'][] = $ticket->assignee;
        }

        // Notify client on resolution/closure
        if ($ticket->contact && in_array($newStatus, ['resolved', 'closed'])) {
            $recipients['email'][] = $ticket->contact;
        }

        // Notify watchers with status change preferences
        if ($ticket->watchers) {
            foreach ($ticket->watchers as $watcher) {
                if ($watcher->user && $this->watcherWantsStatusNotifications($watcher)) {
                    $recipients['email'][] = $watcher->user;
                    $recipients['slack'][] = $watcher->user;
                }
            }
        }

        // Notify supervisors for certain status transitions
        if ($this->requiresSupervisorNotification($oldStatus, $newStatus)) {
            $supervisors = $this->getSupervisors($ticket);
            foreach ($supervisors as $supervisor) {
                $recipients['email'][] = $supervisor;
                $recipients['slack'][] = $supervisor;
            }
        }

        return $recipients;
    }

    /**
     * Generate the notification subject.
     */
    public function getSubject(Ticket $ticket, array $eventData = []): string
    {
        $newStatus = $eventData['new_status'] ?? $ticket->status;
        $statusLabel = ucfirst($newStatus);
        
        return "Ticket {$statusLabel}: #{$ticket->ticket_number}";
    }

    /**
     * Generate the notification message.
     */
    public function getMessage(Ticket $ticket, array $eventData = []): string
    {
        $newStatus = $eventData['new_status'] ?? $ticket->status;
        $oldStatus = $eventData['old_status'] ?? 'Unknown';
        
        $message = "Ticket status has been changed:\n\n";
        $message .= "From: {$oldStatus}\n";
        $message .= "To: {$newStatus}\n\n";
        
        $message .= "Ticket Details:\n";
        $message .= "Subject: {$ticket->subject}\n";
        $message .= "Priority: {$ticket->priority}\n";
        
        if ($ticket->client) {
            $message .= "Client: {$ticket->client->display_name}\n";
        }
        
        if ($ticket->assignee) {
            $message .= "Assigned to: {$ticket->assignee->name}\n";
        }

        // Add status-specific messaging
        $message .= $this->getStatusSpecificMessage($newStatus, $ticket);

        return $message;
    }

    /**
     * Get additional notification data.
     */
    public function getNotificationData(Ticket $ticket, array $eventData = []): array
    {
        return [
            'ticket' => $ticket,
            'event_type' => $this->getEventType(),
            'old_status' => $eventData['old_status'] ?? '',
            'new_status' => $eventData['new_status'] ?? $ticket->status,
            'changed_by' => $eventData['changed_by'] ?? auth()->user()?->name ?? 'System',
            'timestamp' => now(),
            'ticket_url' => route('tickets.show', $ticket->id),
            'template' => 'ticket.status_changed',
        ];
    }

    /**
     * Check if this strategy should execute.
     */
    public function shouldExecute(Ticket $ticket, array $eventData = []): bool
    {
        // Must have both old and new status
        if (!isset($eventData['old_status']) || !isset($eventData['new_status'])) {
            return false;
        }

        // Skip if status hasn't actually changed
        if ($eventData['old_status'] === $eventData['new_status']) {
            return false;
        }

        // Skip if notifications are disabled
        if ($ticket->company && !$ticket->company->notifications_enabled) {
            return false;
        }

        return true;
    }

    /**
     * Get the priority level for this notification strategy.
     */
    public function getPriority(): string
    {
        return 'normal';
    }

    /**
     * Get appropriate channels based on status.
     */
    protected function getChannelsForStatus(string $status): array
    {
        return match ($status) {
            'resolved', 'closed' => ['email'], // Less urgent, email only
            'in_progress', 'pending' => ['email', 'slack'], // Standard channels
            'escalated' => ['email', 'slack', 'sms'], // All channels for escalations
            default => ['email', 'slack']
        };
    }

    /**
     * Check if watcher wants status change notifications.
     */
    protected function watcherWantsStatusNotifications($watcher): bool
    {
        $preferences = $watcher->notification_preferences ?? [];
        return $preferences['status_changes'] ?? true;
    }

    /**
     * Check if status transition requires supervisor notification.
     */
    protected function requiresSupervisorNotification(string $oldStatus, string $newStatus): bool
    {
        // Notify supervisors when tickets are escalated or reopened
        return in_array($newStatus, ['escalated']) || 
               ($oldStatus === 'closed' && $newStatus !== 'closed');
    }

    /**
     * Get status-specific message content.
     */
    protected function getStatusSpecificMessage(string $status, Ticket $ticket): string
    {
        return match ($status) {
            'resolved' => "\n\nThis ticket has been resolved. Please review and close if satisfied.",
            'closed' => "\n\nThis ticket has been closed.",
            'escalated' => "\n\n⚠️ This ticket has been escalated and requires immediate attention.",
            'in_progress' => "\n\nWork has begun on this ticket.",
            'pending' => "\n\nThis ticket is pending additional information or resources.",
            default => ''
        };
    }

    /**
     * Get supervisors for escalation notifications.
     */
    protected function getSupervisors(Ticket $ticket): array
    {
        return \App\Models\User::where('company_id', $ticket->company_id)
            ->where('role', 'supervisor')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}