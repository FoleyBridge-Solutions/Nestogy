<div>
    @script
    <script>
        console.log('💻 AssetRemoteTerminal loaded for asset {{ $asset->id }}');
        
        // Auto-scroll terminal to bottom when new content is added
        $wire.on('terminal-updated', () => {
            const terminal = document.getElementById('terminal-output');
            if (terminal) {
                terminal.scrollTop = terminal.scrollHeight;
            }
        });

        // Focus input when component loads
        document.addEventListener('livewire:navigated', () => {
            const input = document.getElementById('terminal-input');
            if (input) input.focus();
        });
    </script>
    @endscript

    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <flux:heading class="flex items-center gap-2">
                    <flux:icon.command-line class="size-5" />
                    Remote Terminal
                </flux:heading>
                @if($commandRunning || $activeTaskId)
                    <flux:badge color="blue" icon="arrow-path" class="animate-pulse">
                        Running
                    </flux:badge>
                @endif
            </div>

            <div class="flex gap-2 items-center">
                {{-- Shell Selector --}}
                <flux:select wire:model.live="shell" class="w-40" size="sm">
                    <option value="powershell">PowerShell</option>
                    <option value="cmd">CMD</option>
                    <option value="bash">Bash</option>
                </flux:select>

                <flux:button 
                    size="sm" 
                    variant="ghost"
                    icon="trash"
                    wire:click="clearHistory"
                    wire:confirm="Clear terminal history?"
                >
                    Clear
                </flux:button>
            </div>
        </div>

        {{-- Terminal Output Area --}}
        <div 
            id="terminal-output"
            class="bg-zinc-900 text-green-400 rounded-lg p-4 font-mono text-sm h-[500px] overflow-y-auto mb-4"
        >
            @forelse($commandHistory as $entry)
                <div class="mb-2 @if($entry['type'] === 'error') text-red-400 @elseif($entry['type'] === 'info') text-blue-400 @elseif($entry['type'] === 'system') text-yellow-400 @elseif($entry['type'] === 'input') text-white font-bold @endif">
                    <span class="text-zinc-500 text-xs">[{{ $entry['timestamp'] }}]</span>
                    <span class="whitespace-pre-wrap">{{ $entry['content'] }}</span>
                </div>
            @empty
                <div class="text-zinc-500">
                    Terminal ready. Type a command and press Enter.
                </div>
            @endforelse

            @if($commandRunning || $activeTaskId)
                <div class="text-blue-400 animate-pulse">
                    <span class="text-zinc-500 text-xs">[{{ now()->format('H:i:s') }}]</span>
                    ⏳ Executing command...
                </div>
            @endif
        </div>

        {{-- Command Input Area --}}
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 font-mono text-sm">
                    {{ $shell === 'powershell' ? 'PS>' : '>' }}
                </div>
                <flux:input 
                    id="terminal-input"
                    wire:model="currentCommand"
                    wire:keydown.enter="executeCommand"
                    placeholder="Enter command..."
                    class="font-mono pl-12"
                    autocomplete="off"
                />
            </div>

            <flux:button 
                wire:click="executeCommand"
                :icon="$commandRunning || $activeTaskId ? 'arrow-path' : 'paper-airplane'"
            >
                @if($commandRunning || $activeTaskId)
                    Running...
                @else
                    Execute
                @endif
            </flux:button>
        </div>

        {{-- Quick Command Buttons --}}
        <div class="mt-4 pt-4 border-t">
            <flux:text variant="subtle" class="text-xs mb-2">Quick Commands:</flux:text>
            <div class="flex flex-wrap gap-2">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="setQuickCommand('Get-Process | Sort-Object CPU -Descending | Select-Object -First 10')"
                >
                    Top Processes
                </flux:button>
                
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="setQuickCommand('Get-Service')"
                >
                    Running Services
                </flux:button>
                
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="setQuickCommand('Get-EventLog -LogName System -Newest 10')"
                >
                    System Events
                </flux:button>
                
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="setQuickCommand('Get-Disk')"
                >
                    Disk Info
                </flux:button>
                
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="setQuickCommand('Get-NetIPAddress')"
                >
                    Network Info
                </flux:button>
            </div>
        </div>

        {{-- Help Text --}}
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <flux:text class="text-xs text-blue-800 dark:text-blue-200">
                <strong>💡 Tip:</strong> Commands are executed on the remote device. Use PowerShell, CMD, or Bash syntax depending on your selected shell. Results may take a few seconds to appear.
            </flux:text>
        </div>
    </flux:card>
</div>
