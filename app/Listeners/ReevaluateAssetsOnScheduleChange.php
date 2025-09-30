<?php

namespace App\Listeners;

use App\Domains\Asset\Services\AssetSupportService;
use App\Events\ContractScheduleActivated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ReevaluateAssetsOnScheduleChange implements ShouldQueue
{
    use InteractsWithQueue;

    protected AssetSupportService $assetSupportService;

    /**
     * Create the event listener.
     */
    public function __construct(AssetSupportService $assetSupportService)
    {
        $this->assetSupportService = $assetSupportService;
    }

    /**
     * Handle the event.
     */
    public function handle(ContractScheduleActivated $event): void
    {
        try {
            Log::info('Contract schedule activated, re-evaluating client assets', [
                'schedule_id' => $event->schedule->id,
                'schedule_type' => $event->schedule->schedule_type,
                'contract_id' => $event->schedule->contract_id,
                'client_id' => $event->schedule->contract->client_id,
            ]);

            // Re-evaluate all assets for the client when a schedule changes
            $result = $this->assetSupportService->handleScheduleChange($event->schedule);

            if (isset($result['summary'])) {
                Log::info('Asset re-evaluation completed due to schedule change', [
                    'schedule_id' => $event->schedule->id,
                    'assets_evaluated' => $result['summary']['evaluated'],
                    'status_changes' => $result['summary']['status_changes'],
                    'newly_supported' => $result['summary']['newly_supported'],
                    'newly_unsupported' => $result['summary']['newly_unsupported'],
                ]);
            } else {
                Log::info('Schedule change handled', [
                    'schedule_id' => $event->schedule->id,
                    'message' => $result['message'] ?? 'No action needed',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to re-evaluate assets on schedule change', [
                'schedule_id' => $event->schedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't fail the job, just log the error
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ContractScheduleActivated $event, \Throwable $exception): void
    {
        Log::error('Asset re-evaluation job failed', [
            'schedule_id' => $event->schedule->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
