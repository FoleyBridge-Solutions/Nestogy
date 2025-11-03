<?php

namespace App\Console\Commands;

use App\Domains\Asset\Models\Asset;
use App\Events\AssetStatusUpdated;
use Illuminate\Console\Command;

class TestAssetBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:asset-broadcast {asset_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test broadcasting asset status updates via Reverb';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assetId = $this->argument('asset_id');
        
        $asset = Asset::find($assetId);
        
        if (!$asset) {
            $this->error("Asset with ID {$assetId} not found.");
            return 1;
        }

        $this->info("Testing broadcast for asset: {$asset->name} (ID: {$asset->id})");
        
        // Simulate RMM data
        $rmmData = [
            'rmm_online' => true,
            'rmm_last_seen' => now()->toIso8601String(),
            'rmm_public_ip' => '12.79.123.38',
            'rmm_platform' => 'windows',
            'rmm_version' => '1.2.3',
        ];

        $this->info("Broadcasting AssetStatusUpdated event...");
        
        AssetStatusUpdated::dispatch($asset, $rmmData);
        
        $this->info("✓ Event dispatched successfully!");
        $this->info("Channel: assets.{$asset->id}");
        $this->info("Event: AssetStatusUpdated");
        $this->info("");
        $this->info("Open the asset page in your browser and watch for the real-time update!");
        $this->info("URL: " . route('assets.show', $asset));
        
        return 0;
    }
}
