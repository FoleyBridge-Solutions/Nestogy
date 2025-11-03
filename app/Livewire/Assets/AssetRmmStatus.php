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
        
        \Log::info('AssetRmmStatus component mounted', [
            'asset_id' => $this->asset->id,
            'listeners' => $this->getListeners(),
        ]);
    }

    /**
     * Define event listeners for this component.
     * Livewire automatically handles Echo events with the echo: prefix
     * Try both with and without dot prefix for the event name
     */
    public function getListeners()
    {
        return [
            "echo:assets.{$this->asset->id},.AssetStatusUpdated" => 'handleStatusUpdate',
            "echo:assets.{$this->asset->id},AssetStatusUpdated" => 'handleStatusUpdateNoDot',
        ];
    }
    
    public function handleStatusUpdateNoDot($event)
    {
        \Log::info('🎉 Received event WITHOUT dot prefix');
        $this->handleStatusUpdate($event);
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
        \Log::info('🎉 Livewire received Echo event', [
            'event' => $event,
            'asset_id' => $this->asset->id,
        ]);
        
        // Refresh the asset from database
        $this->asset->refresh();
        
        // Update the component state with the broadcasted data
        $this->isOnline = $event['is_online'] ?? false;
        $this->lastSeen = $event['last_seen'] ?? null;
        $this->rmmPublicIp = $event['rmm_public_ip'] ?? null;
        $this->rmmPlatform = $event['rmm_platform'] ?? null;
        $this->rmmVersion = $event['rmm_version'] ?? null;
        
        // Show notification
        $this->showUpdateNotification = true;
        
        \Log::info('✓ Component state updated', [
            'is_online' => $this->isOnline,
            'last_seen' => $this->lastSeen,
            'show_notification' => $this->showUpdateNotification,
        ]);
        
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
