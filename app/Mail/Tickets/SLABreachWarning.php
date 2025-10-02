<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SLABreachWarning extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public Carbon $slaDeadline,
        public int $hoursRemaining
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "SLA Warning: Ticket #{$this->ticket->number} - {$this->hoursRemaining}h Remaining",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.sla-breach-warning',
            with: [
                'ticket' => $this->ticket,
                'slaDeadline' => $this->slaDeadline,
                'hoursRemaining' => $this->hoursRemaining,
                'ticketUrl' => route('tickets.show', $this->ticket->id),
            ],
        );
    }
}
