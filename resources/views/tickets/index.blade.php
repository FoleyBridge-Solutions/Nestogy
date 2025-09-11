@extends('layouts.app')

@section('title', 'Support Tickets')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Support Tickets</flux:heading>
                <flux:text>Manage customer support requests and service tickets</flux:text>
            </div>
            
            <div class="flex gap-2">
                <flux:button href="{{ route('tickets.export.csv', request()->query()) }}" 
                            variant="subtle" 
                            icon="arrow-down-tray">
                    Export CSV
                </flux:button>
                <flux:button href="{{ route('tickets.create') }}" 
                            variant="primary" 
                            icon="plus">
                    New Ticket
                </flux:button>
            </div>
        </div>
    </flux:card>
    <!-- Filters Card -->
    <flux:card class="mb-4">
        <form method="GET" action="{{ route('tickets.index') }}">
            <div class="space-y-4">
                    <!-- Main Filters Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <flux:input 
                            name="search" 
                            placeholder="Search tickets..."
                            icon="magnifying-glass"
                            value="{{ request('search') }}"
                        />

                        <!-- Status -->
                        <flux:select name="status" placeholder="All Statuses" value="{{ request('status') }}">
                            <flux:select.option value="">All Statuses</flux:select.option>
                            @foreach($filterOptions['statuses'] as $status)
                                <flux:select.option value="{{ $status }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <!-- Priority -->
                        <flux:select name="priority" placeholder="All Priorities" value="{{ request('priority') }}">
                            <flux:select.option value="">All Priorities</flux:select.option>
                            @foreach($filterOptions['priorities'] as $priority)
                                <flux:select.option value="{{ $priority }}">
                                    {{ $priority }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <!-- Client Filter -->
                        @php $selectedClient = \App\Services\NavigationService::getSelectedClient(); @endphp
                        @if(!$selectedClient)
                            <flux:select name="client_id" placeholder="All Clients" value="{{ request('client_id') }}">
                                <flux:select.option value="">All Clients</flux:select.option>
                                @foreach($filterOptions['clients'] as $client)
                                    <flux:select.option value="{{ $client->id }}">
                                        {{ $client->name }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @else
                            <flux:input 
                                value="{{ $selectedClient->name }}" 
                                disabled 
                                icon="building-office"
                            />
                        @endif

                        <!-- Filter Actions -->
                        <div class="flex gap-2">
                            <flux:button type="submit" variant="primary">
                                Filter
                            </flux:button>
                            @if(request()->hasAny(['search', 'status', 'priority', 'client_id', 'unassigned', 'overdue', 'watching']))
                                <flux:button href="{{ route('tickets.index') }}" variant="ghost">
                                    Clear
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <!-- Quick Filters -->
                    <flux:separator />
                    <div class="flex flex-wrap gap-2">
                        <flux:badge 
                            href="{{ route('tickets.index', ['unassigned' => '1'] + request()->except('unassigned')) }}"
                            color="{{ request('unassigned') ? 'blue' : 'zinc' }}"
                            size="lg"
                        >
                            Unassigned
                        </flux:badge>
                        
                        <flux:badge 
                            href="{{ route('tickets.index', ['overdue' => '1'] + request()->except('overdue')) }}"
                            color="{{ request('overdue') ? 'red' : 'zinc' }}"
                            size="lg"
                        >
                            Overdue
                        </flux:badge>
                        
                        <flux:badge 
                            href="{{ route('tickets.index', ['watching' => '1'] + request()->except('watching')) }}"
                            color="{{ request('watching') ? 'yellow' : 'zinc' }}"
                            size="lg"
                        >
                            Watching
                        </flux:badge>

                        <flux:badge 
                            href="{{ route('tickets.index', ['status' => 'new'] + request()->except('status')) }}"
                            color="{{ request('status') === 'new' ? 'blue' : 'zinc' }}"
                            size="lg"
                        >
                            New
                        </flux:badge>

                        <flux:badge 
                            href="{{ route('tickets.index', ['status' => 'open'] + request()->except('status')) }}"
                            color="{{ request('status') === 'open' ? 'yellow' : 'zinc' }}"
                            size="lg"
                        >
                            Open
                        </flux:badge>

                        <flux:badge 
                            href="{{ route('tickets.index', ['priority' => 'Critical'] + request()->except('priority')) }}"
                            color="{{ request('priority') === 'Critical' ? 'red' : 'zinc' }}"
                            size="lg"
                        >
                            Critical
                        </flux:badge>
                    </div>
            </div>
        </form>
    </flux:card>

    <!-- Tickets Table -->
    <flux:card>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Ticket</flux:table.column>
                    <flux:table.column>Client</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Priority</flux:table.column>
                    <flux:table.column>Assignee</flux:table.column>
                    <flux:table.column>Updated</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse($tickets as $ticket)
                        <flux:table.row>
                            <!-- Ticket Info -->
                            <flux:table.cell>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <flux:link href="{{ route('tickets.show', $ticket) }}" class="font-semibold">
                                            #{{ $ticket->ticket_number }}
                                        </flux:link>
                                        @if($ticket->priorityQueue)
                                            <flux:badge color="red" size="sm">Priority Queue</flux:badge>
                                        @endif
                                    </div>
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ Str::limit($ticket->subject, 60) }}
                                    </div>
                                    @if($ticket->tags && count($ticket->tags) > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(array_slice($ticket->tags, 0, 3) as $tag)
                                                <flux:badge size="sm" color="blue">{{ $tag }}</flux:badge>
                                            @endforeach
                                            @if(count($ticket->tags) > 3)
                                                <flux:badge size="sm" color="zinc">+{{ count($ticket->tags) - 3 }}</flux:badge>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <!-- Client -->
                            <flux:table.cell>
                                <div>
                                    <div class="font-medium">{{ $ticket->client->name }}</div>
                                    @if($ticket->contact)
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                            {{ $ticket->contact->name }}
                                        </div>
                                    @endif
                                </div>
                            </flux:table.cell>

                            <!-- Status -->
                            <flux:table.cell>
                                @php
                                    $statusColors = [
                                        'new' => 'blue',
                                        'open' => 'yellow',
                                        'in_progress' => 'purple',
                                        'pending' => 'orange',
                                        'resolved' => 'green',
                                        'closed' => 'zinc',
                                    ];
                                    $statusColor = $statusColors[$ticket->status] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </flux:badge>
                            </flux:table.cell>

                            <!-- Priority -->
                            <flux:table.cell>
                                @php
                                    $priorityColors = [
                                        'Critical' => 'red',
                                        'High' => 'orange',
                                        'Medium' => 'yellow',
                                        'Low' => 'green',
                                    ];
                                    $priorityColor = $priorityColors[$ticket->priority] ?? 'zinc';
                                @endphp
                                <flux:badge color="{{ $priorityColor }}" variant="outline">
                                    {{ $ticket->priority }}
                                </flux:badge>
                            </flux:table.cell>

                            <!-- Assignee -->
                            <flux:table.cell>
                                @if($ticket->assignee)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs">
                                            {{ strtoupper(substr($ticket->assignee->name, 0, 2)) }}
                                        </flux:avatar>
                                        <span class="text-sm">{{ $ticket->assignee->name }}</span>
                                    </div>
                                @else
                                    <span class="text-zinc-500 italic text-sm">Unassigned</span>
                                @endif
                            </flux:table.cell>

                            <!-- Updated -->
                            <flux:table.cell>
                                <div class="text-sm">
                                    <div>{{ $ticket->updated_at->format('M j, Y') }}</div>
                                    <div class="text-zinc-600 dark:text-zinc-400">
                                        {{ $ticket->updated_at->format('g:i A') }}
                                    </div>
                                </div>
                            </flux:table.cell>

                            <!-- Actions -->
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button href="{{ route('tickets.show', $ticket) }}" size="sm" variant="ghost" icon="eye" />
                                    <flux:button href="{{ route('tickets.edit', $ticket) }}" size="sm" variant="ghost" icon="pencil" />
                                    
                                    <flux:dropdown align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                                        
                                        <flux:menu>
                                            @if(!$ticket->assignee || $ticket->assignee->id !== auth()->id())
                                                <flux:menu.item 
                                                    href="{{ route('tickets.assignments.assign', $ticket) }}"
                                                    icon="user-plus"
                                                >
                                                    Assign to Me
                                                </flux:menu.item>
                                            @endif
                                            
                                            <flux:menu.item 
                                                href="{{ route('tickets.assignments.watchers.add', $ticket) }}"
                                                icon="eye"
                                            >
                                                Watch Ticket
                                            </flux:menu.item>
                                            
                                            <flux:menu.item 
                                                href="{{ route('tickets.time-tracking.create', ['ticket_id' => $ticket->id]) }}"
                                                icon="clock"
                                            >
                                                Log Time
                                            </flux:menu.item>
                                            
                                            <flux:menu.separator />
                                            
                                            <flux:menu.item 
                                                href="{{ route('tickets.show', $ticket) }}#comments"
                                                icon="chat-bubble-left-right"
                                            >
                                                Add Comment
                                            </flux:menu.item>
                                            
                                            @if($ticket->status !== 'closed')
                                                <flux:menu.item 
                                                    href="{{ route('tickets.status.update', ['ticket' => $ticket, 'status' => 'closed']) }}"
                                                    icon="x-circle"
                                                    variant="danger"
                                                >
                                                    Close Ticket
                                                </flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <div class="text-center py-12">
                                    <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <flux:heading size="lg" class="mt-4">No tickets found</flux:heading>
                                    <flux:text class="mt-2">
                                        @if(request()->hasAny(['search', 'status', 'priority', 'client_id']))
                                            Try adjusting your filters or search criteria
                                        @else
                                            Get started by creating your first support ticket
                                        @endif
                                    </flux:text>
                                    <div class="mt-6">
                                        @if(request()->hasAny(['search', 'status', 'priority', 'client_id']))
                                            <flux:button href="{{ route('tickets.index') }}" variant="primary">
                                                Clear Filters
                                            </flux:button>
                                        @else
                                            <flux:button href="{{ route('tickets.create') }}" variant="primary" icon="plus">
                                                Create First Ticket
                                            </flux:button>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

        @if($tickets->hasPages())
            <div class="mt-6">
                {{ $tickets->appends(request()->query())->links() }}
            </div>
        @endif
    </flux:card>

    <!-- Stats Cards -->
    @if($tickets->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <flux:card class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">Total Tickets</flux:text>
                        <flux:heading size="xl">{{ $tickets->total() }}</flux:heading>
                    </div>
                    <svg class="size-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">Open Tickets</flux:text>
                        <flux:heading size="xl">
                            {{ $tickets->filter(fn($t) => in_array($t->status, ['new', 'open', 'in_progress']))->count() }}
                        </flux:heading>
                    </div>
                    <svg class="size-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">Critical</flux:text>
                        <flux:heading size="xl">
                            {{ $tickets->where('priority', 'Critical')->count() }}
                        </flux:heading>
                    </div>
                    <svg class="size-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-.834-1.962-.834-2.732 0L3.732 16c-.77.834.192 3 1.732 3z" />
                    </svg>
                </div>
            </flux:card>

            <flux:card class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text class="text-zinc-600 dark:text-zinc-400">Unassigned</flux:text>
                        <flux:heading size="xl">
                            {{ $tickets->whereNull('assigned_to')->count() }}
                        </flux:heading>
                    </div>
                    <svg class="size-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                    </svg>
                </div>
            </flux:card>
    </div>
    @endif
</div>
@endsection