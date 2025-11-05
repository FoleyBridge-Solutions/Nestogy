<?php

namespace App\Mail;

use App\Domains\Client\Models\ClientContact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Contact $contact,
        public string $invitationUrl
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $companyName = $this->contact->client->company->name ?? 'Nestogy';

        return new Envelope(
            subject: "You're invited to access your {$companyName} Client Portal",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Extract domain from invitation URL for support email
        $parsedUrl = parse_url($this->invitationUrl);
        $domain = $parsedUrl['host'] ?? config('app.url_host', 'nestogy.io');
        
        return new Content(
            view: 'emails.portal-invitation',
            with: [
                'contactName' => $this->contact->name,
                'clientName' => $this->contact->client->name,
                'companyName' => $this->contact->client->company->name ?? 'Nestogy',
                'invitationUrl' => $this->invitationUrl,
                'expiresAt' => $this->contact->invitation_expires_at,
                'expiresInHours' => now()->diffInHours($this->contact->invitation_expires_at),
                'supportDomain' => $domain,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
