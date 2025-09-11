<div class="relative">
    @if($this->currentClient)
        <!-- Current Client Selected -->
        <flux:dropdown position="bottom" align="start" width="320">
            <button 
                type="button" 
                class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
            >
                <flux:avatar size="sm" :name="$this->currentClient->name" />
                <div class="flex-1 text-left">
                    <flux:heading size="sm">{{ Str::limit($this->currentClient->name, 25) }}</flux:heading>
                    @if($this->currentClient->company_name && $this->currentClient->company_name !== $this->currentClient->name)
                        <flux:text size="sm" class="text-zinc-500">{{ Str::limit($this->currentClient->company_name, 25) }}</flux:text>
                    @endif
                </div>
                <flux:icon name="chevron-down" class="w-4 h-4 text-zinc-400" />
            </button>

            <flux:popover class="!p-5 max-h-[600px] overflow-y-auto">
                <!-- Current Client Info -->
                <div class="flex items-start gap-3 mb-5">
                    <flux:avatar size="lg" :name="$this->currentClient->name" class="flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="min-w-0 flex-1">
                                <flux:heading size="lg" class="truncate">{{ $this->currentClient->name }}</flux:heading>
                                @if($this->currentClient->company_name && $this->currentClient->company_name !== $this->currentClient->name)
                                    <flux:text size="sm" class="text-zinc-500 truncate">{{ $this->currentClient->company_name }}</flux:text>
                                @endif
                            </div>
                            <button 
                                wire:click.stop="toggleFavorite({{ $this->currentClient->id }})"
                                class="ml-2 p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
                                title="{{ $this->isClientFavorite($this->currentClient->id) ? 'Remove from favorites' : 'Add to favorites' }}"
                            >
                                <flux:icon 
                                    name="star" 
                                    class="w-4 h-4 {{ $this->isClientFavorite($this->currentClient->id) ? 'text-yellow-500 fill-current' : 'text-zinc-400' }}" 
                                />
                            </button>
                        </div>
                        <flux:text size="sm" class="text-zinc-500 truncate">{{ $this->currentClient->email }}</flux:text>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="flex items-center gap-4 text-sm mb-4">
                    <flux:text class="flex items-center gap-1">
                        <flux:heading size="sm">{{ $this->currentClient->tickets_count ?? 0 }}</flux:heading>
                        <span class="text-zinc-500">tickets</span>
                    </flux:text>
                    <flux:text class="flex items-center gap-1">
                        <flux:heading size="sm">{{ $this->currentClient->invoices_count ?? 0 }}</flux:heading>
                        <span class="text-zinc-500">invoices</span>
                    </flux:text>
                    @if($this->currentClient->status === 'active')
                        <flux:badge color="green" size="sm">Active</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">{{ ucfirst($this->currentClient->status) }}</flux:badge>
                    @endif
                </div>
                
                <!-- Quick Actions -->
                <div class="flex gap-2 mb-3">
                    <flux:button variant="primary" size="sm" icon="building-office" href="{{ route('clients.index') }}" class="flex-1">
                        Dashboard
                    </flux:button>
                    <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click.stop="clearSelection">
                        Clear
                    </flux:button>
                </div>
                
                <flux:separator class="mb-3" />
                
                <!-- Switch Client Section -->
                <div class="space-y-3">
                    <!-- Quick Switch - Combined Favorites and Recent -->
                    @php
                        $quickSwitchClients = collect();
                        $favIds = $this->favoriteClients->pluck('id')->toArray();
                        
                        // Add favorites first
                        foreach($this->favoriteClients as $client) {
                            if($client->id !== $this->currentClient->id) {
                                $client->is_favorite = true;
                                $quickSwitchClients->push($client);
                            }
                        }
                        
                        // Add recent (excluding favorites and current)
                        foreach($this->recentClients as $client) {
                            if($client->id !== $this->currentClient->id && !in_array($client->id, $favIds)) {
                                $client->is_favorite = false;
                                $quickSwitchClients->push($client);
                            }
                        }
                        
                        // Limit to 8 total items
                        $quickSwitchClients = $quickSwitchClients->take(8);
                    @endphp
                    
                    @if($quickSwitchClients->count() > 0)
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <flux:icon name="arrow-path-rounded-square" class="w-4 h-4 text-zinc-500" />
                                <flux:text size="sm" class="font-medium text-zinc-700 dark:text-zinc-300">Quick Switch</flux:text>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                @foreach($quickSwitchClients as $client)
                                    <div class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors group">
                                        <button 
                                            type="button"
                                            wire:click.stop="selectClient({{ $client->id }})"
                                            class="flex-1 flex items-center gap-2 text-left"
                                        >
                                            <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                    {{ strtoupper(substr($client->name, 0, 2)) }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium truncate">{{ $client->name }}</div>
                                                @if($client->company_name && $client->company_name !== $client->name)
                                                    <div class="text-xs text-zinc-500 truncate">{{ $client->company_name }}</div>
                                                @endif
                                            </div>
                                        </button>
                                        @if($client->is_favorite)
                                            <flux:icon name="star" class="w-4 h-4 text-yellow-500 fill-current flex-shrink-0" />
                                        @else
                                            <button
                                                type="button"
                                                wire:click.stop="toggleFavorite({{ $client->id }})"
                                                class="p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                                title="Add to favorites"
                                            >
                                                <flux:icon name="star" class="w-4 h-4 text-zinc-400" />
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Search Section -->
                    <div>
                        <flux:input 
                            type="search"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="Search clients..."
                            size="sm"
                            icon="magnifying-glass"
                            class="text-xs"
                        />
                        
                        @if(strlen($searchQuery) >= 2 && $this->searchResults->count() > 0)
                            <div class="mt-1 max-h-48 overflow-y-auto space-y-0">
                                @foreach($this->searchResults as $client)
                                    @if($client->id !== $this->currentClient->id)
                                        <div class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors group">
                                            <button 
                                                type="button"
                                                wire:click.stop="selectClient({{ $client->id }})"
                                                class="flex-1 flex items-center gap-2 text-left"
                                            >
                                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                        {{ strtoupper(substr($client->name, 0, 2)) }}
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium truncate">{{ $client->name }}</div>
                                                    @if($client->company_name && $client->company_name !== $client->name)
                                                        <div class="text-xs text-zinc-500 truncate">{{ $client->company_name }}</div>
                                                    @endif
                                                </div>
                                            </button>
                                            <button
                                                type="button"
                                                wire:click.stop="toggleFavorite({{ $client->id }})"
                                                class="p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                                title="Add to favorites"
                                            >
                                                <flux:icon name="star" class="w-4 h-4 text-zinc-400" />
                                            </button>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @elseif(strlen($searchQuery) >= 2)
                            <div class="mt-2 text-center py-4">
                                <flux:text size="sm" class="text-zinc-500">No clients found matching "{{ $searchQuery }}"</flux:text>
                            </div>
                        @endif
                    </div>

                </div>
            </flux:popover>
        </flux:dropdown>
    @else
        <!-- No Client Selected -->
        <flux:dropdown position="bottom" align="start" width="320">
            <flux:button variant="ghost" size="sm" icon="user-group" icon:trailing="chevron-down">
                Select Client
            </flux:button>
            
            <flux:menu>
                <!-- Favorites Section -->
                @if($this->favoriteClients->count() > 0)
                    <div class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <flux:icon name="star" class="w-4 h-4 text-yellow-500" />
                            <flux:text size="sm" class="font-medium text-zinc-700 dark:text-zinc-300">Favorites</flux:text>
                        </div>
                        <div class="space-y-0">
                            @foreach($this->favoriteClients as $client)
                                <button 
                                    type="button"
                                    wire:click.stop="selectClient({{ $client->id }})"
                                    class="w-full flex items-center gap-2 px-2 py-0.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors text-left group"
                                >
                                    <div class="w-6 h-6 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ strtoupper(substr($client->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium truncate leading-none">{{ $client->name }}</div>
                                        @if($client->company_name && $client->company_name !== $client->name)
                                            <div class="text-[10px] text-zinc-500 truncate leading-none mt-0.5">{{ $client->company_name }}</div>
                                        @endif
                                    </div>
                                    <flux:icon name="star" class="w-4 h-4 text-yellow-500 fill-current opacity-0 group-hover:opacity-100 transition-opacity" />
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Recent Section -->
                @if($this->recentClients->count() > 0)
                    <div class="px-3 py-2 {{ $this->favoriteClients->count() > 0 ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <flux:icon name="clock" class="w-4 h-4 text-zinc-500" />
                            <flux:text size="sm" class="font-medium text-zinc-700 dark:text-zinc-300">Recent</flux:text>
                        </div>
                        <div class="space-y-0">
                            @foreach($this->recentClients as $client)
                                <div class="w-full flex items-center gap-2 px-2 py-0.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors group">
                                    <button 
                                        type="button"
                                        wire:click.stop="selectClient({{ $client->id }})"
                                        class="flex-1 flex items-center gap-2 text-left"
                                    >
                                        <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ strtoupper(substr($client->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium truncate">{{ $client->name }}</div>
                                            @if($client->company_name && $client->company_name !== $client->name)
                                                <div class="text-xs text-zinc-500 truncate">{{ $client->company_name }}</div>
                                            @endif
                                        </div>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click.stop="toggleFavorite({{ $client->id }})"
                                        class="p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                        title="Add to favorites"
                                    >
                                        <flux:icon name="star" class="w-4 h-4 text-zinc-400" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Search Section -->
                <div class="p-3">
                    <flux:input 
                        type="search"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search all clients..."
                        size="sm"
                        icon="magnifying-glass"
                    />
                    
                    @if(strlen($searchQuery) >= 2 && $this->searchResults->count() > 0)
                        <div class="mt-1 max-h-48 overflow-y-auto space-y-0">
                            @foreach($this->searchResults as $client)
                                    <div class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors group">
                                    <button 
                                        type="button"
                                        wire:click.stop="selectClient({{ $client->id }})"
                                        class="flex-1 flex items-center gap-2 text-left"
                                    >
                                        <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ strtoupper(substr($client->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium truncate">{{ $client->name }}</div>
                                            @if($client->company_name && $client->company_name !== $client->name)
                                                <div class="text-xs text-zinc-500 truncate">{{ $client->company_name }}</div>
                                            @endif
                                        </div>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click.stop="toggleFavorite({{ $client->id }})"
                                        class="p-0.5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                        title="Add to favorites"
                                    >
                                        <flux:icon name="star" class="w-4 h-4 text-zinc-400" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @elseif(strlen($searchQuery) >= 2)
                        <div class="mt-2 text-center py-4">
                            <flux:text size="sm" class="text-zinc-500">No clients found matching "{{ $searchQuery }}"</flux:text>
                        </div>
                    @endif
                </div>

                <!-- No Clients State -->
                @php
                    $hasClients = auth()->check() ? \App\Models\Client::where('company_id', auth()->user()->company_id)->exists() : false;
                @endphp
                @if(!$hasClients && strlen($searchQuery) < 2)
                    <div class="p-8 text-center">
                        <flux:icon name="user-group" class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" />
                        <flux:text size="sm" class="text-zinc-500 mb-4">No clients available</flux:text>
                        <flux:button variant="primary" size="sm" icon="plus" href="{{ route('clients.create') }}">
                            Add First Client
                        </flux:button>
                    </div>
                @endif
                
                <!-- Footer Actions -->
                @if($this->favoriteClients->count() > 0 || $this->recentClients->count() > 0 || strlen($searchQuery) >= 2)
                    <flux:separator />
                    <div class="p-2">
                        <!-- Keyboard shortcuts hint -->
                        <div class="flex items-center justify-center gap-2 text-xs text-zinc-500">
                            <span class="px-1 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-xs">⌘K</span>
                            <span>to search</span>
                            <span class="px-1 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-xs">1-5</span>
                            <span>for favorites</span>
                        </div>
                    </div>
                @endif
            </flux:menu>
        </flux:dropdown>
    @endif
    
    <!-- Loading Overlay -->
    <div wire:loading wire:target="selectClient,clearSelection,toggleFavorite" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 rounded-lg flex items-center justify-center">
        <flux:icon name="arrow-path" class="w-4 h-4 animate-spin text-zinc-500" />
    </div>

    @script
    <script>
        // Simple keyboard shortcuts for the client switcher
        document.addEventListener('keydown', (e) => {
            // Cmd/Ctrl + K to focus search (if visible)
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Number keys (1-5) to select favorite clients
            if (['1', '2', '3', '4', '5'].includes(e.key)) {
                const number = parseInt(e.key);
                $wire.selectFavoriteByNumber(number);
            }
        });
    </script>
    @endscript
</div>
