@extends('layouts.app')

@section('title', 'Contract Action Buttons')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Contract Action Buttons</h1>
                    <p class="text-muted">Configure custom action buttons for contract management</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#actionButtonModal">
                        <i class="fas fa-plus"></i> Add Action Button
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="createDefaultButtons">
                        <i class="fas fa-magic"></i> Create Default Buttons
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Action Buttons</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enableSorting">
                            <label class="form-check-label" for="enableSorting">
                                Enable Sorting
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="actionButtonsList" class="list-group list-group-flush">
                        @forelse($actionButtons as $button)
                            <div class="list-group-item d-flex justify-content-between align-items-start" 
                                 data-button-id="{{ $button->id }}">
                                <div class="ms-2 me-auto">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-grip-vertical text-muted me-2 sort-handle" style="cursor: move; display: none;"></i>
                                        @if($button->icon)
                                            <i class="{{ $button->icon }} me-2"></i>
                                        @endif
                                        <h6 class="mb-0">{{ $button->label }}</h6>
                                        @if(!$button->is_active)
                                            <span class="badge bg-secondary ms-2">Inactive</span>
                                        @endif
                                    </div>
                                    <p class="mb-1 text-muted small">
                                        Type: <code>{{ $button->action_type }}</code> | 
                                        Slug: <code>{{ $button->slug }}</code>
                                        @if($button->permissions)
                                            | Permissions: <code>{{ implode(', ', $button->permissions) }}</code>
                                        @endif
                                    </p>
                                    @if($button->visibility_conditions)
                                        <div class="small text-info">
                                            <i class="fas fa-eye me-1"></i>
                                            {{ count($button->visibility_conditions) }} visibility condition(s)
                                        </div>
                                    @endif
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-button" 
                                            data-button-id="{{ $button->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary preview-button"
                                            data-button-id="{{ $button->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-button"
                                            data-button-id="{{ $button->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Action Buttons Configured</h5>
                                <p class="text-muted">Get started by creating default action buttons or adding custom ones.</p>
                                <button type="button" class="btn btn-primary" id="createDefaultButtonsEmpty">
                                    <i class="fas fa-magic"></i> Create Default Buttons
                                </button>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Action Button Modal --}}
<div class="modal fade" id="actionButtonModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Action Button</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="actionButtonForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="label" class="form-label">Button Label</label>
                                <input type="text" class="form-control" id="label" name="label" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug" name="slug" required>
                                <div class="form-text">URL-friendly identifier (auto-generated from label)</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="icon" class="form-label">Icon</label>
                                <input type="text" class="form-control" id="icon" name="icon" 
                                       placeholder="fas fa-eye">
                                <div class="form-text">FontAwesome icon class</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="button_class" class="form-label">Button Classes</label>
                                <select class="form-select" id="button_class" name="button_class">
                                    <option value="btn btn-primary btn-sm">Primary Small</option>
                                    <option value="btn btn-secondary btn-sm">Secondary Small</option>
                                    <option value="btn btn-success btn-sm">Success Small</option>
                                    <option value="btn btn-warning btn-sm">Warning Small</option>
                                    <option value="btn btn-danger btn-sm">Danger Small</option>
                                    <option value="btn btn-outline-primary btn-sm">Outline Primary</option>
                                    <option value="btn btn-outline-secondary btn-sm">Outline Secondary</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="action_type" class="form-label">Action Type</label>
                        <select class="form-select" id="action_type" name="action_type" required>
                            <option value="">Select action type...</option>
                            <option value="route">Navigate to Route</option>
                            <option value="ajax">AJAX Request</option>
                            <option value="modal">Show Modal</option>
                            <option value="status_change">Change Status</option>
                            <option value="download">Download File</option>
                        </select>
                    </div>

                    {{-- Dynamic action configuration will be inserted here --}}
                    <div id="actionConfigSection"></div>

                    <div class="mb-3">
                        <label for="confirmation_message" class="form-label">Confirmation Message</label>
                        <input type="text" class="form-control" id="confirmation_message" name="confirmation_message"
                               placeholder="Optional confirmation prompt">
                    </div>

                    {{-- Visibility Conditions --}}
                    <div class="mb-3">
                        <label for="visibilityConditions" class="form-label">Visibility Conditions</label>
                        <div id="visibilityConditions">
                            <div class="text-muted small mb-2">Button will only show when all conditions are met</div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addCondition">
                                <i class="fas fa-plus"></i> Add Condition
                            </button>
                        </div>
                    </div>

                    {{-- Permissions --}}
                    <div class="mb-3">
                        <label for="permissions" class="form-label">Required Permissions</label>
                        <select class="form-select" id="permissions" name="permissions[]" multiple>
                            <option value="view">View</option>
                            <option value="update">Update</option>
                            <option value="delete">Delete</option>
                            <option value="manage">Manage</option>
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple permissions</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="0" min="0" step="10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Action Button</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Button Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" 
        integrity="sha512-Eezs+g9Lq4TCCq0wae01s9PuNWzHYoCMkE97e2qdkYthpI0pzC3UGB03lgEHn2XM85hDNKVvNiMU63mg9JuM8w==" 
        crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let sortable = null;
    
    // Enable/disable sorting
    const sortingToggle = document.getElementById('enableSorting');
    const sortHandles = document.querySelectorAll('.sort-handle');
    
    sortingToggle.addEventListener('change', function() {
        if (this.checked) {
            enableSorting();
        } else {
            disableSorting();
        }
    });
    
    function enableSorting() {
        sortHandles.forEach(handle => handle.style.display = 'inline-block');
        const container = document.getElementById('actionButtonsList');
        sortable = Sortable.create(container, {
            handle: '.sort-handle',
            animation: 150,
            onEnd: function(evt) {
                const buttonIds = Array.from(container.children).map(item => 
                    item.dataset.buttonId
                );
                
                fetch('{{ route("admin.contract-actions.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ button_order: buttonIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Button order updated successfully', 'success');
                    }
                });
            }
        });
    }
    
    function disableSorting() {
        sortHandles.forEach(handle => handle.style.display = 'none');
        if (sortable) {
            sortable.destroy();
            sortable = null;
        }
    }
    
    // Auto-generate slug from label
    document.getElementById('label').addEventListener('input', function() {
        const slug = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-');
        document.getElementById('slug').value = slug;
    });
    
    // Handle action type change
    document.getElementById('action_type').addEventListener('change', function() {
        generateActionConfigSection(this.value);
    });
    
    function generateActionConfigSection(actionType) {
        const container = document.getElementById('actionConfigSection');
        
        switch (actionType) {
            case 'route':
                container.innerHTML = `
                    <div class="mb-3">
                        <label for="config_route" class="form-label">Route Name</label>
                        <input type="text" class="form-control" id="config_route" name="config[route]" 
                               placeholder="contracts.show">
                    </div>
                    <div class="mb-3">
                        <label for="config_parameters" class="form-label">Route Parameters</label>
                        <input type="text" class="form-control" id="config_parameters" name="config[parameters]" 
                               placeholder="{contract.id}" value="{contract.id}">
                        <div class="form-text">Use {contract.field} for dynamic values</div>
                    </div>
                `;
                break;
                
            case 'ajax':
                container.innerHTML = `
                    <div class="mb-3">
                        <label for="config_url" class="form-label">AJAX URL</label>
                        <input type="text" class="form-control" id="config_url" name="config[url]" 
                               placeholder="/contracts/{contract.id}/action">
                    </div>
                    <div class="mb-3">
                        <label for="config_method" class="form-label">HTTP Method</label>
                        <select class="form-select" id="config_method" name="config[method]">
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'status_change':
                container.innerHTML = `
                    <div class="mb-3">
                        <label for="config_status" class="form-label">New Status</label>
                        <input type="text" class="form-control" id="config_status" name="config[status]" 
                               placeholder="active">
                    </div>
                `;
                break;
                
            case 'download':
                container.innerHTML = `
                    <div class="mb-3">
                        <label for="config_download_url" class="form-label">Download URL</label>
                        <input type="text" class="form-control" id="config_download_url" name="config[download_url]" 
                               placeholder="/contracts/{contract.id}/download">
                    </div>
                `;
                break;
                
            case 'modal':
                container.innerHTML = `
                    <div class="mb-3">
                        <label for="config_modal_title" class="form-label">Modal Title</label>
                        <input type="text" class="form-control" id="config_modal_title" name="config[modal][title]" 
                               placeholder="Confirm Action">
                    </div>
                    <div class="mb-3">
                        <label for="config_modal_action" class="form-label">Form Action URL</label>
                        <input type="text" class="form-control" id="config_modal_action" name="config[modal][form_action]" 
                               placeholder="/contracts/{contract.id}/action">
                    </div>
                `;
                break;
                
            default:
                container.innerHTML = '';
        }
    }
    
    // Handle visibility conditions
    let conditionIndex = 0;
    document.getElementById('addCondition').addEventListener('click', function() {
        addVisibilityCondition();
    });
    
    function addVisibilityCondition() {
        const container = document.getElementById('visibilityConditions');
        const conditionDiv = document.createElement('div');
        conditionDiv.className = 'border rounded p-3 mb-2';
        conditionDiv.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Field</label>
                    <input type="text" class="form-control" name="visibility_conditions[${conditionIndex}][field]" 
                           placeholder="status">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operator</label>
                    <select class="form-select" name="visibility_conditions[${conditionIndex}][operator]">
                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value="in">In</option>
                        <option value="not_in">Not In</option>
                        <option value="exists">Exists</option>
                        <option value="not_exists">Not Exists</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Value</label>
                    <input type="text" class="form-control" name="visibility_conditions[${conditionIndex}][value]" 
                           placeholder="active">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-outline-danger d-block remove-condition">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        const addButton = document.getElementById('addCondition');
        container.insertBefore(conditionDiv, addButton);
        
        conditionDiv.querySelector('.remove-condition').addEventListener('click', function() {
            conditionDiv.remove();
        });
        
        conditionIndex++;
    }
    
    // Handle form submission
    document.getElementById('actionButtonForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            if (key.includes('[') && key.includes(']')) {
                // Handle nested keys
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
        
        fetch('{{ route("admin.contract-actions.store") }}', {
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
                showAlert(data.message || 'Error creating action button', 'error');
            }
        });
    });
    
    // Create default buttons
    document.getElementById('createDefaultButtons')?.addEventListener('click', createDefaultButtons);
    document.getElementById('createDefaultButtonsEmpty')?.addEventListener('click', createDefaultButtons);
    
    function createDefaultButtons() {
        if (!confirm('This will create default action buttons for contract management. Continue?')) {
            return;
        }
        
        fetch('{{ route("admin.contract-actions.create-defaults") }}', {
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
                showAlert(data.message || 'Error creating default buttons', 'error');
            }
        });
    }
    
    function showAlert(message, type) {
        // Implementation for showing alerts
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