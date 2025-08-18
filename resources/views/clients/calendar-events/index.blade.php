@extends('layouts.app')

@section('title', 'Client Calendar Events')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <div class="flex justify-between items-center mb-4">
                <h1 class="h3 mb-0">Client Calendar Events</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.calendar-events.standalone.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i>Add New Event
                    </a>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" x-data="{ open: false }" @click="open = !open">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('clients.calendar-events.standalone.export', request()->query()) }}">
                            <i class="fas fa-file-csv mr-2"></i>Export to CSV
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('clients.calendar-events.standalone.index') }}">
                        <div class="flex flex-wrap -mx-4 g-3">
                            <div class="md:w-1/4 px-4">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search title, description, location...">
                            </div>
                            <div class="md:w-1/6 px-4">
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select name="client_id" id="client_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Clients</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="event_type" class="form-label">Event Type</label>
                                <select name="event_type" id="event_type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Types</option>
                                    @foreach($types as $key => $value)
                                        <option value="{{ $key }}" {{ request('event_type') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $key => $value)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="priority" class="form-label">Priority</label>
                                <select name="priority" id="priority" class="form-select">
                                    <option value="">All Priorities</option>
                                    @foreach($priorities as $key => $value)
                                        <option value="{{ $key }}" {{ request('priority') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label block">&nbsp;</label>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="start_date" 
                                       name="start_date" 
                                       value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="end_date" 
                                       name="end_date" 
                                       value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="upcoming_only" 
                                           id="upcoming_only" 
                                           value="1" 
                                           {{ request('upcoming_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="upcoming_only">
                                        Upcoming Only
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="past_only" 
                                           id="past_only" 
                                           value="1" 
                                           {{ request('past_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="past_only">
                                        Past Only
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">&nbsp;</label>
                                <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn btn-outline-secondary">
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
                            <table class="table min-w-full divide-y divide-gray-200-striped [&>tbody>tr:hover]:bg-gray-100">
                                <thead class="table-dark">
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
                                                    <div class="me-3">
                                                        @switch($event->event_type)
                                                            @case('meeting')
                                                                <i class="fas fa-users text-blue-600 fa-lg"></i>
                                                                @break
                                                            @case('appointment')
                                                                <i class="fas fa-calendar-check text-green-600 fa-lg"></i>
                                                                @break
                                                            @case('consultation')
                                                                <i class="fas fa-comments text-info fa-lg"></i>
                                                                @break
                                                            @case('training')
                                                                <i class="fas fa-graduation-cap text-warning fa-lg"></i>
                                                                @break
                                                            @case('maintenance')
                                                                <i class="fas fa-tools text-secondary fa-lg"></i>
                                                                @break
                                                            @case('support')
                                                                <i class="fas fa-headset text-blue-600 fa-lg"></i>
                                                                @break
                                                            @case('follow_up')
                                                                <i class="fas fa-phone text-info fa-lg"></i>
                                                                @break
                                                            @default
                                                                <i class="fas fa-calendar-alt text-gray-600 fa-lg"></i>
                                                        @endswitch
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $event->title }}</div>
                                                        <small class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</small>
                                                        @if($event->location)
                                                            <div class="small text-muted">
                                                                <i class="fas fa-map-marker-alt"></i> {{ $event->location }}
                                                            </div>
                                                        @endif
                                                        @if($event->description)
                                                            <div class="small text-muted mt-1">
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
                                                    <div class="fw-bold">{{ $event->start_datetime->format('M j, Y') }}</div>
                                                    @if($event->all_day)
                                                        <span class="badge bg-info">All Day</span>
                                                    @else
                                                        <div>{{ $event->start_datetime->format('g:i A') }} - {{ $event->end_datetime->format('g:i A') }}</div>
                                                        <small class="text-muted">{{ $event->duration_human }}</small>
                                                    @endif
                                                    @if($event->is_today)
                                                        <div class="mt-1">
                                                            <span class="badge bg-warning">Today</span>
                                                        </div>
                                                    @elseif($event->is_upcoming)
                                                        <div class="mt-1">
                                                            <span class="badge bg-success">{{ $event->time_until }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @switch($event->status)
                                                    @case('scheduled')
                                                        <span class="badge bg-blue-600">Scheduled</span>
                                                        @break
                                                    @case('confirmed')
                                                        <span class="badge bg-success">Confirmed</span>
                                                        @break
                                                    @case('in_progress')
                                                        <span class="badge bg-warning">In Progress</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge bg-success">Completed</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @case('no_show')
                                                        <span class="badge bg-gray-600">No Show</span>
                                                        @break
                                                    @case('rescheduled')
                                                        <span class="badge bg-info">Rescheduled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-gray-600">{{ ucfirst($event->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                @switch($event->priority)
                                                    @case('high')
                                                        <span class="badge bg-danger">High</span>
                                                        @break
                                                    @case('medium')
                                                        <span class="badge bg-warning">Medium</span>
                                                        @break
                                                    @case('low')
                                                        <span class="badge bg-success">Low</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($event->priority) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('clients.calendar-events.standalone.show', $event) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('clients.calendar-events.standalone.edit', $event) }}" 
                                                       class="btn btn-outline-secondary" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
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
                        <div class="d-flex justify-between align-items-center mt-4">
                            <div class="text-muted small">
                                Showing {{ $events->firstItem() }} to {{ $events->lastItem() }} of {{ $events->total() }} results
                            </div>
                            {{ $events->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h5>No calendar events found</h5>
                            <p class="text-muted">Get started by creating your first calendar event.</p>
                            <a href="{{ route('clients.calendar-events.standalone.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Event
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Calendar Event</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this calendar event? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteEventForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Event</button>
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