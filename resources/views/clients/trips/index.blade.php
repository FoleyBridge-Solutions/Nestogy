@extends('layouts.app')

@extends('layouts.app')

@section('content')
<div class="container mx-auto mx-auto px-6 py-8">
    <flux:heading size="xl" level="1">
        {{ $client->name }} - Trips
    </flux:heading>

    <div class="flex justify-between items-center mb-6">
        <flux:subheading>
            Manage travel and trip expenses for {{ $client->name }}
        </flux:subheading>

        <div class="flex gap-3">
            <flux:button variant="outline" href="{{ route('clients.trips.export', $client) }}">
                Export CSV
            </flux:button>
            <flux:button href="{{ route('clients.trips.create', $client) }}">
                Add Trip
            </flux:button>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Trip Details</flux:table.column>
            <flux:table.column>Dates</flux:table.column>
            <flux:table.column>Destination</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($trips as $trip)
            <flux:table.row>
                <flux:table.cell>
                    <div class="font-medium">{{ $trip->title }}</div>
                    <div class="text-sm text-gray-500">{{ $trip->trip_number }}</div>
                    <div class="text-sm text-gray-500">{{ ucfirst($trip->trip_type) }}</div>
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium">{{ $trip->start_date->format('M j, Y') }}</div>
                    @if($trip->end_date)
                        <div class="text-sm text-gray-500">to {{ $trip->end_date->format('M j, Y') }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <div class="font-medium">{{ $trip->destination_city }}</div>
                    @if($trip->destination_state)
                        <div class="text-sm text-gray-500">{{ $trip->destination_state }}</div>
                    @endif
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge color="{{ $trip->status === 'completed' ? 'green' : ($trip->status === 'approved' ? 'blue' : 'yellow') }}">
                        {{ ucfirst($trip->status) }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell>
                    <flux:button variant="ghost" size="sm" href="{{ route('clients.trips.show', [$client, $trip]) }}">
                        View
                    </flux:button>
                    <flux:button variant="ghost" size="sm" href="{{ route('clients.trips.edit', [$client, $trip]) }}">
                        Edit
                    </flux:button>
                </flux:table.cell>
            </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{ $trips->links() }}
</div>
@endsection
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('clients.trips.standalone.index') }}">
                        <div class="flex flex-wrap -mx-4 g-3">
                            <div class="md:w-1/4 px-6">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search trip number, title, destination...">
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
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="status" id="status" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $key => $value)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label for="trip_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trip Type</label>
                                <select name="trip_type" id="trip_type" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Types</option>
                                    @foreach($tripTypes as $key => $value)
                                        <option value="{{ $key }}" {{ request('trip_type') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <label for="transportation_mode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transportation</label>
                                <select name="transportation_mode" id="transportation_mode" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Modes</option>
                                    @foreach($transportationModes as $key => $value)
                                        <option value="{{ $key }}" {{ request('transportation_mode') == $key ? 'selected' : '' }}>
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
                                <div class="flex items-center">
                                    <input class="flex items-center-input" 
                                           type="checkbox" 
                                           name="current_only" 
                                           id="current_only" 
                                           value="1" 
                                           {{ request('current_only') ? 'checked' : '' }}>
                                    <label class="flex items-center-label" for="current_only">
                                        In Progress
                                    </label>
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <div class="flex items-center">
                                    <input class="flex items-center-input" 
                                           type="checkbox" 
                                           name="pending_approval" 
                                           id="pending_approval" 
                                           value="1" 
                                           {{ request('pending_approval') ? 'checked' : '' }}>
                                    <label class="flex items-center-label" for="pending_approval">
                                        Pending Approval
                                    </label>
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <div class="flex items-center">
                                    <input class="flex items-center-input" 
                                           type="checkbox" 
                                           name="billable_only" 
                                           id="billable_only" 
                                           value="1" 
                                           {{ request('billable_only') ? 'checked' : '' }}>
                                    <label class="flex items-center-label" for="billable_only">
                                        Billable Only
                                    </label>
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <div class="flex items-center">
                                    <input class="flex items-center-input" 
                                           type="checkbox" 
                                           name="follow_up_required" 
                                           id="follow_up_required" 
                                           value="1" 
                                           {{ request('follow_up_required') ? 'checked' : '' }}>
                                    <label class="flex items-center-label" for="follow_up_required">
                                        Follow-up Required
                                    </label>
                                </div>
                            </div>
                            <div class="flex-1 px-6-md-2">
                                <a href="{{ route('clients.trips.standalone.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary">
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
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 min-w-full divide-y divide-gray-200-striped [&>tbody>tr:hover]:bg-gray-100">
                                <thead class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-dark">
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
                                                    <div class="font-bold">{{ $trip->trip_number }}</div>
                                                    <div class="text-blue-600">{{ $trip->title }}</div>
                                                    <small class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $trip->trip_type)) }}</small>
                                                    <div class="small text-gray-600 mt-1">
                                                        <i class="fas fa-car mr-1"></i>{{ ucfirst(str_replace('_', ' ', $trip->transportation_mode)) }}
                                                        @if($trip->billable_to_client)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success ml-2">Billable</span>
                                                        @endif
                                                        @if($trip->reimbursable)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info ml-1">Reimbursable</span>
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
                                                    <div class="font-bold">{{ $trip->destination_city }}</div>
                                                    @if($trip->destination_state || $trip->destination_country)
                                                        <small class="text-gray-600 dark:text-gray-400">
                                                            {{ $trip->formatted_destination }}
                                                        </small>
                                                    @endif
                                                    @if($trip->mileage > 0)
                                                        <div class="small text-gray-600 dark:text-gray-400">
                                                            <i class="fas fa-road mr-1"></i>{{ number_format($trip->mileage, 1) }} miles
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="font-bold">{{ $trip->start_date->format('M j, Y') }}</div>
                                                    @if($trip->start_date->ne($trip->end_date))
                                                        <div>to {{ $trip->end_date->format('M j, Y') }}</div>
                                                    @endif
                                                    <div class="text-gray-600 dark:text-gray-400">{{ $trip->duration_in_days }} day(s)</div>
                                                    
                                                    @if($trip->days_until_trip !== null)
                                                        @if($trip->days_until_trip < 0)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600">Past</span>
                                                        @elseif($trip->days_until_trip == 0)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning">Today</span>
                                                        @elseif($trip->days_until_trip <= 7)
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">{{ $trip->days_until_trip }}d away</span>
                                                        @else
                                                            <span class="text-gray-600 dark:text-gray-400">{{ $trip->time_until_trip }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @switch($trip->status)
                                                    @case('planned')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600">Planned</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">Approved</span>
                                                        @break
                                                    @case('in_progress')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-600">In Progress</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">Completed</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger">Cancelled</span>
                                                        @break
                                                    @case('postponed')
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning">Postponed</span>
                                                        @break
                                                    @default
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary">{{ ucfirst($trip->status) }}</span>
                                                @endswitch

                                                @if($trip->requiresApproval())
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning">Needs Approval</span>
                                                    </div>
                                                @endif

                                                @if($trip->follow_up_required && $trip->isCompleted())
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">Follow-up Required</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="small">
                                                    @if($trip->estimated_expenses > 0)
                                                        <div class="flex justify-between">
                                                            <span>Estimated:</span>
                                                            <span class="font-bold">{{ $trip->formatted_estimated_expenses }}</span>
                                                        </div>
                                                    @endif
                                                    @if($trip->actual_expenses > 0)
                                                        <div class="flex justify-between">
                                                            <span>Actual:</span>
                                                            <span class="font-bold text-blue-600">{{ $trip->formatted_actual_expenses }}</span>
                                                        </div>
                                                        @if($trip->expense_variance !== null)
                                                            <div class="mt-1">
                                                                @if($trip->expense_variance > 0)
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger">Over Budget</span>
                                                                @elseif($trip->expense_variance < 0)
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success">Under Budget</span>
                                                                @else
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">On Budget</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                    <div class="text-gray-600 dark:text-gray-400">{{ $trip->currency }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class=" px-6 py-2 font-medium rounded-md transition-colors-group-sm">
                                                    <a href="{{ route('clients.trips.standalone.show', $trip) }}" 
                                                       class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(!in_array($trip->status, ['completed', 'cancelled']))
                                                        <a href="{{ route('clients.trips.standalone.edit', $trip) }}" 
                                                           class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($trip->requiresApproval())
                                                            <button type="button" 
                                                                    class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-success" 
                                                                    title="Approve Trip"
                                                                    onclick="approveTrip({{ $trip->id }})">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        @endif
                                                        @if($trip->status === 'approved')
                                                            <button type="button" 
                                                                    class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary" 
                                                                    title="Start Trip"
                                                                    onclick="startTrip({{ $trip->id }})">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        @endif
                                                        @if($trip->status === 'in_progress')
                                                            <button type="button" 
                                                                    class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-success" 
                                                                    title="Complete Trip"
                                                                    onclick="completeTrip({{ $trip->id }})">
                                                                <i class="fas fa-flag-checkered"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    <button type="button" 
                                                            class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-danger" 
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
                        <div class="flex justify-between items-center mt-6">
                            <div class="text-gray-600 dark:text-gray-400 small">
                                Showing {{ $trips->firstItem() }} to {{ $trips->lastItem() }} of {{ $trips->total() }} results
                            </div>
                            {{ $trips->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-route fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                            <h5>No trips found</h5>
                            <p class="text-gray-600 dark:text-gray-400">Get started by creating your first client trip.</p>
                            <a href="{{ route('clients.trips.standalone.create') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary">
                                <i class="fas fa-plus mr-2"></i>Add New Trip
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
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="deleteTripModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Delete Trip</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                Are you sure you want to delete this trip? This action cannot be undone.
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="inline-flex items-center px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteTripForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Approve Trip Modal -->
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="approveTripModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Approve Trip</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                Approve this trip? This will change the status to approved and allow the trip to proceed.
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="inline-flex items-center px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="approveTripForm" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Approve Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Start Trip Modal -->
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="startTripModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Start Trip</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                Start this trip? This will change the status to in progress.
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" @click="$dispatch('close-modal')">Cancel</button>
                <form id="startTripForm" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary">Start Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Complete Trip Modal -->
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="completeTripModal" tabindex="-1">
    <div class="flex items-center justify-center min-h-screen fixed inset-0 z-50 overflow-y-auto-lg">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Complete Trip</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" @click="$dispatch('close-modal')"></button>
            </div>
            <form id="completeTripForm" method="POST">
                @csrf
                <div class="fixed inset-0 z-50 overflow-y-auto-body">
                    <p>Complete this trip by providing final details:</p>
                    
                    <div class="mb-6">
                        <label for="actual_expenses" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Actual Expenses</label>
                        <input type="number" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="actual_expenses" id="actual_expenses" step="0.01" min="0">
                    </div>
                    
                    <div class="mb-6">
                        <label for="client_feedback" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Feedback</label>
                        <textarea class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="client_feedback" id="client_feedback" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label for="internal_rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Internal Rating (1-5)</label>
                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" name="internal_rating" id="internal_rating">
                            <option value="">Select rating...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input class="flex items-center-input" type="checkbox" name="follow_up_required" id="follow_up_required">
                        <label class="flex items-center-label" for="follow_up_required">
                            Follow-up required
                        </label>
                    </div>
                </div>
                <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                    <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" @click="$dispatch('close-modal')">Cancel</button>
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Complete Trip</button>
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
