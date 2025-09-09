<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quote;
use App\Domains\Financial\Services\QuoteService;
use Illuminate\Support\Facades\DB;

class RecalculateQuoteTaxes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:recalculate-taxes
                            {--company= : Specific company ID to process}
                            {--quote= : Specific quote ID to process}
                            {--dry-run : Preview changes without applying them}
                            {--force : Force recalculation even for quotes with existing tax breakdown}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate taxes for existing quotes using the new tax engine';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting quote tax recalculation...');

        $companyId = $this->option('company');
        $quoteId = $this->option('quote');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be saved');
        }

        // Build query
        $query = Quote::with(['items', 'client']);

        if ($companyId) {
            $query->where('company_id', $companyId);
            $this->info("Processing quotes for company ID: {$companyId}");
        }

        if ($quoteId) {
            $query->where('id', $quoteId);
            $this->info("Processing specific quote ID: {$quoteId}");
        } else {
            // Only process quotes from last 12 months by default
            $query->where('created_at', '>=', now()->subMonths(12));
            $this->info('Processing quotes from last 12 months');
        }

        $quotes = $query->get();

        if ($quotes->isEmpty()) {
            $this->warn('No quotes found matching criteria');
            return 0;
        }

        $this->info("Found {$quotes->count()} quotes to process");

        $progressBar = $this->output->createProgressBar($quotes->count());
        $progressBar->start();

        $processed = 0;
        $updated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($quotes as $quote) {
            try {
                $progressBar->advance();
                $processed++;

                // Check if quote needs processing
                if (!$force && $this->hasModernTaxBreakdown($quote)) {
                    $skipped++;
                    continue;
                }

                // Skip quotes without items
                if ($quote->items->isEmpty()) {
                    $skipped++;
                    continue;
                }

                $oldAmount = $quote->amount;
                $oldTaxAmount = $quote->items->sum('tax');

                if (!$dryRun) {
                    DB::transaction(function () use ($quote) {
                        $quoteService = new QuoteService();
                        $quoteService->calculatePricing($quote);
                    });
                }

                // Reload to get updated values
                $quote->refresh();
                $newTaxAmount = $quote->items->sum('tax');

                if (abs($oldTaxAmount - $newTaxAmount) > 0.01) {
                    $updated++;

                    if ($this->option('verbose')) {
                        $this->newLine();
                        $this->line("Quote {$quote->id}: Tax changed from \${$oldTaxAmount} to \${$newTaxAmount}");
                    }
                }

            } catch (\Exception $e) {
                $errors++;
                if ($this->option('verbose')) {
                    $this->newLine();
                    $this->error("Error processing quote {$quote->id}: " . $e->getMessage());
                }
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Tax recalculation completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun && $updated > 0) {
            $this->warn("Run without --dry-run to apply {$updated} changes");
        }

        if ($errors > 0) {
            $this->error("Encountered {$errors} errors. Use --verbose to see details.");
            return 1;
        }

        return 0;
    }

    /**
     * Check if quote has modern tax breakdown data
     */
    private function hasModernTaxBreakdown(Quote $quote): bool
    {
        return $quote->items->whereNotNull('tax_breakdown')->count() > 0;
    }
}
