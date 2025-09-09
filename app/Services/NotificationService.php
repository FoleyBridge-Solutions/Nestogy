<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Domains\Contract\Models\Contract;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Financial\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * NotificationService - Enhanced multi-channel notification system
 * 
 * Handles notification dispatch across email, database, and SMS channels,
 * with template management, queuing, delivery tracking, and user preferences.
 */
class NotificationService
{
    protected EmailService $emailService;

    /**
     * Notification channels
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_DATABASE = 'database';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_SLACK = 'slack';

    /**
     * Notification types
     */
    const TYPE_TICKET_ASSIGNED = 'ticket_assigned';
    const TYPE_TICKET_ESCALATED = 'ticket_escalated';
    const TYPE_INVOICE_SENT = 'invoice_sent';
    const TYPE_INVOICE_OVERDUE = 'invoice_overdue';
    const TYPE_PAYMENT_SUCCESS = 'payment_success';
    const TYPE_PAYMENT_FAILED = 'payment_failed';
    const TYPE_CONTRACT_RENEWED = 'contract_renewed';
    const TYPE_BULK_OPERATION = 'bulk_operation';

    /**
     * Retry configuration
     */
    protected array $retryConfig = [
        'max_attempts' => 3,
        'retry_delay' => 300,
    ];

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send ticket notification to user
     */
    public function notifyTicketAssigned(Ticket $ticket, User $user): bool
    {
        try {
            $this->emailService->sendEmail(
                $user->email,
                'Ticket Assigned: ' . $ticket->subject,
                'emails.ticket-assigned',
                [
                    'user' => $user,
                    'ticket' => $ticket,
                    'assignedBy' => auth()->user()
                ]
            );

            Log::info('Ticket assignment notification sent', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send ticket assignment notification', [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send ticket status update notification
     */
    public function notifyTicketStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): bool
    {
        try {
            // Notify client
            if ($ticket->client && $ticket->client->email) {
                $this->emailService->sendEmail(
                    $ticket->client->email,
                    'Ticket Status Update: ' . $ticket->subject,
                    'emails.ticket-status-update',
                    [
                        'ticket' => $ticket,
                        'oldStatus' => $oldStatus,
                        'newStatus' => $newStatus,
                        'client' => $ticket->client
                    ]
                );
            }

            // Notify assigned user if different from current user
            if ($ticket->assignedUser && $ticket->assignedUser->id !== auth()->id()) {
                $this->emailService->sendEmail(
                    $ticket->assignedUser->email,
                    'Ticket Status Update: ' . $ticket->subject,
                    'emails.ticket-status-update-internal',
                    [
                        'ticket' => $ticket,
                        'oldStatus' => $oldStatus,
                        'newStatus' => $newStatus,
                        'user' => $ticket->assignedUser
                    ]
                );
            }

            Log::info('Ticket status change notifications sent', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send ticket status change notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send new ticket notification
     */
    public function notifyNewTicket(Ticket $ticket): bool
    {
        try {
            // Get all admin users or users who should be notified of new tickets
            $adminUsers = User::where('role', 'admin')->orWhere('role', 'manager')->get();

            foreach ($adminUsers as $admin) {
                $this->emailService->sendEmail(
                    $admin->email,
                    'New Ticket Created: ' . $ticket->subject,
                    'emails.new-ticket',
                    [
                        'ticket' => $ticket,
                        'admin' => $admin,
                        'client' => $ticket->client
                    ]
                );
            }

            Log::info('New ticket notifications sent', [
                'ticket_id' => $ticket->id,
                'admin_count' => $adminUsers->count()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send new ticket notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send ticket reply notification
     */
    public function notifyTicketReply(Ticket $ticket, array $replyData): bool
    {
        try {
            // Notify client if reply is from staff
            if ($ticket->client && $ticket->client->email && isset($replyData['from_staff']) && $replyData['from_staff']) {
                $this->emailService->sendEmail(
                    $ticket->client->email,
                    'Reply to Ticket: ' . $ticket->subject,
                    'emails.ticket-reply-client',
                    [
                        'ticket' => $ticket,
                        'reply' => $replyData,
                        'client' => $ticket->client
                    ]
                );
            }

            // Notify assigned user if reply is from client
            if ($ticket->assignedUser && 
                $ticket->assignedUser->email && 
                (!isset($replyData['from_staff']) || !$replyData['from_staff'])) {
                
                $this->emailService->sendEmail(
                    $ticket->assignedUser->email,
                    'Client Reply to Ticket: ' . $ticket->subject,
                    'emails.ticket-reply-staff',
                    [
                        'ticket' => $ticket,
                        'reply' => $replyData,
                        'user' => $ticket->assignedUser
                    ]
                );
            }

            Log::info('Ticket reply notifications sent', [
                'ticket_id' => $ticket->id,
                'from_staff' => $replyData['from_staff'] ?? false
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send ticket reply notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send system notification (in-app)
     */
    public function sendSystemNotification(User $user, string $title, string $message, array $data = []): bool
    {
        try {
            // This would typically create a notifications table record
            // For now, we'll just log it
            Log::info('System notification sent', [
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'data' => $data
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send system notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send bulk notification to multiple users
     */
    public function sendBulkNotification(array $userIds, string $subject, string $template, array $data = []): bool
    {
        try {
            $users = User::whereIn('id', $userIds)->get();
            $successCount = 0;

            foreach ($users as $user) {
                try {
                    $this->emailService->sendEmail(
                        $user->email,
                        $subject,
                        $template,
                        array_merge($data, ['user' => $user])
                    );
                    $successCount++;
                } catch (\Exception $e) {
                    Log::warning('Failed to send bulk notification to user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Bulk notification completed', [
                'total_users' => $users->count(),
                'successful_sends' => $successCount,
                'subject' => $subject
            ]);

            return $successCount > 0;
        } catch (\Exception $e) {
            Log::error('Failed to send bulk notifications', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send maintenance notification
     */
    public function sendMaintenanceNotification(string $message, \DateTime $scheduledTime): bool
    {
        try {
            $allUsers = User::where('active', true)->get();

            foreach ($allUsers as $user) {
                $this->emailService->sendEmail(
                    $user->email,
                    'Scheduled Maintenance Notification',
                    'emails.maintenance-notification',
                    [
                        'user' => $user,
                        'message' => $message,
                        'scheduledTime' => $scheduledTime
                    ]
                );
            }

            Log::info('Maintenance notifications sent', [
                'user_count' => $allUsers->count(),
                'scheduled_time' => $scheduledTime->format('Y-m-d H:i:s')
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send maintenance notifications', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    // ====================================
    // Enhanced Multi-Channel Methods
    // ====================================

    /**
     * Send multi-channel notification
     * 
     * @param mixed $notifiable
     * @param string $type
     * @param array $data
     * @param array $channels
     * @return array
     */
    public function notify($notifiable, string $type, array $data, array $channels = []): array
    {
        $results = [
            'sent' => [],
            'failed' => [],
            'queued' => [],
        ];

        try {
            // Use default channels if not specified
            if (empty($channels)) {
                $channels = $this->getDefaultChannels($type);
            }

            // Filter by user preferences
            $channels = $this->filterByUserPreferences($notifiable, $channels);

            foreach ($channels as $channel) {
                try {
                    $sent = $this->sendThroughChannel($notifiable, $channel, $type, $data);
                    if ($sent) {
                        $results['sent'][] = $channel;
                    } else {
                        $results['queued'][] = $channel;
                    }
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'channel' => $channel,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Notification failed on {$channel}", [
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Track delivery
            $this->trackDelivery($notifiable, $type, $results);

        } catch (\Exception $e) {
            Log::error('Notification processing failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Get default channels for notification type
     */
    protected function getDefaultChannels(string $type): array
    {
        $channelMap = [
            self::TYPE_TICKET_ESCALATED => [self::CHANNEL_EMAIL, self::CHANNEL_DATABASE, self::CHANNEL_SMS],
            self::TYPE_INVOICE_OVERDUE => [self::CHANNEL_EMAIL, self::CHANNEL_DATABASE],
            self::TYPE_PAYMENT_FAILED => [self::CHANNEL_EMAIL, self::CHANNEL_DATABASE],
        ];

        return $channelMap[$type] ?? [self::CHANNEL_EMAIL];
    }

    /**
     * Send through specific channel
     */
    protected function sendThroughChannel($notifiable, string $channel, string $type, array $data): bool
    {
        switch ($channel) {
            case self::CHANNEL_EMAIL:
                return $this->sendEmailNotification($notifiable, $type, $data);
            case self::CHANNEL_DATABASE:
                return $this->sendDatabaseNotification($notifiable, $type, $data);
            case self::CHANNEL_SMS:
                return $this->sendSmsNotification($notifiable, $type, $data);
            case self::CHANNEL_SLACK:
                return $this->sendSlackNotification($notifiable, $type, $data);
            default:
                throw new \Exception("Unsupported channel: {$channel}");
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmailNotification($notifiable, string $type, array $data): bool
    {
        $email = $notifiable->email ?? null;
        if (!$email) {
            return false;
        }

        $subject = $this->getSubjectForType($type, $data);
        $template = $this->getTemplateForType($type);

        return $this->emailService->sendEmail($email, $subject, $template, $data);
    }

    /**
     * Send database notification
     */
    protected function sendDatabaseNotification($notifiable, string $type, array $data): bool
    {
        try {
            DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => $type,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Database notification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send SMS notification (placeholder for integration)
     */
    protected function sendSmsNotification($notifiable, string $type, array $data): bool
    {
        // This would integrate with SMS providers like Twilio
        Log::info('SMS notification queued', [
            'type' => $type,
            'phone' => $notifiable->phone ?? 'N/A',
        ]);
        return true;
    }

    /**
     * Send Slack notification (placeholder for integration)
     */
    protected function sendSlackNotification($notifiable, string $type, array $data): bool
    {
        // This would integrate with Slack API
        Log::info('Slack notification sent', ['type' => $type]);
        return true;
    }

    /**
     * Filter channels by user preferences
     */
    protected function filterByUserPreferences($notifiable, array $channels): array
    {
        if (!$notifiable instanceof User) {
            return $channels;
        }

        // Get user preferences (cached)
        $preferences = Cache::remember(
            "user_prefs_{$notifiable->id}",
            3600,
            fn() => $this->getUserPreferences($notifiable)
        );

        if ($preferences['all_disabled'] ?? false) {
            return [];
        }

        return array_intersect($channels, $preferences['enabled_channels'] ?? $channels);
    }

    /**
     * Get user notification preferences
     */
    protected function getUserPreferences(User $user): array
    {
        // This would fetch from user preferences table
        return [
            'enabled_channels' => [self::CHANNEL_EMAIL, self::CHANNEL_DATABASE],
            'all_disabled' => false,
        ];
    }

    /**
     * Track notification delivery
     */
    protected function trackDelivery($notifiable, string $type, array $results): void
    {
        try {
            DB::table('notification_logs')->insert([
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
                'notification_type' => $type,
                'channels_sent' => json_encode($results['sent']),
                'channels_failed' => json_encode($results['failed']),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track notification', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get subject for notification type
     */
    protected function getSubjectForType(string $type, array $data): string
    {
        $subjects = [
            self::TYPE_TICKET_ESCALATED => 'URGENT: Ticket Escalated - ' . ($data['ticket_subject'] ?? ''),
            self::TYPE_INVOICE_SENT => 'Invoice #' . ($data['invoice_number'] ?? ''),
            self::TYPE_INVOICE_OVERDUE => 'Overdue: Invoice #' . ($data['invoice_number'] ?? ''),
            self::TYPE_PAYMENT_SUCCESS => 'Payment Received - Invoice #' . ($data['invoice_number'] ?? ''),
            self::TYPE_PAYMENT_FAILED => 'Payment Failed - Invoice #' . ($data['invoice_number'] ?? ''),
            self::TYPE_CONTRACT_RENEWED => 'Contract Renewed - ' . ($data['contract_number'] ?? ''),
        ];

        return $subjects[$type] ?? 'Notification';
    }

    /**
     * Get template for notification type
     */
    protected function getTemplateForType(string $type): string
    {
        $templates = [
            self::TYPE_TICKET_ESCALATED => 'emails.ticket-escalated',
            self::TYPE_INVOICE_SENT => 'emails.invoice-sent',
            self::TYPE_INVOICE_OVERDUE => 'emails.invoice-overdue',
            self::TYPE_PAYMENT_SUCCESS => 'emails.payment-success',
            self::TYPE_PAYMENT_FAILED => 'emails.payment-failed',
            self::TYPE_CONTRACT_RENEWED => 'emails.contract-renewed',
        ];

        return $templates[$type] ?? 'emails.default';
    }

    // ====================================
    // New Notification Methods for Services
    // ====================================

    /**
     * Notify ticket escalation
     */
    public function notifyEscalation(Ticket $ticket, string $reason): void
    {
        $managers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['manager', 'admin']);
        })->where('company_id', $ticket->company_id)->get();

        foreach ($managers as $manager) {
            $this->notify($manager, self::TYPE_TICKET_ESCALATED, [
                'ticket_id' => $ticket->id,
                'ticket_subject' => $ticket->subject,
                'reason' => $reason,
                'priority' => $ticket->priority,
            ]);
        }
    }

    /**
     * Notify bulk assignment
     */
    public function notifyBulkAssignment(User $technician, array $ticketIds): void
    {
        $this->notify($technician, self::TYPE_BULK_OPERATION, [
            'operation' => 'bulk_assignment',
            'ticket_count' => count($ticketIds),
            'ticket_ids' => $ticketIds,
        ]);
    }

    /**
     * Notify bulk status update
     */
    public function notifyBulkStatusUpdate(array $ticketIds, string $status): void
    {
        Log::info('Bulk status update completed', [
            'ticket_count' => count($ticketIds),
            'new_status' => $status,
        ]);
    }

    /**
     * Notify invoice sent
     */
    public function notifyInvoiceSent(Invoice $invoice): void
    {
        if ($invoice->client) {
            $this->notify($invoice->client, self::TYPE_INVOICE_SENT, [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->prefix . '-' . $invoice->number,
                'amount' => $invoice->amount,
                'due_date' => $invoice->due_date,
            ]);
        }
    }

    /**
     * Notify invoice overdue
     */
    public function notifyInvoiceOverdue(Invoice $invoice): void
    {
        if ($invoice->client) {
            $this->notify($invoice->client, self::TYPE_INVOICE_OVERDUE, [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->prefix . '-' . $invoice->number,
                'amount' => $invoice->amount,
                'days_overdue' => $invoice->due_date->diffInDays(now()),
            ]);
        }
    }

    /**
     * Notify payment success
     */
    public function notifyPaymentSuccess(Payment $payment): void
    {
        if ($payment->invoice && $payment->invoice->client) {
            $this->notify($payment->invoice->client, self::TYPE_PAYMENT_SUCCESS, [
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice->prefix . '-' . $payment->invoice->number,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
            ]);
        }
    }

    /**
     * Notify payment final failure
     */
    public function notifyPaymentFinalFailure(Payment $payment): void
    {
        $admins = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['finance', 'admin']);
        })->get();

        foreach ($admins as $admin) {
            $this->notify($admin, self::TYPE_PAYMENT_FAILED, [
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice->prefix . '-' . $payment->invoice->number,
                'amount' => $payment->amount,
                'retry_count' => $payment->retry_count,
            ]);
        }
    }

    /**
     * Notify payment retry scheduled
     */
    public function notifyPaymentRetryScheduled(Payment $payment, Carbon $nextRetryTime): void
    {
        Log::info('Payment retry scheduled', [
            'payment_id' => $payment->id,
            'next_retry_at' => $nextRetryTime->toDateTimeString(),
        ]);
    }

    /**
     * Notify contract renewed
     */
    public function notifyContractRenewed(Contract $contract): void
    {
        if ($contract->client) {
            $this->notify($contract->client, self::TYPE_CONTRACT_RENEWED, [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'start_date' => $contract->start_date,
                'end_date' => $contract->end_date,
            ]);
        }
    }

    /**
     * Notify bulk invoice generation
     */
    public function notifyBulkInvoiceGeneration(array $results): void
    {
        $admins = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['finance', 'admin']);
        })->get();

        foreach ($admins as $admin) {
            $this->notify($admin, self::TYPE_BULK_OPERATION, [
                'operation' => 'bulk_invoice_generation',
                'success_count' => count($results['success'] ?? []),
                'failed_count' => count($results['failed'] ?? []),
                'total_amount' => collect($results['success'] ?? [])->sum('amount'),
            ]);
        }
    }
}