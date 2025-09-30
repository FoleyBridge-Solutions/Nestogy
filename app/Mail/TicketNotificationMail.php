<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Ticket Notification Mail
 *
 * Mailable class for ticket notifications sent through the EmailChannel.
 */
class TicketNotificationMail extends Mailable
{
    use SerializesModels;

    public array $notificationData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->notificationData['subject'] ?? 'Ticket Notification';
        $template = $this->notificationData['template'] ?? 'emails.tickets.default';

        return $this->subject($subject)
            ->view($template)
            ->with($this->notificationData);
    }
}
