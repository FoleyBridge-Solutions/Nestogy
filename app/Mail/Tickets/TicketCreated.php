<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Ticket Created: #{$this->ticket->number} - {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.created',
            with: [
                'ticket' => $this->ticket,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
            ],
        );
    }
}
