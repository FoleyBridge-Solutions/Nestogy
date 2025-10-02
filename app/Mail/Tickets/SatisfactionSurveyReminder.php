<?php

namespace App\Mail\Tickets;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SatisfactionSurveyReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $surveyUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Feedback Requested - Ticket #{$this->ticket->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tickets.satisfaction-survey-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
