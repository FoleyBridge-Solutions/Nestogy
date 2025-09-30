<div x-data="{
    init() {
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Press 'c' to create new ticket
            if (e.key === 'c' && !e.metaKey && !e.ctrlKey && !e.altKey && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                window.location.href = '{{ route('tickets.create') }}';
            }
            // Press 'v' to toggle view mode
            if (e.key === 'v' && !e.metaKey && !e.ctrlKey && !e.altKey && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                @this.toggleView(@this.viewMode === 'cards' ? 'table' : 'cards');
            }
            // Press '/' to focus search
            if (e.key === '/' && !e.metaKey && !e.ctrlKey && !e.altKey && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                document.querySelector('input[type=search]')?.focus();
            }
            // Press 'Escape' to clear selection
            if (e.key === 'Escape' && @this.selectedTickets.length > 0) {
                @this.set('selectedTickets', []);
                @this.set('selectAll', false);
            }
        });
    }
}">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <flux:toast>{{ session('message') }}</flux:toast>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Tickets</h1>
            <p class="text-gray-500">Manage support tickets and requests</p>
        </div>
        
        @if($viewMode === 'cards')
            {{-- Priority Legend --}}
            <div class="flex items-center gap-4 text-sm">
                <span class="text-gray-500">Priority:</span>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3" style="border-left: 4px solid #dc2626"></div>
                        <span>{{ \App\Domains\Ticket\Models\Ticket::PRIORITY_CRITICAL }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3" style="border-left: 4px solid #f97316"></div>
                        <span>{{ \App\Domains\Ticket\Models\Ticket::PRIORITY_HIGH }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3" style="border-left: 4px solid #eab308"></div>
                        <span>{{ \App\Domains\Ticket\Models\Ticket::PRIORITY_MEDIUM }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3" style="border-left: 4px solid #9ca3af"></div>
                        <span>{{ \App\Domains\Ticket\Models\Ticket::PRIORITY_LOW }}</span>
                    </div>
                </div>
            </div>
        @endif
        <div class="flex items-center gap-3">
            {{-- Keyboard Shortcuts Help --}}
            <flux:dropdown>
                <flux:button variant="ghost" size="sm" class="text-gray-500">
                    <flux:icon.question-mark-circle class="size-4" />
                </flux:button>
                <flux:menu class="w-64">
                    <div class="px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 border-b">
                        Keyboard Shortcuts
                    </div>
                    <flux:menu.item class="text-sm">
                        <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">C</kbd>
                        <span class="ml-2">Create new ticket</span>
                    </flux:menu.item>
                    <flux:menu.item class="text-sm">
                        <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">V</kbd>
                        <span class="ml-2">Toggle view mode</span>
                    </flux:menu.item>
                    <flux:menu.item class="text-sm">
                        <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">/</kbd>
                        <span class="ml-2">Focus search</span>
                    </flux:menu.item>
                    <flux:menu.item class="text-sm">
                        <kbd class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 rounded">Esc</kbd>
                        <span class="ml-2">Clear selection</span>
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
            
            {{-- View Mode Toggle --}}
            <div class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                <button
                    wire:click="toggleView('cards')"
                    class="px-3 py-1.5 rounded {{ $viewMode === 'cards' ? 'bg-white dark:bg-gray-700 shadow text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }} transition-all duration-200"
                >
                    <flux:icon.squares-2x2 class="size-4" />
                </button>
                <button
                    wire:click="toggleView('table')"
                    class="px-3 py-1.5 rounded {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-700 shadow text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }} transition-all duration-200"
                >
                    <flux:icon.bars-3 class="size-4" />
                </button>
            </div>
            
            <flux:button variant="primary" href="{{ route('tickets.create') }}">
                <flux:icon.plus class="size-4" />
                Create Ticket
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
        {{-- Search --}}
        <flux:input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Search tickets..."
            icon="magnifying-glass"
        />

        {{-- Status Pillbox --}}
        <flux:pillbox wire:model.live="selectedStatuses" multiple placeholder="Filter by Status">
            @foreach($statuses as $statusOption)
                <flux:pillbox.option value="{{ $statusOption }}">
                    {{ ucfirst(str_replace('_', ' ', $statusOption)) }}
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        {{-- Priority Pillbox --}}
        <flux:pillbox wire:model.live="selectedPriorities" multiple placeholder="All Priorities">
            @foreach($priorities as $priorityOption)
                <flux:pillbox.option value="{{ $priorityOption }}">
                    {{ ucfirst($priorityOption) }}
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        {{-- Client Pillbox --}}
        <flux:pillbox wire:model.live="selectedClients" multiple placeholder="All Clients" searchable>
            @foreach($clients as $client)
                <flux:pillbox.option value="{{ $client->id }}">
                    {{ $client->name }}
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        {{-- Assignee Pillbox --}}
        <flux:pillbox wire:model.live="selectedAssignees" multiple placeholder="All Technicians" searchable>
            @foreach($users as $user)
                <flux:pillbox.option value="{{ $user->id }}">
                    {{ $user->name }}
                </flux:pillbox.option>
            @endforeach
        </flux:pillbox>

        {{-- Date From --}}
        <flux:input
            type="date"
            wire:model.live="dateFrom"
            placeholder="From Date"
        />
        
        {{-- Date To --}}
        <flux:input
            type="date"
            wire:model.live="dateTo"
            placeholder="To Date"
        />

        {{-- Per Page --}}
        <flux:select wire:model.live="perPage">
            <option value="10">10 per page</option>
            <option value="25">25 per page</option>
            <option value="50">50 per page</option>
            <option value="100">100 per page</option>
        </flux:select>
    </div>
    


    {{-- Bulk Actions --}}
    @if(count($selectedTickets) > 0)
        <flux:card class="mb-4">
            <div class="p-4 flex items-center gap-4">
                <span class="text-gray-600">{{ count($selectedTickets) }} selected</span>
                
                <flux:dropdown>
                    <flux:button variant="outline" size="sm">
                        Update Status
                        <flux:icon.chevron-down class="size-4" />
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="bulkUpdateStatus('open')">
                            <flux:icon.check-circle class="size-4 text-green-500" />
                            Open
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('in_progress')">
                            <flux:icon.clock class="size-4 text-blue-500" />
                            In Progress
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('resolved')">
                            <flux:icon.check-circle class="size-4 text-purple-500" />
                            Resolved
                        </flux:menu.item>
                        <flux:menu.item wire:click="bulkUpdateStatus('closed')">
                            <flux:icon.x-circle class="size-4 text-gray-500" />
                            Closed
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                
                <flux:button 
                    variant="danger" 
                    size="sm"
                    wire:click="bulkDelete"
                    wire:confirm="Are you sure you want to archive {{ count($selectedTickets) }} tickets?"
                >
                    <flux:icon.trash class="size-4" />
                    Archive Selected
                </flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Tickets Display --}}
    <div wire:loading.class="opacity-50 pointer-events-none" wire:target="search, status, priority, assignedTo, clientId, perPage, sortBy">
        @if($viewMode === 'cards')
            {{-- Cards View --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($tickets as $ticket)
            @php
                $priorityColor = match(strtolower($ticket->priority)) {
                    'critical' => '#dc2626', // red-600
                    'high' => '#f97316', // orange-500
                    'medium' => '#eab308', // yellow-500
                    'low' => '#9ca3af', // gray-400
                    default => '#71717a' // zinc-500
                };
            @endphp
            <div 
                wire:key="ticket-{{ $ticket->id }}"
                x-data="{ isUpdating: false }"
                @ticket-updating.window="if ($event.detail.id == {{ $ticket->id }}) isUpdating = true; setTimeout(() => isUpdating = false, 1000)"
                :class="{ 'animate-pulse': isUpdating }"
            >
            <flux:card 
                class="relative group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 ease-out cursor-pointer"
                style="border-left: 4px solid {{ $priorityColor }}"
                onclick="window.location.href='{{ route('tickets.show', $ticket) }}'"
            >
                {{-- Selection Checkbox --}}
                <div class="absolute top-3 left-3 z-10" onclick="event.stopPropagation()">
                    <flux:checkbox 
                        wire:model.live="selectedTickets" 
                        value="{{ $ticket->id }}"
                    />
                </div>
                
                {{-- Card Content --}}
                <div class="p-4">
                    {{-- Header with Ticket Number and Actions --}}
                    <div class="flex items-start justify-between mb-3">
                        <span class="text-blue-600 font-semibold">
                            #{{ $ticket->number }}
                        </span>
                        <div onclick="event.stopPropagation()">
                            <flux:dropdown>
                            <flux:button variant="ghost" size="sm" class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <flux:icon.ellipsis-vertical class="size-4" />
                            </flux:button>
                            <flux:menu>
                                <flux:menu.item href="{{ route('tickets.show', $ticket) }}">
                                    <flux:icon.eye class="size-4" />
                                    View
                                </flux:menu.item>
                                <flux:menu.item href="{{ route('tickets.edit', $ticket) }}">
                                    <flux:icon.pencil class="size-4" />
                                    Edit
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item 
                                    wire:click="deleteTicket({{ $ticket->id }})"
                                    wire:confirm="Are you sure you want to archive this ticket?"
                                    variant="danger"
                                >
                                    <flux:icon.trash class="size-4" />
                                    Archive
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                        </div>
                    </div>
                    
                    {{-- Subject --}}
                    <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ Str::limit($ticket->subject, 50) }}
                    </h3>
                    
                    {{-- Description Preview --}}
                    @if($ticket->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ Str::limit($ticket->description, 80) }}
                        </p>
                    @endif
                    
                    {{-- Badges Row --}}
                    <div class="flex flex-wrap gap-2 mb-3">
                        <flux:badge color="@if($ticket->status === 'open') green @elseif($ticket->status === 'in_progress') blue @elseif($ticket->status === 'pending') amber @elseif($ticket->status === 'resolved') purple @elseif($ticket->status === 'closed') zinc @else red @endif">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </flux:badge>
                        
                        <flux:badge color="@if($ticket->priority === 'critical') red @elseif($ticket->priority === 'urgent') red @elseif($ticket->priority === 'high') orange @elseif($ticket->priority === 'medium') yellow @elseif($ticket->priority === 'low') gray @else zinc @endif">
                            {{ ucfirst($ticket->priority) }}
                        </flux:badge>
                    </div>
                    
                    {{-- Meta Information --}}
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        {{-- Client --}}
                        <div class="flex items-center gap-2">
                            <flux:icon.building-office class="size-4" />
                            <span>{{ $ticket->client?->name ?? 'No client' }}</span>
                        </div>
                        
                        {{-- Assignee --}}
                        <div class="flex items-center gap-2">
                            <flux:icon.user class="size-4" />
                            <span>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</span>
                        </div>
                        
                        {{-- Created Date --}}
                        <div class="flex items-center gap-2">
                            <flux:icon.clock class="size-4" />
                            <span>{{ $ticket->created_at?->format('M d, Y g:i A') ?? '-' }}</span>
                        </div>
                    </div>
                    
                    {{-- Quick Actions Bar (appears on hover) --}}
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-white dark:from-gray-900 to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300" onclick="event.stopPropagation()">
                        <div class="flex gap-2 justify-center">
                            <flux:button 
                                variant="ghost" 
                                size="sm" 
                                href="{{ route('tickets.show', $ticket) }}"
                                class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm"
                            >
                                <flux:icon.eye class="size-4" />
                                View
                            </flux:button>
                            <flux:button 
                                variant="ghost" 
                                size="sm"
                                href="{{ route('tickets.edit', $ticket) }}"
                                class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm"
                            >
                                <flux:icon.pencil class="size-4" />
                                Edit
                            </flux:button>
                            @if($ticket->status !== 'closed')
                                <flux:button 
                                    variant="ghost" 
                                    size="sm"
                                    wire:click="updateStatus({{ $ticket->id }}, 'closed')"
                                    class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm"
                                >
                                    <flux:icon.check class="size-4" />
                                    Close
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            </flux:card>
            </div>
        @empty
            <div class="col-span-full">
                <flux:card>
                    <div class="text-center py-12">
                        <flux:icon.ticket class="size-12 mx-auto mb-4 text-gray-300" />
                        <p class="text-gray-500">No tickets found</p>
                    </div>
                </flux:card>
            </div>
        @endforelse
        </div>
    @else
        {{-- Table View --}}
        <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        <flux:checkbox wire:model.live="selectAll" />
                    </flux:table.column>
                    <flux:table.column
                        sortable
                        wire:click="sortBy('number')"
                        class="cursor-pointer"
                    >
                        Ticket #
                        @if($sortField === 'number')
                            <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        wire:click="sortBy('subject')"
                        class="cursor-pointer"
                    >
                        Subject
                        @if($sortField === 'subject')
                            <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column>Client</flux:table.column>
                    <flux:table.column 
                        sortable 
                        wire:click="sortBy('status')"
                        class="cursor-pointer"
                    >
                        Status
                        @if($sortField === 'status')
                            <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        wire:click="sortBy('priority')"
                        class="cursor-pointer"
                    >
                        Priority
                        @if($sortField === 'priority')
                            <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column>Assigned To</flux:table.column>
                    <flux:table.column 
                        sortable 
                        wire:click="sortBy('created_at')"
                        class="cursor-pointer"
                    >
                        Created
                        @if($sortField === 'created_at')
                            <flux:icon.chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="size-3 inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                
                <flux:table.rows>
                    @forelse($tickets as $ticket)
                        <flux:table.row 
                            class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                            onclick="window.location.href='{{ route('tickets.show', $ticket) }}'"
                        >
                            <flux:table.cell onclick="event.stopPropagation()">
                                <flux:checkbox 
                                    wire:model.live="selectedTickets" 
                                    value="{{ $ticket->id }}"
                                />
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-blue-600 font-medium">
                                    #{{ $ticket->number }}
                                </span>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    <div class="font-medium">{{ Str::limit($ticket->subject, 50) }}</div>
                                    @if($ticket->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($ticket->description, 60) }}</div>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $ticket->client?->name ?? '-' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="@if($ticket->status === 'open') green @elseif($ticket->status === 'in_progress') blue @elseif($ticket->status === 'pending') amber @elseif($ticket->status === 'resolved') purple @elseif($ticket->status === 'closed') zinc @else red @endif">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="@if($ticket->priority === 'critical') red @elseif($ticket->priority === 'urgent') red @elseif($ticket->priority === 'high') orange @elseif($ticket->priority === 'medium') yellow @elseif($ticket->priority === 'low') gray @else zinc @endif">
                                    {{ ucfirst($ticket->priority) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>
                                    {{ $ticket->created_at?->format('M d, Y') ?? '-' }}
                                    <div class="text-sm text-gray-500">
                                        {{ $ticket->created_at?->format('g:i A') ?? '' }}
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell onclick="event.stopPropagation()">
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm">
                                        <flux:icon.ellipsis-horizontal class="size-4" />
                                    </flux:button>
                                    <flux:menu>
                                        <flux:menu.item href="{{ route('tickets.show', $ticket) }}">
                                            <flux:icon.eye class="size-4" />
                                            View
                                        </flux:menu.item>
                                        <flux:menu.item href="{{ route('tickets.edit', $ticket) }}">
                                            <flux:icon.pencil class="size-4" />
                                            Edit
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item 
                                            wire:click="deleteTicket({{ $ticket->id }})"
                                            wire:confirm="Are you sure you want to archive this ticket?"
                                            variant="danger"
                                        >
                                            <flux:icon.trash class="size-4" />
                                            Archive
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9" class="text-center py-8">
                                <div class="text-gray-500">
                                    <flux:icon.ticket class="size-12 mx-auto mb-4 text-gray-300" />
                                    <p>No tickets found</p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
        @endif
    </div>
    
    {{-- Loading Indicator --}}
    <div wire:loading.flex wire:target="search, status, priority, assignedTo, clientId, perPage, sortBy" class="fixed top-20 right-4 z-50 items-center gap-2 bg-white dark:bg-gray-800 shadow-lg rounded-lg px-4 py-2">
        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-medium">Loading tickets...</span>
    </div>
    
    {{-- Pagination --}}
    @if($tickets->hasPages())
        <flux:card class="mt-6">
            <div class="p-4">
                {{ $tickets->links() }}
            </div>
        </flux:card>
    @endif
</div>