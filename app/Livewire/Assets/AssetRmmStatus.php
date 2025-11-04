<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Events\AssetCommandExecuted;
use App\Jobs\PollRmmTaskStatus;
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
    public $commandRunning = false;

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

    public function quickReboot()
    {
        if (!auth()->user()->can('rebootAsset', $this->asset)) {
            $this->addError('reboot', 'You do not have permission to reboot this device.');
            return;
        }

        $this->commandRunning = true;

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            
            if (!$rmmIntegration) {
                $this->addError('reboot', 'No active RMM integration found.');
                $this->commandRunning = false;
                return;
            }

            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();
            
            if (!$deviceMapping) {
                $this->addError('reboot', 'This asset is not mapped to an RMM device.');
                $this->commandRunning = false;
                return;
            }

            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->rebootAgent($deviceMapping->rmm_device_id, [
                'delay' => 30,
            ]);

            if ($result['success']) {
                $taskId = $result['task_id'] ?? null;

                // Broadcast immediate notification
                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => 'System Reboot (30s delay)',
                    'command_type' => 'general',
                    'status' => 'initiated',
                    'executed_by' => auth()->user()->name,
                    'task_id' => $taskId,
                ]));

                // Dispatch background job to poll for completion
                if ($taskId) {
                    PollRmmTaskStatus::dispatch(
                        $taskId,
                        $this->asset->id,
                        $rmmIntegration->id,
                        'System Reboot',
                        'general',
                        auth()->user()->name
                    );
                }

                // Log activity
                activity()
                    ->performedOn($this->asset)
                    ->causedBy(auth()->user())
                    ->withProperties(['command' => 'reboot', 'delay' => 30])
                    ->log('device_reboot_initiated');

                $this->showUpdateNotification = true;
                $this->dispatch('status-updated');
            } else {
                $this->addError('reboot', 'Failed to initiate reboot: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            \Log::error('Quick reboot failed', [
                'asset_id' => $this->asset->id,
                'error' => $e->getMessage(),
            ]);
            $this->addError('reboot', 'An error occurred while initiating reboot.');
        }

        $this->commandRunning = false;
    }

    public function render()
    {
        return view('livewire.assets.asset-rmm-status');
    }
}
