<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Events\AssetCommandExecuted;
use App\Jobs\PollRmmTaskStatus;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetServiceManager extends Component
{
    public Asset $asset;
    public array $services = [];
    public bool $loading = false;
    public bool $commandRunning = false;
    public ?string $lastCommand = null;
    public ?string $lastCommandStatus = null;
    public ?string $activeTaskId = null;
    public string $searchTerm = '';
    public string $filterStatus = 'all'; // all, running, stopped

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        
        Log::info('AssetServiceManager mounted', [
            'asset_id' => $this->asset->id,
            'listeners' => $this->getListeners(),
        ]);
        
        // Auto-load services when component mounts
        $this->loadServices();
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-6">
            <div class="py-16 flex flex-col items-center justify-center">
                <div class="relative">
                    <svg class="size-16 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="mt-4 text-sm text-zinc-500 animate-pulse">Loading service manager...</div>
            </div>
        </div>
        HTML;
    }

    public function getListeners()
    {
        return [
            "echo:assets.{$this->asset->id},.AssetCommandExecuted" => 'handleCommandExecuted',
        ];
    }

    public function loadServices()
    {
        if (!$this->canExecuteRemoteCommands()) {
            $this->addError('services', 'You do not have permission to view services.');
            return;
        }

        $this->loading = true;

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            
            if (!$rmmIntegration) {
                $this->addError('services', 'No active RMM integration found for this asset.');
                $this->loading = false;
                return;
            }

            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();
            
            if (!$deviceMapping) {
                $this->addError('services', 'This asset is not mapped to an RMM device.');
                $this->loading = false;
                return;
            }

            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->getAgentServices($deviceMapping->rmm_device_id);

            if ($result['success']) {
                $this->services = $result['data'];
                
                // Sort services: Running first, then alphabetically by name
                usort($this->services, function ($a, $b) {
                    $aRunning = strtolower($a['status']) === 'running' ? 0 : 1;
                    $bRunning = strtolower($b['status']) === 'running' ? 0 : 1;
                    
                    if ($aRunning !== $bRunning) {
                        return $aRunning - $bRunning;
                    }
                    
                    return strcasecmp($a['display_name'] ?? $a['name'], $b['display_name'] ?? $b['name']);
                });
                
                Log::info('Services loaded successfully', [
                    'asset_id' => $this->asset->id,
                    'service_count' => count($this->services),
                ]);
            } else {
                $this->addError('services', 'Failed to load services: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to load services', [
                'asset_id' => $this->asset->id,
                'error' => $e->getMessage(),
            ]);
            $this->addError('services', 'An error occurred while loading services.');
        }

        $this->loading = false;
    }

    public function startService(string $serviceName)
    {
        $this->executeServiceCommand($serviceName, 'start', "Start-Service -Name '{$serviceName}'");
    }

    public function stopService(string $serviceName)
    {
        $this->executeServiceCommand($serviceName, 'stop', "Stop-Service -Name '{$serviceName}' -Force");
    }

    public function restartService(string $serviceName)
    {
        $this->executeServiceCommand($serviceName, 'restart', "Restart-Service -Name '{$serviceName}' -Force");
    }

    protected function executeServiceCommand(string $serviceName, string $action, string $command)
    {
        if (!$this->canExecuteRemoteCommands()) {
            $this->addError('command', 'You do not have permission to control services.');
            return;
        }

        $this->commandRunning = true;
        $this->lastCommand = "{$action} {$serviceName}";
        $this->lastCommandStatus = 'running';

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();

            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->runCommand($deviceMapping->rmm_device_id, $command, [
                'shell' => 'powershell',
                'timeout' => 60,
            ]);

            if ($result['success']) {
                $this->activeTaskId = $result['task_id'] ?? null;

                // Broadcast command execution to all viewers
                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => $command,
                    'command_type' => 'service',
                    'status' => 'initiated',
                    'executed_by' => auth()->user()->name,
                    'task_id' => $this->activeTaskId,
                ]));

                Log::info('Service command executed', [
                    'asset_id' => $this->asset->id,
                    'service' => $serviceName,
                    'action' => $action,
                    'task_id' => $this->activeTaskId,
                ]);

                // Dispatch background job to poll for task completion
                if ($this->activeTaskId) {
                    PollRmmTaskStatus::dispatch(
                        $this->activeTaskId,
                        $this->asset->id,
                        $rmmIntegration->id,
                        "{$action} {$serviceName}",
                        'service',
                        auth()->user()->name
                    );
                }

                // Log activity
                activity()
                    ->performedOn($this->asset)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'service' => $serviceName,
                        'action' => $action,
                        'command' => $command,
                        'task_id' => $this->activeTaskId,
                    ])
                    ->log("service_{$action}");

            } else {
                $this->lastCommandStatus = 'failed';
                $this->addError('command', 'Failed to execute command: ' . ($result['error'] ?? 'Unknown error'));
                
                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => $command,
                    'command_type' => 'service',
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                    'executed_by' => auth()->user()->name,
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Service command failed', [
                'asset_id' => $this->asset->id,
                'service' => $serviceName,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            $this->lastCommandStatus = 'failed';
            $this->addError('command', 'An error occurred while executing the command.');
        }

        $this->commandRunning = false;
    }

    #[On('echo:assets.{asset.id},.AssetCommandExecuted')]
    public function handleCommandExecuted($event)
    {
        Log::info('Service Manager received command event', [
            'event' => $event,
            'asset_id' => $this->asset->id,
        ]);

        // If another user executed a service command, reload services
        if ($event['command_type'] === 'service' && $event['status'] === 'completed') {
            $this->loadServices();
        }

        // Show notification
        $this->dispatch('command-notification', 
            message: "{$event['executed_by']} executed: {$event['command']}",
            status: $event['status']
        );
    }

    public function getFilteredServices()
    {
        $filtered = $this->services;

        // Apply search filter
        if ($this->searchTerm) {
            $filtered = array_filter($filtered, function ($service) {
                return stripos($service['name'], $this->searchTerm) !== false ||
                       stripos($service['display_name'] ?? '', $this->searchTerm) !== false;
            });
        }

        // Apply status filter
        if ($this->filterStatus !== 'all') {
            $filtered = array_filter($filtered, function ($service) {
                return strtolower($service['status']) === $this->filterStatus;
            });
        }

        return $filtered;
    }

    protected function canExecuteRemoteCommands(): bool
    {
        return auth()->user()->can('executeRemoteCommands', $this->asset);
    }

    public function render()
    {
        return view('livewire.assets.asset-service-manager', [
            'filteredServices' => $this->getFilteredServices(),
        ]);
    }
}
