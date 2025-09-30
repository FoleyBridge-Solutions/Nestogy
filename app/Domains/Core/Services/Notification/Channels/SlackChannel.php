<?php

namespace App\Domains\Core\Services\Notification\Channels;

use App\Domains\Core\Services\Notification\Contracts\NotificationChannelInterface;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Slack Notification Channel
 *
 * Handles Slack notifications for tickets through webhooks and Slack API.
 * Supports both webhook notifications and direct messages to specific users.
 */
class SlackChannel implements NotificationChannelInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'webhook_url' => config('services.slack.webhook_url'),
            'bot_token' => config('services.slack.bot_token'),
            'default_channel' => config('services.slack.default_channel', '#tickets'),
            'username' => config('services.slack.username', 'Nestogy Bot'),
            'icon_emoji' => config('services.slack.icon_emoji', ':robot_face:'),
            'mention_users' => true,
            'use_rich_formatting' => true,
        ], $config);
    }

    /**
     * Send Slack notification.
     */
    public function send(array $recipients, string $subject, string $message, array $data = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'channel' => 'slack',
        ];

        if (! $this->isAvailable()) {
            $results['errors'][] = 'Slack channel is not properly configured';

            return $results;
        }

        $validRecipients = $this->validateRecipients($recipients);

        // Group recipients by delivery method
        $webhookRecipients = [];
        $directMessageRecipients = [];

        foreach ($validRecipients as $recipient) {
            if (isset($recipient['slack_user_id'])) {
                $directMessageRecipients[] = $recipient;
            } else {
                $webhookRecipients[] = $recipient;
            }
        }

        // Send webhook notifications (to channels)
        if (! empty($webhookRecipients)) {
            $webhookResult = $this->sendWebhookNotification($webhookRecipients, $subject, $message, $data);
            $results['sent'] += $webhookResult['sent'];
            $results['failed'] += $webhookResult['failed'];
            $results['errors'] = array_merge($results['errors'], $webhookResult['errors']);
        }

        // Send direct message notifications
        foreach ($directMessageRecipients as $recipient) {
            try {
                $success = $this->sendDirectMessage($recipient, $subject, $message, $data);

                if ($success) {
                    $results['sent']++;
                    Log::info('Slack DM sent', [
                        'channel' => 'slack',
                        'recipient' => $recipient['slack_user_id'],
                        'ticket_id' => $data['ticket']->id ?? null,
                    ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'recipient' => $recipient['name'] ?? $recipient['slack_user_id'],
                        'error' => 'Direct message delivery failed',
                    ];
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recipient' => $recipient['name'] ?? $recipient['slack_user_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::error('Slack DM failed', [
                    'channel' => 'slack',
                    'recipient' => $recipient['slack_user_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'ticket_id' => $data['ticket']->id ?? null,
                ]);
            }
        }

        return $results;
    }

    /**
     * Check if Slack channel is available.
     */
    public function isAvailable(): bool
    {
        return ! empty($this->config['webhook_url']) || ! empty($this->config['bot_token']);
    }

    /**
     * Get channel name.
     */
    public function getName(): string
    {
        return 'slack';
    }

    /**
     * Validate and format recipients for Slack delivery.
     */
    public function validateRecipients(array $recipients): array
    {
        $validRecipients = [];

        foreach ($recipients as $recipient) {
            $slackData = $this->extractSlackData($recipient);

            if ($slackData) {
                $validRecipients[] = $slackData;
            }
        }

        return $validRecipients;
    }

    /**
     * Get required configuration for Slack channel.
     */
    public function getRequiredConfig(): array
    {
        return [
            'webhook_url' => 'Slack webhook URL for channel notifications',
            'bot_token' => 'Slack bot token for direct messages (optional)',
            'default_channel' => 'Default Slack channel for notifications',
        ];
    }

    /**
     * Format message for Slack delivery.
     */
    public function formatMessage(string $message, array $data = []): string
    {
        if (! $this->config['use_rich_formatting']) {
            return $message;
        }

        // Use Slack markdown formatting
        $formattedMessage = $message;

        // Add ticket information if available
        if (isset($data['ticket'])) {
            $ticket = $data['ticket'];
            $ticketUrl = route('tickets.show', $ticket->id); // Assuming this route exists

            $ticketInfo = "\n\n*Ticket Details:*\n".
                          "• *Number:* <{$ticketUrl}|#{$ticket->ticket_number}>\n".
                          "• *Subject:* {$ticket->subject}\n".
                          "• *Priority:* {$ticket->priority}\n".
                          "• *Status:* {$ticket->status}\n";

            if ($ticket->assignee) {
                $ticketInfo .= "• *Assigned to:* {$ticket->assignee->name}\n";
            }

            if ($ticket->client) {
                $ticketInfo .= "• *Client:* {$ticket->client->display_name}\n";
            }

            $formattedMessage .= $ticketInfo;
        }

        return $formattedMessage;
    }

    /**
     * Send webhook notification to Slack channel.
     */
    protected function sendWebhookNotification(array $recipients, string $subject, string $message, array $data = []): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        try {
            $slackMessage = $this->buildSlackMessage($subject, $message, $data);

            $response = Http::post($this->config['webhook_url'], $slackMessage);

            if ($response->successful()) {
                $results['sent'] = count($recipients);
                Log::info('Slack webhook notification sent', [
                    'channel' => 'slack',
                    'webhook_url' => substr($this->config['webhook_url'], 0, 50).'...',
                    'recipients_count' => count($recipients),
                    'ticket_id' => $data['ticket']->id ?? null,
                ]);
            } else {
                $results['failed'] = count($recipients);
                $results['errors'][] = [
                    'type' => 'webhook',
                    'error' => 'Webhook request failed: '.$response->status(),
                ];
            }

        } catch (\Exception $e) {
            $results['failed'] = count($recipients);
            $results['errors'][] = [
                'type' => 'webhook',
                'error' => $e->getMessage(),
            ];

            Log::error('Slack webhook failed', [
                'channel' => 'slack',
                'error' => $e->getMessage(),
                'ticket_id' => $data['ticket']->id ?? null,
            ]);
        }

        return $results;
    }

    /**
     * Send direct message to specific Slack user.
     */
    protected function sendDirectMessage(array $recipient, string $subject, string $message, array $data = []): bool
    {
        if (empty($this->config['bot_token'])) {
            throw new \Exception('Bot token required for direct messages');
        }

        try {
            $slackMessage = $this->formatMessage($message, $data);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['bot_token'],
                'Content-Type' => 'application/json',
            ])->post('https://slack.com/api/chat.postMessage', [
                'channel' => $recipient['slack_user_id'],
                'text' => $subject,
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $slackMessage,
                        ],
                    ],
                ],
            ]);

            $responseData = $response->json();

            return $responseData['ok'] ?? false;

        } catch (\Exception $e) {
            Log::error('Slack direct message failed', [
                'recipient' => $recipient['slack_user_id'],
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build Slack message structure for webhook.
     */
    protected function buildSlackMessage(string $subject, string $message, array $data = []): array
    {
        $formattedMessage = $this->formatMessage($message, $data);

        $slackMessage = [
            'username' => $this->config['username'],
            'icon_emoji' => $this->config['icon_emoji'],
            'channel' => $this->config['default_channel'],
            'text' => $subject,
        ];

        if ($this->config['use_rich_formatting']) {
            $color = $this->getColorForTicket($data['ticket'] ?? null);

            $slackMessage['attachments'] = [
                [
                    'color' => $color,
                    'text' => $formattedMessage,
                    'ts' => time(),
                ],
            ];
        } else {
            $slackMessage['text'] = $subject."\n\n".$formattedMessage;
        }

        return $slackMessage;
    }

    /**
     * Extract Slack data from various recipient types.
     */
    protected function extractSlackData($recipient): ?array
    {
        if (is_string($recipient)) {
            // Assume it's a Slack channel or user ID
            return ['channel' => $recipient, 'type' => 'channel'];
        }

        if (is_array($recipient)) {
            if (isset($recipient['slack_user_id'])) {
                return [
                    'slack_user_id' => $recipient['slack_user_id'],
                    'name' => $recipient['name'] ?? null,
                    'type' => 'user',
                ];
            }

            if (isset($recipient['slack_channel'])) {
                return [
                    'channel' => $recipient['slack_channel'],
                    'type' => 'channel',
                ];
            }
        }

        if ($recipient instanceof User) {
            // Check if user has Slack integration
            if (! empty($recipient->slack_user_id)) {
                return [
                    'slack_user_id' => $recipient->slack_user_id,
                    'name' => $recipient->name,
                    'type' => 'user',
                    'user_id' => $recipient->id,
                ];
            }
        }

        // Default to channel notification
        return ['channel' => $this->config['default_channel'], 'type' => 'channel'];
    }

    /**
     * Get color for ticket based on priority/status.
     */
    protected function getColorForTicket($ticket): string
    {
        if (! $ticket) {
            return '#36a64f'; // Default green
        }

        return match ($ticket->priority ?? 'Medium') {
            'Critical' => '#ff0000', // Red
            'High' => '#ff9900',     // Orange
            'Medium' => '#ffcc00',   // Yellow
            'Low' => '#36a64f',      // Green
            default => '#36a64f'
        };
    }

    /**
     * Set configuration.
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
