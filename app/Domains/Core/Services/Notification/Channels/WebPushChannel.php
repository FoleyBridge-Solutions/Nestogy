<?php

namespace App\Domains\Core\Services\Notification\Channels;

use App\Domains\Core\Services\Notification\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel as BaseWebPushChannel;

/**
 * Web Push Notification Channel
 *
 * Handles web push notifications for ticket notifications using Laravel's WebPush system.
 * Integrates with the existing notification dispatcher architecture.
 */
class WebPushChannel implements NotificationChannelInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'vapid_public_key' => config('webpush.vapid.public_key'),
            'vapid_private_key' => config('webpush.vapid.private_key'),
        ], $config);
    }

    /**
     * Send web push notification.
     */
    public function send(array $recipients, string $subject, string $message, array $data = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'channel' => 'webpush',
        ];

        foreach ($recipients as $recipient) {
            try {
                // Check if recipient has push subscriptions
                if (!method_exists($recipient, 'pushSubscriptions') || 
                    !$recipient->pushSubscriptions()->exists()) {
                    continue;
                }

                // Create WebPush message
                $webPushMessage = (new WebPushMessage)
                    ->title($subject)
                    ->body($this->formatMessage($message, $data))
                    ->icon($data['icon'] ?? '/logo.png')
                    ->badge($data['badge'] ?? '/logo.png')
                    ->tag($data['tag'] ?? 'nestogy-notification-' . time())
                    ->data([
                        'url' => $data['url'] ?? '/',
                        'ticket_id' => $data['ticket']->id ?? null,
                        'type' => $data['type'] ?? 'default',
                    ]);

                // Add require interaction for critical notifications
                if (isset($data['priority']) && in_array($data['priority'], ['Critical', 'Urgent'])) {
                    $webPushMessage->requireInteraction();
                }

                // Send via Laravel Notification system using anonymous notification class
                $recipient->notify(new class($webPushMessage) extends \Illuminate\Notifications\Notification {
                    protected $webPushMessage;
                    
                    public function __construct($webPushMessage) {
                        $this->webPushMessage = $webPushMessage;
                    }
                    
                    public function via($notifiable) {
                        return [BaseWebPushChannel::class];
                    }
                    
                    public function toWebPush($notifiable, $notification) {
                        return $this->webPushMessage;
                    }
                });

                $results['sent']++;

                Log::info('Web push notification sent', [
                    'recipient_id' => $recipient->id ?? null,
                    'subject' => $subject,
                    'type' => $data['type'] ?? 'default',
                ]);

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recipient' => $recipient->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::error('Web push notification failed', [
                    'recipient_id' => $recipient->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Check if web push channel is available.
     */
    public function isAvailable(): bool
    {
        return !empty($this->config['vapid_public_key']) && 
               !empty($this->config['vapid_private_key']);
    }

    /**
     * Get channel name.
     */
    public function getName(): string
    {
        return 'webpush';
    }

    /**
     * Validate and format recipients for web push delivery.
     */
    public function validateRecipients(array $recipients): array
    {
        return array_filter($recipients, function($recipient) {
            return method_exists($recipient, 'pushSubscriptions') &&
                   $recipient->pushSubscriptions()->exists();
        });
    }

    /**
     * Get required configuration for web push channel.
     */
    public function getRequiredConfig(): array
    {
        return [
            'vapid_public_key' => 'VAPID public key for web push',
            'vapid_private_key' => 'VAPID private key for web push',
        ];
    }

    /**
     * Format message for web push delivery.
     */
    public function formatMessage(string $message, array $data = []): string
    {
        // Web push has character limits (typically 120 chars for body)
        $formattedMessage = mb_substr($message, 0, 120);
        
        if (mb_strlen($message) > 120) {
            $formattedMessage .= '...';
        }
        
        return $formattedMessage;
    }

    /**
     * Set web push configuration.
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Get current configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
