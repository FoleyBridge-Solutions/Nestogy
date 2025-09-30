<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Financial\Services\RecurringBillingPerformanceService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

/**
 * ProcessRecurringBilling Command
 *
 * High-performance Artisan command for processing recurring billing at scale.
 * Optimized to handle 10,000+ invoices within 30 minutes with comprehensive
 * monitoring, error handling, and recovery mechanisms.
 */
class ProcessRecurringBilling extends Command
{
    private const DEFAULT_TIMEOUT = 30;

    private const DEFAULT_BATCH_SIZE = 100;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-billing:process
                           {--company= : Process only for specific company ID}
                           {--date-from= : Start date for processing (YYYY-MM-DD)}
                           {--date-to= : End date for processing (YYYY-MM-DD)}
                           {--batch-size=500 : Number of records per batch}
                           {--max-concurrent=5 : Maximum concurrent batches}
                           {--dry-run : Show what would be processed without actually processing}
                           {--force : Force processing even if another instance is running}
                           {--verbose : Show detailed progress information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring billing records with high-performance optimization';

    /**
     * Performance service instance
     */
    protected $performanceService;

    /**
     * Command start time
     */
    protected $startTime;

    /**
     * Create a new command instance.
     */
    public function __construct(RecurringBillingPerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->startTime = microtime(true);

        try {
            // Check for running instances unless forced
            if (!$this->option('force') && $this->isAlreadyRunning()) {
                $this->error('Another instance of this command is already running. Use --force to override.');
                return self::FAILURE;
            }

            // Set process lock
            $this->setProcessLock();

            $this->displayHeader();

            // Build processing options
            $options = $this->buildProcessingOptions();

            if ($this->option('dry-run')) {
                return $this->performDryRun($options);
            }

            // Execute bulk processing
            $results = $this->performanceService->processBulkRecurringBilling($options);

            // Display results
            $this->displayResults($results);

            // Log completion
            $this->logCompletion($results);

            return $this->determineExitCode($results);

        } catch (Throwable $e) {
            $this->handleCommandError($e);
            return self::FAILURE;

        } finally {
            $this->releaseProcessLock();
        }
    }

    /**
     * Display command header
     */
    protected function displayHeader(): void
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔄 VoIP Recurring Billing High-Performance Processor');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('Started: ' . now()->format('Y-m-d H:i:s'));

        if ($this->option('company')) {
            $this->info('Company Filter: ' . $this->option('company'));
        }

        if ($this->option('date-from') || $this->option('date-to')) {
            $this->info('Date Range: ' .
                ($this->option('date-from') ?? 'Any') . ' to ' .
                ($this->option('date-to') ?? 'Any'));
        }

        $this->info('Batch Size: ' . $this->option('batch-size'));
        $this->info('Max Concurrent: ' . $this->option('max-concurrent'));
        $this->newLine();
    }

    /**
     * Build processing options from command arguments
     */
    protected function buildProcessingOptions(): array
    {
        $options = [
            'batch_size' => (int) $this->option('batch-size'),
            'max_concurrent' => (int) $this->option('max-concurrent'),
        ];

        if ($this->option('company')) {
            $options['company_id'] = (int) $this->option('company');
        }

        if ($this->option('date-from')) {
            $options['date_from'] = Carbon::parse($this->option('date-from'));
        }

        if ($this->option('date-to')) {
            $options['date_to'] = Carbon::parse($this->option('date-to'));
        }

        return $options;
    }

    /**
     * Perform dry run showing what would be processed
     */
    protected function performDryRun(array $options): int
    {
        $this->warn('🔍 DRY RUN MODE - No actual processing will occur');
        $this->newLine();

        // Get records that would be processed
        $query = \App\Models\Recurring::where('status', 'active')
            ->where('auto_generate', true);

        if (isset($options['company_id'])) {
            $query->where('company_id', $options['company_id']);
        }

        if (isset($options['date_from'])) {
            $query->where('next_billing_date', '>=', $options['date_from']);
        }

        if (isset($options['date_to'])) {
            $query->where('next_billing_date', '<=', $options['date_to']);
        } else {
            $query->where('next_billing_date', '<=', now());
        }

        $count = $query->count();
        $totalBatches = ceil($count / $options['batch_size']);
        $estimatedTime = ($count / 333.33) * 60; // Assuming 333.33 records per minute

        $this->info("📊 Processing Summary:");
        $this->info("   Records to process: {$count}");
        $this->info("   Estimated batches: {$totalBatches}");
        $this->info("   Estimated time: " . gmdate('H:i:s', (int) $estimatedTime));
        $this->newLine();

        // Show sample records
        $sampleRecords = $query->with('client:id,name')
            ->limit(5)
            ->get(['id', 'client_id', 'next_billing_date', 'amount', 'billing_frequency']);

        if ($sampleRecords->isNotEmpty()) {
            $this->info("📋 Sample Records:");
            foreach ($sampleRecords as $record) {
                $this->info("   ID: {$record->id} | Client: {$record->client->name} | Amount: \${$record->amount} | Due: {$record->next_billing_date}");
            }
        }

        $this->newLine();
        $this->info('✅ Dry run completed successfully');

        return self::SUCCESS;
    }

    /**
     * Display processing results
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📈 Processing Results');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->info("✅ Processed: {$results['processed']}");
        $this->info("❌ Failed: {$results['failed']}");
        $this->info("⏭️  Skipped: {$results['skipped']}");
        $this->info("📦 Batches: {$results['batches_processed']}");
        $this->info("⏱️  Duration: " . gmdate('H:i:s', (int) $results['processing_time']));

        $recordsPerSecond = $results['processed'] / max($results['processing_time'], 1);
        $this->info("🚀 Rate: " . number_format($recordsPerSecond, 2) . " records/second");

        $successRate = ($results['processed'] / max($results['processed'] + $results['failed'], 1)) * 100;
        $this->info("📊 Success Rate: " . number_format($successRate, 2) . "%");

        // Show errors if any
        if (!empty($results['errors']) && $this->option('verbose')) {
            $this->newLine();
            $this->warn('🚨 Error Details:');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->warn("   Record {$error['recurring_id']}: {$error['error']}");
            }

            if (count($results['errors']) > 10) {
                $remaining = count($results['errors']) - 10;
                $this->warn("   ... and {$remaining} more errors");
            }
        }

        $this->newLine();
    }

    /**
     * Log command completion
     */
    protected function logCompletion(array $results): void
    {
        Log::info('Recurring billing processing completed', [
            'command' => 'recurring-billing:process',
            'results' => $results,
            'options' => [
                'company' => $this->option('company'),
                'date_from' => $this->option('date-from'),
                'date_to' => $this->option('date-to'),
                'batch_size' => $this->option('batch-size'),
                'dry_run' => $this->option('dry-run'),
            ],
            'execution_time' => microtime(true) - $this->startTime,
        ]);
    }

    /**
     * Determine exit code based on results
     */
    protected function determineExitCode(array $results): int
    {
        // If more than 10% failed, consider it a partial failure
        $failureRate = $results['failed'] / max($results['processed'] + $results['failed'], 1);

        if ($failureRate > 0.1) {
            $this->warn('⚠️  High failure rate detected (' . number_format($failureRate * 100, 1) . '%)');
            return self::FAILURE;
        }

        if ($results['failed'] > 0) {
            $this->warn('⚠️  Some records failed to process');
        } else {
            $this->info('✅ All records processed successfully');
        }

        return self::SUCCESS;
    }

    /**
     * Handle command errors
     */
    protected function handleCommandError(Throwable $e): void
    {
        $this->error('❌ Critical Error: ' . $e->getMessage());

        if ($this->option('verbose')) {
            $this->error('Stack Trace:');
            $this->error($e->getTraceAsString());
        }

        Log::critical('Recurring billing command failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'options' => $this->options(),
        ]);
    }

    /**
     * Check if another instance is already running
     */
    protected function isAlreadyRunning(): bool
    {
        $lockFile = storage_path('app/locks/recurring-billing-process.lock');

        if (!file_exists($lockFile)) {
            return false;
        }

        $lockTime = filemtime($lockFile);
        $maxAge = config('recurring-billing.automation.max_processing_window', 4) * 3600;

        // Consider stale if older than max processing window
        if (time() - $lockTime > $maxAge) {
            unlink($lockFile);
            return false;
        }

        return true;
    }

    /**
     * Set process lock
     */
    protected function setProcessLock(): void
    {
        $lockDir = storage_path('app/locks');
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        $lockFile = $lockDir . '/recurring-billing-process.lock';
        file_put_contents($lockFile, getmypid());
    }

    /**
     * Release process lock
     */
    protected function releaseProcessLock(): void
    {
        $lockFile = storage_path('app/locks/recurring-billing-process.lock');
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
    }
}
