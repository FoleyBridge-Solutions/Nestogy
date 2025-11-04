<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Events\AssetCommandExecuted;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetRemoteControl extends Component
{
    public Asset $asset;
    public string $activeTab = 'overview'; // overview, services, terminal, processes
    public bool $isOnline = false;
    public ?string $rmmDeviceId = null;
    public array $recentCommands = [];
    public array $notifications = [];
    public bool $quickActionsVisible = true;

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        $this->checkRmmStatus();
        
        Log::info('AssetRemoteControl mounted', [
            'asset_id' => $this->asset->id,
            'is_online' => $this->isOnline,
        ]);
    }

    public function getListeners()
    {
        return [
            "echo:assets.{$this->asset->id},.AssetCommandExecuted" => 'handleCommandExecuted',
            "echo:assets.{$this->asset->id},.AssetStatusUpdated" => 'handleStatusUpdate',
        ];
    }

    protected function checkRmmStatus()
    {
        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            
            if ($rmmIntegration) {
                $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();
                
                if ($deviceMapping) {
                    $this->rmmDeviceId = $deviceMapping->rmm_device_id;
                    
                    // Check online status from asset data
                    $rmmData = null;
                    if ($this->asset->notes) {
                        try {
                            $rmmData = json_decode($this->asset->notes, true);
                        } catch (\Exception $e) {
                            $rmmData = null;
                        }
                    }

                    if ($rmmData && isset($rmmData['rmm_last_seen'])) {
                        try {
                            $lastSeenTime = \Carbon\Carbon::parse($rmmData['rmm_last_seen']);
                            $this->isOnline = $lastSeenTime->diffInMinutes() < 240;
                        } catch (\Exception $e) {
                            $this->isOnline = false;
                        }
                    } else {
                        $this->isOnline = $rmmData['rmm_online'] ?? false;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check RMM status', [
                'asset_id' => $this->asset->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
        
        Log::info('Active tab changed', [
            'asset_id' => $this->asset->id,
            'tab' => $tab,
        ]);
    }

    public function rebootDevice()
    {
        if (!$this->canExecuteRemoteCommands()) {
            $this->addNotification('error', 'You do not have permission to reboot this device.');
            return;
        }

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();

            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->rebootAgent($deviceMapping->rmm_device_id, [
                'delay' => 30,
            ]);

            if ($result['success']) {
                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => 'System Reboot (30 second delay)',
                    'command_type' => 'general',
                    'status' => 'initiated',
                    'executed_by' => auth()->user()->name,
                    'task_id' => $result['task_id'] ?? null,
                ]));

                // Log activity
                activity()
                    ->performedOn($this->asset)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'command' => 'reboot',
                        'delay' => 30,
                    ])
                    ->log('device_reboot');

                $this->addNotification('success', 'Reboot initiated. Device will restart in 30 seconds.');
                $this->addToRecentCommands('System Reboot', 'initiated');
            } else {
                $this->addNotification('error', 'Failed to reboot device: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Reboot failed', [
                'asset_id' => $this->asset->id,
                'error' => $e->getMessage(),
            ]);
            $this->addNotification('error', 'An error occurred while rebooting the device.');
        }
    }

    public function refreshRmmData()
    {
        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            
            if (!$rmmIntegration) {
                $this->addNotification('error', 'No active RMM integration found.');
                return;
            }

            // Dispatch RMM sync job for this specific asset
            \App\Jobs\SyncRmmAgents::dispatch($rmmIntegration);

            $this->addNotification('info', 'RMM data refresh initiated. This may take a few moments.');
        } catch (\Exception $e) {
            Log::error('RMM refresh failed', [
                'asset_id' => $this->asset->id,
                'error' => $e->getMessage(),
            ]);
            $this->addNotification('error', 'Failed to refresh RMM data.');
        }
    }

    #[On('echo:assets.{asset.id},.AssetCommandExecuted')]
    public function handleCommandExecuted($event)
    {
        Log::info('Remote Control received command event', [
            'event' => $event,
            'asset_id' => $this->asset->id,
        ]);

        $message = "[{$event['executed_by']}] {$event['command']} - {$event['status']}";
        
        if ($event['status'] === 'completed') {
            $this->addNotification('success', $message);
        } elseif ($event['status'] === 'failed') {
            $this->addNotification('error', $message);
        } else {
            $this->addNotification('info', $message);
        }

        $this->addToRecentCommands($event['command'], $event['status'], $event['executed_by']);
    }

    #[On('echo:assets.{asset.id},.AssetStatusUpdated')]
    public function handleStatusUpdate($event)
    {
        $this->isOnline = $event['is_online'] ?? false;
        
        Log::info('Status updated', [
            'asset_id' => $this->asset->id,
            'is_online' => $this->isOnline,
        ]);
    }

    protected function addNotification(string $type, string $message)
    {
        $this->notifications[] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => now()->format('H:i:s'),
            'id' => uniqid(),
        ];

        // Keep only last 10 notifications
        if (count($this->notifications) > 10) {
            $this->notifications = array_slice($this->notifications, -10);
        }

        $this->dispatch('notification-added');
    }

    public function dismissNotification(string $id)
    {
        $this->notifications = array_filter($this->notifications, function ($notif) use ($id) {
            return $notif['id'] !== $id;
        });
    }

    protected function addToRecentCommands(string $command, string $status, ?string $executedBy = null)
    {
        $this->recentCommands[] = [
            'command' => $command,
            'status' => $status,
            'executed_by' => $executedBy ?? auth()->user()->name,
            'timestamp' => now()->format('H:i:s'),
        ];

        // Keep only last 10 commands
        if (count($this->recentCommands) > 10) {
            $this->recentCommands = array_slice($this->recentCommands, -10);
        }
    }

    protected function canExecuteRemoteCommands(): bool
    {
        return auth()->user()->can('executeRemoteCommands', $this->asset);
    }

    public function render()
    {
        return view('livewire.assets.asset-remote-control');
    }
}
