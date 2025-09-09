<flux:card class="h-full flex flex-col-span-12">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.queue-list class="size-5 text-blue-500" />
                Ticket Queue
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Manage and prioritize support tickets
            </flux:text>
        </div>
        
        <!-- Actions -->
        <div class="flex items-center gap-2">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm">
                    <flux:icon.signal class="size-4" />
                    Priority: {{ ucfirst($priority) }}
                </flux:button>
                
                <flux:menu>
                    <flux:menu.item wire:click="setPriority('all')">All Priorities</flux:menu.item>
                    <flux:menu.item wire:click="setPriority('critical')">Critical Only</flux:menu.item>
                    <flux:menu.item wire:click="setPriority('high')">High Only</flux:menu.item>
                    <flux:menu.item wire:click="setPriority('medium')">Medium Only</flux:menu.item>
                    <flux:menu.item wire:click="setPriority('low')">Low Only</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm">
                    <flux:icon.funnel class="size-4" />
                    Status: {{ ucfirst(str_replace('_', ' ', $status)) }}
                </flux:button>
                
                <flux:menu>
                    <flux:menu.item wire:click="setStatus('all')">All Active</flux:menu.item>
                    <flux:menu.item wire:click="setStatus('open')">Open</flux:menu.item>
                    <flux:menu.item wire:click="setStatus('in_progress')">In Progress</flux:menu.item>
                    <flux:menu.item wire:click="setStatus('waiting')">Waiting</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            
            <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="loadQueue" />
        </div>
    </div>
    
    <!-- Stats Bar -->
    <div class="grid grid-cols-4 gap-4 mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
        @php
            $stats = [
                'total' => $tickets->count(),
                'critical' => $tickets->where('priority', 'Critical')->count(),
                'overdue' => $tickets->where('sla_status', 'overdue')->count(),
                'unassigned' => $tickets->where('assigned_to', 'Unassigned')->count(),
            ];
        @endphp
        
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500 uppercase">Total</flux:text>
            <flux:heading size="lg" class="mt-1">{{ $stats['total'] }}</flux:heading>
        </div>
        
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500 uppercase">Critical</flux:text>
            <flux:heading size="lg" class="mt-1 text-red-600">{{ $stats['critical'] }}</flux:heading>
        </div>
        
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500 uppercase">Overdue</flux:text>
            <flux:heading size="lg" class="mt-1 text-orange-600">{{ $stats['overdue'] }}</flux:heading>
        </div>
        
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500 uppercase">Unassigned</flux:text>
            <flux:heading size="lg" class="mt-1 text-blue-600">{{ $stats['unassigned'] }}</flux:heading>
        </div>
    </div>
    
    <!-- Ticket List -->
    <div class="flex-1 overflow-y-auto">
        @forelse($tickets as $ticket)
            <div class="group border-b border-zinc-100 dark:border-zinc-800 pb-3 mb-3 last:border-b-0 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 p-2 rounded transition-colors cursor-pointer" wire:click="viewTicket({{ $ticket['id'] }})">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <!-- Priority Badge -->
                            <flux:badge 
                                size="sm"
                                color="{{ match($ticket['priority']) {
                                    'Critical' => 'red',
                                    'High' => 'orange',
                                    'Medium' => 'yellow',
                                    'Low' => 'green',
                                    default => 'zinc'
                                } }}"
                            >
                                {{ $ticket['priority'] }}
                            </flux:badge>
                            
                            <!-- SLA Status -->
                            @if($ticket['sla_status'] === 'overdue')
                                <flux:badge size="sm" color="red" variant="outline">
                                    <flux:icon.exclamation-triangle class="size-3" />
                                    SLA Breach
                                </flux:badge>
                            @elseif($ticket['sla_status'] === 'warning')
                                <flux:badge size="sm" color="amber" variant="outline">
                                    <flux:icon.clock class="size-3" />
                                    SLA Warning
                                </flux:badge>
                            @endif
                            
                            <!-- Time in Queue -->
                            <flux:text size="xs" class="text-zinc-500">
                                {{ $ticket['time_in_queue'] }} in queue
                            </flux:text>
                        </div>
                        
                        <!-- Subject -->
                        <flux:text class="font-medium line-clamp-1">
                            #{{ $ticket['id'] }} - {{ $ticket['subject'] }}
                        </flux:text>
                        
                        <!-- Metadata -->
                        <div class="flex items-center gap-3 mt-1">
                            <flux:text size="xs" class="text-zinc-500">
                                <flux:icon.building-office class="size-3 inline" />
                                {{ $ticket['client'] }}
                            </flux:text>
                            
                            <flux:text size="xs" class="text-zinc-500">
                                <flux:icon.user class="size-3 inline" />
                                {{ $ticket['assigned_to'] }}
                            </flux:text>
                            
                            <flux:text size="xs" class="text-zinc-500">
                                {{ $ticket['created_at']->diffForHumans() }}
                            </flux:text>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-1" @click.stop>
                        @if($ticket['assigned_to'] === 'Unassigned')
                            <flux:button 
                                variant="ghost" 
                                size="xs" 
                                wire:click="assignTicket({{ $ticket['id'] }})"
                            >
                                Take
                            </flux:button>
                        @endif
                        
                        <flux:button 
                            variant="ghost" 
                            size="xs" 
                            icon="arrow-top-right-on-square"
                            wire:click="viewTicket({{ $ticket['id'] }})"
                            title="Open ticket"
                        />
                    </div>
                </div>
            </div>
        @empty
            <!-- Empty State -->
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    @if($loading)
                        <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                        <flux:text>Loading queue...</flux:text>
                    @else
                        <flux:icon.inbox class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:heading size="lg">Queue Empty</flux:heading>
                        <flux:text class="mt-2 text-zinc-500">
                            No tickets match your current filters
                        </flux:text>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
    
    <!-- Load More -->
    @if($tickets->count() >= $limit)
        <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="ghost" size="sm" class="w-full" wire:click="loadMore">
                Load More Tickets
            </flux:button>
        </div>
    @endif
    
    <!-- Loading Overlay -->
    <div wire:loading.delay wire:target="loadQueue,setPriority,setStatus" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-blue-500" />
            <flux:text>Updating queue...</flux:text>
        </div>
    </div>
</flux:card>
