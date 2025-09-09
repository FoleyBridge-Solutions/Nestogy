<?php

namespace App\Listeners;

use App\Events\AssetCreated;
use App\Domains\Asset\Services\AssetSupportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EvaluateAssetSupportStatus implements ShouldQueue
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
    public function handle(AssetCreated $event): void
    {
        try {
            Log::info('Asset created, evaluating support status', [
                'asset_id' => $event->asset->id,
                'asset_name' => $event->asset->name,
                'asset_type' => $event->asset->type,
                'client_id' => $event->asset->client_id,
            ]);

            // Handle asset discovery and support evaluation
            $evaluation = $this->assetSupportService->handleAssetDiscovery($event->asset);

            Log::info('Asset support evaluation completed', [
                'asset_id' => $event->asset->id,
                'support_status' => $evaluation['new_status'],
                'reason' => $evaluation['reason'],
                'auto_assigned' => $evaluation['auto_assigned'] ?? false,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to evaluate asset support status', [
                'asset_id' => $event->asset->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't fail the job, just log the error
            // The asset will remain unsupported and can be evaluated later
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(AssetCreated $event, \Throwable $exception): void
    {
        Log::error('Asset support evaluation job failed', [
            'asset_id' => $event->asset->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}