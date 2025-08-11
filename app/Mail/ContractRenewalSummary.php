<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractRenewalSummary extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Processing results.
     *
     * @var array
     */
    public $results;

    /**
     * Whether this was a dry run.
     *
     * @var bool
     */
    public $isDryRun;

    /**
     * Create a new message instance.
     *
     * @param array $results
     * @param bool $isDryRun
     */
    public function __construct(array $results, bool $isDryRun = false)
    {
        $this->results = $results;
        $this->isDryRun = $isDryRun;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = $this->isDryRun ? '[DRY RUN] ' : '';
        $status = empty($this->results['errors']) ? 'Success' : 'Completed with Errors';
        
        return new Envelope(
            subject: "{$prefix}Contract Renewal Processing Summary - {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contracts.renewal-summary',
            with: [
                'results' => $this->results,
                'isDryRun' => $this->isDryRun,
                'hasErrors' => !empty($this->results['errors']),
                'successRate' => $this->calculateSuccessRate(),
                'revenueImpact' => $this->results['revenue_impact'] ?? 0,
                'executionTime' => $this->results['execution_time'] ?? 0,
            ]
        );
    }

    /**
     * Calculate success rate
     *
     * @return float
     */
    protected function calculateSuccessRate(): float
    {
        $total = ($this->results['renewed'] ?? 0) + ($this->results['failed'] ?? 0);
        
        if ($total === 0) {
            return 100.0;
        }
        
        return round((($this->results['renewed'] ?? 0) / $total) * 100, 2);
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