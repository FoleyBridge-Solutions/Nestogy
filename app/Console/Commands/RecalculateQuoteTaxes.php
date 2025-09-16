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
        
        $options = $this->parseOptions();
        $this->displayRunMode($options);
        
        $quotes = $this->getQuotesToProcess($options);
        
        if ($quotes->isEmpty()) {
            $this->warn('No quotes found matching criteria');
            return 0;
        }
        
        $stats = $this->processQuotes($quotes, $options);
        $this->displayResults($stats, $options);
        
        return $stats['errors'] > 0 ? 1 : 0;
    }

    /**
     * Parse command options into structured array
     */
    private function parseOptions(): array
    {
        return [
            'companyId' => $this->option('company'),
            'quoteId' => $this->option('quote'),
            'dryRun' => $this->option('dry-run'),
            'force' => $this->option('force'),
            'verbose' => $this->option('verbose')
        ];
    }

    /**
     * Display run mode information
     */
    private function displayRunMode(array $options): void
    {
        if ($options['dryRun']) {
            $this->warn('DRY RUN MODE: No changes will be saved');
        }
    }

    /**
     * Get quotes to process based on options
     */
    private function getQuotesToProcess(array $options)
    {
        $query = Quote::with(['items', 'client']);
        
        $this->applyFilters($query, $options);
        
        return $query->get();
    }

    /**
     * Apply filters to the query based on options
     */
    private function applyFilters($query, array $options): void
    {
        if ($options['companyId']) {
            $query->where('company_id', $options['companyId']);
            $this->info("Processing quotes for company ID: {$options['companyId']}");
            return;
        }
        
        if ($options['quoteId']) {
            $query->where('id', $options['quoteId']);
            $this->info("Processing specific quote ID: {$options['quoteId']}");
            return;
        }
        
        // Default: last 12 months
        $query->where('created_at', '>=', now()->subMonths(12));
        $this->info('Processing quotes from last 12 months');
    }

    /**
     * Process all quotes and return statistics
     */
    private function processQuotes($quotes, array $options): array
    {
        $this->info("Found {$quotes->count()} quotes to process");
        
        $progressBar = $this->output->createProgressBar($quotes->count());
        $progressBar->start();
        
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0
        ];
        
        foreach ($quotes as $quote) {
            $progressBar->advance();
            $this->processSingleQuote($quote, $stats, $options);
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        return $stats;
    }

    /**
     * Process a single quote and update statistics
     */
    private function processSingleQuote(Quote $quote, array &$stats, array $options): void
    {
        $stats['processed']++;
        
        try {
            if ($this->shouldSkipQuote($quote, $options)) {
                $stats['skipped']++;
                return;
            }
            
            $taxChange = $this->recalculateQuoteTax($quote, $options);
            
            if ($taxChange['hasChanged']) {
                $stats['updated']++;
                $this->logTaxChange($quote, $taxChange, $options);
            }
            
        } catch (\Exception $e) {
            $stats['errors']++;
            $this->logError($quote, $e, $options);
        }
    }

    /**
     * Determine if quote should be skipped
     */
    private function shouldSkipQuote(Quote $quote, array $options): bool
    {
        if (!$options['force'] && $this->hasModernTaxBreakdown($quote)) {
            return true;
        }
        
        if ($quote->items->isEmpty()) {
            return true;
        }
        
        return false;
    }

    /**
     * Recalculate tax for a quote and return change information
     */
    private function recalculateQuoteTax(Quote $quote, array $options): array
    {
        $oldTaxAmount = $quote->items->sum('tax');
        
        if (!$options['dryRun']) {
            DB::transaction(function () use ($quote) {
                $quoteService = new QuoteService();
                $quoteService->calculatePricing($quote);
            });
            $quote->refresh();
        }
        
        $newTaxAmount = $quote->items->sum('tax');
        
        return [
            'hasChanged' => abs($oldTaxAmount - $newTaxAmount) > 0.01,
            'oldAmount' => $oldTaxAmount,
            'newAmount' => $newTaxAmount
        ];
    }

    /**
     * Log tax change if verbose mode is enabled
     */
    private function logTaxChange(Quote $quote, array $taxChange, array $options): void
    {
        if (!$options['verbose']) {
            return;
        }
        
        $this->newLine();
        $this->line("Quote {$quote->id}: Tax changed from \${$taxChange['oldAmount']} to \${$taxChange['newAmount']}");
    }

    /**
     * Log error if verbose mode is enabled
     */
    private function logError(Quote $quote, \Exception $e, array $options): void
    {
        if (!$options['verbose']) {
            return;
        }
        
        $this->newLine();
        $this->error("Error processing quote {$quote->id}: " . $e->getMessage());
    }

    /**
     * Display final results and summary
     */
    private function displayResults(array $stats, array $options): void
    {
        $this->info('Tax recalculation completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $stats['processed']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );
        
        $this->displayWarnings($stats, $options);
    }

    /**
     * Display warnings based on results
     */
    private function displayWarnings(array $stats, array $options): void
    {
        if ($options['dryRun'] && $stats['updated'] > 0) {
            $this->warn("Run without --dry-run to apply {$stats['updated']} changes");
        }
        
        if ($stats['errors'] > 0) {
            $this->error("Encountered {$stats['errors']} errors. Use --verbose to see details.");
        }
    }

    /**
     * Check if quote has modern tax breakdown data
     */
    private function hasModernTaxBreakdown(Quote $quote): bool
    {
        return $quote->items->whereNotNull('tax_breakdown')->count() > 0;
    }
}
