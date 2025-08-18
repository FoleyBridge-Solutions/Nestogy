@extends('client-portal.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Welcome back, {{ $client->name }}!</h1>
                    <p class="text-muted">Here's an overview of your contracts and recent activity</p>
                </div>
                <div class="text-right">
                    <small class="text-muted">Last login: {{ now()->format('M j, Y g:i A') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Actions Alert -->
    @if(count($pendingActions) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Action Required</h5>
                @foreach($pendingActions as $action)
                <p class="mb-2">
                    <strong>{{ $action['message'] }}</strong>
                    <a href="{{ $action['action_url'] }}" class="btn btn-sm btn-outline-warning ml-2">
                        Take Action
                    </a>
                </p>
                @endforeach
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Contracts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['total_contracts'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Contracts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['active_contracts'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Signatures
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['pending_signatures'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-signature fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Value
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($stats['total_contract_value'], 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Contracts -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Contracts</h6>
                    <a href="{{ route('client.contracts') }}" class="btn btn-sm btn-primary">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Contract</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Value</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contracts->take(5) as $contract)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $contract->title }}</div>
                                        <div class="text-muted small">{{ $contract->contract_number }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColor = match($contract->status) {
                                                'active' => 'success',
                                                'draft' => 'secondary',
                                                'pending_approval' => 'warning',
                                                'pending_signature' => 'info',
                                                'completed' => 'success',
                                                'terminated' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusColor }}">
                                            {{ ucwords(str_replace('_', ' ', $contract->status)) }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($contract->contract_value, 2) }}</td>
                                    <td>
                                        @php
                                            $progress = $contract->completion_percentage ?? 0;
                                        @endphp
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: {{ $progress }}%" 
                                                 aria-valuenow="{{ $progress }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $progress }}%</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('client.contracts.show', $contract) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No contracts found
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Milestones & Recent Activity -->
        <div class="col-xl-4 col-lg-5">
            <!-- Upcoming Milestones -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Milestones</h6>
                </div>
                <div class="card-body">
                    @forelse($upcomingMilestones as $milestone)
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3">
                            @php
                                $daysUntil = \Carbon\Carbon::parse($milestone['due_date'])->diffInDays(now());
                                $isOverdue = \Carbon\Carbon::parse($milestone['due_date'])->isPast();
                                $urgencyClass = $isOverdue ? 'danger' : ($daysUntil <= 3 ? 'warning' : 'success');
                            @endphp
                            <span class="badge badge-{{ $urgencyClass }} badge-pill">
                                {{ $isOverdue ? 'Overdue' : $daysUntil . 'd' }}
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ $milestone['title'] }}</div>
                            <div class="text-muted small">
                                Due: {{ \Carbon\Carbon::parse($milestone['due_date'])->format('M j, Y') }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <p>No upcoming milestones</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                </div>
                <div class="card-body">
                    @forelse($recentActivity as $activity)
                    <div class="d-flex mb-3">
                        <div class="mr-3">
                            @php
                                $iconClass = match($activity['action']) {
                                    'contract_signed' => 'fas fa-signature text-success',
                                    'milestone_completed' => 'fas fa-check-circle text-success',
                                    'contract_created' => 'fas fa-file-plus text-info',
                                    'invoice_generated' => 'fas fa-file-invoice text-warning',
                                    default => 'fas fa-info-circle text-muted'
                                };
                            @endphp
                            <i class="{{ $iconClass }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold">{{ $activity['description'] }}</div>
                            <div class="text-muted small">
                                {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p>No recent activity</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('client.contracts') }}" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-file-contract mb-2"></i><br>
                                View All Contracts
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('client.contracts', ['status' => 'pending_signature']) }}" 
                               class="btn btn-outline-warning btn-block">
                                <i class="fas fa-signature mb-2"></i><br>
                                Sign Documents
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('client.profile') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-user-cog mb-2"></i><br>
                                Update Profile
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('client.contracts', ['type' => 'invoices']) }}" 
                               class="btn btn-outline-success btn-block">
                                <i class="fas fa-file-invoice-dollar mb-2"></i><br>
                                View Invoices
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-dismiss alerts after 10 seconds
setTimeout(function() {
    $('.alert-dismissible').alert('close');
}, 10000);

// Refresh dashboard data every 5 minutes
setInterval(function() {
    location.reload();
}, 300000); // 5 minutes
</script>
@endpush

@push('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-2px);
}
.progress {
    background-color: #e3e6f0;
}
.quick-action-card {
    text-align: center;
    padding: 1.5rem;
    transition: all 0.3s;
    cursor: pointer;
}
.quick-action-card:hover {
    background-color: #f8f9fc;
    transform: translateY(-2px);
}
</style>
@endpush