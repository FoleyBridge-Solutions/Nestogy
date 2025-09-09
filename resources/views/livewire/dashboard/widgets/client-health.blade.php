<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.heart class="size-5 text-red-500" />
                Client Health Monitor
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Track client engagement and satisfaction metrics
            </flux:text>
        </div>
        
        <!-- Filter Controls -->
        <div class="flex items-center gap-2">
            <flux:tab.group>
                <flux:tabs wire:model.live="filter" variant="segmented" size="sm">
                    <flux:tab name="all">All</flux:tab>
                    <flux:tab name="healthy">Healthy</flux:tab>
                    <flux:tab name="at_risk">At Risk</flux:tab>
                    <flux:tab name="critical">Critical</flux:tab>
                </flux:tabs>
                
                <!-- Hidden panels - content is controlled by Livewire filter property -->
                <flux:tab.panel name="all" class="hidden"></flux:tab.panel>
                <flux:tab.panel name="healthy" class="hidden"></flux:tab.panel>
                <flux:tab.panel name="at_risk" class="hidden"></flux:tab.panel>
                <flux:tab.panel name="critical" class="hidden"></flux:tab.panel>
            </flux:tab.group>
            
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon="arrows-up-down" />
                
                <flux:menu>
                    <flux:menu.item icon="chart-bar" wire:click="sort('health_score')">
                        Sort by Health Score
                        @if($sortBy === 'health_score')
                            <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3 ml-auto" />
                        @endif
                    </flux:menu.item>
                    
                    <flux:menu.item icon="user" wire:click="sort('name')">
                        Sort by Name
                        @if($sortBy === 'name')
                            <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3 ml-auto" />
                        @endif
                    </flux:menu.item>
                    
                    <flux:menu.item icon="currency-dollar" wire:click="sort('monthly_revenue')">
                        Sort by Revenue
                        @if($sortBy === 'monthly_revenue')
                            <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3 ml-auto" />
                        @endif
                    </flux:menu.item>
                    
                    <flux:menu.item icon="ticket" wire:click="sort('open_tickets')">
                        Sort by Open Tickets
                        @if($sortBy === 'open_tickets')
                            <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3 ml-auto" />
                        @endif
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    
    <!-- Client List -->
    @if($clients->isNotEmpty())
        <div class="space-y-3">
            @foreach($clients as $client)
                <div class="group p-4 rounded-lg border transition-all hover:shadow-md
                    @switch($client['health_status'])
                        @case('healthy')
                            border-green-200 bg-green-50/50 hover:bg-green-50 dark:border-green-800 dark:bg-green-900/20 dark:hover:bg-green-900/30
                            @break
                        @case('stable')
                            border-blue-200 bg-blue-50/50 hover:bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 dark:hover:bg-blue-900/30
                            @break
                        @case('at_risk')
                            border-orange-200 bg-orange-50/50 hover:bg-orange-50 dark:border-orange-800 dark:bg-orange-900/20 dark:hover:bg-orange-900/30
                            @break
                        @case('critical')
                            border-red-200 bg-red-50/50 hover:bg-red-50 dark:border-red-800 dark:bg-red-900/20 dark:hover:bg-red-900/30
                            @break
                    @endswitch
                " wire:key="client-{{ $client['id'] }}">
                    
                    <div class="flex items-start justify-between gap-4">
                        <!-- Client Info -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <!-- Health Score Circle -->
                                <div class="relative">
                                    <svg class="w-12 h-12 -rotate-90">
                                        <circle cx="24" cy="24" r="20" stroke-width="4" 
                                            class="fill-none stroke-zinc-200 dark:stroke-zinc-700" />
                                        <circle cx="24" cy="24" r="20" stroke-width="4"
                                            stroke-dasharray="{{ 125.6 * ($client['health_score'] / 100) }} 125.6"
                                            class="fill-none transition-all duration-500
                                                @if($client['health_score'] >= 80) stroke-green-500
                                                @elseif($client['health_score'] >= 60) stroke-blue-500
                                                @elseif($client['health_score'] >= 40) stroke-orange-500
                                                @else stroke-red-500
                                                @endif
                                            " />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold">{{ $client['health_score'] }}</span>
                                    </div>
                                </div>
                                
                                <!-- Name and Status -->
                                <div>
                                    <a href="{{ route('clients.show', $client['id']) }}" class="group/link">
                                        <flux:heading size="base" class="group-hover/link:text-blue-600 transition-colors">
                                            {{ $client['name'] }}
                                        </flux:heading>
                                    </a>
                                    
                                    <div class="flex items-center gap-2 mt-1">
                                        <flux:badge size="sm" 
                                            color="{{ match($client['health_status']) {
                                                'healthy' => 'green',
                                                'stable' => 'blue',
                                                'at_risk' => 'orange',
                                                'critical' => 'red',
                                                default => 'zinc'
                                            }">
                                            {{ ucfirst(str_replace('_', ' ', $client['health_status'])) }}
                                        </flux:badge>
                                        
                                        @if($client['trend'] === 'improving')
                                            <flux:icon.arrow-trending-up class="size-4 text-green-500" />
                                        @elseif($client['trend'] === 'declining')
                                            <flux:icon.arrow-trending-down class="size-4 text-red-500" />
                                        @else
                                            <flux:icon.minus class="size-4 text-gray-400" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Metrics Grid -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-right">
                            <!-- Revenue -->
                            <div>
                                <flux:text size="xs" class="text-zinc-500">Monthly Revenue</flux:text>
                                <flux:text class="font-medium">${{ number_format($client['monthly_revenue'], 2) }}</flux:text>
                                @if($client['revenue_change'] != 0)
                                    <flux:text size="xs" class="{{ $client['revenue_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $client['revenue_change'] > 0 ? '+' : '' }}{{ $client['revenue_change'] }}%
                                    </flux:text>
                                @endif
                            </div>
                            
                            <!-- Tickets -->
                            <div>
                                <flux:text size="xs" class="text-zinc-500">Open Tickets</flux:text>
                                <flux:text class="font-medium">{{ $client['open_tickets'] }}</flux:text>
                                @if($client['critical_tickets'] > 0)
                                    <flux:text size="xs" class="text-red-600">
                                        {{ $client['critical_tickets'] }} critical
                                    </flux:text>
                                @endif
                            </div>
                            
                            <!-- Resolution Time -->
                            <div>
                                <flux:text size="xs" class="text-zinc-500">Avg Resolution</flux:text>
                                <flux:text class="font-medium">{{ $client['avg_resolution_time'] }}h</flux:text>
                            </div>
                            
                            <!-- Last Contact -->
                            <div>
                                <flux:text size="xs" class="text-zinc-500">Last Contact</flux:text>
                                <flux:text class="font-medium">
                                    @if($client['days_since_contact'] === 0)
                                        Today
                                    @elseif($client['days_since_contact'] === 1)
                                        Yesterday
                                    @elseif($client['days_since_contact'] < 999)
                                        {{ $client['days_since_contact'] }}d ago
                                    @else
                                        Never
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alert for issues -->
                    @if($client['overdue_amount'] > 0)
                        <div class="mt-3 pt-3 border-t border-current/10">
                            <flux:text size="sm" class="text-red-600 dark:text-red-400">
                                <flux:icon.exclamation-triangle class="size-4 inline" />
                                Overdue: ${{ number_format($client['overdue_amount'], 2) }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        
        <!-- Load More -->
        @if($clients->count() >= $limit)
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" class="w-full" wire:click="loadMore" wire:loading.attr="disabled">
                    <flux:icon.arrow-down class="size-4" wire:loading.class="animate-bounce" />
                    Load More Clients
                </flux:button>
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="flex items-center justify-center h-64">
            <div class="text-center">
                @if($loading)
                    <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                    <flux:text>Analyzing client health...</flux:text>
                @else
                    <flux:icon.heart class="size-12 text-zinc-300 mx-auto mb-3" />
                    <flux:heading size="lg">No Clients Found</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">
                        Client health metrics will appear here
                    </flux:text>
                @endif
            </div>
        </div>
    @endif
    
    <!-- Loading Overlay -->
    <div wire:loading wire:target="loadClientHealth,sort,setFilter" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-red-500" />
            <flux:text>Analyzing health...</flux:text>
        </div>
    </div>
</flux:card>
