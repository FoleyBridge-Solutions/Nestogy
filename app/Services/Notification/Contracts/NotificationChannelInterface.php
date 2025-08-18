<?php

namespace App\Services\Notification\Contracts;

/**
 * Notification Channel Interface
 * 
 * Defines the contract for notification delivery channels.
 * Each channel (Email, SMS, Slack, etc.) implements this interface
 * to provide a consistent API for sending notifications.
 */
interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel.
     * 
     * @param array $recipients List of recipients for this channel
     * @param string $subject The notification subject/title
     * @param string $message The notification content
     * @param array $data Additional data for the notification
     * @return array Results with success/failure information
     */
    public function send(array $recipients, string $subject, string $message, array $data = []): array;

    /**
     * Check if this channel is available/configured.
     * 
     * @return bool True if channel is ready to send notifications
     */
    public function isAvailable(): bool;

    /**
     * Get the channel name/identifier.
     * 
     * @return string Channel identifier (e.g., 'email', 'sms', 'slack')
     */
    public function getName(): string;

    /**
     * Validate recipients for this channel.
     * 
     * @param array $recipients Recipients to validate
     * @return array Valid recipients for this channel
     */
    public function validateRecipients(array $recipients): array;

    /**
     * Get channel configuration requirements.
     * 
     * @return array Required configuration keys for this channel
     */
    public function getRequiredConfig(): array;

    /**
     * Format message for this specific channel.
     * 
     * @param string $message Raw message content
     * @param array $data Additional formatting data
     * @return string Formatted message for this channel
     */
    public function formatMessage(string $message, array $data = []): string;
}