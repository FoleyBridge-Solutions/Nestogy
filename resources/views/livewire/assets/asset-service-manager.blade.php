<div>
    @script
    <script>
        console.log('🔧 AssetServiceManager loaded for asset {{ $asset->id }}');
        
        // Listen for command notifications
        $wire.on('command-notification', (event) => {
            console.log('📢 Command notification:', event);
        });
    </script>
    @endscript

    {{-- Command Status Notification --}}
    @if($lastCommandStatus && $lastCommand)
    <flux:card class="mb-4" :class="$lastCommandStatus === 'completed' ? 'bg-green-50 border-green-200' : ($lastCommandStatus === 'failed' ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200')">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if($lastCommandStatus === 'completed')
                    <flux:icon.check-circle class="text-green-600 size-5" />
                    <flux:text class="text-green-800">Command completed: {{ $lastCommand }}</flux:text>
                @elseif($lastCommandStatus === 'failed')
                    <flux:icon.x-circle class="text-red-600 size-5" />
                    <flux:text class="text-red-800">Command failed: {{ $lastCommand }}</flux:text>
                @else
                    <flux:icon.arrow-path class="text-blue-600 size-5 animate-spin" />
                    <flux:text class="text-blue-800">Running: {{ $lastCommand }}</flux:text>
                @endif
            </div>
            <flux:button 
                size="sm" 
                variant="ghost" 
                icon="x-mark"
                wire:click="$set('lastCommandStatus', null)"
            >
                Dismiss
            </flux:button>
        </div>
    </flux:card>
    @endif

    <flux:card>
        <div class="flex items-center justify-between mb-4">
            <flux:heading class="flex items-center gap-2">
                <flux:icon.cog-6-tooth class="size-5" />
                Windows Services
            </flux:heading>
            
            <div class="flex gap-2">
                @if(count($services) > 0)
                    <flux:badge color="blue">{{ count($filteredServices) }} services</flux:badge>
                @endif
                
                <flux:button 
                    wire:click="loadServices" 
                    size="sm"
                    icon="arrow-path"
                    :loading="$loading"
                >
                    {{ count($services) > 0 ? 'Refresh' : 'Load Services' }}
                </flux:button>
            </div>
        </div>

        @error('services')
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
        @if($loading && count($services) === 0)
        <div class="py-16 flex flex-col items-center justify-center">
            <div class="relative">
                <flux:icon.cog-6-tooth class="size-16 text-blue-500 animate-spin" />
                <flux:icon.cog-6-tooth class="size-8 text-blue-300 animate-spin absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2" style="animation-direction: reverse; animation-duration: 1s;" />
            </div>
            <flux:text variant="subtle" class="mt-4 animate-pulse">Loading services from remote device...</flux:text>
        </div>
        @elseif(count($services) > 0)
            {{-- Search and Filter Controls --}}
            <div class="mb-4 flex gap-3">
                <div class="flex-1">
                    <flux:input 
                        wire:model.live.debounce.300ms="searchTerm" 
                        placeholder="Search services..."
                        icon="magnifying-glass"
                    />
                </div>
                
                <flux:select wire:model.live="filterStatus" class="w-40">
                    <option value="all">All Services</option>
                    <option value="running">Running</option>
                    <option value="stopped">Stopped</option>
                </flux:select>
            </div>

            {{-- Services List --}}
            <div class="space-y-2 max-h-[600px] overflow-y-auto">
                @forelse($filteredServices as $service)
                <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <flux:text variant="strong">{{ $service['display_name'] ?? $service['name'] }}</flux:text>
                            @if(strtolower($service['status']) === 'running')
                                <flux:badge color="green" size="sm">{{ ucfirst($service['status']) }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ ucfirst($service['status']) }}</flux:badge>
                            @endif
                            
                            @if(isset($service['startup_type']))
                            <flux:badge variant="subtle" size="sm">
                                {{ ucfirst($service['startup_type']) }}
                            </flux:badge>
                            @endif
                        </div>
                        
                        @if(isset($service['description']) && $service['description'])
                        <flux:text variant="subtle" class="text-xs mt-1">
                            {{ Str::limit($service['description'], 100) }}
                        </flux:text>
                        @endif
                        
                        <flux:text variant="subtle" class="text-xs font-mono mt-1">
                            {{ $service['name'] }}
                        </flux:text>
                    </div>

                    <div class="flex gap-2 ml-4">
                        @if(strtolower($service['status']) === 'running')
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                icon="stop"
                                wire:click="stopService('{{ $service['name'] }}')"
                                wire:confirm="Are you sure you want to stop this service?"
                            >
                                Stop
                            </flux:button>
                            
                            <flux:button 
                                size="sm"
                                icon="arrow-path"
                                wire:click="restartService('{{ $service['name'] }}')"
                                wire:confirm="Are you sure you want to restart this service?"
                            >
                                Restart
                            </flux:button>
                        @else
                            <flux:button 
                                size="sm"
                                icon="play"
                                wire:click="startService('{{ $service['name'] }}')"
                            >
                                Start
                            </flux:button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <flux:icon.magnifying-glass class="size-12 text-zinc-400 mx-auto mb-2" />
                    <flux:text variant="subtle">No services found matching your search.</flux:text>
                </div>
                @endforelse
            </div>
        @else
            <div class="text-center py-12">
                <flux:icon.cog-6-tooth class="size-16 text-zinc-300 mx-auto mb-4" />
                <flux:heading size="lg" class="mb-2">No Services Loaded</flux:heading>
                <flux:text variant="subtle" class="mb-4">
                    Click "Load Services" to view and manage Windows services on this device.
                </flux:text>
            </div>
        @endif
    </flux:card>
</div>
