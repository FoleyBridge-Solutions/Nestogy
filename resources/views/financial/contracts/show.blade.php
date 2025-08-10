@extends('layouts.app')

@section('title', 'Contract Details - ' . $contract->title)

@push('styles')
<style>
.contract-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.status-badge {
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 0.5rem 1rem;
    border-radius: 25px;
}

.milestone-card {
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}
.milestone-card.completed { border-left-color: #28a745; }
.milestone-card.in-progress { border-left-color: #ffc107; }
.milestone-card.pending { border-left-color: #6c757d; }

.approval-timeline {
    position: relative;
}
.approval-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.approval-item {
    position: relative;
    padding-left: 60px;
    margin-bottom: 2rem;
}

.approval-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.approval-icon.pending {
    background: #ffc107;
    color: white;
}
.approval-icon.approved {
    background: #28a745;
    color: white;
}
.approval-icon.rejected {
    background: #dc3545;
    color: white;
}

.signature-status {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}
.signature-status.completed {
    border-color: #28a745;
    background-color: #f8fff9;
}
.signature-status.pending {
    border-color: #ffc107;
    background-color: #fffaf0;
}

.document-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    max-height: 600px;
}

.tab-content-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-radius: 0 8px 8px 8px;
}

@media print {
    .no-print { display: none; }
    .contract-header { background: #f8f9fa !important; color: #333 !important; }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="contract-header p-4 mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-2">
                    <h1 class="h3 mb-0 me-3">{{ $contract->title }}</h1>
                    <span class="status-badge bg-{{ $contract->status_color ?? 'secondary' }}">
                        {{ $contract->status_label }}
                    </span>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-user me-2"></i>
                            <strong>Client:</strong> {{ $contract->client->name ?? 'No Client' }}
                        </p>
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-calendar me-2"></i>
                            <strong>Duration:</strong> 
                            {{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'Not set' }}
                            @if($contract->end_date)
                                - {{ $contract->end_date->format('M d, Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-dollar-sign me-2"></i>
                            <strong>Value:</strong> ${{ number_format($contract->contract_value, 2) }}
                        </p>
                        <p class="mb-1 opacity-75">
                            <i class="fas fa-tag me-2"></i>
                            <strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="no-print">
                    @if($contract->canBeEdited())
                        <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-light me-2">
                            <i class="fas fa-edit me-1"></i> Edit Contract
                        </a>
                    @endif
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i> Actions
                        </button>
                        <ul class="dropdown-menu">
                            @if($contract->status === 'draft' || $contract->status === 'under_negotiation')
                                <li><a class="dropdown-item" href="#" onclick="submitForApproval()">
                                    <i class="fas fa-check me-2"></i> Submit for Approval
                                </a></li>
                            @endif
                            @if($contract->status === 'pending_signature')
                                <li><a class="dropdown-item" href="#" onclick="sendForSignature()">
                                    <i class="fas fa-signature me-2"></i> Send for Signature
                                </a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('contracts.pdf', $contract) }}" target="_blank">
                                <i class="fas fa-file-pdf me-2"></i> Download PDF
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="window.print()">
                                <i class="fas fa-print me-2"></i> Print Contract
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('contracts.duplicate', $contract) }}">
                                <i class="fas fa-copy me-2"></i> Duplicate Contract
                            </a></li>
                            @if($contract->canBeTerminated())
                                <li><a class="dropdown-item text-danger" href="#" onclick="terminateContract()">
                                    <i class="fas fa-ban me-2"></i> Terminate Contract
                                </a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                <i class="fas fa-info-circle me-1"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#content" type="button">
                <i class="fas fa-file-alt me-1"></i> Contract Content
            </button>
        </li>
        @if($contract->approvals->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#approvals" type="button">
                <i class="fas fa-clipboard-check me-1"></i> Approvals
                @if($contract->approvals->where('status', 'pending')->count() > 0)
                    <span class="badge bg-warning ms-1">{{ $contract->approvals->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </li>
        @endif
        @if($contract->signatures->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#signatures" type="button">
                <i class="fas fa-signature me-1"></i> Signatures
            </button>
        </li>
        @endif
        @if($contract->milestones->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#milestones" type="button">
                <i class="fas fa-tasks me-1"></i> Milestones
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#history" type="button">
                <i class="fas fa-history me-1"></i> History
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview">
            <div class="card tab-content-card">
                <div class="card-body">
                    <div class="row">
                        <!-- Contract Details -->
                        <div class="col-lg-8">
                            <h5 class="card-title">Contract Details</h5>
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Contract Number:</dt>
                                        <dd class="col-sm-7">{{ $contract->contract_number }}</dd>
                                        
                                        <dt class="col-sm-5">Contract Type:</dt>
                                        <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $contract->contract_type)) }}</dd>
                                        
                                        <dt class="col-sm-5">Start Date:</dt>
                                        <dd class="col-sm-7">{{ $contract->start_date ? $contract->start_date->format('M d, Y') : 'Not set' }}</dd>
                                        
                                        <dt class="col-sm-5">End Date:</dt>
                                        <dd class="col-sm-7">{{ $contract->end_date ? $contract->end_date->format('M d, Y') : 'Open-ended' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-sm-6">
                                    <dl class="row">
                                        <dt class="col-sm-5">Contract Value:</dt>
                                        <dd class="col-sm-7">${{ number_format($contract->contract_value, 2) }}</dd>
                                        
                                        <dt class="col-sm-5">Currency:</dt>
                                        <dd class="col-sm-7">{{ strtoupper($contract->currency) }}</dd>
                                        
                                        <dt class="col-sm-5">Created:</dt>
                                        <dd class="col-sm-7">{{ $contract->created_at->format('M d, Y') }}</dd>
                                        
                                        <dt class="col-sm-5">Last Updated:</dt>
                                        <dd class="col-sm-7">{{ $contract->updated_at->format('M d, Y') }}</dd>
                                    </dl>
                                </div>
                            </div>

                            @if($contract->description)
                                <h6>Description</h6>
                                <p class="mb-4">{{ $contract->description }}</p>
                            @endif

                            @if($contract->terms && !empty($contract->terms))
                                <h6>Terms & Conditions</h6>
                                <div class="mb-4">
                                    @foreach($contract->terms as $term)
                                        <div class="mb-2">
                                            <strong>{{ $term['section'] ?? 'General' }}:</strong>
                                            <p class="mb-0">{{ $term['content'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Sidebar Info -->
                        <div class="col-lg-4">
                            <!-- Client Information -->
                            @if($contract->client)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Client Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <h6>{{ $contract->client->name }}</h6>
                                        @if($contract->client->email)
                                            <p class="mb-1"><i class="fas fa-envelope me-2"></i>{{ $contract->client->email }}</p>
                                        @endif
                                        @if($contract->client->phone)
                                            <p class="mb-1"><i class="fas fa-phone me-2"></i>{{ $contract->client->phone }}</p>
                                        @endif
                                        @if($contract->client->address)
                                            <p class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>{{ $contract->client->address }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Progress Summary -->
                            @if($contract->milestones->count() > 0)
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Progress Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small>Overall Progress</small>
                                                <small>{{ $contract->completion_percentage ?? 0 }}%</small>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: {{ $contract->completion_percentage ?? 0 }}%"></div>
                                            </div>
                                        </div>

                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="h5 mb-0 text-success">{{ $contract->milestones->where('status', 'completed')->count() }}</div>
                                                <small class="text-muted">Completed</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="h5 mb-0 text-warning">{{ $contract->milestones->where('status', 'in_progress')->count() }}</div>
                                                <small class="text-muted">In Progress</small>
                                            </div>
                                            <div class="col-4">
                                                <div class="h5 mb-0 text-secondary">{{ $contract->milestones->where('status', 'pending')->count() }}</div>
                                                <small class="text-muted">Pending</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Quick Stats -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Contract Stats</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Approvals:</span>
                                        <span class="fw-bold">{{ $contract->approvals->count() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Signatures:</span>
                                        <span class="fw-bold">{{ $contract->signatures->count() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Milestones:</span>
                                        <span class="fw-bold">{{ $contract->milestones->count() }}</span>
                                    </div>
                                    @if($contract->amendments->count() > 0)
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Amendments:</span>
                                            <span class="fw-bold">{{ $contract->amendments->count() }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Content Tab -->
        <div class="tab-pane fade" id="content">
            <div class="card tab-content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Contract Content</h5>
                        <div>
                            <a href="{{ route('contracts.pdf', $contract) }}" target="_blank" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-external-link-alt me-1"></i> Open in New Tab
                            </a>
                            <a href="{{ route('contracts.pdf', $contract) }}" download class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-1"></i> Download PDF
                            </a>
                        </div>
                    </div>

                    @if($contract->content)
                        <div class="document-preview">
                            <div class="p-4" style="background: white; font-family: 'Times New Roman', serif; line-height: 1.6;">
                                {!! $contract->content !!}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt text-muted mb-3" style="font-size: 3rem;"></i>
                            <h6 class="text-muted">No contract content available</h6>
                            <p class="text-muted">Generate contract content using a template or add custom content.</p>
                            @if($contract->canBeEdited())
                                <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add Content
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Approvals Tab -->
        @if($contract->approvals->count() > 0)
        <div class="tab-pane fade" id="approvals">
            <div class="card tab-content-card">
                <div class="card-body">
                    <h5 class="card-title">Approval Workflow</h5>
                    
                    <div class="approval-timeline">
                        @foreach($contract->approvals->sortBy('approval_order') as $approval)
                            <div class="approval-item">
                                <div class="approval-icon {{ $approval->status }}">
                                    @if($approval->status === 'approved')
                                        <i class="fas fa-check"></i>
                                    @elseif($approval->status === 'rejected')
                                        <i class="fas fa-times"></i>
                                    @elseif($approval->status === 'pending')
                                        <i class="fas fa-clock"></i>
                                    @else
                                        <i class="fas fa-question"></i>
                                    @endif
                                </div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0">{{ $approval->approval_level_label }}</h6>
                                            <span class="badge bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($approval->status) }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-muted mb-2">
                                            <i class="fas fa-user me-1"></i>
                                            {{ $approval->approverUser->name ?? 'Role: ' . ucwords($approval->approver_role) }}
                                        </p>
                                        
                                        @if($approval->required_by)
                                            <p class="small text-muted mb-2">
                                                <i class="fas fa-calendar me-1"></i>
                                                Required by: {{ $approval->required_by->format('M d, Y H:i') }}
                                                @if($approval->is_overdue)
                                                    <span class="text-danger ms-1">(Overdue)</span>
                                                @endif
                                            </p>
                                        @endif
                                        
                                        @if($approval->comments)
                                            <div class="mt-2">
                                                <strong>Comments:</strong>
                                                <p class="mb-0">{{ $approval->comments }}</p>
                                            </div>
                                        @endif
                                        
                                        @if($approval->conditions && !empty($approval->conditions))
                                            <div class="mt-2">
                                                <strong>Conditions:</strong>
                                                <ul class="mb-0">
                                                    @foreach($approval->conditions as $condition)
                                                        <li>{{ $condition }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        
                                        @if($approval->status !== 'pending' && ($approval->approved_at || $approval->rejected_at))
                                            <small class="text-muted">
                                                {{ ucfirst($approval->status) }} on 
                                                {{ ($approval->approved_at ?? $approval->rejected_at)->format('M d, Y H:i') }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Signatures Tab -->
        @if($contract->signatures->count() > 0)
        <div class="tab-pane fade" id="signatures">
            <div class="card tab-content-card">
                <div class="card-body">
                    <h5 class="card-title">Digital Signatures</h5>
                    
                    <div class="row">
                        @foreach($contract->signatures as $signature)
                            <div class="col-md-6 mb-3">
                                <div class="signature-status {{ $signature->status === 'signed' ? 'completed' : 'pending' }}">
                                    <h6 class="mb-2">{{ $signature->signer_name }}</h6>
                                    <p class="mb-2">{{ $signature->signer_email }}</p>
                                    
                                    <div class="mb-2">
                                        <span class="badge bg-{{ $signature->status === 'signed' ? 'success' : ($signature->status === 'sent' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($signature->status) }}
                                        </span>
                                    </div>
                                    
                                    @if($signature->signed_at)
                                        <small class="text-muted">
                                            Signed on {{ $signature->signed_at->format('M d, Y H:i') }}
                                        </small>
                                    @elseif($signature->sent_at)
                                        <small class="text-muted">
                                            Sent on {{ $signature->sent_at->format('M d, Y H:i') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Milestones Tab -->
        @if($contract->milestones->count() > 0)
        <div class="tab-pane fade" id="milestones">
            <div class="card tab-content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Project Milestones</h5>
                        @if($contract->canBeEdited())
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMilestoneModal">
                                <i class="fas fa-plus me-1"></i> Add Milestone
                            </button>
                        @endif
                    </div>
                    
                    @foreach($contract->milestones->sortBy('due_date') as $milestone)
                        <div class="milestone-card card mb-3 {{ $milestone->status }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">{{ $milestone->title }}</h6>
                                    <span class="badge bg-{{ $milestone->status === 'completed' ? 'success' : ($milestone->status === 'in_progress' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $milestone->status)) }}
                                    </span>
                                </div>
                                
                                @if($milestone->description)
                                    <p class="text-muted mb-2">{{ $milestone->description }}</p>
                                @endif
                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Due: {{ $milestone->due_date ? $milestone->due_date->format('M d, Y') : 'No due date' }}
                                        </small>
                                    </div>
                                    <div class="col-sm-6">
                                        @if($milestone->amount)
                                            <small class="text-muted">
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                Value: ${{ number_format($milestone->amount, 2) }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($milestone->completed_at)
                                    <small class="text-success">
                                        <i class="fas fa-check me-1"></i>
                                        Completed on {{ $milestone->completed_at->format('M d, Y') }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- History Tab -->
        <div class="tab-pane fade" id="history">
            <div class="card tab-content-card">
                <div class="card-body">
                    <h5 class="card-title">Contract History</h5>
                    
                    <div class="timeline">
                        @forelse($contract->getAuditHistory() as $event)
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-{{ $event['icon'] ?? 'circle' }} fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <p class="mb-1">
                                        <strong>{{ $event['description'] }}</strong>
                                        @if($event['user'] ?? null)
                                            by {{ $event['user'] }}
                                        @endif
                                    </p>
                                    <small class="text-muted">{{ $event['date'] }}</small>
                                    @if($event['details'] ?? null)
                                        <div class="mt-1">
                                            <small class="text-muted">{{ $event['details'] }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-history text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="text-muted">No history available for this contract.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function submitForApproval() {
    if (confirm('Are you sure you want to submit this contract for approval?')) {
        fetch(`/api/contracts/{{ $contract->id }}/submit-approval`, {
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

function sendForSignature() {
    if (confirm('Send this contract for digital signature?')) {
        fetch(`/api/contracts/{{ $contract->id }}/send-signature`, {
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
                alert('Error sending for signature: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending for signature');
        });
    }
}

function terminateContract() {
    if (confirm('Are you sure you want to terminate this contract? This action cannot be undone.')) {
        const reason = prompt('Please provide a reason for termination:');
        if (reason) {
            fetch(`/api/contracts/{{ $contract->id }}/terminate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error terminating contract: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error terminating contract');
            });
        }
    }
}
</script>
@endpush