<?php

namespace App\Services\Notification\Contracts;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Support\Collection;

/**
 * Notification Dispatcher Interface
 * 
 * Defines the contract for the notification dispatcher service.
 * The dispatcher orchestrates the entire notification process by:
 * - Finding appropriate strategies for events
 * - Coordinating with notification channels
 * - Managing notification queuing and delivery
 */
interface NotificationDispatcherInterface
{
    /**
     * Dispatch notifications for a ticket event.
     * 
     * @param string $eventType The type of event that occurred
     * @param Ticket $ticket The ticket involved in the event
     * @param array $eventData Additional event-specific data
     * @return array Results of the notification dispatch
     */
    public function dispatch(string $eventType, Ticket $ticket, array $eventData = []): array;

    /**
     * Register a notification strategy.
     * 
     * @param NotificationStrategyInterface $strategy Strategy to register
     * @return self
     */
    public function registerStrategy(NotificationStrategyInterface $strategy): self;

    /**
     * Register a notification channel.
     * 
     * @param NotificationChannelInterface $channel Channel to register
     * @return self
     */
    public function registerChannel(NotificationChannelInterface $channel): self;

    /**
     * Get all registered strategies for an event type.
     * 
     * @param string $eventType Event type to get strategies for
     * @return Collection Collection of matching strategies
     */
    public function getStrategiesForEvent(string $eventType): Collection;

    /**
     * Get a specific notification channel by name.
     * 
     * @param string $channelName Name of the channel
     * @return NotificationChannelInterface|null The channel or null if not found
     */
    public function getChannel(string $channelName): ?NotificationChannelInterface;

    /**
     * Get all available notification channels.
     * 
     * @return Collection Collection of available channels
     */
    public function getAvailableChannels(): Collection;

    /**
     * Send bulk notifications for multiple tickets.
     * 
     * @param string $eventType Event type
     * @param Collection $tickets Tickets to notify about
     * @param array $eventData Event data
     * @return array Bulk notification results
     */
    public function dispatchBulk(string $eventType, Collection $tickets, array $eventData = []): array;

    /**
     * Queue notification for later delivery.
     * 
     * @param string $eventType Event type
     * @param Ticket $ticket Ticket involved
     * @param array $eventData Event data
     * @param int $delayInMinutes Minutes to delay delivery
     * @return bool True if successfully queued
     */
    public function queueNotification(string $eventType, Ticket $ticket, array $eventData = [], int $delayInMinutes = 0): bool;

    /**
     * Get notification statistics.
     * 
     * @param array $filters Optional filters for statistics
     * @return array Notification statistics
     */
    public function getStatistics(array $filters = []): array;
}