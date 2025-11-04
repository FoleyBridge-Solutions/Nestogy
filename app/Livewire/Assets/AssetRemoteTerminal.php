<?php

namespace App\Livewire\Assets;

use App\Domains\Asset\Models\Asset;
use App\Domains\Integration\Services\RmmServiceFactory;
use App\Events\AssetCommandExecuted;
use App\Jobs\PollRmmTaskStatus;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class AssetRemoteTerminal extends Component
{
    public Asset $asset;
    public array $commandHistory = [];
    public string $currentCommand = '';
    public string $shell = 'powershell'; // powershell, cmd, bash
    public bool $commandRunning = false;
    public ?string $activeTaskId = null;
    public int $historyIndex = -1;
    public int $maxHistoryItems = 50;

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        
        // Add welcome message
        $this->addToHistory([
            'type' => 'system',
            'content' => "Remote Terminal - Connected to {$asset->name}",
            'timestamp' => now()->format('H:i:s'),
        ]);
        
        Log::info('AssetRemoteTerminal mounted', [
            'asset_id' => $this->asset->id,
        ]);
    }

    public function getListeners()
    {
        return [
            "echo:assets.{$this->asset->id},.AssetCommandExecuted" => 'handleCommandExecuted',
        ];
    }

    public function setQuickCommand(string $command)
    {
        $this->currentCommand = $command;
    }

    public function executeCommand()
    {
        if (empty(trim($this->currentCommand))) {
            return;
        }

        if (!$this->canExecuteRemoteCommands()) {
            $this->addToHistory([
                'type' => 'error',
                'content' => 'Permission denied: You do not have permission to execute remote commands.',
                'timestamp' => now()->format('H:i:s'),
            ]);
            return;
        }

        $command = trim($this->currentCommand);
        
        // Add command to history display
        $this->addToHistory([
            'type' => 'input',
            'content' => ($this->shell === 'powershell' ? 'PS> ' : '> ') . $command,
            'timestamp' => now()->format('H:i:s'),
        ]);

        $this->commandRunning = true;

        try {
            $rmmIntegration = $this->asset->company->rmmIntegrations()->where('is_active', true)->first();
            
            if (!$rmmIntegration) {
                $this->addToHistory([
                    'type' => 'error',
                    'content' => 'No active RMM integration found for this asset.',
                    'timestamp' => now()->format('H:i:s'),
                ]);
                $this->commandRunning = false;
                return;
            }

            $deviceMapping = $this->asset->deviceMappings()->where('integration_id', $rmmIntegration->id)->first();
            
            if (!$deviceMapping) {
                $this->addToHistory([
                    'type' => 'error',
                    'content' => 'This asset is not mapped to an RMM device.',
                    'timestamp' => now()->format('H:i:s'),
                ]);
                $this->commandRunning = false;
                return;
            }

            $rmmService = app(RmmServiceFactory::class)->make($rmmIntegration);
            $result = $rmmService->runCommand($deviceMapping->rmm_device_id, $command, [
                'shell' => $this->shell,
                'timeout' => 300, // 5 minutes
            ]);

            if ($result['success']) {
                $this->activeTaskId = $result['task_id'] ?? null;

                $this->addToHistory([
                    'type' => 'info',
                    'content' => 'Command submitted (Task ID: ' . $this->activeTaskId . '). Waiting for response...',
                    'timestamp' => now()->format('H:i:s'),
                ]);

                // Broadcast command execution
                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => $command,
                    'command_type' => 'terminal',
                    'status' => 'initiated',
                    'executed_by' => auth()->user()->name,
                    'task_id' => $this->activeTaskId,
                ]));

                // Dispatch background job to poll for task completion
                if ($this->activeTaskId) {
                    PollRmmTaskStatus::dispatch(
                        $this->activeTaskId,
                        $this->asset->id,
                        $rmmIntegration->id,
                        substr($command, 0, 50), // Truncate command for display
                        'terminal',
                        auth()->user()->name
                    );
                }

                // Log activity
                activity()
                    ->performedOn($this->asset)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'command' => $command,
                        'shell' => $this->shell,
                        'task_id' => $this->activeTaskId,
                    ])
                    ->log('remote_command_executed');

            } else {
                $this->addToHistory([
                    'type' => 'error',
                    'content' => 'Command failed: ' . ($result['error'] ?? 'Unknown error'),
                    'timestamp' => now()->format('H:i:s'),
                ]);

                event(new AssetCommandExecuted([
                    'asset_id' => $this->asset->id,
                    'asset_name' => $this->asset->name,
                    'command' => $command,
                    'command_type' => 'terminal',
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error',
                    'executed_by' => auth()->user()->name,
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Remote command execution failed', [
                'asset_id' => $this->asset->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            $this->addToHistory([
                'type' => 'error',
                'content' => 'An error occurred: ' . $e->getMessage(),
                'timestamp' => now()->format('H:i:s'),
            ]);
        }

        // Clear input
        $this->currentCommand = '';
        $this->commandRunning = false;
    }

    /**
     * NOTE: Background job (PollRmmTaskStatus) handles polling and broadcasts results.
     * This component receives broadcasts via handleCommandExecuted() and updates UI in real-time.
     */

    #[On('echo:assets.{asset.id},.AssetCommandExecuted')]
    public function handleCommandExecuted($event)
    {
        if ($event['command_type'] !== 'terminal') {
            return; // Only handle terminal commands
        }

        // Show commands executed by other users
        if ($event['executed_by'] !== auth()->user()->name) {
            $this->addToHistory([
                'type' => 'system',
                'content' => "[{$event['executed_by']}] executed: {$event['command']}",
                'timestamp' => now()->format('H:i:s'),
            ]);
        }

        // Handle command completion (for all users, including self)
        if ($event['status'] === 'completed') {
            $output = $event['output'] ?? 'Command completed with no output.';
            
            $this->addToHistory([
                'type' => 'output',
                'content' => $output,
                'timestamp' => now()->format('H:i:s'),
            ]);

            // Clear active task since it's completed
            $this->activeTaskId = null;
        } elseif ($event['status'] === 'failed') {
            $error = $event['error_message'] ?? 'Command failed.';
            
            $this->addToHistory([
                'type' => 'error',
                'content' => $error,
                'timestamp' => now()->format('H:i:s'),
            ]);

            // Clear active task since it failed
            $this->activeTaskId = null;
        }
    }

    public function clearHistory()
    {
        $this->commandHistory = [];
        $this->addToHistory([
            'type' => 'system',
            'content' => 'Terminal cleared.',
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function changeShell(string $shell)
    {
        $this->shell = $shell;
        $this->addToHistory([
            'type' => 'system',
            'content' => "Shell changed to: {$shell}",
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    protected function addToHistory(array $entry)
    {
        $this->commandHistory[] = $entry;

        // Keep only last N items
        if (count($this->commandHistory) > $this->maxHistoryItems) {
            $this->commandHistory = array_slice($this->commandHistory, -$this->maxHistoryItems);
        }

        // Scroll to bottom (handled in frontend)
        $this->dispatch('terminal-updated');
    }

    protected function canExecuteRemoteCommands(): bool
    {
        return auth()->user()->can('executeRemoteCommands', $this->asset);
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="flex items-center justify-center p-12">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Loading Remote Terminal...</p>
            </div>
        </div>
        HTML;
    }

    public function render()
    {
        return view('livewire.assets.asset-remote-terminal');
    }
}
