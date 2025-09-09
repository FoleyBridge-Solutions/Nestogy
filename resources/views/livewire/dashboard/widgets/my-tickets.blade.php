<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.ticket class="size-5 text-blue-500" />
                My Tickets
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Tickets assigned to you or that you're watching
            </flux:text>
        </div>
        
        <!-- Filter Controls -->
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="filter" size="sm">
                <flux:select.option value="assigned">Assigned to Me</flux:select.option>
                <flux:select.option value="created">Created by Me</flux:select.option>
                <flux:select.option value="watching">Watching</flux:select.option>
            </flux:select>
            
            <flux:select wire:model.live="status" size="sm">
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="all">All</flux:select.option>
                <flux:select.option value="closed">Closed</flux:select.option>
            </flux:select>
        </div>
    </div>
    
    <!-- Tickets List -->
    @if($tickets->isNotEmpty())
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @foreach($tickets as $ticket)
                <div class="group p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:shadow-md transition-shadow" 
                     wire:key="ticket-{{ $ticket->id }}">
                    
                    <div class="flex items-start justify-between gap-3">
                        <!-- Ticket Info -->
                        <div class="flex-1 min-w-0">
                            <!-- Title and ID -->
                            <div class="flex items-center gap-2">
                                <a href="{{ $this->getSafeRoute('tickets.show', $ticket->id) }}" class="group/link">
                                    <flux:heading size="sm" class="group-hover/link:text-blue-600 transition-colors">
                                        #{{ $ticket->id }} - {{ $ticket->subject }}
                                    </flux:heading>
                                </a>
                                
                                <!-- Priority Badge -->
                                <flux:badge size="xs" 
                                    color="{{ match($ticket->priority) {
                                        'Critical' => 'red',
                                        'High' => 'orange',
                                        'Medium' => 'yellow',
                                        'Low' => 'green',
                                        default => 'zinc'
                                    } }}">
                                    {{ $ticket->priority }}
                                </flux:badge>
                                
                                <!-- Status Badge -->
                                <flux:badge size="xs" variant="outline"
                                    color="{{ match($ticket->status) {
                                        'Open' => 'blue',
                                        'In Progress' => 'yellow',
                                        'Waiting' => 'orange',
                                        'On Hold' => 'purple',
                                        'Resolved' => 'green',
                                        'Closed' => 'gray',
                                        default => 'zinc'
                                    } }}">
                                    {{ $ticket->status }}
                                </flux:badge>
                            </div>
                            
                            <!-- Client -->
                            @if($ticket->client)
                                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400 mt-1">
                                    <flux:icon.building-office class="size-3 inline" />
                                    {{ $ticket->client->name }}
                                </flux:text>
                            @endif
                            
                            <!-- Description (truncated) -->
                            @if($ticket->description)
                                <flux:text size="sm" class="text-zinc-500 mt-1 line-clamp-2">
                                    {{ Str::limit($ticket->description, 100) }}
                                </flux:text>
                            @endif
                            
                            <!-- Meta Info -->
                            <div class="flex items-center gap-4 mt-2 text-xs text-zinc-500">
                                <span>
                                    <flux:icon.clock class="size-3 inline" />
                                    {{ $ticket->created_at->diffForHumans() }}
                                </span>
                                
                                @if($ticket->createdBy && $ticket->createdBy->id !== Auth::id())
                                    <span>
                                        <flux:icon.user class="size-3 inline" />
                                        by {{ $ticket->createdBy->name }}
                                    </span>
                                @endif
                                
                                @if($ticket->due_date)
                                    <span class="{{ $ticket->due_date->isPast() ? 'text-red-600' : '' }}">
                                        <flux:icon.calendar class="size-3 inline" />
                                        {{ $ticket->due_date->format('M d') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-1">
                            @if($ticket->assigned_to === null)
                                <flux:button variant="ghost" size="xs" wire:click="takeTicket({{ $ticket->id }})">
                                    Take
                                </flux:button>
                            @elseif($ticket->assigned_to === Auth::id() && $ticket->status !== 'Closed')
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="xs" icon="chevron-down" />
                                    
                                    <flux:menu>
                                        @if($ticket->status === 'Open')
                                            <flux:menu.item wire:click="updateStatus({{ $ticket->id }}, 'In Progress')">
                                                Start Working
                                            </flux:menu.item>
                                        @endif
                                        
                                        @if(in_array($ticket->status, ['Open', 'In Progress']))
                                            <flux:menu.item wire:click="updateStatus({{ $ticket->id }}, 'Waiting')">
                                                Wait for Info
                                            </flux:menu.item>
                                            
                                            <flux:menu.item wire:click="updateStatus({{ $ticket->id }}, 'Resolved')">
                                                Mark Resolved
                                            </flux:menu.item>
                                        @endif
                                        
                                        @if($ticket->status === 'Resolved')
                                            <flux:menu.item wire:click="updateStatus({{ $ticket->id }}, 'Closed')">
                                                Close Ticket
                                            </flux:menu.item>
                                        @endif
                                        
                                        <flux:menu.separator />
                                        
                                        <flux:menu.item icon="eye">
                                            <a href="{{ $this->getSafeRoute('tickets.show', $ticket->id) }}">View Details</a>
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @else
                                <a href="{{ $this->getSafeRoute('tickets.show', $ticket->id) }}">
                                    <flux:button variant="ghost" size="xs" icon="eye">
                                        View
                                    </flux:button>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Load More -->
        @if($tickets->count() >= $limit)
            <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button variant="ghost" size="sm" class="w-full" wire:click="loadMore">
                    <flux:icon.arrow-down class="size-4" />
                    Load More Tickets
                </flux:button>
            </div>
        @endif
        
        <!-- Summary -->
        <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between text-xs text-zinc-500">
                <span>{{ $tickets->count() }} tickets shown</span>
                <div class="flex items-center gap-3">
                    <span class="text-red-600">{{ $tickets->where('priority', 'Critical')->count() }} Critical</span>
                    <span class="text-orange-600">{{ $tickets->where('priority', 'High')->count() }} High</span>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="flex items-center justify-center h-32">
            <div class="text-center">
                @if($loading)
                    <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400 mx-auto mb-2" />
                    <flux:text class="text-zinc-500">Loading tickets...</flux:text>
                @else
                    <flux:icon.check-circle class="size-8 text-green-500 mx-auto mb-2" />
                    <flux:heading size="lg">No Tickets</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 mt-1">
                        @if($status === 'active')
                            You're all caught up! No active tickets assigned.
                        @else
                            No tickets found matching the current filter.
                        @endif
                    </flux:text>
                @endif
            </div>
        </div>
    @endif
    
    <!-- Loading Overlay -->
    <div wire:loading wire:target="loadTickets,setFilter,setStatus,takeTicket,updateStatus" 
         class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-blue-500" />
            <flux:text>Updating tickets...</flux:text>
        </div>
    </div>
</flux:card>
