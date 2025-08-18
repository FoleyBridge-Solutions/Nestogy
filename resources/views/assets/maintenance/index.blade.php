@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Asset Maintenance</h1>
                    <p class="text-gray-600 mb-0">Manage asset maintenance schedules and history</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('assets.maintenance.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus"></i> Schedule Maintenance
                    </a>
                    <a href="{{ route('assets.maintenance.export') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('assets.maintenance.index') }}" id="filterForm">
                        <div class="flex flex-wrap -mx-4 g-3">
                            <div class="md:w-1/4 px-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Statuses</option>
                                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            <div class="md:w-1/4 px-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select name="type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Types</option>
                                    <option value="preventive" {{ request('type') === 'preventive' ? 'selected' : '' }}>Preventive</option>
                                    <option value="corrective" {{ request('type') === 'corrective' ? 'selected' : '' }}>Corrective</option>
                                    <option value="emergency" {{ request('type') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                    <option value="routine" {{ request('type') === 'routine' ? 'selected' : '' }}>Routine</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Priorities</option>
                                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ request('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" value="{{ request('search') }}" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search assets...">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @if(request()->hasAny(['status', 'type', 'priority', 'search']))
                            <div class="mt-2">
                                <a href="{{ route('assets.maintenance.index') }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden border-left-primary">
                        <div class="p-6">
                            <div class="d-flex items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-blue-600 mb-1">Scheduled</h6>
                                    <h4 class="mb-0">{{ $maintenanceStats['scheduled'] ?? 0 }}</h4>
                                </div>
                                <div class="text-blue-600">
                                    <i class="fas fa-calendar-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-warning mb-1">Overdue</h6>
                                    <h4 class="mb-0">{{ $maintenanceStats['overdue'] ?? 0 }}</h4>
                                </div>
                                <div class="text-warning">
                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-info mb-1">In Progress</h6>
                                    <h4 class="mb-0">{{ $maintenanceStats['in_progress'] ?? 0 }}</h4>
                                </div>
                                <div class="text-info">
                                    <i class="fas fa-tools fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="text-green-600 mb-1">Completed</h6>
                                    <h4 class="mb-0">{{ $maintenanceStats['completed'] ?? 0 }}</h4>
                                </div>
                                <div class="text-green-600">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maintenance List -->
            <div class="card">
                <div class="card-body">
                    @if($maintenance && $maintenance->count() > 0)
                        <div class="min-w-full divide-y divide-gray-200-responsive">
                            <table class="min-w-full divide-y divide-gray-200 [&>tbody>tr:hover]:bg-gray-100">
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Scheduled Date</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Assigned To</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($maintenance as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <strong>{{ $item->asset->name ?? 'N/A' }}</strong>
                                                        @if($item->asset)
                                                            <br><small class="text-gray-600">{{ $item->asset->asset_tag }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-gray-600">{{ ucfirst($item->maintenance_type) }}</span>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px;">
                                                    {{ Str::limit($item->description, 50) }}
                                                    @if(strlen($item->description) > 50)
                                                        <span class="text-muted" x-data x-tooltip title="{{ $item->description }}">
                                                            <i class="fas fa-info-circle"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    {{ $item->scheduled_date ? $item->scheduled_date->format('M d, Y') : 'Not scheduled' }}
                                                    @if($item->scheduled_date && $item->scheduled_date->isPast() && $item->status !== 'completed')
                                                        <br><small class="text-red-600"><i class="fas fa-exclamation-triangle"></i> Overdue</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'scheduled' => 'bg-primary',
                                                        'in_progress' => 'bg-info',
                                                        'completed' => 'bg-success',
                                                        'cancelled' => 'bg-secondary'
                                                    ];
                                                @endphp
                                                <span class="badge {{ $statusColors[$item->status] ?? 'bg-gray-600' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $priorityColors = [
                                                        'low' => 'text-success',
                                                        'medium' => 'text-warning',
                                                        'high' => 'text-danger',
                                                        'critical' => 'text-danger fw-bold'
                                                    ];
                                                @endphp
                                                <span class="{{ $priorityColors[$item->priority] ?? 'text-muted' }}">
                                                    {{ ucfirst($item->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $item->assignedUser->name ?? 'Unassigned' }}
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('assets.maintenance.show', $item) }}" class="btn btn-sm btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('assets.maintenance.edit', $item) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if($item->status === 'scheduled' || $item->status === 'in_progress')
                                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                                onclick="markCompleted({{ $item->id }})" title="Mark Complete">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteItem({{ $item->id }})" title="Delete">
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
                        @if(method_exists($maintenance, 'links'))
                            <div class="d-flex justify-center mt-4">
                                {{ $maintenance->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                            <h5>No Maintenance Records Found</h5>
                            <p class="text-muted">Get started by scheduling your first maintenance task.</p>
                            <a href="{{ route('assets.maintenance.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus"></i> Schedule Maintenance
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this maintenance record? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Complete Confirmation Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Maintenance Complete</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to mark this maintenance as completed?
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="completeForm" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Mark Complete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-primary { border-left: 4px solid #007bff !important; }
.border-left-warning { border-left: 4px solid #ffc107 !important; }
.border-left-info { border-left: 4px solid #17a2b8 !important; }
.border-left-success { border-left: 4px solid #28a745 !important; }
</style>
@endpush

@push('scripts')
<script>
function deleteItem(id) {
    const form = document.getElementById('deleteForm');
    form.action = `/assets/maintenance/${id}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function markCompleted(id) {
    const form = document.getElementById('completeForm');
    form.action = `/assets/maintenance/${id}/complete`;
    
    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[x-data x-tooltip]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});
</script>
@endpush