<?php

namespace App\Domains\Core\Services\Notification\Contracts;

use App\Domains\Ticket\Models\Ticket;

/**
 * Notification Strategy Interface
 * 
 * Defines the contract for different notification strategies.
 * Each strategy handles a specific type of ticket event (created, updated, status changed, etc.)
 * and determines who should be notified and how.
 */
interface NotificationStrategyInterface
{
    /**
     * Execute the notification strategy for a ticket event.
     * 
     * @param Ticket $ticket The ticket involved in the event
     * @param array $eventData Additional event-specific data
     * @return array Notification instructions for the dispatcher
     */
    public function execute(Ticket $ticket, array $eventData = []): array;

    /**
     * Get the event type this strategy handles.
     * 
     * @return string Event type identifier (e.g., 'ticket_created', 'status_changed')
     */
    public function getEventType(): string;

    /**
     * Determine recipients for this notification strategy.
     * 
     * @param Ticket $ticket The ticket to determine recipients for
     * @param array $eventData Additional event data
     * @return array Recipients organized by channel type
     */
    public function getRecipients(Ticket $ticket, array $eventData = []): array;

    /**
     * Generate the notification subject for this strategy.
     * 
     * @param Ticket $ticket The ticket involved
     * @param array $eventData Additional event data
     * @return string The notification subject
     */
    public function getSubject(Ticket $ticket, array $eventData = []): string;

    /**
     * Generate the notification message for this strategy.
     * 
     * @param Ticket $ticket The ticket involved
     * @param array $eventData Additional event data
     * @return string The notification message
     */
    public function getMessage(Ticket $ticket, array $eventData = []): string;

    /**
     * Get additional data for the notification.
     * 
     * @param Ticket $ticket The ticket involved
     * @param array $eventData Event-specific data
     * @return array Additional notification data
     */
    public function getNotificationData(Ticket $ticket, array $eventData = []): array;

    /**
     * Check if this strategy should execute for the given ticket and event.
     * 
     * @param Ticket $ticket The ticket to check
     * @param array $eventData Event data
     * @return bool True if this strategy should execute
     */
    public function shouldExecute(Ticket $ticket, array $eventData = []): bool;

    /**
     * Get the priority level for this notification strategy.
     * 
     * @return string Priority level (low, normal, high, critical)
     */
    public function getPriority(): string;
}