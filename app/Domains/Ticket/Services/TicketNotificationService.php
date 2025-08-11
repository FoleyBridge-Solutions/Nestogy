<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketWatcher;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Ticket Notification Service
 * 
 * Handles all ticket-related notifications including email alerts,
 * watcher notifications, assignment notifications, and escalation alerts.
 */
class TicketNotificationService
{
    /**
     * Send notification for new ticket creation
     */
    public function notifyTicketCreated(Ticket $ticket): void
    {
        try {
            // Notify assignee if assigned
            if ($ticket->assignee) {
                $this->sendEmailNotification(
                    $ticket->assignee,
                    'ticket.created.assigned',
                    [
                        'ticket' => $ticket,
                        'subject' => "New ticket assigned: #{$ticket->ticket_number}"
                    ]
                );
            }

            // Notify client contact if public ticket
            if ($ticket->contact && $ticket->is_public) {
                $this->sendEmailNotification(
                    $ticket->contact,
                    'ticket.created.client',
                    [
                        'ticket' => $ticket,
                        'subject' => "Ticket created: #{$ticket->ticket_number}"
                    ]
                );
            }

            // Notify watchers
            $this->notifyWatchers($ticket, 'ticket_created', [
                'subject' => "New ticket: #{$ticket->ticket_number}",
                'message' => "A new ticket has been created: {$ticket->subject}"
            ]);

            Log::info('Ticket creation notifications sent', [
                'ticket_id' => $ticket->id,
                'recipients' => $this->getNotificationRecipients($ticket, 'ticket_created')
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send ticket creation notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for ticket updates
     */
    public function notifyTicketUpdated(Ticket $ticket, array $changes = []): void
    {
        try {
            $changeDescription = $this->formatChanges($changes);

            // Notify assignee
            if ($ticket->assignee) {
                $this->sendEmailNotification(
                    $ticket->assignee,
                    'ticket.updated.assigned',
                    [
                        'ticket' => $ticket,
                        'changes' => $changeDescription,
                        'subject' => "Ticket updated: #{$ticket->ticket_number}"
                    ]
                );
            }

            // Notify client contact if changes affect them
            if ($ticket->contact && $this->shouldNotifyClient($changes)) {
                $this->sendEmailNotification(
                    $ticket->contact,
                    'ticket.updated.client',
                    [
                        'ticket' => $ticket,
                        'changes' => $changeDescription,
                        'subject' => "Ticket update: #{$ticket->ticket_number}"
                    ]
                );
            }

            // Notify watchers based on their preferences
            $this->notifyWatchersConditional($ticket, 'ticket_updated', $changes, [
                'subject' => "Ticket updated: #{$ticket->ticket_number}",
                'message' => "Ticket has been updated: {$changeDescription}"
            ]);

            Log::info('Ticket update notifications sent', [
                'ticket_id' => $ticket->id,
                'changes' => $changes
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send ticket update notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for status changes
     */
    public function notifyStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        try {
            $data = [
                'ticket' => $ticket,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'subject' => "Status changed: #{$ticket->ticket_number}"
            ];

            // Notify assignee
            if ($ticket->assignee) {
                $this->sendEmailNotification($ticket->assignee, 'ticket.status_changed', $data);
            }

            // Notify client on resolution/closure
            if ($ticket->contact && in_array($newStatus, ['resolved', 'closed'])) {
                $this->sendEmailNotification($ticket->contact, 'ticket.status_changed.client', $data);
            }

            // Notify watchers with status change preferences
            $this->notifyWatchers($ticket, 'status_changes', [
                'subject' => $data['subject'],
                'message' => "Ticket status changed from {$oldStatus} to {$newStatus}"
            ], ['status_changes']);

            Log::info('Status change notifications sent', [
                'ticket_id' => $ticket->id,
                'status_change' => "{$oldStatus} -> {$newStatus}"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send status change notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for priority changes
     */
    public function notifyPriorityChanged(Ticket $ticket, string $oldPriority, string $newPriority): void
    {
        try {
            // Only notify on priority increases
            $priorityLevels = ['Low' => 1, 'Medium' => 2, 'High' => 3, 'Critical' => 4];
            if (($priorityLevels[$newPriority] ?? 0) <= ($priorityLevels[$oldPriority] ?? 0)) {
                return;
            }

            $data = [
                'ticket' => $ticket,
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
                'subject' => "Priority escalated: #{$ticket->ticket_number}"
            ];

            // Notify assignee and supervisor on high/critical priority
            if ($ticket->assignee && in_array($newPriority, ['High', 'Critical'])) {
                $this->sendEmailNotification($ticket->assignee, 'ticket.priority_escalated', $data);
                
                // Notify supervisor for critical tickets
                if ($newPriority === 'Critical' && $ticket->assignee->supervisor) {
                    $this->sendEmailNotification($ticket->assignee->supervisor, 'ticket.priority_critical', $data);
                }
            }

            // Notify watchers with priority change preferences
            $this->notifyWatchers($ticket, 'priority_changes', [
                'subject' => $data['subject'],
                'message' => "Ticket priority changed from {$oldPriority} to {$newPriority}"
            ], ['priority_changes']);

            Log::info('Priority change notifications sent', [
                'ticket_id' => $ticket->id,
                'priority_change' => "{$oldPriority} -> {$newPriority}"
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send priority change notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for assignment changes
     */
    public function notifyAssignmentChanged(Ticket $ticket, ?User $oldAssignee, ?User $newAssignee): void
    {
        try {
            // Notify new assignee
            if ($newAssignee) {
                $this->sendEmailNotification(
                    $newAssignee,
                    'ticket.assigned',
                    [
                        'ticket' => $ticket,
                        'subject' => "Ticket assigned to you: #{$ticket->ticket_number}"
                    ]
                );
            }

            // Notify old assignee about unassignment
            if ($oldAssignee && !$newAssignee) {
                $this->sendEmailNotification(
                    $oldAssignee,
                    'ticket.unassigned',
                    [
                        'ticket' => $ticket,
                        'subject' => "Ticket unassigned: #{$ticket->ticket_number}"
                    ]
                );
            }

            // Notify watchers with assignment preferences
            $message = $newAssignee ? 
                "Ticket assigned to {$newAssignee->name}" : 
                "Ticket unassigned";

            $this->notifyWatchers($ticket, 'assignments', [
                'subject' => "Assignment change: #{$ticket->ticket_number}",
                'message' => $message
            ], ['assignments']);

            Log::info('Assignment change notifications sent', [
                'ticket_id' => $ticket->id,
                'old_assignee' => $oldAssignee?->id,
                'new_assignee' => $newAssignee?->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send assignment notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for new replies/comments
     */
    public function notifyNewReply(Ticket $ticket, array $reply): void
    {
        try {
            $isPublic = $reply['type'] === 'public';
            $author = User::find($reply['user_id']);

            $data = [
                'ticket' => $ticket,
                'reply' => $reply,
                'author' => $author,
                'subject' => "New reply: #{$ticket->ticket_number}"
            ];

            // Notify assignee if not the author
            if ($ticket->assignee && $ticket->assignee->id !== $reply['user_id']) {
                $this->sendEmailNotification($ticket->assignee, 'ticket.new_reply', $data);
            }

            // Notify client if public reply and not from client
            if ($isPublic && $ticket->contact && $author?->id !== $ticket->contact->id) {
                $this->sendEmailNotification($ticket->contact, 'ticket.new_reply.client', $data);
            }

            // Notify watchers with comment preferences
            $this->notifyWatchers($ticket, 'new_comments', [
                'subject' => $data['subject'],
                'message' => "New comment from {$author->name}: " . substr($reply['content'], 0, 100) . '...'
            ], ['new_comments'], [$reply['user_id']]);

            Log::info('New reply notifications sent', [
                'ticket_id' => $ticket->id,
                'reply_type' => $reply['type'],
                'author_id' => $reply['user_id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send new reply notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send SLA breach notifications
     */
    public function notifySLABreach(Ticket $ticket, string $breachType): void
    {
        try {
            $data = [
                'ticket' => $ticket,
                'breach_type' => $breachType,
                'subject' => "SLA BREACH: #{$ticket->ticket_number}"
            ];

            // Notify assignee and supervisor
            if ($ticket->assignee) {
                $this->sendEmailNotification($ticket->assignee, 'ticket.sla_breach', $data);
                
                if ($ticket->assignee->supervisor) {
                    $this->sendEmailNotification($ticket->assignee->supervisor, 'ticket.sla_breach.supervisor', $data);
                }
            }

            // Notify all watchers for SLA breaches
            $this->notifyWatchers($ticket, 'sla_breach', [
                'subject' => $data['subject'],
                'message' => "SLA breach detected: {$breachType}"
            ]);

            // Log as warning
            Log::warning('SLA breach notification sent', [
                'ticket_id' => $ticket->id,
                'breach_type' => $breachType
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send SLA breach notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send escalation notifications
     */
    public function notifyEscalation(Ticket $ticket, string $escalationReason): void
    {
        try {
            $data = [
                'ticket' => $ticket,
                'escalation_reason' => $escalationReason,
                'subject' => "ESCALATION: #{$ticket->ticket_number}"
            ];

            // Notify management/supervisors
            $supervisors = $this->getSupervisors();
            foreach ($supervisors as $supervisor) {
                $this->sendEmailNotification($supervisor, 'ticket.escalated', $data);
            }

            // Notify assignee
            if ($ticket->assignee) {
                $this->sendEmailNotification($ticket->assignee, 'ticket.escalated.assignee', $data);
            }

            Log::warning('Escalation notification sent', [
                'ticket_id' => $ticket->id,
                'escalation_reason' => $escalationReason,
                'supervisors_notified' => $supervisors->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send escalation notifications', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send bulk notifications for multiple tickets
     */
    public function sendBulkNotifications(Collection $tickets, string $notificationType, array $data = []): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($tickets as $ticket) {
            try {
                switch ($notificationType) {
                    case 'reminder':
                        $this->sendTicketReminder($ticket, $data);
                        break;
                    case 'update':
                        $this->notifyTicketUpdated($ticket, $data);
                        break;
                    case 'status_change':
                        $this->notifyStatusChanged($ticket, $data['old_status'], $data['new_status']);
                        break;
                    default:
                        throw new \Exception("Unknown notification type: {$notificationType}");
                }
                
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Send ticket reminder notifications
     */
    public function sendTicketReminder(Ticket $ticket, array $reminderData = []): void
    {
        $reminderType = $reminderData['type'] ?? 'general';
        
        $data = [
            'ticket' => $ticket,
            'reminder_type' => $reminderType,
            'subject' => "Reminder: #{$ticket->ticket_number}"
        ];

        // Notify assignee
        if ($ticket->assignee) {
            $this->sendEmailNotification($ticket->assignee, 'ticket.reminder', $data);
        }

        Log::info('Ticket reminder sent', [
            'ticket_id' => $ticket->id,
            'reminder_type' => $reminderType
        ]);
    }

    /**
     * Notify watchers based on their notification preferences
     */
    private function notifyWatchers(
        Ticket $ticket, 
        string $eventType, 
        array $data, 
        array $requiredPreferences = [],
        array $excludeUserIds = []
    ): void {
        $watchers = $ticket->watchers()->with('user')->get();

        foreach ($watchers as $watcher) {
            // Skip excluded users
            if (in_array($watcher->user_id, $excludeUserIds)) {
                continue;
            }

            // Check notification preferences
            if (!empty($requiredPreferences)) {
                $preferences = $watcher->notification_preferences ?? [];
                $shouldNotify = false;
                
                foreach ($requiredPreferences as $pref) {
                    if ($preferences[$pref] ?? false) {
                        $shouldNotify = true;
                        break;
                    }
                }
                
                if (!$shouldNotify) {
                    continue;
                }
            }

            $this->sendEmailNotification($watcher->user, 'ticket.watcher_notification', array_merge($data, [
                'event_type' => $eventType
            ]));
        }
    }

    /**
     * Notify watchers conditionally based on change types
     */
    private function notifyWatchersConditional(Ticket $ticket, string $eventType, array $changes, array $data): void
    {
        $watchers = $ticket->watchers()->with('user')->get();

        foreach ($watchers as $watcher) {
            $preferences = $watcher->notification_preferences ?? [];
            $shouldNotify = false;

            // Check if any of the changes match watcher preferences
            foreach ($changes as $field => $change) {
                switch ($field) {
                    case 'status':
                        if ($preferences['status_changes'] ?? false) {
                            $shouldNotify = true;
                        }
                        break;
                    case 'priority':
                        if ($preferences['priority_changes'] ?? false) {
                            $shouldNotify = true;
                        }
                        break;
                    case 'assigned_to':
                        if ($preferences['assignments'] ?? false) {
                            $shouldNotify = true;
                        }
                        break;
                    default:
                        // For other changes, use general preferences
                        if ($preferences['ticket_updates'] ?? true) {
                            $shouldNotify = true;
                        }
                }
                
                if ($shouldNotify) break;
            }

            if ($shouldNotify) {
                $this->sendEmailNotification($watcher->user, 'ticket.watcher_notification', array_merge($data, [
                    'event_type' => $eventType,
                    'changes' => $changes
                ]));
            }
        }
    }

    /**
     * Send email notification to a user
     */
    private function sendEmailNotification($recipient, string $template, array $data): void
    {
        // TODO: Implement actual email sending using Laravel Mail
        // For now, just log the notification
        Log::info('Email notification queued', [
            'recipient' => $recipient->email ?? $recipient->id,
            'template' => $template,
            'ticket_id' => $data['ticket']->id ?? null,
            'subject' => $data['subject'] ?? 'Ticket Notification'
        ]);
        
        // Example implementation:
        // Mail::to($recipient)->queue(new TicketNotificationMail($template, $data));
    }

    /**
     * Format changes array into readable string
     */
    private function formatChanges(array $changes): string
    {
        if (empty($changes)) {
            return 'Ticket details updated';
        }

        $descriptions = [];
        foreach ($changes as $field => $change) {
            $descriptions[] = match ($field) {
                'status' => "Status: {$change['old']} → {$change['new']}",
                'priority' => "Priority: {$change['old']} → {$change['new']}",
                'assigned_to' => "Assignment changed",
                default => ucfirst($field) . " updated"
            };
        }

        return implode(', ', $descriptions);
    }

    /**
     * Check if client should be notified based on changes
     */
    private function shouldNotifyClient(array $changes): bool
    {
        $clientRelevantChanges = ['status', 'priority', 'assigned_to'];
        
        return !empty(array_intersect(array_keys($changes), $clientRelevantChanges));
    }

    /**
     * Get notification recipients for a ticket event
     */
    private function getNotificationRecipients(Ticket $ticket, string $eventType): array
    {
        $recipients = [];

        if ($ticket->assignee) {
            $recipients[] = $ticket->assignee->email;
        }

        if ($ticket->contact) {
            $recipients[] = $ticket->contact->email;
        }

        $watcherEmails = $ticket->watchers()->with('user')->get()
                               ->pluck('user.email')
                               ->filter()
                               ->toArray();

        return array_unique(array_merge($recipients, $watcherEmails));
    }

    /**
     * Get supervisors/managers for escalation notifications
     */
    private function getSupervisors(): Collection
    {
        // TODO: Implement actual supervisor lookup
        // For now, return users with supervisor role
        return User::where('company_id', auth()->user()->company_id)
                  ->where('role', 'supervisor')
                  ->where('is_active', true)
                  ->get();
    }
}