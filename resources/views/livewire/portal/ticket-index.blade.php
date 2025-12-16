<div>
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Support Tickets</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage your support requests and track their progress</p>
            </div>
            @if(in_array('can_create_tickets', $this->permissions))
            <div>
                <flux:button href="{{ route('client.tickets.create') ?? '#' }}" variant="primary" icon="plus">
                    New Ticket
                </flux:button>
            </div>
            @endif
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center">
                <div class="flex-1 mr-2">
                    <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">
                        Total Tickets
                    </div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        {{ $this->stats['total_tickets'] }}
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <flux:icon.clipboard-document-list class="size-8 text-gray-300 dark:text-gray-600" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-1 mr-2">
                    <div class="text-xs font-bold text-red-600 dark:text-red-400 uppercase mb-1">
                        Open Tickets
                    </div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        {{ $this->stats['open_tickets'] }}
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <flux:icon.ticket class="size-8 text-gray-300 dark:text-gray-600" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-1 mr-2">
                    <div class="text-xs font-bold text-green-600 dark:text-green-400 uppercase mb-1">
                        Resolved This Month
                    </div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        {{ $this->stats['resolved_this_month'] }}
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <flux:icon.check-circle class="size-8 text-gray-300 dark:text-gray-600" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-1 mr-2">
                    <div class="text-xs font-bold text-yellow-600 dark:text-yellow-400 uppercase mb-1">
                        Avg Response Time
                    </div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                        {{ $this->stats['avg_response_time'] }}
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <flux:icon.clock class="size-8 text-gray-300 dark:text-gray-600" />
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <flux:input 
                    wire:model.live.debounce.300ms="search"
                    type="text" 
                    placeholder="Search tickets..."
                    icon="magnifying-glass" />
            </div>
            <div class="min-w-[200px]">
                <flux:select variant="listbox" multiple wire:model.live="statuses" placeholder="All Status">
                    <flux:select.option value="Open">Open</flux:select.option>
                    <flux:select.option value="Awaiting Customer">Awaiting Customer</flux:select.option>
                    <flux:select.option value="In Progress">In Progress</flux:select.option>
                    <flux:select.option value="Resolved">Resolved</flux:select.option>
                    <flux:select.option value="Closed">Closed</flux:select.option>
                </flux:select>
            </div>
            <div class="min-w-[200px]">
                <flux:select variant="listbox" multiple wire:model.live="priorities" placeholder="All Priorities">
                    <flux:select.option value="Critical">Critical</flux:select.option>
                    <flux:select.option value="High">High</flux:select.option>
                    <flux:select.option value="Medium">Medium</flux:select.option>
                    <flux:select.option value="Low">Low</flux:select.option>
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="perPage">
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                </flux:select>
            </div>
            @if($this->hasActiveFilters())
            <div>
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                    Clear Filters
                </flux:button>
            </div>
            @endif
        </div>
    </flux:card>

    <!-- Tickets Table -->
    <flux:card>
        @if($this->tickets->isEmpty())
            <div class="text-center py-12">
                <flux:icon.ticket class="mx-auto size-16 text-gray-300 dark:text-gray-600 mb-6" />
                <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">No Tickets Found</h3>
                <p class="text-gray-500 dark:text-gray-500 mb-6">
                    @if($this->hasActiveFilters())
                        No tickets match your current filters. Try adjusting your search criteria.
                    @else
                        You haven't submitted any support tickets yet.
                    @endif
                </p>
                @if(in_array('can_create_tickets', $this->permissions) && !$this->hasActiveFilters())
                    <flux:button href="{{ route('client.tickets.create') }}" variant="primary" icon="plus">
                        Create Your First Ticket
                    </flux:button>
                @endif
            </div>
        @else
            <flux:table :paginate="$this->tickets">
                <flux:table.columns>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'number'" 
                        :direction="$sortDirection" 
                        wire:click="sort('number')">
                        #
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'subject'" 
                        :direction="$sortDirection" 
                        wire:click="sort('subject')">
                        Subject
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'status'" 
                        :direction="$sortDirection" 
                        wire:click="sort('status')">
                        Status
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'priority'" 
                        :direction="$sortDirection" 
                        wire:click="sort('priority')">
                        Priority
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'created_at'" 
                        :direction="$sortDirection" 
                        wire:click="sort('created_at')">
                        Created
                    </flux:table.column>
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'updated_at'" 
                        :direction="$sortDirection" 
                        wire:click="sort('updated_at')">
                        Last Updated
                    </flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->tickets as $ticket)
                    <flux:table.row :key="$ticket->id">
                        <flux:table.cell>
                            <span class="font-mono text-sm">{{ $ticket->ticket_number ?? '#' . $ticket->id }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <a href="{{ route('client.tickets.show', $ticket->id) }}" class="text-blue-600 hover:underline font-medium">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </a>
                                @if($ticket->category)
                                    <span class="block text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $ticket->category)) }}</span>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-status-badge :model="$ticket" :status="$ticket->status" size="sm" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-priority-badge :model="$ticket" :priority="$ticket->priority" size="sm" />
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $ticket->created_at->format('M j, Y') }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $ticket->updated_at->diffForHumans() }}
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="sm" variant="ghost" href="{{ route('client.tickets.show', $ticket->id) }}" icon="eye" />
                                @if(in_array($ticket->status, ['Open', 'Awaiting Customer', 'In Progress']))
                                    <flux:button size="sm" variant="ghost" href="{{ route('client.tickets.show', $ticket->id) }}#reply" icon="arrow-uturn-left" />
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
