<?php

namespace App\Domains\Core\Services\Notification\Strategies;

use App\Domains\Core\Services\Notification\Contracts\NotificationStrategyInterface;
use App\Domains\Ticket\Models\Ticket;

/**
 * Ticket Created Notification Strategy
 *
 * Handles notifications when a new ticket is created.
 * Notifies assignees, watchers, and clients based on ticket visibility.
 */
class TicketCreatedStrategy implements NotificationStrategyInterface
{
    /**
     * Execute the notification strategy for ticket creation.
     */
    public function execute(Ticket $ticket, array $eventData = []): array
    {
        if (! $this->shouldExecute($ticket, $eventData)) {
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
            'channels' => ['email', 'slack'], // Primary channels for ticket creation
        ];
    }

    /**
     * Get the event type this strategy handles.
     */
    public function getEventType(): string
    {
        return 'ticket_created';
    }

    /**
     * Determine recipients for ticket creation notifications.
     */
    public function getRecipients(Ticket $ticket, array $eventData = []): array
    {
        $recipients = [
            'email' => [],
            'slack' => [],
            'sms' => [], // For high priority tickets
        ];

        // Notify assignee if assigned
        if ($ticket->assignee) {
            $recipients['email'][] = $ticket->assignee;
            $recipients['slack'][] = $ticket->assignee;

            // SMS for critical tickets
            if ($ticket->priority === 'Critical') {
                $recipients['sms'][] = $ticket->assignee;
            }
        }

        // Notify client contact if public ticket
        if ($ticket->contact && $ticket->is_public) {
            $recipients['email'][] = $ticket->contact;
        }

        // Notify watchers
        if ($ticket->watchers) {
            foreach ($ticket->watchers as $watcher) {
                if ($watcher->user) {
                    $recipients['email'][] = $watcher->user;
                    $recipients['slack'][] = $watcher->user;
                }
            }
        }

        // Notify supervisors for high/critical priority tickets
        if (in_array($ticket->priority, ['High', 'Critical'])) {
            $supervisors = $this->getSupervisors($ticket);
            foreach ($supervisors as $supervisor) {
                $recipients['email'][] = $supervisor;
                $recipients['slack'][] = $supervisor;

                if ($ticket->priority === 'Critical') {
                    $recipients['sms'][] = $supervisor;
                }
            }
        }

        return $recipients;
    }

    /**
     * Generate the notification subject.
     */
    public function getSubject(Ticket $ticket, array $eventData = []): string
    {
        $priorityPrefix = $ticket->priority === 'Critical' ? '🚨 CRITICAL: ' : '';

        return $priorityPrefix."New ticket created: #{$ticket->ticket_number}";
    }

    /**
     * Generate the notification message.
     */
    public function getMessage(Ticket $ticket, array $eventData = []): string
    {
        $message = "A new ticket has been created:\n\n";
        $message .= "Subject: {$ticket->subject}\n";
        $message .= "Priority: {$ticket->priority}\n";
        $message .= "Status: {$ticket->status}\n";

        if ($ticket->client) {
            $message .= "Client: {$ticket->client->display_name}\n";
        }

        if ($ticket->assignee) {
            $message .= "Assigned to: {$ticket->assignee->name}\n";
        }

        if ($ticket->details) {
            $message .= "\nDetails:\n".substr($ticket->details, 0, 200);
            if (strlen($ticket->details) > 200) {
                $message .= '...';
            }
        }

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
            'timestamp' => now(),
            'created_by' => $ticket->creator?->name ?? 'System',
            'ticket_url' => route('tickets.show', $ticket->id),
            'template' => 'ticket.created',
        ];
    }

    /**
     * Check if this strategy should execute.
     */
    public function shouldExecute(Ticket $ticket, array $eventData = []): bool
    {
        // Only execute for newly created tickets
        if (! $ticket->wasRecentlyCreated) {
            return false;
        }

        // Skip if notifications are disabled for this company
        if ($ticket->company && ! $ticket->company->notifications_enabled) {
            return false;
        }

        // Skip if this is a system-generated ticket without notification flag
        if (isset($eventData['skip_notifications']) && $eventData['skip_notifications']) {
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
     * Get supervisors for escalation notifications.
     */
    protected function getSupervisors(Ticket $ticket): array
    {
        // Get users with supervisor role for this company
        return \App\Models\User::where('company_id', $ticket->company_id)
            ->where('role', 'supervisor')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}
