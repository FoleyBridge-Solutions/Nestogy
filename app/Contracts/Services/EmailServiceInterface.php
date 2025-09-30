<?php

namespace App\Contracts\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Mail\Mailable;

interface EmailServiceInterface
{
    /**
     * Send a simple email
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool;

    /**
     * Send email using a Mailable class
     */
    public function sendMailable(string $to, Mailable $mailable): bool;

    /**
     * Send bulk emails
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $attachments = []): array;

    /**
     * Send notification email
     */
    public function sendNotification(string $to, string $type, array $data): bool;

    /**
     * Get email configuration
     */
    public function getConfig(): array;

    /**
     * Test email connection
     */
    public function testConnection(): bool;

    /**
     * Send quote email to client
     */
    public function sendQuoteEmail(Quote $quote): bool;

    /**
     * Send quote approval request email
     */
    public function sendQuoteApprovalRequest(Quote $quote, User $approver): bool;

    /**
     * Send quote expiry reminder
     */
    public function sendQuoteExpiryReminder(Quote $quote, int $daysUntilExpiry): bool;

    /**
     * Send invoice email to client
     */
    public function sendInvoiceEmail(Invoice $invoice): bool;

    /**
     * Send payment receipt email
     */
    public function sendPaymentReceiptEmail(Payment $payment): bool;
}
