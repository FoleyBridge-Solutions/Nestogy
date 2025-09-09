@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Maintenance Details</h1>
                    <p class="text-gray-600 mb-0">View maintenance information and history</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('assets.maintenance.edit', $maintenance ?? 1) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @if(isset($maintenance) && in_array($maintenance->status ?? 'scheduled', ['scheduled', 'in_progress']))
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="markCompleted()">
                            <i class="fas fa-check"></i> Mark Complete
                        </button>
                    @endif
                    @if(isset($maintenance) && $maintenance->status === 'completed' && $maintenance->recurring_interval)
                        <button type="button" class="btn btn-info" onclick="scheduleNext()">
                            <i class="fas fa-calendar-plus"></i> Schedule Next
                        </button>
                    @endif
                    <a href="{{ route('assets.maintenance.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="md:w-2/3 px-4">
                    <!-- Main Details -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="d-flex justify-between items-center">
                                <h5 class="bg-white rounded-lg shadow-md overflow-hidden-title mb-0">{{ $maintenance->title ?? 'Sample Maintenance Task' }}</h5>
                                <div class="d-flex gap-2">
                                    @php
                                        $status = $maintenance->status ?? 'scheduled';
                                        $statusColors = [
                                            'scheduled' => 'bg-primary',
                                            'in_progress' => 'bg-info',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-secondary'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusColors[$status] ?? 'bg-gray-600' }}">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </span>
                                    @php
                                        $priority = $maintenance->priority ?? 'medium';
                                        $priorityColors = [
                                            'low' => 'bg-success',
                                            'medium' => 'bg-warning',
                                            'high' => 'bg-danger',
                                            'critical' => 'bg-danger'
                                        ];
                                    @endphp
                                    <span class="badge {{ $priorityColors[$priority] ?? 'bg-gray-600' }}">
                                        {{ ucfirst($priority) }} Priority
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="row mb-3">
                                <div class="md:w-1/2 px-4">
                                    <strong>Asset:</strong>
                                    <div class="ml-3">
                                        {{ $maintenance->asset->name ?? 'Sample Asset' }}
                                        <br><small class="text-gray-600">{{ $maintenance->asset->asset_tag ?? 'AST-001' }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <strong>Maintenance Type:</strong>
                                    <div class="ml-3">
                                        <span class="badge bg-secondary">{{ ucfirst($maintenance->maintenance_type ?? 'Preventive') }}</span>
                                    </div>
                                </div>
                            </div>

                            @if(isset($maintenance->description) || true)
                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <div class="ms-3 mt-1">
                                        {{ $maintenance->description ?? 'Regular preventive maintenance to ensure optimal performance and prevent unexpected failures.' }}
                                    </div>
                                </div>
                            @endif

                            @if(isset($maintenance->instructions) || true)
                                <div class="mb-3">
                                    <strong>Instructions:</strong>
                                    <div class="ms-3 mt-1">
                                        <div class="bg-gray-100 p-3 rounded">
                                            {!! nl2br(e($maintenance->instructions ?? "1. Power down the asset safely\n2. Perform visual inspection\n3. Clean components\n4. Check connections\n5. Test functionality\n6. Document findings")) !!}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if(isset($maintenance->notes) || true)
                                <div class="mb-3">
                                    <strong>Notes:</strong>
                                    <div class="ms-3 mt-1">
                                        <div class="bg-gray-100 p-3 rounded">
                                            {{ $maintenance->notes ?? 'Additional maintenance notes and observations will be recorded here.' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Schedule & Assignment -->
                    <div class="card mb-4">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h6 class="card-title mb-0">Schedule & Assignment</h6>
                        </div>
                        <div class="p-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Scheduled Date:</strong>
                                        <div class="ms-3">
                                            @php
                                                $scheduledDate = $maintenance->scheduled_date ?? now()->addDays(7);
                                                $isOverdue = $scheduledDate->isPast() && $status !== 'completed';
                                            @endphp
                                            {{ $scheduledDate->format('M d, Y') }}
                                            @if($isOverdue)
                                                <span class="text-red-600 ms-2">
                                                    <i class="fas fa-exclamation-triangle"></i> Overdue
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(isset($maintenance->estimated_duration))
                                        <div class="mb-3">
                                            <strong>Estimated Duration:</strong>
                                            <div class="ms-3">{{ $maintenance->estimated_duration }} hours</div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Assigned To:</strong>
                                        <div class="ms-3">
                                            @if(isset($maintenance->assignedUser))
                                                <div class="d-flex align-items-center">
                                                    <div class="mr-2">
                                                        <i class="fas fa-user-circle fa-lg text-secondary"></i>
                                                    </div>
                                                    <div>
                                                        {{ $maintenance->assignedUser->name }}
                                                        <br><small class="text-muted">{{ $maintenance->assignedUser->email }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(isset($maintenance->recurring_interval))
                                        <div class="mb-3">
                                            <strong>Recurring:</strong>
                                            <div class="ms-3">
                                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $maintenance->recurring_interval)) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Cost Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <strong>Estimated Cost:</strong>
                                        <div class="ms-3">
                                            ${{ number_format($maintenance->estimated_cost ?? 150.00, 2) }}
                                        </div>
                                    </div>
                                    @if(isset($maintenance->actual_cost))
                                        <div class="mb-3">
                                            <strong>Actual Cost:</strong>
                                            <div class="ms-3">
                                                ${{ number_format($maintenance->actual_cost, 2) }}
                                                @if($maintenance->actual_cost > ($maintenance->estimated_cost ?? 0))
                                                    <span class="text-warning ms-2">
                                                        <i class="fas fa-exclamation-triangle"></i> Over Budget
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    @if(isset($maintenance->parts_cost))
                                        <div class="mb-3">
                                            <strong>Parts Cost:</strong>
                                            <div class="ms-3">${{ number_format($maintenance->parts_cost, 2) }}</div>
                                        </div>
                                    @endif
                                    @if(isset($maintenance->labor_cost))
                                        <div class="mb-3">
                                            <strong>Labor Cost:</strong>
                                            <div class="ms-3">${{ number_format($maintenance->labor_cost, 2) }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Completion Details -->
                    @if($status === 'completed')
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-check-circle"></i> Completion Details
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>Completed Date:</strong>
                                            <div class="ms-3">{{ ($maintenance->completed_at ?? now())->format('M d, Y g:i A') }}</div>
                                        </div>
                                        @if(isset($maintenance->actual_duration))
                                            <div class="mb-3">
                                                <strong>Actual Duration:</strong>
                                                <div class="ms-3">{{ $maintenance->actual_duration }} hours</div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>Completed By:</strong>
                                            <div class="ms-3">{{ $maintenance->completedBy->name ?? 'John Technician' }}</div>
                                        </div>
                                    </div>
                                </div>
                                @if(isset($maintenance->completion_notes))
                                    <div class="mb-3">
                                        <strong>Completion Notes:</strong>
                                        <div class="ms-3 mt-1">
                                            <div class="bg-light p-3 rounded">
                                                {{ $maintenance->completion_notes }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Asset Quick Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Asset Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>{{ $maintenance->asset->name ?? 'Sample Server' }}</strong>
                                <br><small class="text-muted">{{ $maintenance->asset->asset_tag ?? 'SRV-001' }}</small>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Category:</small> {{ $maintenance->asset->category ?? 'Hardware' }}
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Location:</small> {{ $maintenance->asset->location ?? 'Data Center A' }}
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">Status:</small> 
                                <span class="badge bg-success">{{ $maintenance->asset->status ?? 'Active' }}</span>
                            </div>
                            <a href="{{ route('assets.show', $maintenance->asset_id ?? 1) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt"></i> View Asset Details
                            </a>
                        </div>
                    </div>

                    <!-- Maintenance History -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Recent Maintenance</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                @php
                                    $recentMaintenance = [
                                        ['date' => '2024-01-15', 'type' => 'Preventive', 'status' => 'completed'],
                                        ['date' => '2023-10-15', 'type' => 'Corrective', 'status' => 'completed'],
                                        ['date' => '2023-07-15', 'type' => 'Preventive', 'status' => 'completed'],
                                    ];
                                @endphp
                                @foreach($recentMaintenance as $item)
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="mr-3">
                                            @if($item['status'] === 'completed')
                                                <i class="fas fa-check-circle text-green-600"></i>
                                            @else
                                                <i class="fas fa-clock text-warning"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ $item['type'] }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($item['date'])->format('M d, Y') }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <a href="{{ route('assets.maintenance.index', ['asset_id' => $maintenance->asset_id ?? 1]) }}" 
                               class="btn btn-sm btn-outline-secondary w-100">
                                View All Maintenance
                            </a>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($status === 'scheduled')
                                    <button type="button" class="btn btn-info btn-sm" onclick="startMaintenance()">
                                        <i class="fas fa-play"></i> Start Maintenance
                                    </button>
                                @endif
                                @if(in_array($status, ['scheduled', 'in_progress']))
                                    <button type="button" class="btn btn-warning btn-sm" onclick="postponeMaintenance()">
                                        <i class="fas fa-calendar-plus"></i> Postpone
                                    </button>
                                    <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 btn-sm" onclick="cancelMaintenance()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                @endif
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="duplicateMaintenance()">
                                    <i class="fas fa-copy"></i> Duplicate Task
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="exportMaintenance()">
                                    <i class="fas fa-download"></i> Export Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Modals -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="actionForm" method="POST" style="display: inline;">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="confirmButton">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showActionModal(title, message, action, buttonClass = 'btn-primary', buttonText = 'Confirm') {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = message;
    document.getElementById('confirmButton').className = `btn ${buttonClass}`;
    document.getElementById('confirmButton').textContent = buttonText;
    
    const form = document.getElementById('actionForm');
    form.action = action;
    
    const modal = new bootstrap.Modal(document.getElementById('actionModal'));
    modal.show();
}

function markCompleted() {
    showActionModal(
        'Mark Maintenance Complete',
        'Are you sure you want to mark this maintenance as completed?',
        '{{ route("assets.maintenance.complete", $maintenance ?? 1) }}',
        'btn-success',
        'Mark Complete'
    );
}

function startMaintenance() {
    const form = document.getElementById('actionForm');
    form.action = '{{ route("assets.maintenance.update", $maintenance ?? 1) }}';
    
    // Add hidden input for status
    const statusInput = document.createElement('input');
    statusInput.type = 'hidden';
    statusInput.name = 'status';
    statusInput.value = 'in_progress';
    form.appendChild(statusInput);
    
    showActionModal(
        'Start Maintenance',
        'Are you sure you want to start this maintenance task?',
        '{{ route("assets.maintenance.update", $maintenance ?? 1) }}',
        'btn-info',
        'Start'
    );
}

function postponeMaintenance() {
    window.location.href = '{{ route("assets.maintenance.edit", $maintenance ?? 1) }}';
}

function cancelMaintenance() {
    showActionModal(
        'Cancel Maintenance',
        'Are you sure you want to cancel this maintenance? This action cannot be undone.',
        '{{ route("assets.maintenance.update", $maintenance ?? 1) }}',
        'btn-secondary',
        'Cancel Maintenance'
    );
}

function scheduleNext() {
    showActionModal(
        'Schedule Next Maintenance',
        'Create the next recurring maintenance based on this schedule?',
        '{{ route("assets.maintenance.schedule-next", $maintenance ?? 1) }}',
        'btn-info',
        'Schedule Next'
    );
}

function duplicateMaintenance() {
    window.location.href = '{{ route("assets.maintenance.create", ["duplicate" => $maintenance->id ?? 1]) }}';
}

function exportMaintenance() {
    window.open('{{ route("assets.maintenance.show", $maintenance ?? 1) }}?format=pdf', '_blank');
}
</script>
@endpush