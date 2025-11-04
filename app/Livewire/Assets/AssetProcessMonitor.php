<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Events\AssetCommandExecuted;
use App\Events\AssetProcessUpdate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetProcessMonitor extends Component
{
    public Asset $asset;
    public array $processes = [];
    public bool $loading = false;
    public bool $autoRefresh = false;
    public string $sortBy = 'cpu_percent'; // cpu_percent, memory_mb, name
    public string $sortDirection = 'desc';
    public string $searchTerm = '';
    public int $displayLimit = 20;

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        
        Log::info('AssetProcessMonitor mounted', [
            'asset_id' => $this->asset->id,
        ]);
        
        // Auto-load processes when component mounts
        $this->loadProcesses();
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 p-6">
            <div class="py-16 flex flex-col items-center justify-center">
                <div class="relative">
                    <svg class="size-16 text-purple-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <p class="text-zinc-600 dark:text-zinc-400 mt-4 text-sm">Loading Process Monitor...</p>
            </div>
        </div>
        HTML;
    }

    public function getListeners()
    {
        return [
            "echo:assets.{$this->asset->id},.AssetProcessUpdate" => 'handleProcessUpdate',
            "echo:assets.{$this->asset->id},.AssetCommandExecuted" => 'handleCommandExecuted',
        ];
    }

    public function loadProcesses()
    {
        if (!$this->canViewProcesses()) {
            $this->addError('processes', 'You do not have permission to view processes.');
            return;
        }

        $this->loading = true;

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            
            if (!$rmmIntegration) {
                $this->addError('processes', 'No active RMM integration found for this asset.');
                $this->loading = false;
                return;
            }

            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();
            
            if (!$deviceMapping) {
                $this->addError('processes', 'This asset is not mapped to an RMM device.');
                $this->loading = false;
                return;
            }

            // Get process list directly from RMM API
            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->getDeviceProcesses($deviceMapping->rmm_device_id);

            if ($result['success']) {
                $this->processes = $result['data'];
                
                // Sort processes: Running first, then alphabetically by name
                usort($this->processes, function ($a, $b) {
                    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                });
                
                Log::info('Processes loaded successfully', [
                    'asset_id' => $this->asset->id,
                    'process_count' => count($this->processes),
                ]);
            } else {
                $this->addError('processes', $result['error'] ?? 'Failed to retrieve processes.');
                Log::error('Process list retrieval failed', [
                    'asset_id' => $this->asset->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to load processes', [
                'asset_id' => $this->asset->id,
                'error' => $e->getMessage(),
            ]);
            $this->addError('processes', 'An error occurred while loading processes.');
        }

        $this->loading = false;
    }

    public function killProcess(int $pid)
    {
        if (!$this->canExecuteRemoteCommands()) {
            $this->addError('command', 'You do not have permission to kill processes.');
            return;
        }

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();
            
            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->killProcess($deviceMapping->rmm_device_id, $pid);

            if ($result['success']) {
                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => "Kill process PID: {$pid}",
                    'command_type' => 'process',
                    'status' => 'initiated',
                    'executed_by' => auth()->user()->name,
                    'task_id' => $result['task_id'] ?? null,
                ]));

                // Log activity
                activity()
                    ->performedOn($this->asset)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'pid' => $pid,
                        'command' => "Kill process PID: {$pid}",
                    ])
                    ->log('process_killed');

                // Reload processes after 2 seconds
                $this->dispatch('reload-processes-delayed');
            } else {
                $this->addError('command', 'Failed to kill process: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Failed to kill process', [
                'asset_id' => $this->asset->id,
                'pid' => $pid,
                'error' => $e->getMessage(),
            ]);
            $this->addError('command', 'An error occurred while killing the process.');
        }
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->loadProcesses();
        }
    }

    public function setSortBy(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    #[On('echo:assets.{asset.id},.AssetProcessUpdate')]
    public function handleProcessUpdate($event)
    {
        Log::info('Process update received', [
            'event' => $event,
            'asset_id' => $this->asset->id,
        ]);

        $this->processes = $event['processes'] ?? [];
    }

    #[On('echo:assets.{asset.id},.AssetCommandExecuted')]
    public function handleCommandExecuted($event)
    {
        if ($event['command_type'] === 'process') {
            $this->dispatch('process-command-notification', 
                message: "{$event['executed_by']}: {$event['command']}",
                status: $event['status']
            );
        }
    }

    public function getFilteredAndSortedProcesses()
    {
        $processes = $this->processes;

        // Apply search filter
        if ($this->searchTerm) {
            $processes = array_filter($processes, function ($process) {
                return stripos($process['name'], $this->searchTerm) !== false;
            });
        }

        // Apply sorting
        usort($processes, function ($a, $b) {
            $aVal = $a[$this->sortBy] ?? 0;
            $bVal = $b[$this->sortBy] ?? 0;

            if ($this->sortBy === 'name') {
                $result = strcasecmp($aVal, $bVal);
            } else {
                $result = $aVal <=> $bVal;
            }

            return $this->sortDirection === 'asc' ? $result : -$result;
        });

        // Apply limit
        return array_slice($processes, 0, $this->displayLimit);
    }

    protected function getTopProcesses(string $metric, int $limit)
    {
        $sorted = $this->processes;
        
        usort($sorted, function ($a, $b) use ($metric) {
            return ($b[$metric] ?? 0) <=> ($a[$metric] ?? 0);
        });

        return array_slice($sorted, 0, $limit);
    }

    protected function canViewProcesses(): bool
    {
        return auth()->user()->can('viewProcesses', $this->asset);
    }

    protected function canExecuteRemoteCommands(): bool
    {
        return auth()->user()->can('executeRemoteCommands', $this->asset);
    }

    public function render()
    {
        return view('livewire.assets.asset-process-monitor', [
            'filteredProcesses' => $this->getFilteredAndSortedProcesses(),
        ]);
    }
}
