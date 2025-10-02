<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketCommentAdded extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketComment $comment
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Comment on Ticket #{$this->ticket->number}: {$this->ticket->subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.comment-added',
            with: [
                'ticket' => $this->ticket,
                'comment' => $this->comment,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
            ],
        );
    }
}
