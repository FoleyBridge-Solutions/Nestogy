@extends('layouts.app')

@section('title', 'Client Trips')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <div class="flex justify-between items-center mb-4">
                <h1 class="h3 mb-0">Client Trips</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.trips.standalone.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i>Add New Trip
                    </a>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" x-data="{ open: false }" @click="open = !open">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('clients.trips.standalone.export', request()->query()) }}">
                            <i class="fas fa-file-csv mr-2"></i>Export to CSV
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('clients.trips.standalone.index') }}">
                        <div class="flex flex-wrap -mx-4 g-3">
                            <div class="md:w-1/4 px-4">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search trip number, title, destination...">
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
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $key => $value)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="trip_type" class="form-label">Trip Type</label>
                                <select name="trip_type" id="trip_type" class="form-select">
                                    <option value="">All Types</option>
                                    @foreach($tripTypes as $key => $value)
                                        <option value="{{ $key }}" {{ request('trip_type') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="transportation_mode" class="form-label">Transportation</label>
                                <select name="transportation_mode" id="transportation_mode" class="form-select">
                                    <option value="">All Modes</option>
                                    @foreach($transportationModes as $key => $value)
                                        <option value="{{ $key }}" {{ request('transportation_mode') == $key ? 'selected' : '' }}>
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
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="current_only" 
                                           id="current_only" 
                                           value="1" 
                                           {{ request('current_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="current_only">
                                        In Progress
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="pending_approval" 
                                           id="pending_approval" 
                                           value="1" 
                                           {{ request('pending_approval') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="pending_approval">
                                        Pending Approval
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="billable_only" 
                                           id="billable_only" 
                                           value="1" 
                                           {{ request('billable_only') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="billable_only">
                                        Billable Only
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="follow_up_required" 
                                           id="follow_up_required" 
                                           value="1" 
                                           {{ request('follow_up_required') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="follow_up_required">
                                        Follow-up Required
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <a href="{{ route('clients.trips.standalone.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Trips Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    @if($trips->count() > 0)
                        <div class="min-w-full divide-y divide-gray-200-responsive">
                            <table class="table min-w-full divide-y divide-gray-200-striped [&>tbody>tr:hover]:bg-gray-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Trip Details</th>
                                        <th>Client</th>
                                        <th>Destination</th>
                                        <th>Dates</th>
                                        <th>Status</th>
                                        <th>Expenses</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trips as $trip)
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $trip->trip_number }}</div>
                                                    <div class="text-blue-600">{{ $trip->title }}</div>
                                                    <small class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $trip->trip_type)) }}</small>
                                                    <div class="small text-gray-600 mt-1">
                                                        <i class="fas fa-car me-1"></i>{{ ucfirst(str_replace('_', ' ', $trip->transportation_mode)) }}
                                                        @if($trip->billable_to_client)
                                                            <span class="badge bg-success ml-2">Billable</span>
                                                        @endif
                                                        @if($trip->reimbursable)
                                                            <span class="badge bg-info ml-1">Reimbursable</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('clients.show', $trip->client) }}" class="text-decoration-none">
                                                    {{ $trip->client->display_name }}
                                                </a>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $trip->destination_city }}</div>
                                                    @if($trip->destination_state || $trip->destination_country)
                                                        <small class="text-muted">
                                                            {{ $trip->formatted_destination }}
                                                        </small>
                                                    @endif
                                                    @if($trip->mileage > 0)
                                                        <div class="small text-muted">
                                                            <i class="fas fa-road me-1"></i>{{ number_format($trip->mileage, 1) }} miles
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="fw-bold">{{ $trip->start_date->format('M j, Y') }}</div>
                                                    @if($trip->start_date->ne($trip->end_date))
                                                        <div>to {{ $trip->end_date->format('M j, Y') }}</div>
                                                    @endif
                                                    <div class="text-muted">{{ $trip->duration_in_days }} day(s)</div>
                                                    
                                                    @if($trip->days_until_trip !== null)
                                                        @if($trip->days_until_trip < 0)
                                                            <span class="badge bg-gray-600">Past</span>
                                                        @elseif($trip->days_until_trip == 0)
                                                            <span class="badge bg-warning">Today</span>
                                                        @elseif($trip->days_until_trip <= 7)
                                                            <span class="badge bg-info">{{ $trip->days_until_trip }}d away</span>
                                                        @else
                                                            <span class="text-muted">{{ $trip->time_until_trip }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @switch($trip->status)
                                                    @case('planned')
                                                        <span class="badge bg-gray-600">Planned</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="badge bg-success">Approved</span>
                                                        @break
                                                    @case('in_progress')
                                                        <span class="badge bg-blue-600">In Progress</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge bg-success">Completed</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @case('postponed')
                                                        <span class="badge bg-warning">Postponed</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($trip->status) }}</span>
                                                @endswitch

                                                @if($trip->requiresApproval())
                                                    <div class="mt-1">
                                                        <span class="badge bg-warning">Needs Approval</span>
                                                    </div>
                                                @endif

                                                @if($trip->follow_up_required && $trip->isCompleted())
                                                    <div class="mt-1">
                                                        <span class="badge bg-info">Follow-up Required</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="small">
                                                    @if($trip->estimated_expenses > 0)
                                                        <div class="flex justify-between">
                                                            <span>Estimated:</span>
                                                            <span class="fw-bold">{{ $trip->formatted_estimated_expenses }}</span>
                                                        </div>
                                                    @endif
                                                    @if($trip->actual_expenses > 0)
                                                        <div class="d-flex justify-content-between">
                                                            <span>Actual:</span>
                                                            <span class="fw-bold text-blue-600">{{ $trip->formatted_actual_expenses }}</span>
                                                        </div>
                                                        @if($trip->expense_variance !== null)
                                                            <div class="mt-1">
                                                                @if($trip->expense_variance > 0)
                                                                    <span class="badge bg-danger">Over Budget</span>
                                                                @elseif($trip->expense_variance < 0)
                                                                    <span class="badge bg-success">Under Budget</span>
                                                                @else
                                                                    <span class="badge bg-info">On Budget</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                    <div class="text-muted">{{ $trip->currency }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('clients.trips.standalone.show', $trip) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!in_array($trip->status, ['completed', 'cancelled']))
                                                        <a href="{{ route('clients.trips.standalone.edit', $trip) }}" 
                                                           class="btn btn-outline-secondary" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($trip->requiresApproval())
                                                            <button type="button" 
                                                                    class="btn btn-outline-success" 
                                                                    title="Approve Trip"
                                                                    onclick="approveTrip({{ $trip->id }})">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        @endif
                                                        @if($trip->status === 'approved')
                                                            <button type="button" 
                                                                    class="btn btn-outline-primary" 
                                                                    title="Start Trip"
                                                                    onclick="startTrip({{ $trip->id }})">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        @endif
                                                        @if($trip->status === 'in_progress')
                                                            <button type="button" 
                                                                    class="btn btn-outline-success" 
                                                                    title="Complete Trip"
                                                                    onclick="completeTrip({{ $trip->id }})">
                                                                <i class="fas fa-flag-checkered"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            title="Delete"
                                                            onclick="deleteTrip({{ $trip->id }})">
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
                        <div class="d-flex justify-content-between items-center mt-4">
                            <div class="text-muted small">
                                Showing {{ $trips->firstItem() }} to {{ $trips->lastItem() }} of {{ $trips->total() }} results
                            </div>
                            {{ $trips->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-route fa-3x text-muted mb-3"></i>
                            <h5>No trips found</h5>
                            <p class="text-muted">Get started by creating your first client trip.</p>
                            <a href="{{ route('clients.trips.standalone.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Trip
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Delete Trip Modal -->
<div class="modal fade" id="deleteTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Trip</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this trip? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteTripForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Approve Trip Modal -->
<div class="modal fade" id="approveTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Trip</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Approve this trip? This will change the status to approved and allow the trip to proceed.
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="approveTripForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Approve Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Start Trip Modal -->
<div class="modal fade" id="startTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start Trip</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Start this trip? This will change the status to in progress.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="$dispatch('close-modal')">Cancel</button>
                <form id="startTripForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Start Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Complete Trip Modal -->
<div class="modal fade" id="completeTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Trip</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <form id="completeTripForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Complete this trip by providing final details:</p>
                    
                    <div class="mb-3">
                        <label for="actual_expenses" class="form-label">Actual Expenses</label>
                        <input type="number" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="actual_expenses" id="actual_expenses" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="client_feedback" class="form-label">Client Feedback</label>
                        <textarea class="form-control" name="client_feedback" id="client_feedback" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="internal_rating" class="form-label">Internal Rating (1-5)</label>
                        <select class="form-select" name="internal_rating" id="internal_rating">
                            <option value="">Select rating...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="follow_up_required" id="follow_up_required">
                        <label class="form-check-label" for="follow_up_required">
                            Follow-up required
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$dispatch('close-modal')">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Complete Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteTrip(tripId) {
    const form = document.getElementById('deleteTripForm');
    form.action = '/clients/trips/' + tripId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteTripModal'));
    modal.show();
}

function approveTrip(tripId) {
    const form = document.getElementById('approveTripForm');
    form.action = '/clients/trips/' + tripId + '/approve';
    
    const modal = new bootstrap.Modal(document.getElementById('approveTripModal'));
    modal.show();
}

function startTrip(tripId) {
    const form = document.getElementById('startTripForm');
    form.action = '/clients/trips/' + tripId + '/start';
    
    const modal = new bootstrap.Modal(document.getElementById('startTripModal'));
    modal.show();
}

function completeTrip(tripId) {
    const form = document.getElementById('completeTripForm');
    form.action = '/clients/trips/' + tripId + '/complete';
    
    const modal = new bootstrap.Modal(document.getElementById('completeTripModal'));
    modal.show();
}
</script>
@endpush