<div>
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
        <flux:button variant="primary" href="{{ route('tickets.create') }}">
            <flux:icon.plus class="size-4" />
            Create Ticket
        </flux:button>
    </div>

    {{-- Filters --}}
    <flux:card class="mb-6">
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Search --}}
                <flux:input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search tickets..."
                    icon="magnifying-glass"
                />

                {{-- Status Filter --}}
                <flux:select wire:model.live="status" placeholder="All Statuses">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $statusOption)
                        <option value="{{ $statusOption }}">{{ ucfirst(str_replace('_', ' ', $statusOption)) }}</option>
                    @endforeach
                </flux:select>

                {{-- Priority Filter --}}
                <flux:select wire:model.live="priority" placeholder="All Priorities">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $priorityOption)
                        <option value="{{ $priorityOption }}">{{ ucfirst($priorityOption) }}</option>
                    @endforeach
                </flux:select>

                {{-- Client Filter --}}
                <flux:select wire:model.live="clientId" placeholder="All Clients">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                {{-- Assigned To Filter --}}
                <flux:select wire:model.live="assignedTo" placeholder="All Technicians">
                    <option value="">All Technicians</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </flux:select>

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
        </div>
    </flux:card>

    {{-- Bulk Actions --}}
    @if(count($selectedTickets) > 0)
        <flux:card class="mb-4">
            <div class="p-4 flex items-center gap-4">
                <span class="text-sm text-gray-600">{{ count($selectedTickets) }} selected</span>
                
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

    {{-- Tickets Table --}}
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>
                    <flux:checkbox wire:model.live="selectAll" />
                </flux:table.column>
                <flux:table.column 
                    sortable 
                    wire:click="sortBy('ticket_number')"
                    class="cursor-pointer"
                >
                    Ticket #
                    @if($sortField === 'ticket_number')
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
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:checkbox 
                                wire:model.live="selectedTickets" 
                                value="{{ $ticket->id }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell>
                            <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:underline">
                                #{{ $ticket->ticket_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ Str::limit($ticket->subject, 50) }}</div>
                                @if($ticket->description)
                                    <div class="text-xs text-gray-500">{{ Str::limit($ticket->description, 60) }}</div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $ticket->client?->name ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="{{ 
                                $ticket->status === 'open' ? 'success' : 
                                ($ticket->status === 'in_progress' ? 'info' : 
                                ($ticket->status === 'resolved' ? 'warning' : 
                                ($ticket->status === 'closed' ? 'outline' : 'danger')))
                            }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="{{ 
                                $ticket->priority === 'urgent' ? 'danger' : 
                                ($ticket->priority === 'high' ? 'warning' : 
                                ($ticket->priority === 'medium' ? 'info' : 'outline'))
                            }}">
                                {{ ucfirst($ticket->priority) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $ticket->assignedTo?->name ?? 'Unassigned' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">
                                {{ $ticket->created_at?->format('M d, Y') ?? '-' }}
                                <div class="text-xs text-gray-500">
                                    {{ $ticket->created_at?->format('g:i A') ?? '' }}
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
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
        
        @if($tickets->hasPages())
            <div class="p-4 border-t">
                {{ $tickets->links() }}
            </div>
        @endif
    </flux:card>
</div>