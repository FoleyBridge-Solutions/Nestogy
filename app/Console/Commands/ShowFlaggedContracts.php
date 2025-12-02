<?php

namespace App\Console\Commands;

use App\Domains\Contract\Models\ContractContactAssignment;
use Illuminate\Console\Command;

class ShowFlaggedContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:show-flagged-contracts {--clear= : Clear flag for specific contract ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show contracts flagged for review due to usage abuse detection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if clearing a flag
        if ($contractId = $this->option('clear')) {
            return $this->clearFlag($contractId);
        }

        $this->info('🏴 Contracts Flagged for Review (Abuse Detection)');
        $this->newLine();

        $flaggedContracts = ContractContactAssignment::flaggedForReview()
            ->with(['contract', 'contact', 'contact.client'])
            ->get();

        if ($flaggedContracts->isEmpty()) {
            $this->info('✓ No contracts currently flagged for review.');
            return 0;
        }

        $this->warn("Found {$flaggedContracts->count()} flagged contracts:");
        $this->newLine();

        $headers = ['ID', 'Client', 'Contact', 'Reason', 'Tickets', 'Hours', 'Flagged At'];
        $rows = [];

        foreach ($flaggedContracts as $assignment) {
            $metadata = $assignment->metadata ?? [];
            
            $rows[] = [
                $assignment->id,
                $assignment->contact->client->name ?? 'N/A',
                $assignment->contact->name ?? 'N/A',
                $assignment->getFlagReason(),
                $assignment->current_month_tickets ?? 0,
                number_format($assignment->current_month_support_hours ?? 0, 1),
                $metadata['flagged_at'] ?? 'Unknown',
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('💡 To clear a flag: php artisan billing:show-flagged-contracts --clear=<ID>');

        return 0;
    }

    /**
     * Clear review flag for a contract
     */
    protected function clearFlag(int $contractId): int
    {
        $assignment = ContractContactAssignment::find($contractId);

        if (!$assignment) {
            $this->error("Contract assignment #{$contractId} not found.");
            return 1;
        }

        if (!$assignment->isFlaggedForReview()) {
            $this->info("Contract #{$contractId} is not flagged.");
            return 0;
        }

        $reason = $assignment->getFlagReason();
        
        if ($this->confirm("Clear flag '{$reason}' for contract #{$contractId}?")) {
            $assignment->clearReviewFlag();
            $this->info("✓ Flag cleared for contract #{$contractId}");
        } else {
            $this->info('Cancelled.');
        }

        return 0;
    }
}
