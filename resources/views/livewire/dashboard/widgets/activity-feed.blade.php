<flux:card class="h-full flex flex-col-span-12">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.clock class="size-5 text-blue-500" />
                Activity Feed
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Recent system activities and updates
            </flux:text>
        </div>
        
        <!-- Filter Dropdown -->
        <flux:dropdown position="bottom" align="end">
            <flux:button variant="ghost" size="sm">
                <flux:icon.funnel class="size-4" />
                Filter: {{ ucfirst($filter) }}
            </flux:button>
            
            <flux:menu>
                <flux:menu.item wire:click="$set('filter', 'all')">All Activities</flux:menu.item>
                <flux:menu.item wire:click="$set('filter', 'tickets')">Tickets Only</flux:menu.item>
                <flux:menu.item wire:click="$set('filter', 'financial')">Financial Only</flux:menu.item>
                <flux:menu.item wire:click="$set('filter', 'clients')">Clients Only</flux:menu.item>
                <flux:menu.item wire:click="$set('filter', 'system')">System Only</flux:menu.item>
                
                <flux:menu.separator />
                
                <flux:menu.checkbox wire:model="autoRefresh">
                    Auto-refresh (30s)
                </flux:menu.checkbox>
            </flux:menu>
        </flux:dropdown>
    </div>
    
    <!-- Activity List -->
    <div class="flex-1 overflow-y-auto" wire:poll.30s="loadActivities">
        @forelse($activities as $activity)
            <div class="group relative flex gap-3 pb-6 last:pb-0" wire:key="{{ $activity['id'] }}">
                <!-- Timeline Line -->
                <div class="absolute left-5 top-10 bottom-0 w-0.5 bg-zinc-200 dark:bg-zinc-700 group-last:hidden"></div>
                
                <!-- Icon -->
                <div class="relative z-10 flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                    @switch($activity['color'])
                        @case('green') bg-green-100 dark:bg-green-900/30 @break
                        @case('blue') bg-blue-100 dark:bg-blue-900/30 @break
                        @case('orange') bg-orange-100 dark:bg-orange-900/30 @break
                        @case('purple') bg-purple-100 dark:bg-purple-900/30 @break
                        @case('red') bg-red-100 dark:bg-red-900/30 @break
                        @case('gray') bg-gray-100 dark:bg-gray-900/30 @break
                        @default bg-zinc-100 dark:bg-zinc-900/30
                    @endswitch
                ">
                    @php
                        $iconColorClass = match($activity['color']) {
                            'green' => 'text-green-600 dark:text-green-400',
                            'blue' => 'text-blue-600 dark:text-blue-400',
                            'orange' => 'text-orange-600 dark:text-orange-400',
                            'purple' => 'text-purple-600 dark:text-purple-400',
                            'red' => 'text-red-600 dark:text-red-400',
                            'gray' => 'text-gray-600 dark:text-gray-400',
                            default => 'text-zinc-600 dark:text-zinc-400'
                        };
                    @endphp
                    
                    @switch($activity['icon'])
                        @case('ticket')
                            <flux:icon.ticket class="size-5 {{ $iconColorClass }}" />
                            @break
                        @case('currency-dollar')
                            <flux:icon.currency-dollar class="size-5 {{ $iconColorClass }}" />
                            @break
                        @case('user-plus')
                            <flux:icon.user-plus class="size-5 {{ $iconColorClass }}" />
                            @break
                        @case('cog')
                            <flux:icon.cog class="size-5 {{ $iconColorClass }}" />
                            @break
                        @case('document-text')
                            <flux:icon.document-text class="size-5 {{ $iconColorClass }}" />
                            @break
                        @case('check-circle')
                            <flux:icon.check-circle class="size-5 {{ $iconColorClass }}" />
                            @break
                        @case('x-circle')
                            <flux:icon.x-circle class="size-5 {{ $iconColorClass }}" />
                            @break
                        @default
                            <flux:icon.information-circle class="size-5 {{ $iconColorClass }}" />
                    @endswitch
                </div>
                
                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1">
                            <!-- Title with optional link -->
                            @if($activity['link'] ?? null)
                                <a href="{{ $activity['link'] }}" class="group/link">
                                    <flux:text class="font-medium group-hover/link:text-blue-600 transition-colors">
                                        {{ $activity['title'] }}
                                    </flux:text>
                                </a>
                            @else
                                <flux:text class="font-medium">{{ $activity['title'] }}</flux:text>
                            @endif
                            
                            <!-- Description -->
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400 mt-0.5">
                                {{ $activity['description'] }}
                            </flux:text>
                            
                            <!-- Metadata -->
                            <div class="flex items-center gap-3 mt-2">
                                @if($activity['client'] ?? null)
                                    <flux:badge size="sm" variant="outline">
                                        <flux:icon.user class="size-3" />
                                        {{ $activity['client'] }}
                                    </flux:badge>
                                @endif
                                
                                @if($activity['priority'] ?? null)
                                    <flux:badge size="sm" 
                                        color="{{ match($activity['priority']) {
                                            'Critical' => 'red',
                                            'High' => 'orange',
                                            'Medium' => 'yellow',
                                            'Low' => 'green',
                                            default => 'zinc'
                                        } }}"
                                    >
                                        {{ $activity['priority'] }}
                                    </flux:badge>
                                @endif
                                
                                @if($activity['status'] ?? null)
                                    <flux:badge size="sm" variant="outline">
                                        {{ $activity['status'] }}
                                    </flux:badge>
                                @endif
                                
                                @if($activity['method'] ?? null)
                                    <flux:badge size="sm" color="green">
                                        {{ $activity['method'] }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Timestamp -->
                        <flux:tooltip content="{{ $activity['timestamp']->format('M d, Y g:i A') }}">
                            <flux:text size="xs" class="text-zinc-500 whitespace-nowrap">
                                {{ $activity['timestamp']->diffForHumans() }}
                            </flux:text>
                        </flux:tooltip>
                    </div>
                    
                    <!-- User info -->
                    <flux:text size="xs" class="text-zinc-400 mt-1">
                        by {{ $activity['user'] }}
                    </flux:text>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    @if($loading)
                        <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                        <flux:text>Loading activities...</flux:text>
                    @else
                        <flux:icon.clock class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:heading size="lg">No Recent Activity</flux:heading>
                        <flux:text class="mt-2 text-zinc-500">
                            Activities will appear here as they occur
                        </flux:text>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
    
    <!-- Load More Button -->
    @if($activities->count() >= $limit)
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" size="sm" class="w-full" wire:click="loadMore" wire:loading.attr="disabled">
                <flux:icon.arrow-down class="size-4" wire:loading.class="animate-bounce" />
                Load More Activities
            </flux:button>
        </div>
    @endif
    
    <!-- Loading Overlay -->
    <div wire:loading.delay wire:target="loadActivities,setFilter" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-blue-500" />
            <flux:text>Updating feed...</flux:text>
        </div>
    </div>
</flux:card>
