<?php

namespace App\Domains\Core\Services\Notification\Channels;

use App\Domains\Core\Services\Notification\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Email Notification Channel
 * 
 * Handles email delivery for ticket notifications using Laravel's Mail system.
 * Supports both user and contact recipients with proper validation and formatting.
 */
class EmailChannel implements NotificationChannelInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'from_name' => config('app.name'),
            'from_email' => config('mail.from.address'),
            'queue' => true,
            'template_path' => 'emails.tickets',
        ], $config);
    }

    /**
     * Send email notification.
     */
    public function send(array $recipients, string $subject, string $message, array $data = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
            'channel' => 'email'
        ];

        $validRecipients = $this->validateRecipients($recipients);

        foreach ($validRecipients as $recipient) {
            try {
                $emailData = array_merge($data, [
                    'subject' => $subject,
                    'message' => $this->formatMessage($message, $data),
                    'recipient' => $recipient,
                ]);

                if ($this->config['queue']) {
                    Mail::to($recipient['email'])
                        ->queue(new \App\Mail\TicketNotificationMail($emailData));
                } else {
                    Mail::to($recipient['email'])
                        ->send(new \App\Mail\TicketNotificationMail($emailData));
                }

                $results['sent']++;

                Log::info('Email notification sent', [
                    'channel' => 'email',
                    'recipient' => $recipient['email'],
                    'subject' => $subject,
                    'ticket_id' => $data['ticket']->id ?? null
                ]);

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'recipient' => $recipient['email'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];

                Log::error('Email notification failed', [
                    'channel' => 'email',
                    'recipient' => $recipient['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'ticket_id' => $data['ticket']->id ?? null
                ]);
            }
        }

        return $results;
    }

    /**
     * Check if email channel is available.
     */
    public function isAvailable(): bool
    {
        try {
            return !empty($this->config['from_email']) && 
                   !empty(config('mail.default')) &&
                   !empty(config('mail.mailers.' . config('mail.default')));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get channel name.
     */
    public function getName(): string
    {
        return 'email';
    }

    /**
     * Validate and format recipients for email delivery.
     */
    public function validateRecipients(array $recipients): array
    {
        $validRecipients = [];

        foreach ($recipients as $recipient) {
            $emailData = $this->extractEmailData($recipient);
            
            if ($emailData && $this->isValidEmail($emailData['email'])) {
                $validRecipients[] = $emailData;
            }
        }

        return $validRecipients;
    }

    /**
     * Get required configuration for email channel.
     */
    public function getRequiredConfig(): array
    {
        return [
            'from_email' => 'Sender email address',
            'from_name' => 'Sender name',
            'mail_driver' => 'Mail driver configuration'
        ];
    }

    /**
     * Format message for email delivery.
     */
    public function formatMessage(string $message, array $data = []): string
    {
        // Add HTML formatting for email
        $formattedMessage = nl2br(htmlspecialchars($message));
        
        // Add ticket information if available
        if (isset($data['ticket'])) {
            $ticket = $data['ticket'];
            $ticketInfo = "\n\n" . 
                          "Ticket Details:\n" .
                          "- Number: #{$ticket->ticket_number}\n" .
                          "- Subject: {$ticket->subject}\n" .
                          "- Priority: {$ticket->priority}\n" .
                          "- Status: {$ticket->status}\n";
            
            if ($ticket->assignee) {
                $ticketInfo .= "- Assigned to: {$ticket->assignee->name}\n";
            }
            
            $formattedMessage .= nl2br(htmlspecialchars($ticketInfo));
        }

        return $formattedMessage;
    }

    /**
     * Extract email data from various recipient types.
     */
    protected function extractEmailData($recipient): ?array
    {
        if (is_string($recipient)) {
            // Direct email string
            return ['email' => $recipient, 'name' => null, 'type' => 'email'];
        }

        if (is_array($recipient) && isset($recipient['email'])) {
            // Array with email key
            return [
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
                'type' => $recipient['type'] ?? 'array'
            ];
        }

        if ($recipient instanceof User) {
            // User model
            return [
                'email' => $recipient->email,
                'name' => $recipient->name,
                'type' => 'user',
                'user_id' => $recipient->id
            ];
        }

        if ($recipient instanceof Contact) {
            // Contact model
            return [
                'email' => $recipient->email,
                'name' => $recipient->name,
                'type' => 'contact',
                'contact_id' => $recipient->id
            ];
        }

        return null;
    }

    /**
     * Validate email address format.
     */
    protected function isValidEmail(string $email): bool
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email'
        ]);

        return !$validator->fails();
    }

    /**
     * Get email template path for notifications.
     */
    public function getTemplatePath(string $notificationType = 'default'): string
    {
        return $this->config['template_path'] . '.' . $notificationType;
    }

    /**
     * Set email configuration.
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