<?php

namespace App\Services;

use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class EmailService
{
    protected Mailer $mailer;
    protected array $config;

    public function __construct(Mailer $mailer, array $config)
    {
        $this->mailer = $mailer;
        $this->config = $config;
    }

    /**
     * Send a simple email
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        try {
            Mail::send([], [], function ($message) use ($to, $subject, $body, $attachments) {
                $message->to($to)
                    ->subject($subject)
                    ->html($body);

                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? null,
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    } else {
                        $message->attach($attachment);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email using a Mailable class
     */
    public function sendMailable(string $to, Mailable $mailable): bool
    {
        try {
            Mail::to($to)->send($mailable);
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send mailable', [
                'to' => $to,
                'mailable' => get_class($mailable),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send bulk emails
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $attachments = []): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? null) : null;
            
            $results[$email] = $this->send($email, $subject, $body, $attachments);
        }

        return $results;
    }

    /**
     * Send notification email
     */
    public function sendNotification(string $to, string $type, array $data): bool
    {
        $templates = [
            'ticket_created' => [
                'subject' => 'New Ticket Created: #{{ticket_id}}',
                'template' => 'emails.notifications.ticket-created',
            ],
            'ticket_updated' => [
                'subject' => 'Ticket Updated: #{{ticket_id}}',
                'template' => 'emails.notifications.ticket-updated',
            ],
            'invoice_generated' => [
                'subject' => 'Invoice Generated: #{{invoice_number}}',
                'template' => 'emails.notifications.invoice-generated',
            ],
            'payment_received' => [
                'subject' => 'Payment Received: #{{invoice_number}}',
                'template' => 'emails.notifications.payment-received',
            ],
        ];

        if (!isset($templates[$type])) {
            logger()->warning('Unknown notification type', ['type' => $type]);
            return false;
        }

        $template = $templates[$type];
        $subject = $this->replaceTokens($template['subject'], $data);

        try {
            Mail::send($template['template'], $data, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send notification', [
                'to' => $to,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Replace tokens in template strings
     */
    protected function replaceTokens(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Get email configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Test email connection
     */
    public function testConnection(): bool
    {
        try {
            // Send a test email to the configured from address
            $fromAddress = $this->config['from']['address'] ?? 'test@example.com';
            
            return $this->send(
                $fromAddress,
                'Email Connection Test',
                'This is a test email to verify the email configuration is working correctly.'
            );
        } catch (\Exception $e) {
            logger()->error('Email connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}