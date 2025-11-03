<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetRmmStatus extends Component
{
    public Asset $asset;
    public $isOnline = false;
    public $lastSeen = null;
    public $rmmPublicIp = null;
    public $rmmPlatform = null;
    public $rmmVersion = null;
    public $showUpdateNotification = false;

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        $this->loadRmmData();
    }

    /**
     * Define event listeners for this component.
     * Using getListeners() method as it's more reliable than attributes for Echo events.
     */
    public function getListeners()
    {
        return [
            "echo:assets.{$this->asset->id},.AssetStatusUpdated" => 'handleStatusUpdate',
        ];
    }

    protected function loadRmmData()
    {
        $rmmData = null;
        if ($this->asset->notes) {
            try {
                $rmmData = json_decode($this->asset->notes, true);
            } catch (\Exception $e) {
                $rmmData = null;
            }
        }

        if ($rmmData) {
            $this->rmmPublicIp = $rmmData['rmm_public_ip'] ?? null;
            $this->rmmPlatform = $rmmData['rmm_platform'] ?? null;
            $this->rmmVersion = $rmmData['rmm_version'] ?? null;

            if (isset($rmmData['rmm_last_seen'])) {
                try {
                    $lastSeenTime = \Carbon\Carbon::parse($rmmData['rmm_last_seen']);
                    $this->isOnline = $lastSeenTime->diffInMinutes() < 240;
                    $this->lastSeen = $lastSeenTime->diffForHumans();
                } catch (\Exception $e) {
                    $this->isOnline = false;
                    $this->lastSeen = null;
                }
            } else {
                $this->isOnline = $rmmData['rmm_online'] ?? false;
            }
        }
    }

    public function handleStatusUpdate($event)
    {
        // Refresh the asset from database
        $this->asset->refresh();
        
        // Update the component state with the broadcasted data
        $this->isOnline = $event['is_online'];
        $this->lastSeen = $event['last_seen'];
        $this->rmmPublicIp = $event['rmm_public_ip'];
        $this->rmmPlatform = $event['rmm_platform'];
        $this->rmmVersion = $event['rmm_version'];
        
        // Show notification
        $this->showUpdateNotification = true;
        
        // Hide notification after 3 seconds
        $this->dispatch('status-updated');
    }

    public function hideNotification()
    {
        $this->showUpdateNotification = false;
    }

    public function render()
    {
        return view('livewire.assets.asset-rmm-status');
    }
}
