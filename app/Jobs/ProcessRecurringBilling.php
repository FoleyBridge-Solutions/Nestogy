<?php

namespace App\Jobs;

use App\Models\Recurring;
use App\Services\RecurringBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ProcessRecurringBilling Job
 * 
 * Automated job for processing recurring billing invoices.
 * Handles bulk invoice generation with comprehensive error handling and logging.
 */
class ProcessRecurringBilling implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $maxExceptions = 3;

    protected $companyId;
    protected $billingDate;
    protected $dryRun;
    protected $batchSize;
    protected $recurringIds;
    protected $filters;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $companyId,
        ?Carbon $billingDate = null,
        bool $dryRun = false,
        int $batchSize = 100,
        ?array $recurringIds = null,
        array $filters = []
    ) {
        $this->companyId = $companyId;
        $this->billingDate = $billingDate ?? now();
        $this->dryRun = $dryRun;
        $this->batchSize = $batchSize;
        $this->recurringIds = $recurringIds;
        $this->filters = $filters;

        $this->onQueue('recurring-billing');
    }

    /**
     * Execute the job.
     */
    public function handle(RecurringBillingService $recurringBillingService): void
    {
        Log::info('Starting recurring billing processing job', [
            'company_id' => $this->companyId,
            'billing_date' => $this->billingDate->toDateString(),
            'dry_run' => $this->dryRun,
            'batch_size' => $this->batchSize,
            'recurring_ids_count' => $this->recurringIds ? count($this->recurringIds) : 0
        ]);

        try {
            $results = $recurringBillingService->bulkGenerateInvoices([
                'company_id' => $this->companyId,
                'billing_date' => $this->billingDate,
                'dry_run' => $this->dryRun,
                'batch_size' => $this->batchSize,
                'recurring_ids' => $this->recurringIds,
                'filters' => $this->filters
            ]);

            Log::info('Recurring billing processing completed successfully', [
                'company_id' => $this->companyId,
                'processed_count' => $results['processed_count'] ?? 0,
                'generated_count' => $results['generated_count'] ?? 0,
                'failed_count' => $results['failed_count'] ?? 0,
                'total_amount' => $results['total_amount'] ?? 0
            ]);

            // Dispatch follow-up jobs if needed
            if (!$this->dryRun && ($results['generated_count'] ?? 0) > 0) {
                // Dispatch email sending jobs
                $this->dispatchEmailJobs($results['generated_invoices'] ?? []);
                
                // Dispatch tax processing jobs
                $this->dispatchTaxProcessingJobs($results['tax_calculations'] ?? []);
            }

        } catch (\Exception $e) {
            Log::error('Recurring billing processing failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Recurring billing job failed permanently', [
            'company_id' => $this->companyId,
            'billing_date' => $this->billingDate->toDateString(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Dispatch email sending jobs for generated invoices
     */
    protected function dispatchEmailJobs(array $generatedInvoices): void
    {
        foreach ($generatedInvoices as $invoice) {
            if ($invoice['recurring']['auto_send'] ?? false) {
                SendRecurringInvoiceEmail::dispatch($invoice['id'])
                    ->onQueue('emails')
                    ->delay(now()->addMinutes(2));
            }
        }
    }

    /**
     * Dispatch tax processing jobs
     */
    protected function dispatchTaxProcessingJobs(array $taxCalculations): void
    {
        if (!empty($taxCalculations)) {
            ProcessVoIPTaxCompliance::dispatch($this->companyId, $taxCalculations)
                ->onQueue('tax-processing')
                ->delay(now()->addMinutes(5));
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'recurring-billing',
            'company:' . $this->companyId,
            'date:' . $this->billingDate->toDateString()
        ];
    }
}