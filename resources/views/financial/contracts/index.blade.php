@extends('layouts.app')

@section('title', 'Contract Management')

@push('styles')
<style>
.contract-card {
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}
.contract-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.contract-card.status-active { border-left-color: #28a745; }
.contract-card.status-pending-signature { border-left-color: #ffc107; }
.contract-card.status-pending-review { border-left-color: #17a2b8; }
.contract-card.status-draft { border-left-color: #6c757d; }
.contract-card.status-expired { border-left-color: #dc3545; }
.contract-card.status-terminated { border-left-color: #343a40; }

.status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contract-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #28a745;
}

.approval-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}
.approval-indicator.pending { background-color: #ffc107; }
.approval-indicator.approved { background-color: #28a745; }
.approval-indicator.rejected { background-color: #dc3545; }

.contract-progress {
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    background-color: #e9ecef;
}

.filter-sidebar {
    background: #f8f9fa;
    border-radius: 8px;
    min-height: 400px;
}

.quick-stats {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.metric-card {
    background: white;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}
.metric-card:hover {
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .filter-sidebar {
        margin-bottom: 1rem;
    }
    .contract-card {
        margin-bottom: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Contract Management</h1>
            <p class="text-muted mb-0">Manage all your service contracts and agreements</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contracts.templates.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-file-alt me-1"></i> Templates
            </a>
            <a href="{{ route('contracts.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Contract
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="quick-stats p-4 mb-4">
                <div class="row text-center">
                    <div class="col-6 col-md-3">
                        <h3 class="mb-0">{{ $stats['total_contracts'] ?? 0 }}</h3>
                        <small class="opacity-75">Total Contracts</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <h3 class="mb-0">${{ number_format($stats['total_value'] ?? 0, 0) }}</h3>
                        <small class="opacity-75">Total Value</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <h3 class="mb-0">{{ $stats['active_contracts'] ?? 0 }}</h3>
                        <small class="opacity-75">Active Contracts</small>
                    </div>
                    <div class="col-6 col-md-3">
                        <h3 class="mb-0">{{ $stats['pending_approval'] ?? 0 }}</h3>
                        <small class="opacity-75">Pending Approval</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3">
            <div class="metric-card p-3 text-center">
                <i class="fas fa-signature text-warning mb-2" style="font-size: 1.5rem;"></i>
                <h6 class="mb-1">{{ $stats['pending_signature'] ?? 0 }}</h6>
                <small class="text-muted">Pending Signature</small>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="metric-card p-3 text-center">
                <i class="fas fa-clock text-info mb-2" style="font-size: 1.5rem;"></i>
                <h6 class="mb-1">{{ $stats['expiring_soon'] ?? 0 }}</h6>
                <small class="text-muted">Expiring Soon</small>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="metric-card p-3 text-center">
                <i class="fas fa-dollar-sign text-success mb-2" style="font-size: 1.5rem;"></i>
                <h6 class="mb-1">${{ number_format($stats['monthly_revenue'] ?? 0, 0) }}</h6>
                <small class="text-muted">Monthly Revenue</small>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="metric-card p-3 text-center">
                <i class="fas fa-chart-line text-primary mb-2" style="font-size: 1.5rem;"></i>
                <h6 class="mb-1">{{ number_format($stats['renewal_rate'] ?? 0, 1) }}%</h6>
                <small class="text-muted">Renewal Rate</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="filter-sidebar p-3 mb-4">
                <h6 class="fw-bold mb-3">
                    <i class="fas fa-filter me-2"></i>Filters
                </h6>
                
                <form id="contractFilters" method="GET">
                    <!-- Status Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="under_negotiation" {{ request('status') == 'under_negotiation' ? 'selected' : '' }}>Under Negotiation</option>
                            <option value="pending_review" {{ request('status') == 'pending_review' ? 'selected' : '' }}>Pending Review</option>
                            <option value="pending_signature" {{ request('status') == 'pending_signature' ? 'selected' : '' }}>Pending Signature</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                    </div>

                    <!-- Contract Type Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contract Type</label>
                        <select name="contract_type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="one_time_service" {{ request('contract_type') == 'one_time_service' ? 'selected' : '' }}>One-time Service</option>
                            <option value="recurring_service" {{ request('contract_type') == 'recurring_service' ? 'selected' : '' }}>Recurring Service</option>
                            <option value="maintenance" {{ request('contract_type') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="support" {{ request('contract_type') == 'support' ? 'selected' : '' }}>Support</option>
                            <option value="master_service" {{ request('contract_type') == 'master_service' ? 'selected' : '' }}>Master Service</option>
                        </select>
                    </div>

                    <!-- Value Range Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contract Value</label>
                        <select name="value_range" class="form-select form-select-sm">
                            <option value="">All Values</option>
                            <option value="0-5000" {{ request('value_range') == '0-5000' ? 'selected' : '' }}>$0 - $5,000</option>
                            <option value="5000-25000" {{ request('value_range') == '5000-25000' ? 'selected' : '' }}>$5,000 - $25,000</option>
                            <option value="25000-100000" {{ request('value_range') == '25000-100000' ? 'selected' : '' }}>$25,000 - $100,000</option>
                            <option value="100000+" {{ request('value_range') == '100000+' ? 'selected' : '' }}>$100,000+</option>
                        </select>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Created Date</label>
                        <input type="date" name="date_from" class="form-control form-control-sm mb-2" 
                               value="{{ request('date_from') }}" placeholder="From">
                        <input type="date" name="date_to" class="form-control form-control-sm" 
                               value="{{ request('date_to') }}" placeholder="To">
                    </div>

                    <!-- Client Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Client</label>
                        <select name="client_id" class="form-select form-select-sm">
                            <option value="">All Clients</option>
                            @foreach($clients ?? [] as $client)
                                <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i> Clear All
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contracts List -->
        <div class="col-lg-9 col-md-8">
            <!-- Search and Sort -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search contracts..." 
                               name="search" value="{{ request('search') }}" form="contractFilters">
                        <button class="btn btn-outline-secondary" type="submit" form="contractFilters">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="sort" class="form-select" form="contractFilters" onchange="document.getElementById('contractFilters').submit();">
                        <option value="created_at-desc" {{ request('sort') == 'created_at-desc' ? 'selected' : '' }}>Newest First</option>
                        <option value="created_at-asc" {{ request('sort') == 'created_at-asc' ? 'selected' : '' }}>Oldest First</option>
                        <option value="contract_value-desc" {{ request('sort') == 'contract_value-desc' ? 'selected' : '' }}>Highest Value</option>
                        <option value="contract_value-asc" {{ request('sort') == 'contract_value-asc' ? 'selected' : '' }}>Lowest Value</option>
                        <option value="start_date-desc" {{ request('sort') == 'start_date-desc' ? 'selected' : '' }}>Start Date</option>
                        <option value="end_date-asc" {{ request('sort') == 'end_date-asc' ? 'selected' : '' }}>End Date</option>
                    </select>
                </div>
            </div>

            <!-- Contracts Grid -->
            @if($contracts->count() > 0)
                <div class="row">
                    @foreach($contracts as $contract)
                        <div class="col-12 mb-3">
                            <div class="contract-card card h-100 status-{{ str_replace('_', '-', $contract->status) }}">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <!-- Contract Info -->
                                        <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" 
                                                         style="width: 48px; height: 48px;">
                                                        <i class="fas fa-file-contract text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <a href="{{ route('contracts.show', $contract) }}" 
                                                           class="text-decoration-none text-dark fw-bold">
                                                            {{ $contract->title }}
                                                        </a>
                                                    </h6>
                                                    <p class="text-muted small mb-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        {{ $contract->client->name ?? 'No Client' }}
                                                    </p>
                                                    <span class="status-badge badge bg-{{ $contract->status_color ?? 'secondary' }}">
                                                        {{ $contract->status_label }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Contract Details -->
                                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                            <div class="contract-value mb-2">
                                                ${{ number_format($contract->contract_value, 0) }}
                                            </div>
                                            <div class="small text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'No start date' }}
                                                @if($contract->end_date)
                                                    - {{ $contract->end_date->format('M d, Y') }}
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                <i class="fas fa-tag me-1"></i>
                                                {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                                            </div>
                                        </div>

                                        <!-- Progress & Approvals -->
                                        <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                            @if($contract->approvals_count > 0)
                                                <div class="small mb-2">
                                                    <strong>Approvals:</strong>
                                                    <div class="mt-1">
                                                        @php
                                                            $approvalStats = $contract->approval_statistics ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
                                                        @endphp
                                                        <span class="approval-indicator pending" title="Pending"></span>{{ $approvalStats['pending'] }}
                                                        <span class="approval-indicator approved ms-2" title="Approved"></span>{{ $approvalStats['approved'] }}
                                                        <span class="approval-indicator rejected ms-2" title="Rejected"></span>{{ $approvalStats['rejected'] }}
                                                    </div>
                                                </div>
                                            @endif

                                            @if($contract->milestones_count > 0)
                                                <div class="small mb-2">
                                                    <strong>Progress:</strong>
                                                    <div class="contract-progress mt-1">
                                                        <div class="progress-bar bg-success" 
                                                             style="width: {{ $contract->completion_percentage ?? 0 }}%"></div>
                                                    </div>
                                                    <span class="text-muted">{{ $contract->completion_percentage ?? 0 }}% Complete</span>
                                                </div>
                                            @endif

                                            @if($contract->signature_status)
                                                <div class="small">
                                                    <i class="fas fa-signature me-1"></i>
                                                    {{ ucwords(str_replace('_', ' ', $contract->signature_status)) }}
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Actions -->
                                        <div class="col-lg-2 col-md-6">
                                            <div class="d-flex flex-column gap-1">
                                                <a href="{{ route('contracts.show', $contract) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                
                                                @if($contract->canBeEdited())
                                                    <a href="{{ route('contracts.edit', $contract) }}" 
                                                       class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </a>
                                                @endif

                                                @if($contract->status === 'draft' || $contract->status === 'under_negotiation')
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="submitForApproval({{ $contract->id }})">
                                                        <i class="fas fa-check me-1"></i> Submit
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="row">
                    <div class="col-12">
                        {{ $contracts->withQueryString()->links() }}
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-file-contract text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted mb-3">No contracts found</h5>
                    <p class="text-muted mb-4">
                        @if(request()->hasAny(['status', 'contract_type', 'value_range', 'search', 'client_id']))
                            Try adjusting your filters to see more contracts.
                        @else
                            Get started by creating your first contract.
                        @endif
                    </p>
                    <a href="{{ route('contracts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Create First Contract
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function submitForApproval(contractId) {
    if (confirm('Are you sure you want to submit this contract for approval?')) {
        fetch(`/api/contracts/${contractId}/submit-approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error submitting for approval: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting for approval');
        });
    }
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('#contractFilters select:not([name="sort"])');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('contractFilters').submit();
        });
    });
});
</script>
@endpush