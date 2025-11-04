<div>
    @script
    <script>
        console.log('🎮 AssetRemoteControl loaded for asset {{ $asset->id }}');
        
        $wire.on('notification-added', () => {
            console.log('New notification added');
        });
    </script>
    @endscript

    {{-- Notifications Toast --}}
    <div class="fixed top-20 right-4 z-50 space-y-2" style="max-width: 400px;">
        @foreach($notifications as $notification)
        @php
            $notifBg = match($notification['type']) {
                'success' => 'bg-green-50 border-green-200',
                'error' => 'bg-red-50 border-red-200',
                'info' => 'bg-blue-50 border-blue-200',
                default => 'bg-zinc-50 border-zinc-200'
            };
        @endphp
        <div class="p-4 rounded-lg shadow-lg border animate-in slide-in-from-right {{ $notifBg }}"
        >
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-2 flex-1">
                    @if($notification['type'] === 'success')
                        <flux:icon.check-circle class="text-green-600 size-5 flex-shrink-0" />
                    @elseif($notification['type'] === 'error')
                        <flux:icon.x-circle class="text-red-600 size-5 flex-shrink-0" />
                    @else
                        <flux:icon.information-circle class="text-blue-600 size-5 flex-shrink-0" />
                    @endif
                    
                    <div class="flex-1">
                        <flux:text class="text-sm font-medium">{{ $notification['message'] }}</flux:text>
                        <flux:text variant="subtle" class="text-xs">{{ $notification['timestamp'] }}</flux:text>
                    </div>
                </div>
                
                <button wire:click="dismissNotification('{{ $notification['id'] }}')" class="text-zinc-400 hover:text-zinc-600">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Main Control Panel --}}
    <div class="space-y-4">
        {{-- Status Card --}}
        <flux:card>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <flux:heading size="lg">{{ $asset->name }}</flux:heading>
                        <flux:text variant="subtle">Remote Control Center</flux:text>
                    </div>
                    
                    @if($isOnline)
                        <flux:badge color="green" class="text-lg px-4 py-2">
                            <span class="flex items-center gap-2">
                                <span class="size-3 rounded-full bg-green-400 animate-pulse"></span>
                                Online
                            </span>
                        </flux:badge>
                    @else
                        <flux:badge color="red" class="text-lg px-4 py-2">
                            <span class="flex items-center gap-2">
                                <span class="size-3 rounded-full bg-red-400"></span>
                                Offline
                            </span>
                        </flux:badge>
                    @endif
                </div>

                @if($quickActionsVisible)
                <div class="flex gap-2">
                    <button 
                        class="px-3 py-1.5 text-sm rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center gap-2"
                        wire:click="refreshRmmData"
                    >
                        <flux:icon.arrow-path class="size-4" />
                        Refresh Data
                    </button>

                    <button 
                        class="px-3 py-1.5 text-sm rounded bg-red-600 text-white hover:bg-red-700 flex items-center gap-2"
                        wire:click="rebootDevice"
                        wire:confirm="Are you sure you want to reboot {{ $asset->name }}? The device will restart in 30 seconds."
                    >
                        <flux:icon.power class="size-4" />
                        Reboot Device
                    </button>
                </div>
                @endif
            </div>
        </flux:card>

        {{-- Tab Navigation --}}
        <flux:card class="p-0">
            <div class="flex border-b">
                <button 
                    wire:click="setActiveTab('overview')"
                    class="flex-1 px-6 py-4 text-center font-medium transition {{ $activeTab === 'overview' ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                >
                    <flux:icon.chart-bar class="size-5 mx-auto mb-1" />
                    Overview
                </button>
                
                <button 
                    wire:click="setActiveTab('services')"
                    class="flex-1 px-6 py-4 text-center font-medium transition {{ $activeTab === 'services' ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                >
                    <flux:icon.cog-6-tooth class="size-5 mx-auto mb-1" />
                    Services
                </button>
                
                <button 
                    wire:click="setActiveTab('terminal')"
                    class="flex-1 px-6 py-4 text-center font-medium transition {{ $activeTab === 'terminal' ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                >
                    <flux:icon.command-line class="size-5 mx-auto mb-1" />
                    Terminal
                </button>
                
                <button 
                    wire:click="setActiveTab('processes')"
                    class="flex-1 px-6 py-4 text-center font-medium transition {{ $activeTab === 'processes' ? 'border-b-2 border-blue-600 text-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800' }}"
                >
                    <flux:icon.cpu-chip class="size-5 mx-auto mb-1" />
                    Processes
                </button>
            </div>
        </flux:card>

        {{-- Tab Content --}}
        <div>
            @if($activeTab === 'overview')
                {{-- Overview: Recent Commands and Activity --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Recent Commands</flux:heading>
                        
                        @if(count($recentCommands) > 0)
                            <div class="space-y-2">
                                @foreach(array_reverse($recentCommands) as $cmd)
                                <div class="flex items-center justify-between p-3 border rounded hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    <div class="flex-1">
                                        <flux:text variant="strong" class="text-sm">{{ $cmd['command'] }}</flux:text>
                                        <flux:text variant="subtle" class="text-xs">by {{ $cmd['executed_by'] }} at {{ $cmd['timestamp'] }}</flux:text>
                                    </div>
                                    
                                    @if($cmd['status'] === 'completed')
                                        <flux:badge color="green" size="sm">Completed</flux:badge>
                                    @elseif($cmd['status'] === 'failed')
                                        <flux:badge color="red" size="sm">Failed</flux:badge>
                                    @else
                                        <flux:badge color="blue" size="sm">{{ ucfirst($cmd['status']) }}</flux:badge>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <flux:icon.clock class="size-12 text-zinc-300 mx-auto mb-2" />
                                <flux:text variant="subtle">No recent commands executed</flux:text>
                            </div>
                        @endif
                    </flux:card>

                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Quick Info</flux:heading>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded">
                                <flux:text variant="subtle">Device Status</flux:text>
                                @if($isOnline)
                                    <flux:badge color="green">Online</flux:badge>
                                @else
                                    <flux:badge color="red">Offline</flux:badge>
                                @endif
                            </div>

                            @if($rmmDeviceId)
                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded">
                                <flux:text variant="subtle">RMM Device ID</flux:text>
                                <flux:text variant="strong" class="font-mono text-sm">{{ $rmmDeviceId }}</flux:text>
                            </div>
                            @endif

                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded">
                                <flux:text variant="subtle">Asset Type</flux:text>
                                <flux:text variant="strong">{{ $asset->assetType->name ?? 'Unknown' }}</flux:text>
                            </div>

                            @if($asset->ip)
                            <div class="flex justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded">
                                <flux:text variant="subtle">IP Address</flux:text>
                                <flux:text variant="strong" class="font-mono">{{ $asset->ip }}</flux:text>
                            </div>
                            @endif
                        </div>
                    </flux:card>
                </div>
            @elseif($activeTab === 'services')
                <livewire:assets.asset-service-manager :asset="$asset" :key="'services-'.$asset->id" lazy />
            @elseif($activeTab === 'terminal')
                <livewire:assets.asset-remote-terminal :asset="$asset" :key="'terminal-'.$asset->id" lazy />
            @elseif($activeTab === 'processes')
                <livewire:assets.asset-process-monitor :asset="$asset" :key="'processes-'.$asset->id" />
            @endif
        </div>

        {{-- Help Card --}}
        @if($activeTab === 'overview')
        <flux:card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-blue-200">
            <div class="flex items-start gap-4">
                <flux:icon.light-bulb class="size-8 text-blue-600 flex-shrink-0" />
                <div>
                    <flux:heading size="sm" class="mb-2">Remote Control Features</flux:heading>
                    <flux:text class="text-sm">
                        This control center allows you to manage {{ $asset->name }} remotely in real-time. 
                        Navigate between tabs to:
                    </flux:text>
                    <ul class="mt-2 space-y-1 text-sm">
                        <li class="flex items-center gap-2">
                            <flux:icon.check class="size-4 text-green-600" />
                            <flux:text>Manage Windows services (start, stop, restart)</flux:text>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon.check class="size-4 text-green-600" />
                            <flux:text>Execute PowerShell, CMD, or Bash commands remotely</flux:text>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon.check class="size-4 text-green-600" />
                            <flux:text>Monitor running processes and resource usage</flux:text>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:icon.check class="size-4 text-green-600" />
                            <flux:text>See live updates when other users execute commands</flux:text>
                        </li>
                    </ul>
                </div>
            </div>
        </flux:card>
        @endif
    </div>
</div>
