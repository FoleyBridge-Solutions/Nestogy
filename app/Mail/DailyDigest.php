<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $digestData
    ) {
    }

    public function envelope(): Envelope
    {
        $activityCount = $this->digestData['activity_count'] ?? 0;
        
        return new Envelope(
            subject: "Daily Digest: {$activityCount} Updates - " . now()->format('M j, Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-digest',
            with: [
                'user' => $this->user,
                'newTickets' => $this->digestData['new_tickets'] ?? [],
                'assignedTickets' => $this->digestData['assigned_tickets'] ?? [],
                'resolvedTickets' => $this->digestData['resolved_tickets'] ?? [],
                'overdueTickets' => $this->digestData['overdue_tickets'] ?? [],
                'highPriorityTickets' => $this->digestData['high_priority_tickets'] ?? [],
                'activityCount' => $this->digestData['activity_count'] ?? 0,
            ],
        );
    }
}
