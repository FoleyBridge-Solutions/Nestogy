<div>
    @script
    <script>
        console.log('📊 AssetProcessMonitor loaded for asset {{ $asset->id }}');
        
        // Listen for reload trigger
        $wire.on('reload-processes-delayed', () => {
            setTimeout(() => {
                $wire.loadProcesses();
            }, 2000);
        });

        $wire.on('process-command-notification', (event) => {
            console.log('Process command:', event);
        });
    </script>
    @endscript

    {{-- Auto-refresh when enabled --}}
    @if($autoRefresh)
    <div wire:poll.10s="loadProcesses"></div>
    @endif

    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <flux:heading class="flex items-center gap-2">
                <flux:icon.cpu-chip class="size-5" />
                Process Monitor
                @if($autoRefresh)
                    <flux:badge color="green">
                        <flux:icon.arrow-path class="animate-spin size-3" />
                        Live
                    </flux:badge>
                @endif
            </flux:heading>

            <div class="flex gap-2">
                @if(count($processes) > 0)
                    <flux:badge color="blue">{{ count($processes) }} processes</flux:badge>
                @endif

                @if($autoRefresh)
                    <flux:button 
                        size="sm"
                        variant="ghost"
                        icon="pause"
                        wire:click="toggleAutoRefresh"
                    >
                        Stop Auto-Refresh
                    </flux:button>
                @else
                    <flux:button 
                        size="sm"
                        variant="ghost"
                        icon="play"
                        wire:click="toggleAutoRefresh"
                    >
                        Auto-Refresh
                    </flux:button>
                @endif

                <flux:button 
                    wire:click="loadProcesses" 
                    size="sm"
                    icon="arrow-path"
                    :loading="$loading"
                >
                    {{ count($processes) > 0 ? 'Refresh' : 'Load Processes' }}
                </flux:button>
            </div>
        </div>

        @error('processes')
        <flux:card class="mb-4 bg-red-50 border-red-200">
            <div class="flex items-center gap-2">
                <flux:icon.exclamation-circle class="text-red-600" />
                <flux:text class="text-red-800">{{ $message }}</flux:text>
            </div>
        </flux:card>
        @enderror

        @error('command')
        <flux:card class="mb-4 bg-red-50 border-red-200">
            <div class="flex items-center gap-2">
                <flux:icon.exclamation-circle class="text-red-600" />
                <flux:text class="text-red-800">{{ $message }}</flux:text>
            </div>
        </flux:card>
        @enderror

        {{-- Loading State --}}
        @if($loading && count($processes) === 0)
        <div class="py-16 flex flex-col items-center justify-center">
            <div class="relative">
                <flux:icon.cpu-chip class="size-16 text-purple-500 animate-pulse" />
                <div class="absolute inset-0 flex items-center justify-center">
                    <flux:icon.arrow-path class="size-8 text-purple-300 animate-spin" />
                </div>
            </div>
            <flux:text variant="subtle" class="mt-4 animate-pulse">Loading processes from remote device...</flux:text>
            <flux:text variant="subtle" class="text-xs mt-1">This may take a few seconds...</flux:text>
        </div>
        @elseif(count($processes) > 0)
            {{-- Search and Controls --}}
            <div class="mb-4 flex gap-3 items-center">
                <div class="flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="searchTerm" 
                        placeholder="Search processes..."
                        icon="magnifying-glass"
                    />
                </div>

                <flux:select wire:model.live="displayLimit" class="w-32">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="50">Top 50</option>
                    <option value="100">Top 100</option>
                </flux:select>
            </div>

            {{-- Process Table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <button 
                                    wire:click="setSortBy('name')" 
                                    class="flex items-center gap-1 hover:text-blue-600"
                                >
                                    <flux:text variant="strong" class="text-xs uppercase">Process Name</flux:text>
                                    @if($sortBy === 'name')
                                        <flux:icon.chevron-up-down class="size-4" />
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left">
                                <flux:text variant="strong" class="text-xs uppercase">PID</flux:text>
                            </th>
                            <th class="px-4 py-3 text-right">
                                <button 
                                    wire:click="setSortBy('cpu_percent')" 
                                    class="flex items-center gap-1 hover:text-blue-600 ml-auto"
                                >
                                    <flux:text variant="strong" class="text-xs uppercase">CPU %</flux:text>
                                    @if($sortBy === 'cpu_percent')
                                        <flux:icon.chevron-up-down class="size-4" />
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right">
                                <button 
                                    wire:click="setSortBy('memory_mb')" 
                                    class="flex items-center gap-1 hover:text-blue-600 ml-auto"
                                >
                                    <flux:text variant="strong" class="text-xs uppercase">Memory (MB)</flux:text>
                                    @if($sortBy === 'memory_mb')
                                        <flux:icon.chevron-up-down class="size-4" />
                                    @endif
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right">
                                <flux:text variant="strong" class="text-xs uppercase">Handles</flux:text>
                            </th>
                            <th class="px-4 py-3 text-right">
                                <flux:text variant="strong" class="text-xs uppercase">Actions</flux:text>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($filteredProcesses as $process)
                        <tr class="border-b hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                            <td class="px-4 py-3">
                                <flux:text variant="strong" class="font-mono text-sm">
                                    {{ $process['name'] }}
                                </flux:text>
                            </td>
                            <td class="px-4 py-3">
                                <flux:text class="font-mono text-sm">
                                    {{ $process['pid'] }}
                                </flux:text>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:text class="font-mono text-sm">
                                    {{ number_format($process['cpu_percent'] ?? 0, 2) }}%
                                </flux:text>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:text class="font-mono text-sm">
                                    {{ number_format(($process['memory_mb'] ?? 0) / 1024 / 1024, 2) }}
                                </flux:text>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:text class="font-mono text-sm">
                                    {{ number_format($process['num_handles'] ?? 0) }}
                                </flux:text>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button 
                                    size="sm" 
                                    variant="danger"
                                    icon="x-mark"
                                    wire:click="killProcess({{ $process['pid'] }})"
                                    wire:confirm="Are you sure you want to kill process '{{ $process['name'] }}' (PID: {{ $process['pid'] }})?"
                                >
                                    Kill
                                </flux:button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center">
                                <flux:icon.magnifying-glass class="size-12 text-zinc-400 mx-auto mb-2" />
                                <flux:text variant="subtle">No processes found matching your search.</flux:text>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Stats Summary --}}
            <div class="mt-4 pt-4 border-t">
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                        <flux:text variant="subtle" class="text-xs">Total Processes</flux:text>
                        <flux:text variant="strong" class="text-2xl">{{ count($processes) }}</flux:text>
                    </div>
                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded">
                        <flux:text variant="subtle" class="text-xs">Showing</flux:text>
                        <flux:text variant="strong" class="text-2xl">{{ count($filteredProcesses) }}</flux:text>
                    </div>
                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded">
                        <flux:text variant="subtle" class="text-xs">Auto-Refresh</flux:text>
                        <flux:text variant="strong" class="text-2xl">{{ $autoRefresh ? 'ON' : 'OFF' }}</flux:text>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon.cpu-chip class="size-16 text-zinc-300 mx-auto mb-4" />
                <flux:heading size="lg" class="mb-2">No Processes Loaded</flux:heading>
                <flux:text variant="subtle" class="mb-4">
                    Click "Load Processes" to view running processes on this device.
                </flux:text>
            </div>
        @endif
    </flux:card>
</div>
