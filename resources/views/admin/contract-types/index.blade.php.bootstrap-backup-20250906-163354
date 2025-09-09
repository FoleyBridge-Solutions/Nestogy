@extends('layouts.app')

@section('title', 'Contract Types')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Contract Types</h1>
                    <p class="text-muted">Manage contract types and their configurations</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contractTypeModal">
                        <i class="fas fa-plus"></i> Add Contract Type
                    </button>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $statistics['total_types'] }}</h5>
                                    <p class="card-text">Total Types</p>
                                </div>
                                <i class="fas fa-file-contract fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $statistics['active_types'] }}</h5>
                                    <p class="card-text">Active Types</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $statistics['contracts_this_month'] }}</h5>
                                    <p class="card-text">New This Month</p>
                                </div>
                                <i class="fas fa-calendar fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">${{ number_format($statistics['total_value'], 0) }}</h5>
                                    <p class="card-text">Total Value</p>
                                </div>
                                <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Contract Types</h5>
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" id="searchInput" 
                                       placeholder="Search contract types...">
                                <button class="btn btn-outline-secondary btn-sm" type="button" id="searchBtn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" data-filter="all">All Types</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="active">Active Only</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="inactive">Inactive Only</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-filter="has_template">With Template</a></li>
                                    <li><a class="dropdown-item" href="#" data-filter="no_template">Without Template</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Template</th>
                                    <th>Status</th>
                                    <th>Contracts</th>
                                    <th>Default Billing</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contractTypesTable">
                                @forelse($contractTypes as $type)
                                    <tr data-type-id="{{ $type['id'] }}" data-filter-tags="{{ strtolower($type['name'] . ' ' . $type['category']) }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($type['icon'])
                                                    <i class="{{ $type['icon'] }} me-2"></i>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $type['name'] }}</div>
                                                    @if($type['description'])
                                                        <div class="text-muted small">{{ Str::limit($type['description'], 60) }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $type['category'] }}</span>
                                        </td>
                                        <td>
                                            @if($type['template_id'])
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> Configured
                                                </span>
                                            @else
                                                <span class="text-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> Missing
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox" 
                                                       data-type-id="{{ $type['id'] }}"
                                                       {{ $type['is_active'] ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $type['contracts_count'] ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <div class="small">
                                                {{ ucfirst(str_replace('_', ' ', $type['default_billing_model'] ?? 'Not Set')) }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary edit-type" 
                                                        data-type-id="{{ $type['id'] }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info configure-type" 
                                                        data-type-id="{{ $type['id'] }}">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success clone-type" 
                                                        data-type-id="{{ $type['id'] }}">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-type" 
                                                        data-type-id="{{ $type['id'] }}"
                                                        {{ ($type['contracts_count'] ?? 0) > 0 ? 'disabled title="Cannot delete type with existing contracts"' : '' }}>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-file-contract fa-3x text-muted mb-3 d-block"></i>
                                            <h5 class="text-muted">No Contract Types Found</h5>
                                            <p class="text-muted">Get started by creating your first contract type.</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contractTypeModal">
                                                <i class="fas fa-plus"></i> Create Contract Type
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Contract Type Modal --}}
<div class="modal fade" id="contractTypeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Contract Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="contractTypeForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Contract Type Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category">
                                            <option value="service">Service Agreement</option>
                                            <option value="maintenance">Maintenance Contract</option>
                                            <option value="licensing">Software License</option>
                                            <option value="consulting">Consulting Agreement</option>
                                            <option value="support">Support Contract</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                          placeholder="Brief description of this contract type..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="icon" class="form-label">Icon</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="icon" name="icon" 
                                                   placeholder="fas fa-file-contract">
                                            <button class="btn btn-outline-secondary" type="button" id="iconPreview">
                                                <i id="iconDisplay" class="fas fa-file-contract"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">FontAwesome icon class</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="color" class="form-label">Color Theme</label>
                                        <select class="form-select" id="color" name="color">
                                            <option value="primary">Primary (Blue)</option>
                                            <option value="secondary">Secondary (Gray)</option>
                                            <option value="success">Success (Green)</option>
                                            <option value="warning">Warning (Yellow)</option>
                                            <option value="danger">Danger (Red)</option>
                                            <option value="info">Info (Cyan)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Billing Configuration --}}
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Default Billing Configuration</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="default_billing_model" class="form-label">Default Billing Model</label>
                                                <select class="form-select" id="default_billing_model" name="default_billing_model">
                                                    <option value="">Select billing model...</option>
                                                    <option value="fixed">Fixed Price</option>
                                                    <option value="per_asset">Per Asset/Device</option>
                                                    <option value="per_user">Per User</option>
                                                    <option value="tiered">Tiered Pricing</option>
                                                    <option value="usage_based">Usage Based</option>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="default_term_length" class="form-label">Default Term (months)</label>
                                                <input type="number" class="form-control" id="default_term_length" 
                                                       name="default_term_length" min="1" max="120" value="12">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="requires_signature" name="requires_signature" checked>
                                        <label class="form-check-label" for="requires_signature">
                                            Requires Digital Signature
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Workflow Configuration --}}
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Workflow Configuration</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="workflow_stages" class="form-label">Workflow Stages</label>
                                        <div id="workflowStages">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small">Define the stages this contract type goes through</span>
                                                <button type="button" class="btn btn-sm btn-outline-primary" id="addWorkflowStage">
                                                    <i class="fas fa-plus"></i> Add Stage
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Settings & Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                            <label class="form-check-label" for="is_active">
                                                Active
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                               value="0" min="0" step="10">
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label class="form-label">Features</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="allows_amendments" name="allows_amendments" checked>
                                            <label class="form-check-label" for="allows_amendments">
                                                Allow Amendments
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="supports_milestones" name="supports_milestones">
                                            <label class="form-check-label" for="supports_milestones">
                                                Support Milestones
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="auto_renew" name="auto_renew">
                                            <label class="form-check-label" for="auto_renew">
                                                Auto-Renewal Available
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="requires_approval" name="requires_approval">
                                            <label class="form-check-label" for="requires_approval">
                                                Requires Approval
                                            </label>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label for="notification_settings" class="form-label">Notifications</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notify_expiration" name="notification_settings[expiration]" checked>
                                            <label class="form-check-label" for="notify_expiration">
                                                Expiration Notifications
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notify_renewal" name="notification_settings[renewal]">
                                            <label class="form-check-label" for="notify_renewal">
                                                Renewal Notifications
                                            </label>
                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="notify_milestone" name="notification_settings[milestone]">
                                            <label class="form-check-label" for="notify_milestone">
                                                Milestone Notifications
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Card --}}
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div id="typePreview" class="text-center">
                                        <i id="previewIcon" class="fas fa-file-contract fa-3x text-primary mb-2"></i>
                                        <h6 id="previewName">Contract Type Name</h6>
                                        <span id="previewCategory" class="badge bg-secondary">service</span>
                                        <p id="previewDescription" class="text-muted small mt-2">Description will appear here...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Contract Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Configuration Modal --}}
<div class="modal fade" id="configurationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configure Contract Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="nav nav-pills nav-justified mb-3" id="configTabs" role="tablist">
                            <button class="nav-link active" id="template-tab" data-bs-toggle="pill" 
                                    data-bs-target="#template-pane" type="button" role="tab">
                                Template
                            </button>
                            <button class="nav-link" id="fields-tab" data-bs-toggle="pill" 
                                    data-bs-target="#fields-pane" type="button" role="tab">
                                Fields
                            </button>
                            <button class="nav-link" id="validation-tab" data-bs-toggle="pill" 
                                    data-bs-target="#validation-pane" type="button" role="tab">
                                Validation
                            </button>
                            <button class="nav-link" id="actions-tab" data-bs-toggle="pill" 
                                    data-bs-target="#actions-pane" type="button" role="tab">
                                Actions
                            </button>
                        </div>

                        <div class="tab-content" id="configTabsContent">
                            <div class="tab-pane fade show active" id="template-pane" role="tabpanel">
                                <h6>Contract Template</h6>
                                <p class="text-muted">Configure the document template for this contract type.</p>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-primary" id="configureTemplate">
                                        Configure Template
                                    </button>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="fields-pane" role="tabpanel">
                                <h6>Custom Fields</h6>
                                <p class="text-muted">Add custom fields specific to this contract type.</p>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-primary" id="manageFields">
                                        Manage Fields
                                    </button>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="validation-pane" role="tabpanel">
                                <h6>Validation Rules</h6>
                                <p class="text-muted">Set up validation rules for this contract type.</p>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-primary" id="configureValidation">
                                        Configure Rules
                                    </button>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="actions-pane" role="tabpanel">
                                <h6>Action Buttons</h6>
                                <p class="text-muted">Configure custom action buttons for this contract type.</p>
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-primary" id="configureActions">
                                        Configure Actions
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let workflowStageIndex = 0;
    let currentTypeId = null;

    // Icon preview
    document.getElementById('icon').addEventListener('input', function() {
        const iconDisplay = document.getElementById('iconDisplay');
        const previewIcon = document.getElementById('previewIcon');
        const iconClass = this.value || 'fas fa-file-contract';
        
        iconDisplay.className = iconClass;
        previewIcon.className = iconClass + ' fa-3x text-primary mb-2';
    });

    // Live preview updates
    document.getElementById('name').addEventListener('input', function() {
        document.getElementById('previewName').textContent = this.value || 'Contract Type Name';
    });

    document.getElementById('category').addEventListener('change', function() {
        document.getElementById('previewCategory').textContent = this.value;
    });

    document.getElementById('description').addEventListener('input', function() {
        document.getElementById('previewDescription').textContent = this.value || 'Description will appear here...';
    });

    // Add workflow stage
    document.getElementById('addWorkflowStage').addEventListener('click', function() {
        addWorkflowStage();
    });

    function addWorkflowStage() {
        const container = document.getElementById('workflowStages');
        const stageDiv = document.createElement('div');
        stageDiv.className = 'border rounded p-3 mb-2';
        stageDiv.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Stage Name</label>
                    <input type="text" class="form-control form-control-sm" name="workflow_stages[${workflowStageIndex}][name]" 
                           placeholder="Draft">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <input type="text" class="form-control form-control-sm" name="workflow_stages[${workflowStageIndex}][status]" 
                           placeholder="draft">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Order</label>
                    <input type="number" class="form-control form-control-sm" name="workflow_stages[${workflowStageIndex}][order]" 
                           value="${workflowStageIndex + 1}" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-outline-danger d-block remove-stage">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control form-control-sm" name="workflow_stages[${workflowStageIndex}][description]" 
                           placeholder="Stage description...">
                </div>
            </div>
        `;
        
        const addButton = document.getElementById('addWorkflowStage');
        container.insertBefore(stageDiv, addButton.parentElement);
        
        stageDiv.querySelector('.remove-stage').addEventListener('click', function() {
            stageDiv.remove();
        });
        
        workflowStageIndex++;
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    
    searchInput.addEventListener('input', performSearch);
    searchBtn.addEventListener('click', performSearch);

    function performSearch() {
        const query = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('#contractTypesTable tr[data-type-id]');
        
        rows.forEach(row => {
            const tags = row.dataset.filterTags;
            if (tags.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Filter functionality
    document.querySelectorAll('[data-filter]').forEach(filterBtn => {
        filterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            applyFilter(this.dataset.filter);
        });
    });

    function applyFilter(filter) {
        const rows = document.querySelectorAll('#contractTypesTable tr[data-type-id]');
        
        rows.forEach(row => {
            let show = true;
            
            switch (filter) {
                case 'active':
                    show = row.querySelector('.status-toggle').checked;
                    break;
                case 'inactive':
                    show = !row.querySelector('.status-toggle').checked;
                    break;
                case 'has_template':
                    show = row.textContent.includes('Configured');
                    break;
                case 'no_template':
                    show = row.textContent.includes('Missing');
                    break;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    // Status toggle
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-toggle')) {
            const typeId = e.target.dataset.typeId;
            const isActive = e.target.checked;
            
            updateTypeStatus(typeId, isActive);
        }
    });

    function updateTypeStatus(typeId, isActive) {
        fetch(`/admin/contract-types/${typeId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ is_active: isActive })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(`Contract type ${isActive ? 'activated' : 'deactivated'} successfully`, 'success');
            } else {
                showAlert('Failed to update contract type status', 'error');
                // Revert toggle on failure
                e.target.checked = !isActive;
            }
        })
        .catch(() => {
            showAlert('Error updating contract type status', 'error');
            e.target.checked = !isActive;
        });
    }

    // Form submission
    document.getElementById('contractTypeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            if (key.includes('[') && key.includes(']')) {
                const keys = key.split(/[\[\]]+/).filter(Boolean);
                let current = data;
                for (let i = 0; i < keys.length - 1; i++) {
                    if (!current[keys[i]]) current[keys[i]] = {};
                    current = current[keys[i]];
                }
                current[keys[keys.length - 1]] = value;
            } else {
                data[key] = value;
            }
        }
        
        fetch('/admin/contract-types', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.message || 'Error creating contract type', 'error');
            }
        })
        .catch(() => {
            showAlert('Error creating contract type', 'error');
        });
    });

    // Edit type
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-type')) {
            const typeId = e.target.closest('.edit-type').dataset.typeId;
            editType(typeId);
        }
    });

    function editType(typeId) {
        // Implementation for editing contract type
        console.log('Edit type:', typeId);
    }

    // Configure type
    document.addEventListener('click', function(e) {
        if (e.target.closest('.configure-type')) {
            const typeId = e.target.closest('.configure-type').dataset.typeId;
            currentTypeId = typeId;
            const configModal = new bootstrap.Modal(document.getElementById('configurationModal'));
            configModal.show();
        }
    });

    // Clone type
    document.addEventListener('click', function(e) {
        if (e.target.closest('.clone-type')) {
            const typeId = e.target.closest('.clone-type').dataset.typeId;
            cloneType(typeId);
        }
    });

    function cloneType(typeId) {
        if (!confirm('Create a copy of this contract type?')) {
            return;
        }
        
        fetch(`/admin/contract-types/${typeId}/clone`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert('Error cloning contract type', 'error');
            }
        });
    }

    // Delete type
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-type')) {
            const typeId = e.target.closest('.delete-type').dataset.typeId;
            deleteType(typeId);
        }
    });

    function deleteType(typeId) {
        if (!confirm('Are you sure you want to delete this contract type? This action cannot be undone.')) {
            return;
        }
        
        fetch(`/admin/contract-types/${typeId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.message || 'Error deleting contract type', 'error');
            }
        });
    }

    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
});
</script>
@endpush