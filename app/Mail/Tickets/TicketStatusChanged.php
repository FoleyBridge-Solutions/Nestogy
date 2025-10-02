<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $oldStatus,
        public string $newStatus
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket Status Updated: #{$this->ticket->number} - {$this->newStatus}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.status-changed',
            with: [
                'ticket' => $this->ticket,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
            ],
        );
    }
}
