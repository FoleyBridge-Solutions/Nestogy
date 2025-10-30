<?php

namespace App\Notifications;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Ticket Assigned Notification
 * 
 * Sent when a ticket is assigned to a user.
 * Supports email, database, and web push channels.
 */
class TicketAssignedNotification extends Notification
{
    protected Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = [];
        
        // Check user preferences if available
        $preferences = $notifiable->notificationPreferences ?? null;
        
        if (!$preferences || $preferences->shouldSendEmail('ticket_assigned')) {
            $channels[] = 'mail';
        }
        
        if (!$preferences || $preferences->shouldSendInApp('ticket_assigned')) {
            $channels[] = 'database';
        }
        
        // Add WebPush if user has subscriptions and it's enabled
        if ($notifiable->pushSubscriptions()->exists()) {
            if (!$preferences || ($preferences->push_enabled ?? true)) {
                $channels[] = WebPushChannel::class;
            }
        }
        
        return $channels;
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        $priorityEmoji = $this->ticket->priority === 'Critical' ? '🚨 ' : 
                        ($this->ticket->priority === 'High' ? '⚠️ ' : '');
        
        return (new WebPushMessage)
            ->title($priorityEmoji . 'Ticket Assigned')
            ->body("You've been assigned to ticket #{$this->ticket->ticket_number}: {$this->ticket->subject}")
            ->icon('/logo.png')
            ->badge('/logo.png')
            ->tag('ticket-' . $this->ticket->id)
            ->requireInteraction($this->ticket->priority === 'Critical')
            ->data([
                'url' => route('tickets.show', $this->ticket->id),
                'ticket_id' => $this->ticket->id,
                'type' => 'ticket_assigned',
                'priority' => $this->ticket->priority,
                'ticket_number' => $this->ticket->ticket_number
            ])
            ->action('View Ticket', 'open')
            ->action('Dismiss', 'dismiss');
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Ticket Assigned: ' . $this->ticket->subject)
            ->line("You've been assigned to a new ticket.")
            ->line('**Ticket:** #' . $this->ticket->ticket_number)
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**Priority:** ' . $this->ticket->priority)
            ->line('**Status:** ' . $this->ticket->status)
            ->action('View Ticket', route('tickets.show', $this->ticket->id))
            ->line('Please review and respond to this ticket as soon as possible.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'ticket_assigned',
            'title' => 'Ticket Assigned',
            'message' => "Ticket #{$this->ticket->ticket_number} has been assigned to you",
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'url' => route('tickets.show', $this->ticket->id)
        ];
    }
}
