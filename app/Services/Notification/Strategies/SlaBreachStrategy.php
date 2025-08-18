<?php

namespace App\Services\Notification\Strategies;

use App\Services\Notification\Contracts\NotificationStrategyInterface;
use App\Domains\Ticket\Models\Ticket;

/**
 * SLA Breach Notification Strategy
 * 
 * Handles critical notifications when SLA breaches occur.
 * Ensures immediate escalation to supervisors and management.
 */
class SlaBreachStrategy implements NotificationStrategyInterface
{
    /**
     * Execute the notification strategy for SLA breaches.
     */
    public function execute(Ticket $ticket, array $eventData = []): array
    {
        if (!$this->shouldExecute($ticket, $eventData)) {
            return ['skipped' => true, 'reason' => 'Strategy execution conditions not met'];
        }

        $recipients = $this->getRecipients($ticket, $eventData);
        $subject = $this->getSubject($ticket, $eventData);
        $message = $this->getMessage($ticket, $eventData);
        $notificationData = $this->getNotificationData($ticket, $eventData);

        return [
            'event_type' => $this->getEventType(),
            'priority' => $this->getPriority(),
            'recipients' => $recipients,
            'subject' => $subject,
            'message' => $message,
            'data' => $notificationData,
            'channels' => ['email', 'slack', 'sms'], // All channels for SLA breaches
            'urgent' => true,
        ];
    }

    /**
     * Get the event type this strategy handles.
     */
    public function getEventType(): string
    {
        return 'sla_breach';
    }

    /**
     * Determine recipients for SLA breach notifications.
     */
    public function getRecipients(Ticket $ticket, array $eventData = []): array
    {
        $recipients = [
            'email' => [],
            'slack' => [],
            'sms' => [],
        ];

        // Always notify assignee
        if ($ticket->assignee) {
            $recipients['email'][] = $ticket->assignee;
            $recipients['slack'][] = $ticket->assignee;
            $recipients['sms'][] = $ticket->assignee;
            
            // Notify assignee's supervisor
            if ($ticket->assignee->supervisor) {
                $recipients['email'][] = $ticket->assignee->supervisor;
                $recipients['slack'][] = $ticket->assignee->supervisor;
                $recipients['sms'][] = $ticket->assignee->supervisor;
            }
        }

        // Notify all supervisors and managers
        $supervisors = $this->getSupervisors($ticket);
        foreach ($supervisors as $supervisor) {
            $recipients['email'][] = $supervisor;
            $recipients['slack'][] = $supervisor;
            $recipients['sms'][] = $supervisor;
        }

        // Notify managers
        $managers = $this->getManagers($ticket);
        foreach ($managers as $manager) {
            $recipients['email'][] = $manager;
            $recipients['slack'][] = $manager;
            $recipients['sms'][] = $manager;
        }

        // Notify watchers who want SLA breach notifications
        if ($ticket->watchers) {
            foreach ($ticket->watchers as $watcher) {
                if ($watcher->user && $this->watcherWantsSlaNotifications($watcher)) {
                    $recipients['email'][] = $watcher->user;
                    $recipients['slack'][] = $watcher->user;
                }
            }
        }

        return $recipients;
    }

    /**
     * Generate the notification subject.
     */
    public function getSubject(Ticket $ticket, array $eventData = []): string
    {
        $breachType = $eventData['breach_type'] ?? 'SLA';
        return "🚨 {$breachType} BREACH: #{$ticket->ticket_number} - IMMEDIATE ACTION REQUIRED";
    }

    /**
     * Generate the notification message.
     */
    public function getMessage(Ticket $ticket, array $eventData = []): string
    {
        $breachType = $eventData['breach_type'] ?? 'SLA';
        $breachDetails = $eventData['breach_details'] ?? [];
        
        $message = "⚠️ CRITICAL: {$breachType} BREACH DETECTED ⚠️\n\n";
        $message .= "A service level agreement has been breached and requires immediate attention.\n\n";
        
        $message .= "Ticket Details:\n";
        $message .= "• Number: #{$ticket->ticket_number}\n";
        $message .= "• Subject: {$ticket->subject}\n";
        $message .= "• Priority: {$ticket->priority}\n";
        $message .= "• Status: {$ticket->status}\n";
        $message .= "• Created: {$ticket->created_at->format('M d, Y H:i')}\n";
        
        if ($ticket->client) {
            $message .= "• Client: {$ticket->client->display_name}\n";
        }
        
        if ($ticket->assignee) {
            $message .= "• Assigned to: {$ticket->assignee->name}\n";
        }

        // Add SLA breach details
        if (!empty($breachDetails)) {
            $message .= "\nBreach Details:\n";
            
            if (isset($breachDetails['response_time_exceeded'])) {
                $message .= "• Response Time: EXCEEDED (Target: {$breachDetails['target_response_time']})\n";
            }
            
            if (isset($breachDetails['resolution_time_exceeded'])) {
                $message .= "• Resolution Time: EXCEEDED (Target: {$breachDetails['target_resolution_time']})\n";
            }
            
            if (isset($breachDetails['time_exceeded_by'])) {
                $message .= "• Time Exceeded By: {$breachDetails['time_exceeded_by']}\n";
            }
        }

        $message .= "\n🔥 REQUIRED ACTIONS:\n";
        $message .= "1. Review ticket immediately\n";
        $message .= "2. Contact client if necessary\n";
        $message .= "3. Escalate to appropriate team\n";
        $message .= "4. Document resolution steps\n";

        return $message;
    }

    /**
     * Get additional notification data.
     */
    public function getNotificationData(Ticket $ticket, array $eventData = []): array
    {
        return [
            'ticket' => $ticket,
            'event_type' => $this->getEventType(),
            'breach_type' => $eventData['breach_type'] ?? 'SLA',
            'breach_details' => $eventData['breach_details'] ?? [],
            'timestamp' => now(),
            'ticket_url' => route('tickets.show', $ticket->id),
            'sla' => $ticket->getEffectiveSLA(),
            'template' => 'ticket.sla_breach',
            'is_critical' => true,
        ];
    }

    /**
     * Check if this strategy should execute.
     */
    public function shouldExecute(Ticket $ticket, array $eventData = []): bool
    {
        // Must have breach type specified
        if (!isset($eventData['breach_type'])) {
            return false;
        }

        // Skip if ticket is already closed
        if ($ticket->status === 'closed') {
            return false;
        }

        // Skip if SLA notifications are disabled (rare case)
        if (isset($eventData['skip_sla_notifications']) && $eventData['skip_sla_notifications']) {
            return false;
        }

        return true;
    }

    /**
     * Get the priority level for this notification strategy.
     */
    public function getPriority(): string
    {
        return 'critical';
    }

    /**
     * Check if watcher wants SLA breach notifications.
     */
    protected function watcherWantsSlaNotifications($watcher): bool
    {
        $preferences = $watcher->notification_preferences ?? [];
        return $preferences['sla_breach'] ?? true; // Default to true for SLA breaches
    }

    /**
     * Get supervisors for SLA breach notifications.
     */
    protected function getSupervisors(Ticket $ticket): array
    {
        return \App\Models\User::where('company_id', $ticket->company_id)
            ->where('role', 'supervisor')
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Get managers for SLA breach notifications.
     */
    protected function getManagers(Ticket $ticket): array
    {
        return \App\Models\User::where('company_id', $ticket->company_id)
            ->whereIn('role', ['manager', 'admin'])
            ->where('is_active', true)
            ->get()
            ->toArray();
    }
}