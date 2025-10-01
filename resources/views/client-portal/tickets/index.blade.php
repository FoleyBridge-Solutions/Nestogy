@extends('client-portal.layouts.app')

@section('title', 'Support Tickets')

@section('content')
<!-- Header -->
<div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Support Tickets</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage your support requests and track their progress</p>
            </div>
            @php
                $permissions = $contact->portal_permissions ?? [];
            @endphp
            @if(in_array('can_create_tickets', $permissions))
            <div>
                <flux:button href="{{ route('client.tickets.create') ?? '#' }}" variant="primary">
                    <i class="fas fa-plus mr-2"></i>New Ticket
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
                            {{ $stats['total_tickets'] ?? 0 }}
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300 dark:text-gray-600"></i>
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
                            {{ $stats['open_tickets'] ?? 0 }}
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-ticket-alt fa-2x text-gray-300 dark:text-gray-600"></i>
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
                            {{ $stats['resolved_this_month'] ?? 0 }}
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x text-gray-300 dark:text-gray-600"></i>
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
                            {{ $stats['avg_response_time'] ?? '< 1h' }}
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-2x text-gray-300 dark:text-gray-600"></i>
                    </div>
                </div>
            
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        
            <form method="GET" action="{{ route('client.tickets') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <flux:input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search tickets..."
                        icon="magnifying-glass" />
                </div>
                <div>
                    <flux:select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Open" {{ request('status') == 'Open' ? 'selected' : '' }}>Open</option>
                        <option value="Awaiting Customer" {{ request('status') == 'Awaiting Customer' ? 'selected' : '' }}>Awaiting Customer</option>
                        <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
                    </flux:select>
                </div>
                <div>
                    <flux:select name="priority" onchange="this.form.submit()">
                        <option value="">All Priorities</option>
                        <option value="Critical" {{ request('priority') == 'Critical' ? 'selected' : '' }}>Critical</option>
                        <option value="High" {{ request('priority') == 'High' ? 'selected' : '' }}>High</option>
                        <option value="Medium" {{ request('priority') == 'Medium' ? 'selected' : '' }}>Medium</option>
                        <option value="Low" {{ request('priority') == 'Low' ? 'selected' : '' }}>Low</option>
                    </flux:select>
                </div>
                <div>
                    <flux:button type="submit" variant="primary">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </flux:button>
                </div>
            </form>
        
    </flux:card>

    <!-- Tickets Table -->
    <flux:card>
        
            @if($tickets->isEmpty())
                <div class="text-center py-12">
                    <i class="fas fa-ticket-alt text-6xl text-gray-300 dark:text-gray-600 mb-6"></i>
                    <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-400 mb-2">No Tickets Found</h3>
                    <p class="text-gray-500 dark:text-gray-500 mb-6">You haven't submitted any support tickets yet.</p>
                    @if(in_array('can_create_tickets', $permissions))
                        <flux:button href="{{ route('client.tickets.create') }}" variant="primary">
                            <i class="fas fa-plus mr-2"></i>Create Your First Ticket
                        </flux:button>
                    @endif
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>#</flux:table.column>
                        <flux:table.column>Subject</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Priority</flux:table.column>
                        <flux:table.column>Created</flux:table.column>
                        <flux:table.column>Last Updated</flux:table.column>
                        <flux:table.column>Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($tickets as $ticket)
                        <flux:table.row>
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
                                @php
                                    $statusColors = [
                                        'Open' => 'red',
                                        'Awaiting Customer' => 'yellow',
                                        'In Progress' => 'blue',
                                        'Resolved' => 'green',
                                        'Closed' => 'zinc'
                                    ];
                                    $statusColor = $statusColors[$ticket->status] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $statusColor }}" size="sm">{{ $ticket->status }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $priorityColors = [
                                        'Critical' => 'red',
                                        'High' => 'orange',
                                        'Medium' => 'yellow',
                                        'Low' => 'green'
                                    ];
                                    $priorityColor = $priorityColors[$ticket->priority] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $priorityColor }}" size="sm">{{ $ticket->priority }}</flux:badge>
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
                                    <flux:button size="sm" variant="ghost" href="{{ route('client.tickets.show', $ticket->id) }}">
                                        <i class="fas fa-eye"></i>
                                    </flux:button>
                                    @if(in_array($ticket->status, ['Open', 'Awaiting Customer', 'In Progress']))
                                        <flux:button size="sm" variant="ghost" href="{{ route('client.tickets.show', $ticket->id) }}#reply">
                                            <i class="fas fa-reply"></i>
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <!-- Pagination -->
                <div class="px-6 py-6 border-t border-gray-200 dark:border-gray-700">
                    {{ $tickets->links() }}
                </div>
            @endif
    </flux:card>
@endsection
