<?php

namespace App\Services;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    protected EmailService $emailService;

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
}