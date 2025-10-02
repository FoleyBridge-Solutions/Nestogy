<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public User $assignedTo,
        public ?User $assignedBy = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket Assigned: #{$this->ticket->number} - {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.assigned',
            with: [
                'ticket' => $this->ticket,
                'assignedTo' => $this->assignedTo,
                'assignedBy' => $this->assignedBy,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
            ],
        );
    }
}
