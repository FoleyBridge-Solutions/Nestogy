<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SLABreached extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public Carbon $slaDeadline,
        public int $hoursOverdue
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "SLA BREACHED: Ticket #{$this->ticket->number} - {$this->hoursOverdue}h Overdue",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.sla-breached',
            with: [
                'ticket' => $this->ticket,
                'slaDeadline' => $this->slaDeadline,
                'hoursOverdue' => $this->hoursOverdue,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
            ],
        );
    }
}
