<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domains\Financial\Services\RecurringBillingService;
use App\Domains\Financial\Models\Invoice;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessFailedPayments extends Command
{
    private const MAX_RETRIES = 3;

    // Class constants to reduce duplication
    private const STATUS_FAILED = 'failed';
    private const STATUS_RETRYING = 'retrying';
    private const MAX_RETRY_ATTEMPTS = 3;
    private const MSG_PAYMENT_START = 'Processing failed payments...';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:retry-failed
                            {--company= : Process for specific company ID}
                            {--max-attempts=self::MAX_RETRIES : Maximum retry attempts}
                            {--dry-run : Preview without processing payments}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed payment attempts for overdue invoices';

    protected RecurringBillingService $billingService;
    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        RecurringBillingService $billingService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->billingService = $billingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting failed payment retry process...');

        $companyId = $this->option('company');
        $maxAttempts = $this->option('max-attempts');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY RUN mode - no payments will be processed');
        }

        try {
            // Get overdue invoices with payment methods
            $query = Invoice::where('status', '!=', 'paid')
                ->where('due_date', '<', now())
                ->whereNotNull('payment_method_id')
                ->where(function ($q) use ($maxAttempts) {
                    $q->whereNull('payment_attempts')
                        ->orWhere('payment_attempts', '<', $maxAttempts);
                })
                ->with(['client', 'paymentMethod']);

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $invoices = $query->get();

            if ($invoices->isEmpty()) {
                $this->info('No invoices requiring payment retry found');
                return Command::SUCCESS;
            }

            $this->info("Found {$invoices->count()} invoices to retry");

            $results = [
                'successful' => 0,
                'failed' => 0,
                'skipped' => 0,
                'total_collected' => 0
            ];

            $progressBar = $this->output->createProgressBar($invoices->count());
            $progressBar->start();

            foreach ($invoices as $invoice) {
                $progressBar->advance();

                // Check if we should retry based on last attempt
                if ($this->shouldRetryPayment($invoice)) {
                    if (!$dryRun) {
                        $result = $this->billingService->retryFailedPayment($invoice);

                        if ($result['success']) {
                            $results['successful']++;
                            $results['total_collected'] += $invoice->total;

                            // Send success notification
                            $this->notificationService->notifyPaymentSuccess(
                                $invoice,
                                $result['transaction_id'] ?? null
                            );
                        } else {
                            $results['failed']++;

                            // Send failure notification if max attempts reached
                            if ($invoice->payment_attempts >= $maxAttempts) {
                                $this->notificationService->notifyPaymentFailed(
                                    $invoice,
                                    $result['error'] ?? 'Maximum retry attempts reached'
                                );
                            }
                        }
                    } else {
                        $this->info("\nWould retry payment for invoice {$invoice->invoice_number}");
                        $results['skipped']++;
                    }
                } else {
                    $results['skipped']++;
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->displayResults($results, $dryRun);

            // Log the process
            Log::info('Failed payment retry process completed', [
                'company_id' => $companyId,
                'dry_run' => $dryRun,
                'results' => $results
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to process payment retries: ' . $e->getMessage());
            Log::error('Payment retry process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Check if payment should be retried based on last attempt
     */
    private function shouldRetryPayment(Invoice $invoice): bool
    {
        if (!$invoice->last_payment_attempt) {
            return true;
        }

        $hoursSinceLastAttempt = Carbon::parse($invoice->last_payment_attempt)
            ->diffInHours(now());

        // Use exponential backoff: 1 hour, 4 hours, 12 hours, 24 hours
        $attempts = $invoice->payment_attempts ?? 0;
        $waitHours = min(pow(2, $attempts) * 2, 24);

        return $hoursSinceLastAttempt >= $waitHours;
    }

    /**
     * Display process results
     */
    private function displayResults(array $results, bool $dryRun)
    {
        $this->info('Payment Retry Process Complete');
        $this->info('================================');

        if ($dryRun) {
            $this->warn('DRY RUN - No actual payments were processed');
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Successful Payments', $results['successful']],
                ['Failed Payments', $results['failed']],
                ['Skipped (Too Soon)', $results['skipped']],
                ['Total Collected', '$' . number_format($results['total_collected'], 2)]
            ]
        );

        if ($results['successful'] > 0) {
            $this->info("Successfully collected ${$results['total_collected']} from {$results['successful']} invoices");
        }

        if ($results['failed'] > 0) {
            $this->warn("{$results['failed']} payments failed and may need manual intervention");
        }
    }
}
