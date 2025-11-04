<?php

namespace App\Jobs;

use App\Domains\Asset\Models\Asset;
use App\Domains\Integration\Models\RmmIntegration;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Events\AssetCommandExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PollRmmTaskStatus implements ShouldQueue
{
    use Queueable;

    protected string $taskId;
    protected int $assetId;
    protected int $rmmIntegrationId;
    protected string $commandDescription;
    protected string $commandType;
    protected string $executedBy;
    protected int $maxAttempts;
    protected int $currentAttempt;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $taskId,
        int $assetId,
        int $rmmIntegrationId,
        string $commandDescription,
        string $commandType = 'general',
        string $executedBy = 'System',
        int $maxAttempts = 30
    ) {
        $this->taskId = $taskId;
        $this->assetId = $assetId;
        $this->rmmIntegrationId = $rmmIntegrationId;
        $this->commandDescription = $commandDescription;
        $this->commandType = $commandType;
        $this->executedBy = $executedBy;
        $this->maxAttempts = $maxAttempts;
        $this->currentAttempt = 0;
    }

    /**
     * Execute the job.
     */
    public function handle(RmmServiceFactory $factory): void
    {
        try {
            $asset = Asset::findOrFail($this->assetId);
            $rmmIntegration = RmmIntegration::findOrFail($this->rmmIntegrationId);

            Log::info('Polling RMM task status', [
                'task_id' => $this->taskId,
                'asset_id' => $this->assetId,
                'attempt' => $this->currentAttempt,
            ]);

            $rmmService = $factory->make($rmmIntegration);
            $result = $rmmService->getTaskStatus($this->taskId);

            if (!$result['success']) {
                Log::warning('Failed to get task status', [
                    'task_id' => $this->taskId,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                // Retry with delay
                if ($this->currentAttempt < $this->maxAttempts) {
                    $this->retryWithDelay();
                } else {
                    $this->broadcastFailed('Failed to retrieve task status');
                }

                return;
            }

            $taskData = $result['data'] ?? [];
            $status = $taskData['status'] ?? 'unknown';

            Log::info('Task status retrieved', [
                'task_id' => $this->taskId,
                'status' => $status,
            ]);

            if ($status === 'completed' || $status === 'success') {
                // Task completed successfully
                $output = $taskData['output'] ?? null;

                event(new AssetCommandExecuted([
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'command' => $this->commandDescription,
                    'command_type' => $this->commandType,
                    'status' => 'completed',
                    'output' => $output,
                    'executed_by' => $this->executedBy,
                ]));

                // Log activity
                activity()
                    ->performedOn($asset)
                    ->withProperties([
                        'task_id' => $this->taskId,
                        'command' => $this->commandDescription,
                        'output' => $output,
                    ])
                    ->log('remote_command_completed');

                Log::info('Task completed successfully', [
                    'task_id' => $this->taskId,
                    'asset_id' => $asset->id,
                ]);

            } elseif ($status === 'failed' || $status === 'error') {
                // Task failed
                $error = $taskData['error'] ?? $taskData['output'] ?? 'Unknown error';

                $this->broadcastFailed($error);

                Log::error('Task failed', [
                    'task_id' => $this->taskId,
                    'asset_id' => $asset->id,
                    'error' => $error,
                ]);

            } elseif ($status === 'pending' || $status === 'running') {
                // Task still running, retry
                if ($this->currentAttempt < $this->maxAttempts) {
                    $this->retryWithDelay();
                } else {
                    $this->broadcastFailed('Task timed out after ' . $this->maxAttempts . ' attempts');
                    
                    Log::warning('Task timed out', [
                        'task_id' => $this->taskId,
                        'asset_id' => $asset->id,
                        'attempts' => $this->currentAttempt,
                    ]);
                }
            } else {
                // Unknown status
                Log::warning('Unknown task status', [
                    'task_id' => $this->taskId,
                    'status' => $status,
                ]);

                if ($this->currentAttempt < $this->maxAttempts) {
                    $this->retryWithDelay();
                } else {
                    $this->broadcastFailed('Unknown task status: ' . $status);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error polling RMM task status', [
                'task_id' => $this->taskId,
                'asset_id' => $this->assetId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry on exception
            if ($this->currentAttempt < $this->maxAttempts) {
                $this->retryWithDelay();
            } else {
                $this->broadcastFailed('Exception: ' . $e->getMessage());
            }
        }
    }

    /**
     * Retry the job with a delay.
     */
    protected function retryWithDelay(): void
    {
        $this->currentAttempt++;

        // Exponential backoff: 2s, 4s, 8s, then 10s
        $delay = min(pow(2, $this->currentAttempt), 10);

        $newJob = new PollRmmTaskStatus(
            $this->taskId,
            $this->assetId,
            $this->rmmIntegrationId,
            $this->commandDescription,
            $this->commandType,
            $this->executedBy,
            $this->maxAttempts
        );
        $newJob->currentAttempt = $this->currentAttempt;

        $newJob->delay(now()->addSeconds($delay));

        dispatch($newJob);

        Log::info('Retrying task status poll', [
            'task_id' => $this->taskId,
            'attempt' => $this->currentAttempt,
            'delay' => $delay,
        ]);
    }

    /**
     * Broadcast task failure.
     */
    protected function broadcastFailed(string $errorMessage): void
    {
        try {
            $asset = Asset::findOrFail($this->assetId);

            event(new AssetCommandExecuted([
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'command' => $this->commandDescription,
                'command_type' => $this->commandType,
                'status' => 'failed',
                'error_message' => $errorMessage,
                'executed_by' => $this->executedBy,
            ]));

            // Log activity
            activity()
                ->performedOn($asset)
                ->withProperties([
                    'task_id' => $this->taskId,
                    'command' => $this->commandDescription,
                    'error' => $errorMessage,
                ])
                ->log('remote_command_failed');
        } catch (\Exception $e) {
            Log::error('Failed to broadcast task failure', [
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PollRmmTaskStatus job failed permanently', [
            'task_id' => $this->taskId,
            'asset_id' => $this->assetId,
            'error' => $exception->getMessage(),
        ]);

        $this->broadcastFailed('Job failed: ' . $exception->getMessage());
    }
}
