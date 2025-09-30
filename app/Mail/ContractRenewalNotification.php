<?php

namespace App\Mail;

use App\Domains\Contract\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractRenewalNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The contract instance.
     *
     * @var Contract
     */
    public $contract;

    /**
     * Days before expiry.
     *
     * @var int
     */
    public $daysBeforeExpiry;

    /**
     * Create a new message instance.
     */
    public function __construct(Contract $contract, int $daysBeforeExpiry)
    {
        $this->contract = $contract;
        $this->daysBeforeExpiry = $daysBeforeExpiry;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->daysBeforeExpiry) {
            90 => 'Contract Renewal Notice - 90 Days',
            60 => 'Contract Renewal Reminder - 60 Days',
            30 => 'Urgent: Contract Expires in 30 Days',
            default => "Contract Renewal Notice - {$this->daysBeforeExpiry} Days"
        };

        return new Envelope(
            subject: "{$subject} - {$this->contract->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contracts.renewal-notification',
            with: [
                'contract' => $this->contract,
                'daysBeforeExpiry' => $this->daysBeforeExpiry,
                'client' => $this->contract->client,
                'expiryDate' => $this->contract->end_date,
                'renewalValue' => $this->calculateRenewalValue(),
                'isAutoRenew' => $this->contract->auto_renew,
                'escalationRate' => $this->contract->escalation_rate ?? 3.0,
            ]
        );
    }

    /**
     * Calculate renewal value with escalation
     */
    protected function calculateRenewalValue(): float
    {
        $escalationRate = $this->contract->escalation_rate ?? 3.0;

        return round($this->contract->value * (1 + ($escalationRate / 100)), 2);
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
