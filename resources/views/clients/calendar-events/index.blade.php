@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Calendar Events
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage calendar events and appointments for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.calendar-events.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.calendar-events.create', $client) }}">
                Add Event
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Event Details</flux:table.column>
            <flux:table.column>Date & Time</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Priority</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($events as $event)
            <flux:table.row>
                <flux:table.cell>
                    <div class="font-medium">{{ $event->title }}</div>
                    <div class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</div>
                    @if($event->location)
                        <div class="text-sm text-gray-500">{{ $event->location }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium">{{ $event->start_datetime->format('M j, Y') }}</div>
                    @if(!$event->all_day)
                        <div class="text-sm text-gray-500">{{ $event->start_datetime->format('g:i A') }} - {{ $event->end_datetime->format('g:i A') }}</div>
                    @else
                        <flux:badge color="blue">All Day</flux:badge>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="{{ $event->status === 'completed' ? 'green' : ($event->status === 'cancelled' ? 'red' : 'blue') }}">
                        {{ ucfirst($event->status) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="{{ $event->priority === 'high' ? 'red' : ($event->priority === 'medium' ? 'yellow' : 'green') }}">
                        {{ ucfirst($event->priority) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:button variant="ghost" size="sm" href="{{ route('clients.calendar-events.show', [$client, $event]) }}">
                        View
                    </flux:button>
                    <flux:button variant="ghost" size="sm" href="{{ route('clients.calendar-events.edit', [$client, $event]) }}">
                        Edit
                    </flux:button>
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{ $events->links() }}
</div>
@endsection
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('clients.calendar-events.standalone.index') }}">
                        <div class="flex flex-wrap -mx-4 g-3">
                            <div class="md:w-1/4 px-6">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search title, description, location...">
                            </div>
                            <div class="md:w-1/6 px-6">
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select name="client_id" id="client_id" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Clients</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label for="event_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Type</label>
                                <select name="event_type" id="event_type" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Types</option>
                                    @foreach($types as $key => $value)
                                        <option value="{{ $key }}" {{ request('event_type') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="status" id="status" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $key => $value)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                                <select name="priority" id="priority" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Priorities</option>
                                    @foreach($priorities as $key => $value)
                                        <option value="{{ $key }}" {{ request('priority') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 px-6-md-1">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">&nbsp;</label>
                                <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap -mx-4 g-3 mt-2">
                            <div class="flex-1 px-6-md-2">
                                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                                <input type="date" 
                                       class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="{{ request('start_date') }}">
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                                <input type="date" 
                                       class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="{{ request('end_date') }}">
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">&nbsp;</label>
                                <div class="flex items-center">
                                    <input class="flex items-center-input" 
                                           type="checkbox" 
                                           name="upcoming_only" 
                                           id="upcoming_only" 
                                           value="1" 
                                           {{ request('upcoming_only') ? 'checked' : '' }}>
                                    <label class="flex items-center-label" for="upcoming_only">
                                        Upcoming Only
                                    </label>
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">&nbsp;</label>
                                <div class="flex items-center">
                                    <input class="flex items-center-input" 
                                           type="checkbox" 
                                           name="past_only" 
                                           id="past_only" 
                                           value="1" 
                                           {{ request('past_only') ? 'checked' : '' }}>
                                    <label class="flex items-center-label" for="past_only">
                                        Past Only
                                    </label>
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">&nbsp;</label>
                                <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Events Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    @if($events->count() > 0)
                        <div class="min-w-full divide-y divide-gray-200-responsive">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 min-w-full divide-y divide-gray-200-striped [&>tbody>tr:hover]:bg-gray-100">
                                <thead class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-dark">
                                    <tr>
                                        <th>Event Details</th>
                                        <th>Client</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($events as $event)
                                        <tr>
                                            <td>
                                                <div class="flex items-center">
                                                    <div class="mr-4">
                                                        @switch($event->event_type)
                                                            @case('meeting')
                                                                <i class="fas fa-users text-blue-600 fa-lg"></i>
                                                                @break
                                                            @case('appointment')
                                                                <i class="fas fa-calendar-check text-green-600 fa-lg"></i>
                                                                @break
                                                            @case('consultation')
                                                                <i class="fas fa-comments text-cyan-600 dark:text-cyan-400 fa-lg"></i>
                                                                @break
                                                            @case('training')
                                                                <i class="fas fa-graduation-cap text-yellow-600 dark:text-yellow-400 fa-lg"></i>
                                                                @break
                                                            @case('maintenance')
                                                                <i class="fas fa-tools text-gray-600 dark:text-gray-400 fa-lg"></i>
                                                                @break
                                                            @case('support')
                                                                <i class="fas fa-headset text-blue-600 fa-lg"></i>
                                                                @break
                                                            @case('follow_up')
                                                                <i class="fas fa-phone text-cyan-600 dark:text-cyan-400 fa-lg"></i>
                                                                @break
                                                            @default
                                                                <i class="fas fa-calendar-alt text-gray-600 fa-lg"></i>
                                                        @endswitch
                                                    </div>
                                                    <div>
                                                        <div class="font-bold">{{ $event->title }}</div>
                                                        <small class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</small>
                                                        @if($event->location)
                                                            <div class="small text-gray-600 dark:text-gray-400">
                                                                <i class="fas fa-map-marker-alt"></i> {{ $event->location }}
                                                            </div>
                                                        @endif
                                                        @if($event->description)
                                                            <div class="small text-gray-600 dark:text-gray-400 mt-1">
                                                                {{ Str::limit($event->description, 100) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('clients.show', $event->client) }}" class="text-decoration-none">
                                                    {{ $event->client->display_name }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="font-bold">{{ $event->start_datetime->format('M j, Y') }}</div>
                                                    @if($event->all_day)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">All Day</span>
                                                    @else
                                                        <div>{{ $event->start_datetime->format('g:i A') }} - {{ $event->end_datetime->format('g:i A') }}</div>
                                                        <small class="text-gray-600 dark:text-gray-400">{{ $event->duration_human }}</small>
                                                    @endif
                                                    @if($event->is_today)
                                                        <div class="mt-1">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning">Today</span>
                                                        </div>
                                                    @elseif($event->is_upcoming)
                                                        <div class="mt-1">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">{{ $event->time_until }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @switch($event->status)
                                                    @case('scheduled')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600">Scheduled</span>
                                                        @break
                                                    @case('confirmed')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">Confirmed</span>
                                                        @break
                                                    @case('in_progress')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning">In Progress</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">Completed</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger">Cancelled</span>
                                                        @break
                                                    @case('no_show')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600">No Show</span>
                                                        @break
                                                    @case('rescheduled')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">Rescheduled</span>
                                                        @break
                                                    @default
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600">{{ ucfirst($event->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                @switch($event->priority)
                                                    @case('high')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger">High</span>
                                                        @break
                                                    @case('medium')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning">Medium</span>
                                                        @break
                                                    @case('low')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">Low</span>
                                                        @break
                                                    @default
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary">{{ ucfirst($event->priority) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class=" px-6 py-2 font-medium rounded-md transition-colors-group-sm">
                                                    <a href="{{ route('clients.calendar-events.standalone.show', $event) }}" 
                                                       class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('clients.calendar-events.standalone.edit', $event) }}" 
                                                       class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-danger" 
                                                            title="Delete"
                                                            onclick="deleteEvent({{ $event->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="flex justify-between items-center mt-6">
                            <div class="text-gray-600 dark:text-gray-400 small">
                                Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} results
                            </div>
                            {{ $events->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-alt fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                            <h5>No calendar events found</h5>
                            <p class="text-gray-600 dark:text-gray-400">Get started by creating your first calendar event.</p>
                            <a href="{{ route('clients.calendar-events.standalone.create') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary">
                                <i class="fas fa-plus mr-2"></i>Add New Event
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="deleteEventModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Delete Calendar Event</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                Are you sure you want to delete this calendar event? This action cannot be undone.
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="inline-flex items-center px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteEventForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Event</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteEvent(eventId) {
    const form = document.getElementById('deleteEventForm');
    form.action = '/clients/calendar-events/' + eventId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteEventModal'));
    modal.show();
}
</script>
@endpush
