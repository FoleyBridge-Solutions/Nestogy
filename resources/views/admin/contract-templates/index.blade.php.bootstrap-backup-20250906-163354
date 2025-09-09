@extends('layouts.app')

@section('title', 'Contract Templates')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Contract Templates</h1>
                    <p class="text-muted">Create and manage contract document templates</p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary me-2" id="importTemplateBtn">
                        <i class="fas fa-upload"></i> Import Template
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                        <i class="fas fa-plus"></i> Create Template
                    </button>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $statistics['total_templates'] }}</h5>
                                    <p class="card-text">Total Templates</p>
                                </div>
                                <i class="fas fa-file-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $statistics['active_templates'] }}</h5>
                                    <p class="card-text">Active Templates</p>
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
                                    <h5 class="card-title">{{ $statistics['contracts_generated'] }}</h5>
                                    <p class="card-text">Contracts Generated</p>
                                </div>
                                <i class="fas fa-file-contract fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">{{ $statistics['pending_review'] }}</h5>
                                    <p class="card-text">Pending Review</p>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Contract Templates</h5>
                                <div class="d-flex gap-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-sm" id="searchTemplates" 
                                               placeholder="Search templates...">
                                        <button class="btn btn-outline-secondary btn-sm" type="button">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <select class="form-select form-select-sm" id="filterCategory">
                                        <option value="">All Categories</option>
                                        <option value="service">Service Agreements</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="licensing">Licensing</option>
                                        <option value="consulting">Consulting</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Template</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Version</th>
                                            <th>Usage</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="templatesTable">
                                        @forelse($templates as $template)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="template-icon me-3">
                                                            <i class="fas fa-file-alt text-primary fa-2x"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $template->name }}</div>
                                                            <div class="text-muted small">{{ Str::limit($template->description, 50) }}</div>
                                                            <div class="small text-info">{{ $template->contract_type }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ ucfirst($template->category) }}</span>
                                                    @if($template->is_default)
                                                        <span class="badge bg-primary ms-1">Default</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $template->status === 'active' ? 'success' : ($template->status === 'draft' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($template->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="font-monospace">v{{ $template->version }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $template->usage_count ?? 0 }}</span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary preview-template" 
                                                                data-template-id="{{ $template->id }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary edit-template" 
                                                                data-template-id="{{ $template->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-success clone-template" 
                                                                data-template-id="{{ $template->id }}">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" data-bs-toggle="dropdown">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#" data-action="export" data-template-id="{{ $template->id }}">
                                                                    <i class="fas fa-download me-2"></i>Export
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" data-action="version" data-template-id="{{ $template->id }}">
                                                                    <i class="fas fa-code-branch me-2"></i>Version History
                                                                </a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" data-action="delete" data-template-id="{{ $template->id }}">
                                                                    <i class="fas fa-trash me-2"></i>Delete
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="empty-state">
                                                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                        <h5 class="text-muted">No Contract Templates Found</h5>
                                                        <p class="text-muted">Create your first contract template to get started.</p>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                                                            <i class="fas fa-plus"></i> Create Template
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary" id="createMSPTemplate">
                                    <i class="fas fa-server"></i> Create MSP Service Agreement
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="createMaintenanceTemplate">
                                    <i class="fas fa-tools"></i> Create Maintenance Contract
                                </button>
                                <button type="button" class="btn btn-outline-info" id="createConsultingTemplate">
                                    <i class="fas fa-handshake"></i> Create Consulting Agreement
                                </button>
                                <button type="button" class="btn btn-outline-success" id="importStandardTemplates">
                                    <i class="fas fa-magic"></i> Import Standard Templates
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Template Variables</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Available variables for use in templates:</p>
                            
                            <div class="accordion accordion-flush" id="variablesAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#companyVars">
                                            Company Variables
                                        </button>
                                    </h2>
                                    <div id="companyVars" class="accordion-collapse collapse" data-bs-parent="#variablesAccordion">
                                        <div class="accordion-body py-2">
                                            <div class="small">
                                                <code>{{company.name}}</code><br>
                                                <code>{{company.address}}</code><br>
                                                <code>{{company.phone}}</code><br>
                                                <code>{{company.email}}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#clientVars">
                                            Client Variables
                                        </button>
                                    </h2>
                                    <div id="clientVars" class="accordion-collapse collapse" data-bs-parent="#variablesAccordion">
                                        <div class="accordion-body py-2">
                                            <div class="small">
                                                <code>{{client.name}}</code><br>
                                                <code>{{client.contact_name}}</code><br>
                                                <code>{{client.address}}</code><br>
                                                <code>{{client.email}}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#contractVars">
                                            Contract Variables
                                        </button>
                                    </h2>
                                    <div id="contractVars" class="accordion-collapse collapse" data-bs-parent="#variablesAccordion">
                                        <div class="accordion-body py-2">
                                            <div class="small">
                                                <code>{{contract.name}}</code><br>
                                                <code>{{contract.start_date}}</code><br>
                                                <code>{{contract.end_date}}</code><br>
                                                <code>{{contract.value}}</code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create/Edit Template Modal --}}
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Contract Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="templateForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="templateName" class="form-label">Template Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="templateName" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="contractType" class="form-label">Contract Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="contractType" name="contract_type" required>
                                            <option value="">Select contract type...</option>
                                            @foreach($contractTypes as $type)
                                                <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category">
                                            <option value="general">General</option>
                                            <option value="service">Service Agreement</option>
                                            <option value="maintenance">Maintenance Contract</option>
                                            <option value="licensing">Software License</option>
                                            <option value="consulting">Consulting Agreement</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="version" class="form-label">Version</label>
                                        <input type="text" class="form-control" id="version" name="version" value="1.0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="Brief description of this template..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="templateContent" class="form-label">Template Content <span class="text-danger">*</span></label>
                                <div id="templateEditor" style="height: 400px; border: 1px solid #ddd;"></div>
                                <div class="form-text">Use variables like {{client.name}}, {{company.name}}, {{contract.value}}</div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Template Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft">Draft</option>
                                            <option value="active">Active</option>
                                            <option value="archived">Archived</option>
                                        </select>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="isDefault" name="is_default">
                                        <label class="form-check-label" for="isDefault">
                                            Default Template
                                        </label>
                                        <div class="form-text">Use as default for this contract type</div>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label class="form-label">Associated Clauses</label>
                                        <div id="clauseSelection" style="max-height: 200px; overflow-y: auto;">
                                            @foreach($clauses as $clause)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="clause_{{ $clause->id }}" name="clauses[]" value="{{ $clause->id }}">
                                                    <label class="form-check-label small" for="clause_{{ $clause->id }}">
                                                        {{ $clause->title }}
                                                        <div class="text-muted">{{ $clause->category }}</div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div id="templatePreview" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #f9f9f9; font-size: 12px;">
                                        <p class="text-muted">Template preview will appear here as you type...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" id="previewTemplateBtn">Preview</button>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Template Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="fullPreviewContent" style="min-height: 400px; padding: 20px; background: white; border: 1px solid #ddd;">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="downloadPreviewPDF">Download PDF</button>
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
    let quill = new Quill('#templateEditor', {
        theme: 'snow',
        placeholder: 'Enter your contract template content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    // Update preview as user types
    quill.on('text-change', function() {
        updatePreview();
    });

    function updatePreview() {
        const content = quill.root.innerHTML;
        const previewDiv = document.getElementById('templatePreview');
        
        // Replace some common variables with sample data for preview
        let previewContent = content
            .replace(/\{\{company\.name\}\}/g, 'Your Company Name')
            .replace(/\{\{client\.name\}\}/g, 'Sample Client Corp')
            .replace(/\{\{contract\.value\}\}/g, '$5,000.00')
            .replace(/\{\{contract\.start_date\}\}/g, new Date().toLocaleDateString())
            .replace(/\{\{contract\.end_date\}\}/g, new Date(Date.now() + 365*24*60*60*1000).toLocaleDateString());

        previewDiv.innerHTML = previewContent || '<p class="text-muted">Template preview will appear here as you type...</p>';
    }

    // Form submission
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('content', quill.root.innerHTML);
        
        fetch('/admin/contract-templates', {
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
                alert('Error creating template: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating template');
        });
    });

    // Quick template creation
    document.getElementById('createMSPTemplate').addEventListener('click', function() {
        createQuickTemplate('msp');
    });

    function createQuickTemplate(type) {
        const templates = {
            msp: {
                name: 'MSP Service Agreement',
                category: 'service',
                content: `
                    <h2>MANAGED SERVICE PROVIDER AGREEMENT</h2>
                    <p><strong>Effective Date:</strong> {{contract.start_date}}</p>
                    
                    <p>This Managed Service Provider Agreement ("Agreement") is entered into between:</p>
                    <ul>
                        <li><strong>Service Provider:</strong> {{company.name}}<br>
                            Address: {{company.address}}<br>
                            Phone: {{company.phone}}<br>
                            Email: {{company.email}}
                        </li>
                        <li><strong>Client:</strong> {{client.name}}<br>
                            Contact: {{client.contact_name}}<br>
                            Address: {{client.address}}<br>
                            Email: {{client.contact_email}}
                        </li>
                    </ul>

                    <h3>1. SERVICES PROVIDED</h3>
                    <p>Service Provider will provide the following managed services:</p>
                    {{#each services}}
                    <ul><li>{{name}}: {{description}}</li></ul>
                    {{/each}}

                    <h3>2. PAYMENT TERMS</h3>
                    <p>Client agrees to pay {{contract.value}} {{billing.frequency}} with payment due {{billing.due_date}} days from invoice date.</p>

                    <h3>3. TERM</h3>
                    <p>This Agreement shall commence on {{contract.start_date}} and continue until {{contract.end_date}}, unless terminated earlier in accordance with the terms herein.</p>
                `
            }
        };

        const template = templates[type];
        if (template) {
            document.getElementById('templateName').value = template.name;
            document.getElementById('category').value = template.category;
            quill.root.innerHTML = template.content;
            updatePreview();
            
            const modal = new bootstrap.Modal(document.getElementById('templateModal'));
            modal.show();
        }
    }

    // Import standard templates
    document.getElementById('importStandardTemplates').addEventListener('click', function() {
        if (confirm('This will import standard MSP contract templates. Continue?')) {
            fetch('/admin/contract-templates/import-standards', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Standard templates imported successfully!');
                    location.reload();
                } else {
                    alert('Error importing templates: ' + data.message);
                }
            });
        }
    });

    // Preview template
    document.getElementById('previewTemplateBtn').addEventListener('click', function() {
        const content = quill.root.innerHTML;
        document.getElementById('fullPreviewContent').innerHTML = content
            .replace(/\{\{company\.name\}\}/g, '{{company.name}}')
            .replace(/\{\{client\.name\}\}/g, 'Sample Client Corporation')
            .replace(/\{\{contract\.value\}\}/g, '$5,000.00 monthly');

        const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        previewModal.show();
    });
});
</script>
@endpush

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
.template-icon {
    opacity: 0.7;
}
.empty-state {
    padding: 2rem;
}
#templateEditor {
    background: white;
}
.ql-editor {
    min-height: 300px;
}
</style>
@endpush