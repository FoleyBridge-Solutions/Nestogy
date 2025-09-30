<?php

namespace App\Listeners;

use App\Domains\Contract\Services\ContractAutomationService;
use App\Events\AssetCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessAssetAutomation implements ShouldQueue
{
    use InteractsWithQueue;

    protected $automationService;

    /**
     * Create the event listener.
     */
    public function __construct(ContractAutomationService $automationService)
    {
        $this->automationService = $automationService;
    }

    /**
     * Handle the event.
     */
    public function handle(AssetCreated $event): void
    {
        try {
            $this->automationService->processNewAsset($event->asset);
        } catch (\Exception $e) {
            Log::error('Failed to process asset automation', [
                'asset_id' => $event->asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
