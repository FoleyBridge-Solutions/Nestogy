<?php

namespace App\Console\Commands;

use App\Domains\Asset\Models\Asset;
use App\Events\AssetStatusUpdated;
use Illuminate\Console\Command;

class TestBroadcastNow extends Command
{
    protected $signature = 'test:broadcast-now {asset_id=1165}';
    protected $description = 'Immediately broadcast an asset status update for testing';

    public function handle()
    {
        $assetId = $this->argument('asset_id');
        $asset = Asset::find($assetId);
        
        if (!$asset) {
            $this->error("Asset {$assetId} not found");
            return 1;
        }

        $this->info("Broadcasting AssetStatusUpdated for asset {$asset->name} (ID: {$assetId})");
        $this->info("Channel: assets.{$assetId}");
        
        $testData = [
            'rmm_online' => true,
            'rmm_last_seen' => now()->toIso8601String(),
            'rmm_public_ip' => '8.8.8.8',
            'rmm_platform' => 'test',
            'rmm_version' => '1.0.0-test',
        ];
        
        $this->info("Broadcasting with test data...");
        
        AssetStatusUpdated::dispatch($asset, $testData);
        
        $this->newLine();
        $this->info("✓ Event broadcasted!");
        $this->info("Open the asset page and check if the blue notification appears");
        $this->info("URL: " . url("/assets/{$assetId}"));
        
        return 0;
    }
}
