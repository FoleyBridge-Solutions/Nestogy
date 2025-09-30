<?php

namespace App\Jobs;

use App\Domains\Financial\Services\VoIPTaxComplianceService;
use App\Domains\Financial\Services\VoIPTaxReportingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessVoIPTaxCompliance Job
 *
 * Handles VoIP tax compliance processing and reporting for recurring billing.
 */
class ProcessVoIPTaxCompliance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180; // 3 minutes

    public $tries = 2;

    public $maxExceptions = 1;

    protected $companyId;

    protected $taxCalculations;

    /**
     * Create a new job instance.
     */
    public function __construct(int $companyId, array $taxCalculations)
    {
        $this->companyId = $companyId;
        $this->taxCalculations = $taxCalculations;
        $this->onQueue('tax-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(
        VoIPTaxComplianceService $complianceService,
        VoIPTaxReportingService $reportingService
    ): void {
        try {
            Log::info('Starting VoIP tax compliance processing', [
                'company_id' => $this->companyId,
                'calculations_count' => count($this->taxCalculations),
            ]);

            // Process compliance requirements
            $complianceResults = $complianceService->processCompliance(
                $this->companyId,
                $this->taxCalculations
            );

            // Update tax reporting data
            $reportingService->updateTaxReporting(
                $this->companyId,
                $complianceResults
            );

            // Check for compliance violations
            $violations = $complianceService->checkViolations(
                $this->companyId,
                $complianceResults
            );

            if (! empty($violations)) {
                Log::warning('VoIP tax compliance violations detected', [
                    'company_id' => $this->companyId,
                    'violations_count' => count($violations),
                    'violations' => $violations,
                ]);

                // Dispatch notification job for violations
                $this->dispatchViolationNotifications($violations);
            }

            Log::info('VoIP tax compliance processing completed', [
                'company_id' => $this->companyId,
                'processed_calculations' => count($this->taxCalculations),
                'violations_found' => count($violations),
            ]);

        } catch (\Exception $e) {
            Log::error('VoIP tax compliance processing failed', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('VoIP tax compliance job failed permanently', [
            'company_id' => $this->companyId,
            'calculations_count' => count($this->taxCalculations),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Dispatch violation notification jobs
     */
    protected function dispatchViolationNotifications(array $violations): void
    {
        // Dispatch email notifications for critical violations
        foreach ($violations as $violation) {
            if ($violation['severity'] === 'critical') {
                // NotifyTaxComplianceViolation::dispatch($this->companyId, $violation)
                //     ->onQueue('notifications');
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'tax-compliance',
            'voip-tax',
            'company:'.$this->companyId,
        ];
    }
}
