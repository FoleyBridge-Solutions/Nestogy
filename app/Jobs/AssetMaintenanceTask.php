<?php

namespace App\Jobs;

use App\Domains\Asset\Services\AssetMaintenanceService;
use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Asset Maintenance Task Job
 *
 * Executes automated maintenance workflows for assets through RMM systems.
 */
class AssetMaintenanceTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Asset $asset;

    protected string $workflowType;

    protected array $options;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [300, 600, 1200]; // 5m, 10m, 20m
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Asset $asset, string $workflowType, array $options = [])
    {
        $this->asset = $asset;
        $this->workflowType = $workflowType;
        $this->options = $options;
        $this->queue = 'maintenance';
    }

    /**
     * Execute the job.
     */
    public function handle(AssetMaintenanceService $maintenanceService): void
    {
        $startTime = microtime(true);

        try {
            Log::info('Starting asset maintenance task', [
                'asset_id' => $this->asset->id,
                'asset_name' => $this->asset->name,
                'workflow_type' => $this->workflowType,
                'attempt' => $this->attempts(),
                'options' => $this->options,
            ]);

            // Check if asset supports remote management
            if (! $this->asset->supportsRemoteManagement()) {
                Log::warning('Asset does not support remote management', [
                    'asset_id' => $this->asset->id,
                    'workflow_type' => $this->workflowType,
                ]);

                return;
            }

            // Execute the maintenance workflow
            $result = $maintenanceService->executeMaintenanceWorkflow($this->asset, $this->workflowType);

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                Log::info('Asset maintenance task completed successfully', [
                    'asset_id' => $this->asset->id,
                    'workflow_type' => $this->workflowType,
                    'processing_time_ms' => $processingTime,
                    'results_summary' => $this->summarizeResults($result),
                ]);

                // Update asset maintenance date
                $this->asset->update([
                    'next_maintenance_date' => $this->calculateNextMaintenanceDate(),
                ]);

            } else {
                Log::warning('Asset maintenance task completed with errors', [
                    'asset_id' => $this->asset->id,
                    'workflow_type' => $this->workflowType,
                    'processing_time_ms' => $processingTime,
                    'errors' => $result['errors'],
                ]);
            }

            // Store maintenance record (would typically save to database)
            $this->storeMaintenanceRecord($result, $processingTime);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('Asset maintenance task failed', [
                'asset_id' => $this->asset->id,
                'workflow_type' => $this->workflowType,
                'attempt' => $this->attempts(),
                'processing_time_ms' => $processingTime,
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
        Log::error('Asset maintenance task failed permanently', [
            'asset_id' => $this->asset->id,
            'workflow_type' => $this->workflowType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally create a ticket or alert for failed maintenance
        $this->createMaintenanceFailureAlert($exception);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'asset-maintenance',
            'asset:'.$this->asset->id,
            'workflow:'.$this->workflowType,
            'company:'.$this->asset->company_id,
        ];
    }

    /**
     * Summarize maintenance results for logging.
     */
    protected function summarizeResults(array $result): array
    {
        $summary = [
            'workflow_type' => $result['workflow_type'],
            'success' => $result['success'],
            'completed_at' => $result['completed_at'],
        ];

        // Add specific summaries based on workflow type
        switch ($this->workflowType) {
            case 'weekly_maintenance':
                $summary['tasks_completed'] = count($result['results']);
                break;

            case 'security_updates':
                $summary['updates_installed'] = count($result['results']['installation']['updates'] ?? []);
                $summary['reboot_required'] = $result['results']['installation']['reboot_required'] ?? false;
                break;

            case 'performance_optimization':
                $summary['optimizations_applied'] = count($result['results']);
                break;

            case 'health_check':
                $summary['health_score'] = $result['results']['health_score']['score'] ?? null;
                break;

            case 'cleanup':
                $summary['space_reclaimed'] = $result['results']['space_reclaimed']['total_space_freed'] ?? '0 MB';
                break;
        }

        return $summary;
    }

    /**
     * Calculate next maintenance date based on workflow type.
     */
    protected function calculateNextMaintenanceDate(): ?\Carbon\Carbon
    {
        return match ($this->workflowType) {
            'weekly_maintenance' => now()->addWeek(),
            'security_updates' => now()->addDay(),
            'performance_optimization' => now()->addMonth(),
            'health_check' => now()->addDay(),
            'cleanup' => now()->addWeek(),
            default => null,
        };
    }

    /**
     * Store maintenance record for historical tracking.
     */
    protected function storeMaintenanceRecord(array $result, float $processingTime): void
    {
        // This would typically insert into a maintenance_records table
        $record = [
            'asset_id' => $this->asset->id,
            'workflow_type' => $this->workflowType,
            'executed_at' => now(),
            'success' => $result['success'],
            'processing_time_ms' => $processingTime,
            'results' => $result,
            'options' => $this->options,
        ];

        Log::info('Maintenance record stored', [
            'asset_id' => $this->asset->id,
            'workflow_type' => $this->workflowType,
            'record' => $record,
        ]);
    }

    /**
     * Create alert for maintenance failure.
     */
    protected function createMaintenanceFailureAlert(\Throwable $exception): void
    {
        // This would typically create a ticket or send an alert
        Log::critical('Creating maintenance failure alert', [
            'asset_id' => $this->asset->id,
            'asset_name' => $this->asset->name,
            'workflow_type' => $this->workflowType,
            'error' => $exception->getMessage(),
            'company_id' => $this->asset->company_id,
        ]);

        // Example: Create a support ticket automatically
        // Ticket::create([
        //     'company_id' => $this->asset->company_id,
        //     'client_id' => $this->asset->client_id,
        //     'asset_id' => $this->asset->id,
        //     'subject' => "Maintenance Failure: {$this->workflowType} for {$this->asset->name}",
        //     'description' => "Automated maintenance task '{$this->workflowType}' failed for asset {$this->asset->name}.\n\nError: {$exception->getMessage()}",
        //     'priority' => 'high',
        //     'status' => 'open',
        //     'source' => 'system',
        // ]);
    }

    /**
     * Determine if the job should be retried.
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // Don't retry for certain types of errors
        $nonRetryableErrors = [
            'Asset does not support remote management',
            'No RMM mapping found',
            'Integration is not active',
        ];

        foreach ($nonRetryableErrors as $error) {
            if (str_contains($exception->getMessage(), $error)) {
                return false;
            }
        }

        return $this->attempts() < $this->tries;
    }
}
