<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Core\Services\Notification\NotificationDispatcher;
use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Ticket Notification Service (Refactored with Composition)
 *
 * This service has been refactored to use composition over inheritance.
 * Instead of containing all notification logic, it now composes with
 * the NotificationDispatcher service and delegates to specialized strategies.
 *
 * COMPOSITION BENEFITS DEMONSTRATED HERE:
 * - Single Responsibility: This service now only handles ticket-specific orchestration
 * - Dependency Injection: Composes NotificationDispatcher rather than inheriting
 * - Flexibility: Easy to swap notification strategies without changing this service
 * - Testability: Can mock the dispatcher for unit testing
 */
class TicketNotificationService
{
    protected NotificationDispatcher $notificationDispatcher;

    /**
     * Constructor injection demonstrates composition over inheritance.
     * We compose the notification dispatcher rather than inheriting from a base class.
     */
    public function __construct(NotificationDispatcher $notificationDispatcher)
    {
        $this->notificationDispatcher = $notificationDispatcher;
    }

    /**
     * Send notification for new ticket creation.
     *
     * COMPOSITION PATTERN: Delegates to NotificationDispatcher instead of handling directly.
     */
    public function notifyTicketCreated(Ticket $ticket): array
    {
        try {
            return $this->notificationDispatcher->dispatch('ticket_created', $ticket, [
                'created_by' => auth()->user()?->name ?? 'System',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send ticket creation notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send notification for ticket updates.
     *
     * COMPOSITION PATTERN: Strategy determined by event type, not inheritance hierarchy.
     */
    public function notifyTicketUpdated(Ticket $ticket, array $changes = []): array
    {
        try {
            return $this->notificationDispatcher->dispatch('ticket_updated', $ticket, [
                'changes' => $changes,
                'updated_by' => auth()->user()?->name ?? 'System',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send ticket update notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send notification for status changes.
     *
     * COMPOSITION PATTERN: Specific strategy handles status change logic.
     */
    public function notifyStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): array
    {
        try {
            return $this->notificationDispatcher->dispatch('ticket_status_changed', $ticket, [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => auth()->user()?->name ?? 'System',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send status change notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send notification for priority changes.
     */
    public function notifyPriorityChanged(Ticket $ticket, string $oldPriority, string $newPriority): array
    {
        try {
            return $this->notificationDispatcher->dispatch('priority_changed', $ticket, [
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'changed_by' => auth()->user()?->name ?? 'System',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send priority change notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send notification for assignment changes.
     */
    public function notifyAssignmentChanged(Ticket $ticket, ?User $oldAssignee, ?User $newAssignee): array
    {
        try {
            return $this->notificationDispatcher->dispatch('assignment_changed', $ticket, [
                'old_assignee' => $oldAssignee?->toArray(),
                'new_assignee' => $newAssignee?->toArray(),
                'changed_by' => auth()->user()?->name ?? 'System',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send assignment notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send notification for new replies/comments.
     */
    public function notifyNewReply(Ticket $ticket, array $reply): array
    {
        try {
            return $this->notificationDispatcher->dispatch('new_reply', $ticket, [
                'reply' => $reply,
                'author' => auth()->user()?->toArray(),
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send new reply notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send SLA breach notifications.
     *
     * COMPOSITION PATTERN: Critical notifications handled by specialized strategy.
     */
    public function notifySLABreach(Ticket $ticket, string $breachType, array $breachDetails = []): array
    {
        try {
            return $this->notificationDispatcher->dispatch('sla_breach', $ticket, [
                'breach_type' => $breachType,
                'breach_details' => $breachDetails,
                'detected_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send SLA breach notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send escalation notifications.
     */
    public function notifyEscalation(Ticket $ticket, string $escalationReason): array
    {
        try {
            return $this->notificationDispatcher->dispatch('escalation', $ticket, [
                'escalation_reason' => $escalationReason,
                'escalated_by' => auth()->user()?->name ?? 'System',
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send escalation notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Send bulk notifications for multiple tickets.
     *
     * COMPOSITION PATTERN: Bulk operations delegated to dispatcher.
     */
    public function sendBulkNotifications(Collection $tickets, string $notificationType, array $data = []): array
    {
        try {
            return $this->notificationDispatcher->dispatchBulk($notificationType, $tickets, $data);

        } catch (\Exception $e) {
            Log::error('Failed to send bulk notifications', [
                'notification_type' => $notificationType,
                'ticket_count' => $tickets->count(),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tickets_processed' => 0,
                'total_notifications_sent' => 0,
                'total_notifications_failed' => $tickets->count(),
            ];
        }
    }

    /**
     * Send ticket reminder notifications.
     */
    public function sendTicketReminder(Ticket $ticket, array $reminderData = []): array
    {
        try {
            return $this->notificationDispatcher->dispatch('ticket_reminder', $ticket, array_merge($reminderData, [
                'reminder_sent_at' => now(),
            ]));

        } catch (\Exception $e) {
            Log::error('Failed to send ticket reminder', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'notifications_sent' => 0,
                'notifications_failed' => 1,
            ];
        }
    }

    /**
     * Queue notification for later delivery.
     *
     * COMPOSITION PATTERN: Queuing logic handled by dispatcher.
     */
    public function queueNotification(string $eventType, Ticket $ticket, array $eventData = [], int $delayInMinutes = 0): bool
    {
        return $this->notificationDispatcher->queueNotification($eventType, $ticket, $eventData, $delayInMinutes);
    }

    /**
     * Get notification statistics.
     *
     * COMPOSITION PATTERN: Statistics aggregated from composed services.
     */
    public function getNotificationStatistics(array $filters = []): array
    {
        $dispatcherStats = $this->notificationDispatcher->getStatistics($filters);

        // Add ticket-specific statistics
        return array_merge($dispatcherStats, [
            'service' => 'TicketNotificationService',
            'composition_pattern' => 'Uses NotificationDispatcher composition',
            'available_events' => [
                'ticket_created',
                'ticket_updated',
                'ticket_status_changed',
                'priority_changed',
                'assignment_changed',
                'new_reply',
                'sla_breach',
                'escalation',
                'ticket_reminder',
            ],
        ]);
    }

    /**
     * Test notification system with a sample ticket.
     *
     * Useful for verifying the composition is working correctly.
     */
    public function testNotificationSystem(Ticket $ticket): array
    {
        return [
            'dispatcher_info' => $this->notificationDispatcher->getChannelInfo(),
            'available_channels' => $this->notificationDispatcher->getAvailableChannels()->keys()->toArray(),
            'test_notification' => $this->notificationDispatcher->dispatch('ticket_created', $ticket, [
                'test_mode' => true,
                'timestamp' => now(),
            ]),
        ];
    }

    /**
     * Set a custom notification dispatcher (useful for testing).
     *
     * COMPOSITION PATTERN: Easy to inject different implementations.
     */
    public function setNotificationDispatcher(NotificationDispatcher $dispatcher): self
    {
        $this->notificationDispatcher = $dispatcher;

        return $this;
    }

    /**
     * Get the current notification dispatcher.
     */
    public function getNotificationDispatcher(): NotificationDispatcher
    {
        return $this->notificationDispatcher;
    }
}
