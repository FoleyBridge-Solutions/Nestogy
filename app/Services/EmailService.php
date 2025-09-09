<?php

namespace App\Services;

use App\Contracts\Services\EmailServiceInterface;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class EmailService implements EmailServiceInterface
{
    protected Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Get configuration value
     */
    protected function config(string $key = null)
    {
        $config = config('mail');
        return $key ? ($config[$key] ?? null) : $config;
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
        return config('mail');
    }

    /**
     * Test email connection
     */
    public function testConnection(): bool
    {
        try {
            // Send a test email to the configured from address
            $fromAddress = config('mail.from.address', 'test@example.com');
            
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

    /**
     * Send quote email to client
     */
    public function sendQuoteEmail(Quote $quote): bool
    {
        try {
            $client = $quote->client;
            
            if (!$client || !$client->email) {
                Log::warning('Cannot send quote email - no client email', [
                    'quote_id' => $quote->id,
                    'client_id' => $client->id ?? null
                ]);
                return false;
            }

            $emailData = [
                'quote' => $quote,
                'client' => $client,
                'viewUrl' => $this->generateSecureQuoteUrl($quote),
                'expiryDate' => $quote->expire_date ?? $quote->valid_until,
                'totalAmount' => $quote->getFormattedAmount(),
            ];

            Mail::send('emails.quotes.send', $emailData, function ($message) use ($client, $quote) {
                $message->to($client->email, $client->name)
                        ->subject("Quote #{$quote->getFullNumber()}")
                        ->from(config('mail.from.address'), config('app.name'));
            });

            Log::info('Quote email sent successfully', [
                'quote_id' => $quote->id,
                'client_email' => $client->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send quote email', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send quote approval request email
     */
    public function sendQuoteApprovalRequest(Quote $quote, User $approver): bool
    {
        try {
            if (!$approver->email) {
                return false;
            }

            $emailData = [
                'quote' => $quote,
                'approver' => $approver,
                'approvalUrl' => route('financial.quotes.approve', $quote),
                'client' => $quote->client,
                'totalAmount' => $quote->getFormattedAmount(),
            ];

            Mail::send('emails.quotes.approval-request', $emailData, function ($message) use ($approver, $quote) {
                $message->to($approver->email, $approver->name)
                        ->subject("Quote Approval Required: #{$quote->getFullNumber()}")
                        ->priority(1);
            });

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send quote approval request', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send quote expiry reminder
     */
    public function sendQuoteExpiryReminder(Quote $quote, int $daysUntilExpiry): bool
    {
        try {
            $client = $quote->client;
            
            if (!$client || !$client->email) {
                return false;
            }

            $emailData = [
                'quote' => $quote,
                'client' => $client,
                'daysUntilExpiry' => $daysUntilExpiry,
                'viewUrl' => $this->generateSecureQuoteUrl($quote),
            ];

            $subject = $daysUntilExpiry === 1 
                ? "Quote #{$quote->getFullNumber()} expires tomorrow"
                : "Quote #{$quote->getFullNumber()} expires in {$daysUntilExpiry} days";

            Mail::send('emails.quotes.expiry-reminder', $emailData, function ($message) use ($client, $subject) {
                $message->to($client->email, $client->name)
                        ->subject($subject);
            });

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send quote expiry reminder', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate secure URL for quote viewing
     */
    private function generateSecureQuoteUrl(Quote $quote): string
    {
        if (!$quote->url_key) {
            $quote->generateUrlKey();
        }

        return url('/quote/' . $quote->url_key);
    }

    /**
     * Send invoice email to client
     */
    public function sendInvoiceEmail(Invoice $invoice): bool
    {
        try {
            $client = $invoice->client;
            
            if (!$client || !$client->email) {
                Log::warning('Cannot send invoice email - no client email', [
                    'invoice_id' => $invoice->id,
                    'client_id' => $client->id ?? null
                ]);
                return false;
            }

            $emailData = [
                'invoice' => $invoice,
                'client' => $client,
                'viewUrl' => $this->generateSecureInvoiceUrl($invoice),
                'dueDate' => $invoice->due_date,
                'totalAmount' => number_format($invoice->amount, 2),
            ];

            Mail::send('emails.invoices.send', $emailData, function ($message) use ($client, $invoice) {
                $message->to($client->email, $client->name)
                        ->subject("Invoice #{$invoice->number}")
                        ->from(config('mail.from.address'), config('app.name'));
            });

            Log::info('Invoice email sent successfully', [
                'invoice_id' => $invoice->id,
                'client_email' => $client->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send payment receipt email
     */
    public function sendPaymentReceiptEmail(Payment $payment): bool
    {
        try {
            $invoice = $payment->invoice;
            $client = $invoice->client;
            
            if (!$client || !$client->email) {
                Log::warning('Cannot send payment receipt - no client email', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'client_id' => $client->id ?? null
                ]);
                return false;
            }

            $emailData = [
                'payment' => $payment,
                'invoice' => $invoice,
                'client' => $client,
                'paymentAmount' => number_format($payment->amount, 2),
                'paymentDate' => $payment->payment_date,
                'paymentMethod' => $payment->payment_method,
                'invoiceBalance' => number_format($invoice->amount - $invoice->payments->sum('amount'), 2),
            ];

            Mail::send('emails.payments.receipt', $emailData, function ($message) use ($client, $invoice, $payment) {
                $message->to($client->email, $client->name)
                        ->subject("Payment Receipt - Invoice #{$invoice->number}")
                        ->from(config('mail.from.address'), config('app.name'));
            });

            Log::info('Payment receipt email sent successfully', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'client_email' => $client->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send payment receipt email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate secure URL for invoice viewing
     */
    private function generateSecureInvoiceUrl(Invoice $invoice): string
    {
        // Assuming invoices have a similar URL key mechanism as quotes
        // If not, we can use a signed URL or the regular route
        return route('invoices.show', $invoice);
    }
}