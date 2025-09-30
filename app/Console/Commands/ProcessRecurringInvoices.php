<?php

namespace App\Console\Commands;

use App\Domains\Financial\Services\RecurringInvoiceService;
use App\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ProcessRecurringInvoices Command
 *
 * Artisan command to automatically process due recurring invoices
 * based on active contracts. Can be scheduled to run daily.
 */
class ProcessRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-recurring
                            {--date= : Process invoices due on specific date (Y-m-d format)}
                            {--dry-run : Show what would be processed without actually creating invoices}
                            {--company= : Process only for specific company ID}
                            {--force : Force generation even if invoices were already generated today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring invoices from active contracts';

    protected $recurringInvoiceService;

    /**
     * Create a new command instance.
     */
    public function __construct(RecurringInvoiceService $recurringInvoiceService)
    {
        parent::__construct();
        $this->recurringInvoiceService = $recurringInvoiceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting recurring invoice processing...');

        // Parse options
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $isDryRun = $this->option('dry-run');
        $companyId = $this->option('company');
        $force = $this->option('force');

        $this->info("Processing date: {$date->toDateString()}");

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
        }

        try {
            // Get due recurring invoices
            $query = RecurringInvoice::with(['contract', 'client', 'company'])
                ->where('status', RecurringInvoice::STATUS_ACTIVE)
                ->where('auto_generate', true)
                ->where('next_invoice_date', '<=', $date);

            if ($companyId) {
                $query->where('company_id', $companyId);
                $this->info("Filtering by company ID: {$companyId}");
            }

            // Filter out already processed today unless forced
            if (! $force) {
                $query->where(function ($q) use ($date) {
                    $q->whereNull('last_invoice_date')
                        ->orWhere('last_invoice_date', '<', $date->startOfDay());
                });
            }

            $dueRecurringInvoices = $query->get();

            if ($dueRecurringInvoices->isEmpty()) {
                $this->info('No recurring invoices due for processing.');

                return Command::SUCCESS;
            }

            $this->info("Found {$dueRecurringInvoices->count()} recurring invoice(s) due for processing");

            // Display summary table
            $this->displaySummaryTable($dueRecurringInvoices);

            if ($isDryRun) {
                $this->info('Dry run completed. No invoices were created.');

                return Command::SUCCESS;
            }

            // Confirm processing unless forced
            if (! $force && ! $this->confirm('Proceed with invoice generation?')) {
                $this->info('Processing cancelled.');

                return Command::SUCCESS;
            }

            // Process each recurring invoice
            $results = [
                'processed' => 0,
                'generated' => 0,
                'errors' => 0,
                'total_amount' => 0,
                'details' => [],
            ];

            $progressBar = $this->output->createProgressBar($dueRecurringInvoices->count());
            $progressBar->start();

            foreach ($dueRecurringInvoices as $recurring) {
                try {
                    $invoice = $this->processRecurringInvoice($recurring, $date);

                    if ($invoice) {
                        $results['generated']++;
                        $results['total_amount'] += $invoice->amount;
                        $results['details'][] = [
                            'recurring_id' => $recurring->id,
                            'invoice_id' => $invoice->id,
                            'client' => $recurring->client->name,
                            'amount' => $invoice->amount,
                            'status' => 'success',
                        ];
                    }

                    $results['processed']++;
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'recurring_id' => $recurring->id,
                        'client' => $recurring->client->name,
                        'error' => $e->getMessage(),
                        'status' => 'error',
                    ];

                    Log::error('Failed to process recurring invoice', [
                        'recurring_invoice_id' => $recurring->id,
                        'error' => $e->getMessage(),
                        'command' => 'process-recurring-invoices',
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->displayResults($results);

            // Log summary
            Log::info('Recurring invoice processing completed', [
                'processed' => $results['processed'],
                'generated' => $results['generated'],
                'errors' => $results['errors'],
                'total_amount' => $results['total_amount'],
                'date' => $date->toDateString(),
                'command' => 'process-recurring-invoices',
            ]);

            return $results['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error('Recurring invoice processing command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'command' => 'process-recurring-invoices',
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process a single recurring invoice
     */
    protected function processRecurringInvoice(RecurringInvoice $recurring, Carbon $date)
    {
        // Validate contract is still active
        if (! $recurring->contract || ! $recurring->contract->isActive()) {
            $this->warn("Skipping recurring invoice {$recurring->id} - contract not active");

            // Pause the recurring invoice
            $recurring->pause('Contract no longer active');

            return null;
        }

        // Generate the invoice
        return $this->recurringInvoiceService->generateInvoiceFromRecurring($recurring, $date);
    }

    /**
     * Display summary table of due invoices
     */
    protected function displaySummaryTable($recurringInvoices): void
    {
        $headers = ['ID', 'Client', 'Contract', 'Amount', 'Frequency', 'Next Due', 'Status'];

        $rows = $recurringInvoices->map(function ($recurring) {
            return [
                $recurring->id,
                $recurring->client->name ?? 'Unknown',
                $recurring->contract->contract_number ?? 'N/A',
                '$'.number_format($recurring->amount, 2),
                $recurring->billing_frequency_label,
                $recurring->next_invoice_date->format('M d, Y'),
                $recurring->contract->isActive() ? 'Ready' : 'Contract Inactive',
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    /**
     * Display processing results
     */
    protected function displayResults(array $results): void
    {
        $this->info('Processing Results:');
        $this->info("- Processed: {$results['processed']}");
        $this->info("- Generated: {$results['generated']}");
        $this->info("- Errors: {$results['errors']}");
        $this->info('- Total Amount: $'.number_format($results['total_amount'], 2));

        if ($results['errors'] > 0) {
            $this->newLine();
            $this->error('Errors occurred during processing:');

            foreach ($results['details'] as $detail) {
                if ($detail['status'] === 'error') {
                    $this->error("- Recurring ID {$detail['recurring_id']} ({$detail['client']}): {$detail['error']}");
                }
            }
        }

        if ($results['generated'] > 0) {
            $this->newLine();
            $this->info('Successfully generated invoices:');

            foreach ($results['details'] as $detail) {
                if ($detail['status'] === 'success') {
                    $this->info("- Invoice #{$detail['invoice_id']} for {$detail['client']}: $".number_format($detail['amount'], 2));
                }
            }
        }
    }
}
