<?php

namespace App\Jobs;

use App\Models\Recurring;
use App\Domains\Product\Services\VoIPUsageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ProcessVoIPUsageData Job
 * 
 * Handles processing of VoIP usage data for usage-based and tiered billing.
 */
class ProcessVoIPUsageData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for large datasets
    public $tries = 3;
    public $maxExceptions = 2;

    protected $recurringId;
    protected $usageData;
    protected $processingOptions;

    /**
     * Create a new job instance.
     */
    public function __construct(int $recurringId, array $usageData, array $processingOptions = [])
    {
        $this->recurringId = $recurringId;
        $this->usageData = $usageData;
        $this->processingOptions = array_merge([
            'validate_data' => true,
            'calculate_costs' => true,
            'update_recurring' => true,
            'generate_reports' => false
        ], $processingOptions);

        $this->onQueue('usage-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(VoIPUsageService $voipUsageService): void
    {
        try {
            $recurring = Recurring::with('client')->findOrFail($this->recurringId);

            Log::info('Starting VoIP usage data processing', [
                'recurring_id' => $this->recurringId,
                'client_id' => $recurring->client_id,
                'data_records_count' => count($this->usageData['records'] ?? [])
            ]);

            // Process usage data
            $results = $voipUsageService->processUsageData($recurring, $this->usageData);

            // Update recurring billing record with latest usage
            if ($this->processingOptions['update_recurring'] && $results['processed_count'] > 0) {
                $this->updateRecurringUsageMetrics($recurring, $results);
            }

            // Generate usage reports if requested
            if ($this->processingOptions['generate_reports'] && $results['processed_count'] > 0) {
                $this->generateUsageReports($recurring, $results);
            }

            // Check for billing alerts (overage, unusual usage patterns)
            $this->checkUsageAlerts($recurring, $results);

            Log::info('VoIP usage data processing completed', [
                'recurring_id' => $this->recurringId,
                'processed_count' => $results['processed_count'],
                'total_usage' => $results['total_usage'],
                'total_cost' => $results['total_cost']
            ]);

        } catch (\Exception $e) {
            Log::error('VoIP usage data processing failed', [
                'recurring_id' => $this->recurringId,
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
        Log::error('VoIP usage data processing job failed permanently', [
            'recurring_id' => $this->recurringId,
            'data_records_count' => count($this->usageData['records'] ?? []),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Update recurring billing record with usage metrics
     */
    protected function updateRecurringUsageMetrics(Recurring $recurring, array $results): void
    {
        $metadata = $recurring->metadata ?? [];
        $currentPeriod = now()->format('Y-m');

        // Update current period usage metrics
        $metadata['usage_metrics'][$currentPeriod] = [
            'processed_count' => $results['processed_count'],
            'total_usage' => $results['total_usage'],
            'total_cost' => $results['total_cost'],
            'last_processed_at' => now()->toISOString(),
            'processing_errors' => count($results['errors'] ?? [])
        ];

        // Keep only last 12 months of metrics
        if (isset($metadata['usage_metrics']) && count($metadata['usage_metrics']) > 12) {
            $metadata['usage_metrics'] = array_slice($metadata['usage_metrics'], -12, null, true);
        }

        $recurring->update(['metadata' => $metadata]);
    }

    /**
     * Generate usage reports
     */
    protected function generateUsageReports(Recurring $recurring, array $results): void
    {
        // Dispatch job to generate detailed usage reports
        // GenerateVoIPUsageReport::dispatch($this->recurringId, $results)
        //     ->onQueue('reporting')
        //     ->delay(now()->addMinutes(5));
    }

    /**
     * Check for usage-based alerts
     */
    protected function checkUsageAlerts(Recurring $recurring, array $results): void
    {
        $alerts = [];

        // Check for overage alerts
        $serviceTiers = $recurring->service_tiers ?? [];
        foreach ($serviceTiers as $tier) {
            $serviceType = $tier['service_type'];
            $allowance = $tier['monthly_allowance'] ?? 0;
            $currentUsage = $results['usage_by_type'][$serviceType] ?? 0;

            // Alert if usage exceeds 80% of allowance
            if ($allowance > 0 && $currentUsage >= ($allowance * 0.8)) {
                $percentage = round(($currentUsage / $allowance) * 100, 1);
                
                $alerts[] = [
                    'type' => 'overage_warning',
                    'service_type' => $serviceType,
                    'current_usage' => $currentUsage,
                    'allowance' => $allowance,
                    'usage_percentage' => $percentage,
                    'severity' => $percentage >= 100 ? 'critical' : 'warning'
                ];
            }
        }

        // Check for unusual usage patterns (spike detection)
        $averageUsage = $this->calculateAverageUsage($recurring);
        if ($averageUsage > 0 && $results['total_usage'] > ($averageUsage * 2)) {
            $alerts[] = [
                'type' => 'usage_spike',
                'current_usage' => $results['total_usage'],
                'average_usage' => $averageUsage,
                'spike_multiplier' => round($results['total_usage'] / $averageUsage, 1),
                'severity' => 'warning'
            ];
        }

        // Dispatch alert notifications if any alerts found
        if (!empty($alerts)) {
            $this->dispatchUsageAlerts($recurring, $alerts);
        }
    }

    /**
     * Calculate average usage from historical data
     */
    protected function calculateAverageUsage(Recurring $recurring): float
    {
        $metadata = $recurring->metadata ?? [];
        $usageMetrics = $metadata['usage_metrics'] ?? [];

        if (empty($usageMetrics)) {
            return 0.0;
        }

        $totalUsage = array_sum(array_column($usageMetrics, 'total_usage'));
        return $totalUsage / count($usageMetrics);
    }

    /**
     * Dispatch usage alert notifications
     */
    protected function dispatchUsageAlerts(Recurring $recurring, array $alerts): void
    {
        foreach ($alerts as $alert) {
            // NotifyUsageAlert::dispatch($recurring->id, $alert)
            //     ->onQueue('notifications');
            
            Log::warning('VoIP usage alert triggered', [
                'recurring_id' => $recurring->id,
                'client_id' => $recurring->client_id,
                'alert_type' => $alert['type'],
                'severity' => $alert['severity'],
                'details' => $alert
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'usage-processing',
            'voip-usage',
            'recurring:' . $this->recurringId
        ];
    }
}