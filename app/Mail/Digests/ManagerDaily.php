<?php

namespace App\Mail\Digests;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ManagerDaily extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $manager,
        public array $data
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Ticket Digest - ' . now()->format('F j, Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.digests.manager-daily',
        );
    }
}
