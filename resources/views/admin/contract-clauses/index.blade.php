@extends('layouts.app')

@section('title', 'Contract Clauses Library')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Contract Clauses Library</h1>
                    <p class="text-muted">Manage reusable contract clauses and legal terms</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" id="importClausesBtn">
                        <i class="fas fa-upload"></i> Import Clauses
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clauseModal">
                        <i class="fas fa-plus"></i> Add Clause
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Filter Clauses</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="categoryFilter" class="form-label">Category</label>
                                <select class="form-select form-select-sm" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $key => $category)
                                        <option value="{{ $key }}">{{ $category['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="typeFilter" class="form-label">Type</label>
                                <select class="form-select form-select-sm" id="typeFilter">
                                    <option value="">All Types</option>
                                    <option value="standard">Standard</option>
                                    <option value="legal">Legal</option>
                                    <option value="compliance">Compliance</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reviewFilter" class="form-label">Review Status</label>
                                <select class="form-select form-select-sm" id="reviewFilter">
                                    <option value="">All Statuses</option>
                                    <option value="approved">Approved</option>
                                    <option value="pending">Pending Review</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requiredOnly">
                                <label class="form-check-label" for="requiredOnly">
                                    Required Only
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="createMSPClauses">
                                    <i class="fas fa-server"></i> Create MSP Clauses
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="createLegalClauses">
                                    <i class="fas fa-gavel"></i> Create Legal Boilerplate
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" id="createComplianceClauses">
                                    <i class="fas fa-shield-alt"></i> Create Compliance Terms
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" id="bulkImport">
                                    <i class="fas fa-magic"></i> Import Standard Library
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Library Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="text-primary">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                    <div class="small text-muted">Total</div>
                                    <div class="fw-bold">{{ $statistics['total_clauses'] }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-success">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                    <div class="small text-muted">Approved</div>
                                    <div class="fw-bold">{{ $statistics['approved_clauses'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Contract Clauses</h5>
                                <div class="input-group" style="width: 300px;">
                                    <input type="text" class="form-control form-control-sm" id="searchClauses" 
                                           placeholder="Search clauses...">
                                    <button class="btn btn-outline-secondary btn-sm" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="clausesList">
                                @foreach($clauses->groupBy('category') as $categoryKey => $categoryGroup)
                                    @php($categoryInfo = $categories[$categoryKey] ?? ['name' => ucfirst($categoryKey), 'color' => 'secondary'])
                                    <div class="clause-category" data-category="{{ $categoryKey }}">
                                        <div class="category-header bg-light px-3 py-2 border-bottom">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-folder text-{{ $categoryInfo['color'] }} me-2"></i>
                                                    <strong>{{ $categoryInfo['name'] }}</strong>
                                                    <span class="badge bg-{{ $categoryInfo['color'] }} ms-2">{{ $categoryGroup->count() }}</span>
                                                </div>
                                                <button class="btn btn-sm btn-link text-muted category-toggle" type="button">
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="category-content">
                                            @foreach($categoryGroup as $clause)
                                                <div class="clause-item border-bottom p-3" data-clause-id="{{ $clause->id }}">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="d-flex align-items-start">
                                                                <div class="clause-icon me-3 mt-1">
                                                                    @if($clause->is_required)
                                                                        <i class="fas fa-exclamation-circle text-warning" title="Required Clause"></i>
                                                                    @else
                                                                        <i class="fas fa-file-alt text-muted"></i>
                                                                    @endif
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-1">{{ $clause->title }}</h6>
                                                                    @if($clause->description)
                                                                        <p class="text-muted small mb-2">{{ $clause->description }}</p>
                                                                    @endif
                                                                    <div class="clause-preview small text-muted" style="max-height: 60px; overflow: hidden;">
                                                                        {!! Str::limit(strip_tags($clause->content), 120) !!}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="d-flex flex-column h-100">
                                                                <div class="mb-2">
                                                                    <span class="badge bg-{{ $categoryInfo['color'] }}">{{ ucfirst($clause->type) }}</span>
                                                                    @if($clause->legal_review_status === 'approved')
                                                                        <span class="badge bg-success">Approved</span>
                                                                    @elseif($clause->legal_review_status === 'rejected')
                                                                        <span class="badge bg-danger">Rejected</span>
                                                                    @else
                                                                        <span class="badge bg-warning">Pending</span>
                                                                    @endif
                                                                </div>
                                                                <div class="mt-auto">
                                                                    <div class="btn-group">
                                                                        <button type="button" class="btn btn-sm btn-outline-primary view-clause" 
                                                                                data-clause-id="{{ $clause->id }}">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-secondary edit-clause" 
                                                                                data-clause-id="{{ $clause->id }}">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-success copy-clause" 
                                                                                data-clause-id="{{ $clause->id }}">
                                                                            <i class="fas fa-copy"></i>
                                                                        </button>
                                                                        <div class="dropdown">
                                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                                    type="button" data-bs-toggle="dropdown">
                                                                                <i class="fas fa-ellipsis-v"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu">
                                                                                @if($clause->legal_review_status !== 'approved')
                                                                                <li><a class="dropdown-item" href="#" data-action="approve" data-clause-id="{{ $clause->id }}">
                                                                                    <i class="fas fa-check text-success me-2"></i>Approve
                                                                                </a></li>
                                                                                @endif
                                                                                <li><a class="dropdown-item" href="#" data-action="export" data-clause-id="{{ $clause->id }}">
                                                                                    <i class="fas fa-download me-2"></i>Export
                                                                                </a></li>
                                                                                <li><hr class="dropdown-divider"></li>
                                                                                <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-clause-id="{{ $clause->id }}">
                                                                                    <i class="fas fa-trash me-2"></i>Delete
                                                                                </a></li>
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                @if($clauses->isEmpty())
                                    <div class="text-center py-5">
                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Contract Clauses Found</h5>
                                        <p class="text-muted">Start building your clause library to create comprehensive contracts.</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clauseModal">
                                            <i class="fas fa-plus"></i> Add Your First Clause
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Clause Modal --}}
<div class="modal fade" id="clauseModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Contract Clause</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="clauseForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="clauseTitle" class="form-label">Clause Title <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="clauseTitle" name="title" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="clauseCategory" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select" id="clauseCategory" name="category" required>
                                            @foreach($categories as $key => $category)
                                                <option value="{{ $key }}">{{ $category['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="clauseType" class="form-label">Type</label>
                                        <select class="form-select" id="clauseType" name="type">
                                            <option value="standard">Standard</option>
                                            <option value="legal">Legal</option>
                                            <option value="compliance">Compliance</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sortOrder" class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" id="sortOrder" name="sort_order" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="clauseDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="clauseDescription" name="description" rows="2" 
                                          placeholder="Brief description of this clause..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="clauseContent" class="form-label">Clause Content <span class="text-danger">*</span></label>
                                <div id="clauseEditor" style="height: 300px; border: 1px solid #ddd;"></div>
                                <div class="form-text">Use variables like {{{client.name}}}, {{{contract.value}}}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Variables Used in This Clause</label>
                                <div id="clauseVariables">
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="text" class="form-control" placeholder="Variable name (e.g., client.name)" name="variable_name[]">
                                        <input type="text" class="form-control" placeholder="Description" name="variable_description[]">
                                        <button class="btn btn-outline-danger" type="button" onclick="removeVariable(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariable()">
                                    <i class="fas fa-plus"></i> Add Variable
                                </button>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Clause Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isRequired" name="is_required">
                                            <label class="form-check-label" for="isRequired">
                                                Required Clause
                                            </label>
                                            <div class="form-text">Must be included in contracts</div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                            <label class="form-check-label" for="isActive">
                                                Active
                                            </label>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label class="form-label">Inclusion Conditions</label>
                                        <div class="form-text mb-2">When should this clause be included?</div>
                                        <div id="clauseConditions">
                                            <div class="condition-group mb-2 p-2 border rounded">
                                                <div class="row g-2">
                                                    <div class="col-4">
                                                        <select class="form-select form-select-sm" name="condition_field[]">
                                                            <option value="">Select field...</option>
                                                            <option value="contract.type">Contract Type</option>
                                                            <option value="contract.value">Contract Value</option>
                                                            <option value="client.industry">Client Industry</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-3">
                                                        <select class="form-select form-select-sm" name="condition_operator[]">
                                                            <option value="=">=</option>
                                                            <option value="!=">!=</option>
                                                            <option value="in">In</option>
                                                            <option value="contains">Contains</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-4">
                                                        <input type="text" class="form-control form-control-sm" 
                                                               placeholder="Value" name="condition_value[]">
                                                    </div>
                                                    <div class="col-1">
                                                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="removeCondition(this)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCondition()">
                                            <i class="fas fa-plus"></i> Add Condition
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div id="clausePreview" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #f9f9f9; font-size: 12px;">
                                        <p class="text-muted">Clause preview will appear here as you type...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-warning" id="submitForReview">Submit for Review</button>
                    <button type="submit" class="btn btn-primary">Save Clause</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Clause Modal --}}
<div class="modal fade" id="viewClauseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Clause</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewClauseContent">
                    <!-- Clause content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editFromView">Edit Clause</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js" 
        integrity="sha512-XdRsNSqN3eU4jdOEL7Rg3SHQ5GIhGcXcRWDVlKuwJxmLc3nJgjUqPCAIKqNYqRGfaEBglmyfmQ0r3VHXVR7dNg==" 
        crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Rich Text Editor
    let quill = new Quill('#clauseEditor', {
        theme: 'snow',
        placeholder: 'Enter your clause content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['clean']
            ]
        }
    });

    // Update preview as user types
    quill.on('text-change', function() {
        updateClausePreview();
    });

    function updateClausePreview() {
        const content = quill.root.innerHTML;
        const previewDiv = document.getElementById('clausePreview');
        
        // Replace some common variables with sample data for preview
        let previewContent = content
            .replace(/\{\{client\.name\}\}/g, 'Sample Client Corp')
            .replace(/\{\{contract\.value\}\}/g, '$5,000.00')
            .replace(/\{\{company\.name\}\}/g, 'Your Company');

        previewDiv.innerHTML = previewContent || '<p class="text-muted">Clause preview will appear here as you type...</p>';
    }

    // Category toggle
    document.querySelectorAll('.category-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const content = this.closest('.clause-category').querySelector('.category-content');
            const icon = this.querySelector('i');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                icon.className = 'fas fa-chevron-down';
            } else {
                content.style.display = 'none';
                icon.className = 'fas fa-chevron-right';
            }
        });
    });

    // Form submission
    document.getElementById('clauseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('content', quill.root.innerHTML);
        
        // Collect variables
        const variables = {};
        const variableNames = document.querySelectorAll('input[name="variable_name[]"]');
        const variableDescriptions = document.querySelectorAll('input[name="variable_description[]"]');
        
        for (let i = 0; i < variableNames.length; i++) {
            if (variableNames[i].value && variableDescriptions[i].value) {
                variables[variableNames[i].value] = variableDescriptions[i].value;
            }
        }
        formData.append('variables', JSON.stringify(variables));
        
        // Collect conditions
        const conditions = [];
        const conditionFields = document.querySelectorAll('select[name="condition_field[]"]');
        const conditionOperators = document.querySelectorAll('select[name="condition_operator[]"]');
        const conditionValues = document.querySelectorAll('input[name="condition_value[]"]');
        
        for (let i = 0; i < conditionFields.length; i++) {
            if (conditionFields[i].value) {
                conditions.push({
                    field: conditionFields[i].value,
                    operator: conditionOperators[i].value,
                    value: conditionValues[i].value
                });
            }
        }
        formData.append('conditions', JSON.stringify(conditions));
        
        fetch('/admin/contract-clauses', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error creating clause: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating clause');
        });
    });

    // Create MSP clauses
    document.getElementById('createMSPClauses').addEventListener('click', function() {
        if (confirm('This will create standard MSP contract clauses. Continue?')) {
            fetch('/admin/contract-clauses/create-msp-defaults', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('MSP clauses created successfully!');
                    location.reload();
                } else {
                    alert('Error creating MSP clauses: ' + data.message);
                }
            });
        }
    });

    // Bulk import
    document.getElementById('bulkImport').addEventListener('click', function() {
        if (confirm('This will import a comprehensive contract clause library. Continue?')) {
            fetch('/admin/contract-clauses/import-standard-library', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Standard clause library imported successfully!');
                    location.reload();
                } else {
                    alert('Error importing library: ' + data.message);
                }
            });
        }
    });
});

// Helper functions
function addVariable() {
    const container = document.getElementById('clauseVariables');
    const newVar = document.createElement('div');
    newVar.className = 'input-group input-group-sm mb-2';
    newVar.innerHTML = `
        <input type="text" class="form-control" placeholder="Variable name (e.g., client.name)" name="variable_name[]">
        <input type="text" class="form-control" placeholder="Description" name="variable_description[]">
        <button class="btn btn-outline-danger" type="button" onclick="removeVariable(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(newVar);
}

function removeVariable(button) {
    button.closest('.input-group').remove();
}

function addCondition() {
    const container = document.getElementById('clauseConditions');
    const newCondition = document.createElement('div');
    newCondition.className = 'condition-group mb-2 p-2 border rounded';
    newCondition.innerHTML = `
        <div class="row g-2">
            <div class="col-4">
                <select class="form-select form-select-sm" name="condition_field[]">
                    <option value="">Select field...</option>
                    <option value="contract.type">Contract Type</option>
                    <option value="contract.value">Contract Value</option>
                    <option value="client.industry">Client Industry</option>
                </select>
            </div>
            <div class="col-3">
                <select class="form-select form-select-sm" name="condition_operator[]">
                    <option value="=">=</option>
                    <option value="!=">!=</option>
                    <option value="in">In</option>
                    <option value="contains">Contains</option>
                </select>
            </div>
            <div class="col-4">
                <input type="text" class="form-control form-control-sm" 
                       placeholder="Value" name="condition_value[]">
            </div>
            <div class="col-1">
                <button class="btn btn-sm btn-outline-danger" type="button" onclick="removeCondition(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newCondition);
}

function removeCondition(button) {
    button.closest('.condition-group').remove();
}
</script>
@endpush

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
.clause-category .category-content {
    display: block;
}
.clause-item:hover {
    background-color: #f8f9fa;
}
.clause-icon {
    width: 20px;
    text-align: center;
}
#clauseEditor {
    background: white;
}
.ql-editor {
    min-height: 250px;
}
</style>
@endpush