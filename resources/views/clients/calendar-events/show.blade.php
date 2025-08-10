@extends('layouts.app')

@section('title', 'Calendar Event Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Calendar Event Details</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.calendar-events.standalone.edit', $calendarEvent) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Event
                    </a>
                    <a href="{{ route('clients.calendar-events.standalone.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Events
                    </a>
                    <button type="button" 
                            class="btn btn-outline-danger" 
                            onclick="deleteEvent({{ $calendarEvent->id }})">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Main Event Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    @switch($calendarEvent->event_type)
                                        @case('meeting')
                                            <i class="fas fa-users text-primary me-2"></i>
                                            @break
                                        @case('appointment')
                                            <i class="fas fa-calendar-check text-success me-2"></i>
                                            @break
                                        @case('consultation')
                                            <i class="fas fa-comments text-info me-2"></i>
                                            @break
                                        @case('training')
                                            <i class="fas fa-graduation-cap text-warning me-2"></i>
                                            @break
                                        @case('maintenance')
                                            <i class="fas fa-tools text-secondary me-2"></i>
                                            @break
                                        @case('support')
                                            <i class="fas fa-headset text-primary me-2"></i>
                                            @break
                                        @case('follow_up')
                                            <i class="fas fa-phone text-info me-2"></i>
                                            @break
                                        @default
                                            <i class="fas fa-calendar-alt text-muted me-2"></i>
                                    @endswitch
                                    {{ $calendarEvent->title }}
                                </h5>
                                <div class="d-flex gap-2">
                                    @switch($calendarEvent->status)
                                        @case('scheduled')
                                            <span class="badge bg-primary">Scheduled</span>
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
                                            <span class="badge bg-secondary">No Show</span>
                                            @break
                                        @case('rescheduled')
                                            <span class="badge bg-info">Rescheduled</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($calendarEvent->status) }}</span>
                                    @endswitch

                                    @switch($calendarEvent->priority)
                                        @case('high')
                                            <span class="badge bg-danger">High Priority</span>
                                            @break
                                        @case('medium')
                                            <span class="badge bg-warning">Medium Priority</span>
                                            @break
                                        @case('low')
                                            <span class="badge bg-success">Low Priority</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Event Type</h6>
                                    <p class="mb-3">{{ ucfirst(str_replace('_', ' ', $calendarEvent->event_type)) }}</p>

                                    <h6 class="text-muted">Client</h6>
                                    <p class="mb-3">
                                        <a href="{{ route('clients.show', $calendarEvent->client) }}" class="text-decoration-none">
                                            {{ $calendarEvent->client->display_name }}
                                        </a>
                                    </p>

                                    @if($calendarEvent->location)
                                        <h6 class="text-muted">Location</h6>
                                        <p class="mb-3">
                                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                            {{ $calendarEvent->location }}
                                        </p>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-muted">Date & Time</h6>
                                    @if($calendarEvent->all_day)
                                        <p class="mb-2">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            {{ $calendarEvent->start_datetime->format('l, F j, Y') }}
                                            <span class="badge bg-info ms-2">All Day</span>
                                        </p>
                                    @else
                                        <p class="mb-2">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            {{ $calendarEvent->start_datetime->format('l, F j, Y') }}
                                        </p>
                                        <p class="mb-2">
                                            <i class="fas fa-clock text-muted me-2"></i>
                                            {{ $calendarEvent->start_datetime->format('g:i A') }} - {{ $calendarEvent->end_datetime->format('g:i A') }}
                                            <small class="text-muted">({{ $calendarEvent->duration_human }})</small>
                                        </p>
                                    @endif

                                    @if($calendarEvent->is_today)
                                        <div class="alert alert-warning py-2 mb-2">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Today!</strong> This event is scheduled for today.
                                        </div>
                                    @elseif($calendarEvent->is_upcoming)
                                        <div class="alert alert-info py-2 mb-2">
                                            <i class="fas fa-clock me-2"></i>
                                            <strong>Upcoming:</strong> {{ $calendarEvent->time_until }}
                                        </div>
                                    @elseif($calendarEvent->is_past)
                                        <div class="alert alert-secondary py-2 mb-2">
                                            <i class="fas fa-history me-2"></i>
                                            <strong>Past:</strong> {{ $calendarEvent->time_since }}
                                        </div>
                                    @endif

                                    @if($calendarEvent->reminder_minutes > 0)
                                        <h6 class="text-muted mt-3">Reminder</h6>
                                        <p class="mb-3">
                                            <i class="fas fa-bell text-muted me-2"></i>
                                            {{ \App\Domains\Client\Models\ClientCalendarEvent::getReminderOptions()[$calendarEvent->reminder_minutes] ?? $calendarEvent->reminder_minutes . ' minutes' }} before event
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($calendarEvent->description)
                                <hr>
                                <h6 class="text-muted">Description</h6>
                                <div class="mb-3">
                                    {!! nl2br(e($calendarEvent->description)) !!}
                                </div>
                            @endif

                            @if($calendarEvent->attendees && count($calendarEvent->attendees) > 0)
                                <hr>
                                <h6 class="text-muted">Attendees</h6>
                                <div class="mb-3">
                                    @foreach($calendarEvent->attendees as $attendee)
                                        <span class="badge bg-light text-dark me-2 mb-1">
                                            <i class="fas fa-user me-1"></i>{{ $attendee }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if($calendarEvent->notes)
                                <hr>
                                <h6 class="text-muted">Notes</h6>
                                <div class="bg-light p-3 rounded">
                                    {!! nl2br(e($calendarEvent->notes)) !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Event Metadata -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Event Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-primary">{{ $calendarEvent->duration_minutes }}</div>
                                        <small class="text-muted">Minutes</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-success">{{ count($calendarEvent->attendees ?? []) }}</div>
                                        <small class="text-muted">Attendees</small>
                                    </div>
                                </div>
                            </div>

                            <div class="small text-muted">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Created:</span>
                                    <span>{{ $calendarEvent->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                @if($calendarEvent->creator)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Created by:</span>
                                        <span>{{ $calendarEvent->creator->name }}</span>
                                    </div>
                                @endif
                                @if($calendarEvent->updated_at != $calendarEvent->created_at)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Last updated:</span>
                                        <span>{{ $calendarEvent->updated_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                                @if($calendarEvent->accessed_at)
                                    <div class="d-flex justify-content-between">
                                        <span>Last viewed:</span>
                                        <span>{{ $calendarEvent->accessed_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if(in_array($calendarEvent->status, ['scheduled', 'confirmed']))
                                    <button class="btn btn-outline-success btn-sm" onclick="updateStatus('in_progress')">
                                        <i class="fas fa-play me-2"></i>Mark as In Progress
                                    </button>
                                @endif

                                @if(in_array($calendarEvent->status, ['scheduled', 'confirmed', 'in_progress']))
                                    <button class="btn btn-outline-primary btn-sm" onclick="updateStatus('completed')">
                                        <i class="fas fa-check me-2"></i>Mark as Completed
                                    </button>
                                @endif

                                @if(!in_array($calendarEvent->status, ['cancelled', 'completed']))
                                    <button class="btn btn-outline-danger btn-sm" onclick="updateStatus('cancelled')">
                                        <i class="fas fa-times me-2"></i>Cancel Event
                                    </button>
                                @endif

                                <hr class="my-2">
                                
                                <a href="{{ route('clients.calendar-events.standalone.create', ['client_id' => $calendarEvent->client_id]) }}" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-plus me-2"></i>New Event for Client
                                </a>
                                
                                <a href="{{ route('clients.show', $calendarEvent->client) }}" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-user me-2"></i>View Client
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Related Events -->
                    @if($calendarEvent->client->calendarEvents()->where('id', '!=', $calendarEvent->id)->exists())
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Other Client Events
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    @foreach($calendarEvent->client->calendarEvents()->where('id', '!=', $calendarEvent->id)->orderBy('start_datetime', 'desc')->limit(5)->get() as $relatedEvent)
                                        <div class="list-group-item px-0 py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="small">
                                                    <a href="{{ route('clients.calendar-events.standalone.show', $relatedEvent) }}" 
                                                       class="text-decoration-none fw-bold">
                                                        {{ Str::limit($relatedEvent->title, 30) }}
                                                    </a>
                                                    <div class="text-muted">
                                                        {{ $relatedEvent->start_datetime->format('M j, Y') }}
                                                        @if(!$relatedEvent->all_day)
                                                            at {{ $relatedEvent->start_datetime->format('g:i A') }}
                                                        @endif
                                                    </div>
                                                </div>
                                                <span class="badge bg-secondary small">
                                                    {{ ucfirst($relatedEvent->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-center mt-2">
                                    <a href="{{ route('clients.calendar-events.standalone.index', ['client_id' => $calendarEvent->client_id]) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        View All Client Events
                                    </a>
                                </div>
                            </div>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this calendar event? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteEventForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Event</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Event Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="statusUpdateMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="statusUpdateForm" method="POST" class="d-inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" id="newStatus">
                    <button type="submit" class="btn btn-primary" id="statusUpdateButton">Update Status</button>
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

function updateStatus(newStatus) {
    const messages = {
        'in_progress': 'Mark this event as in progress?',
        'completed': 'Mark this event as completed?',
        'cancelled': 'Cancel this event?'
    };

    const buttonTexts = {
        'in_progress': 'Mark In Progress',
        'completed': 'Mark Completed',
        'cancelled': 'Cancel Event'
    };

    document.getElementById('statusUpdateMessage').textContent = messages[newStatus];
    document.getElementById('newStatus').value = newStatus;
    document.getElementById('statusUpdateButton').textContent = buttonTexts[newStatus];
    
    const form = document.getElementById('statusUpdateForm');
    form.action = '{{ route("clients.calendar-events.standalone.update", $calendarEvent) }}';
    
    // Copy all current form data
    @foreach(['client_id', 'title', 'description', 'event_type', 'location', 'start_datetime', 'end_datetime', 'all_day', 'priority', 'attendees', 'reminder_minutes', 'notes'] as $field)
        const {{ $field }}Input = document.createElement('input');
        {{ $field }}Input.type = 'hidden';
        {{ $field }}Input.name = '{{ $field }}';
        {{ $field }}Input.value = @json($calendarEvent->{$field} ?? '');
        form.appendChild({{ $field }}Input);
    @endforeach

    const modal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
    modal.show();
}
</script>
@endpush