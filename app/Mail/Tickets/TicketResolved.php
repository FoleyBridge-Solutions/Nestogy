<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketResolved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public ?User $resolvedBy = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket Resolved: #{$this->ticket->number} - {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.resolved',
            with: [
                'ticket' => $this->ticket,
                'resolvedBy' => $this->resolvedBy,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
                'surveyUrl' => route('portal.tickets.survey', $this->ticket->id),
            ],
        );
    }
}
