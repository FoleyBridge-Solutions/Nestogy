@extends('layouts.app')

@section('title', 'Contract Form Designer')

@section('content')
<div class="contract-form-designer">
    <div class="row">
        {{-- Designer Header --}}
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Contract Form Designer</h2>
                    <p class="text-muted mb-0">Design and configure dynamic contract forms</p>
                </div>
                <div class="form-actions">
                    <button class="btn btn-outline-secondary me-2" id="preview-form">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button class="btn btn-outline-success me-2" id="save-draft">
                        <i class="fas fa-save"></i> Save Draft
                    </button>
                    <button class="btn btn-success" id="publish-form">
                        <i class="fas fa-rocket"></i> Publish Form
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Form Configuration Panel --}}
        <div class="col-md-3">
            <div class="design-sidebar">
                {{-- Basic Configuration --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cog"></i> Form Configuration
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="form-config">
                            <div class="mb-3">
                                <label class="form-label">Form Name</label>
                                <input type="text" class="form-control" id="form-name" placeholder="Enter form name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contract Type</label>
                                <select class="form-select" id="contract-type" required>
                                    <option value="">Select contract type...</option>
                                    <option value="service_agreement">Service Agreement</option>
                                    <option value="maintenance_contract">Maintenance Contract</option>
                                    <option value="support_contract">Support Contract</option>
                                    <option value="custom">Custom Contract</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="form-description" rows="3" 
                                          placeholder="Brief description of this form"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="form-status">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="allow-client-access">
                                <label class="form-check-label" for="allow-client-access">
                                    Allow client access
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
                
                {{-- Field Components Library --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-puzzle-piece"></i> Field Components
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="component-library">
                            {{-- Basic Fields --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Basic Fields</strong>
                                </div>
                                <div class="component-list">
                                    <div class="component-item" data-type="text">
                                        <i class="fas fa-font"></i> Text Input
                                    </div>
                                    <div class="component-item" data-type="textarea">
                                        <i class="fas fa-align-left"></i> Textarea
                                    </div>
                                    <div class="component-item" data-type="number">
                                        <i class="fas fa-hashtag"></i> Number
                                    </div>
                                    <div class="component-item" data-type="email">
                                        <i class="fas fa-at"></i> Email
                                    </div>
                                    <div class="component-item" data-type="currency">
                                        <i class="fas fa-dollar-sign"></i> Currency
                                    </div>
                                    <div class="component-item" data-type="percentage">
                                        <i class="fas fa-percent"></i> Percentage
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Selection Fields --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Selection Fields</strong>
                                </div>
                                <div class="component-list">
                                    <div class="component-item" data-type="select">
                                        <i class="fas fa-list"></i> Dropdown
                                    </div>
                                    <div class="component-item" data-type="multiselect">
                                        <i class="fas fa-tasks"></i> Multi-select
                                    </div>
                                    <div class="component-item" data-type="radio">
                                        <i class="fas fa-dot-circle"></i> Radio Buttons
                                    </div>
                                    <div class="component-item" data-type="checkbox">
                                        <i class="fas fa-check-square"></i> Checkboxes
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Date & Time --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Date & Time</strong>
                                </div>
                                <div class="component-list">
                                    <div class="component-item" data-type="date">
                                        <i class="fas fa-calendar"></i> Date Picker
                                    </div>
                                    <div class="component-item" data-type="datetime">
                                        <i class="fas fa-calendar-alt"></i> Date Time
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Special Fields --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Special Fields</strong>
                                </div>
                                <div class="component-list">
                                    <div class="component-item" data-type="client-selector">
                                        <i class="fas fa-building"></i> Client Selector
                                    </div>
                                    <div class="component-item" data-type="user-selector">
                                        <i class="fas fa-user"></i> User Selector
                                    </div>
                                    <div class="component-item" data-type="asset-selector">
                                        <i class="fas fa-server"></i> Asset Selector
                                    </div>
                                    <div class="component-item" data-type="file">
                                        <i class="fas fa-file-upload"></i> File Upload
                                    </div>
                                    <div class="component-item" data-type="json">
                                        <i class="fas fa-code"></i> JSON Editor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Form Layout Options --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-th-large"></i> Layout Options
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Form Layout</label>
                            <select class="form-select" id="form-layout">
                                <option value="single-column">Single Column</option>
                                <option value="two-column" selected>Two Column</option>
                                <option value="three-column">Three Column</option>
                                <option value="custom">Custom Grid</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Form Style</label>
                            <select class="form-select" id="form-style">
                                <option value="standard">Standard</option>
                                <option value="compact">Compact</option>
                                <option value="spacious">Spacious</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="show-progress" checked>
                            <label class="form-check-label" for="show-progress">
                                Show progress indicator
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="enable-validation">
                            <label class="form-check-label" for="enable-validation">
                                Enable client-side validation
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="auto-save">
                            <label class="form-check-label" for="auto-save">
                                Auto-save draft
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Form Builder Canvas --}}
        <div class="col-md-9">
            <div class="form-builder-canvas">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-paint-brush"></i> Form Builder
                        </h6>
                        <div class="canvas-actions">
                            <button class="btn btn-outline-secondary btn-sm me-2" id="clear-form">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="add-section">
                                <i class="fas fa-plus"></i> Add Section
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Drop Zone --}}
                        <div class="form-drop-zone" id="form-canvas">
                            <div class="empty-canvas-message">
                                <div class="text-center py-5">
                                    <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Start Building Your Form</h5>
                                    <p class="text-muted">
                                        Drag and drop components from the left panel to build your form.
                                        <br>You can also click "Add Section" to organize your fields.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Field Configuration Modal --}}
<div class="modal fade" id="fieldConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configure Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="field-config-form">
                    <div class="row">
                        {{-- Basic Configuration --}}
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Basic Settings</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Field Label</label>
                                <input type="text" class="form-control" name="label" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Field Key</label>
                                <input type="text" class="form-control" name="field_slug" required>
                                <small class="form-text text-muted">Used for database storage (auto-generated from label)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Placeholder</label>
                                <input type="text" class="form-control" name="placeholder">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Help Text</label>
                                <textarea class="form-control" name="help_text" rows="2"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_required">
                                        <label class="form-check-label">Required</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_readonly">
                                        <label class="form-check-label">Read Only</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Field-Specific Configuration --}}
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Field Options</h6>
                            <div id="field-specific-config">
                                {{-- Dynamic content based on field type --}}
                            </div>
                        </div>
                        
                        {{-- Layout Configuration --}}
                        <div class="col-12 mt-3">
                            <h6 class="border-bottom pb-2 mb-3">Layout & Display</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Column Width</label>
                                    <select class="form-select" name="column_width">
                                        <option value="col-12">Full Width</option>
                                        <option value="col-md-6" selected>Half Width</option>
                                        <option value="col-md-4">One Third</option>
                                        <option value="col-md-3">One Quarter</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" class="form-control" name="sort_order" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Section</label>
                                    <select class="form-select" name="section_id">
                                        <option value="">No Section</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Conditional Logic --}}
                        <div class="col-12 mt-3">
                            <h6 class="border-bottom pb-2 mb-3">Conditional Logic</h6>
                            <div class="conditional-rules" id="conditional-rules">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-condition">
                                    <i class="fas fa-plus"></i> Add Condition
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-field-config">Save Field</button>
            </div>
        </div>
    </div>
</div>

{{-- Section Configuration Modal --}}
<div class="modal fade" id="sectionConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configure Section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="section-config-form">
                    <div class="mb-3">
                        <label class="form-label">Section Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="text" class="form-control" name="icon" placeholder="fas fa-info-circle">
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_collapsible">
                                <label class="form-check-label">Collapsible</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_expanded">
                                <label class="form-check-label">Expanded by Default</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-section-config">Save Section</button>
            </div>
        </div>
    </div>
</div>

{{-- Form Preview Modal --}}
<div class="modal fade" id="formPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="form-preview-container">
                    {{-- Preview content will be loaded here --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="test-form">Test Form</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js">
<style>
    .contract-form-designer {
        min-height: calc(100vh - 200px);
    }
    
    .design-sidebar {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }
    
    .component-library {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .component-category {
        border-bottom: 1px solid #e9ecef;
    }
    
    .category-header {
        padding: 0.75rem 1rem 0.5rem;
        background-color: #f8f9fa;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .component-list {
        padding: 0.5rem 0;
    }
    
    .component-item {
        padding: 0.75rem 1rem;
        cursor: grab;
        border-bottom: 1px solid #f1f3f4;
        transition: all 0.2s ease;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .component-item:hover {
        background-color: #e3f2fd;
        transform: translateX(4px);
    }
    
    .component-item:active {
        cursor: grabbing;
    }
    
    .component-item i {
        color: #1976d2;
        width: 16px;
    }
    
    .form-builder-canvas {
        min-height: 600px;
    }
    
    .form-drop-zone {
        min-height: 500px;
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .form-drop-zone.drag-over {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .empty-canvas-message {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
    }
    
    .form-section {
        margin-bottom: 2rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        background: white;
    }
    
    .section-header {
        padding: 1rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        display: flex;
        justify-content-between;
        align-items: center;
    }
    
    .section-content {
        padding: 1rem;
        min-height: 100px;
    }
    
    .field-item {
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        background: white;
        position: relative;
        transition: all 0.2s ease;
    }
    
    .field-item:hover {
        border-color: #1976d2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .field-item.selected {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .field-controls {
        position: absolute;
        top: -10px;
        right: -10px;
        display: none;
        gap: 0.25rem;
    }
    
    .field-item:hover .field-controls,
    .field-item.selected .field-controls {
        display: flex;
    }
    
    .field-control-btn {
        width: 24px;
        height: 24px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    .field-preview {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .sortable-ghost {
        opacity: 0.4;
        background-color: #e3f2fd !important;
    }
    
    .sortable-chosen {
        background-color: #e3f2fd !important;
    }
    
    .conditional-rule {
        padding: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
        background-color: #f8f9fa;
    }
    
    .conditional-rule .row {
        align-items: end;
    }
    
    .drag-placeholder {
        height: 60px;
        border: 2px dashed #1976d2;
        border-radius: 0.375rem;
        background-color: #e3f2fd;
        margin: 0.5rem 0;
        display: none;
    }
    
    .drag-placeholder.active {
        display: block;
    }
    
    /* Field type specific icons */
    .field-item[data-type="text"] .field-icon { color: #2196f3; }
    .field-item[data-type="email"] .field-icon { color: #ff5722; }
    .field-item[data-type="number"] .field-icon { color: #4caf50; }
    .field-item[data-type="select"] .field-icon { color: #9c27b0; }
    .field-item[data-type="date"] .field-icon { color: #ff9800; }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .design-sidebar {
            position: static;
            max-height: none;
        }
        
        .component-item {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-builder-canvas {
            margin-top: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formCanvas = document.getElementById('form-canvas');
        const componentItems = document.querySelectorAll('.component-item');
        const fieldConfigModal = new bootstrap.Modal(document.getElementById('fieldConfigModal'));
        const sectionConfigModal = new bootstrap.Modal(document.getElementById('sectionConfigModal'));
        const formPreviewModal = new bootstrap.Modal(document.getElementById('formPreviewModal'));
        
        let formData = {
            config: {},
            sections: [],
            fields: []
        };
        let draggedFieldType = null;
        let currentEditingField = null;
        let fieldIdCounter = 1;
        let sectionIdCounter = 1;
        
        // Initialize form designer
        initializeDesigner();
        
        function initializeDesigner() {
            setupDragAndDrop();
            setupEventHandlers();
            setupSortable();
            loadExistingForm();
        }
        
        // Setup drag and drop
        function setupDragAndDrop() {
            // Make component items draggable
            componentItems.forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedFieldType = this.dataset.type;
                    e.dataTransfer.effectAllowed = 'copy';
                });
            });
            
            // Setup drop zone
            formCanvas.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('drag-over');
            });
            
            formCanvas.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            formCanvas.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                if (draggedFieldType) {
                    addFieldToForm(draggedFieldType);
                    draggedFieldType = null;
                }
            });
        }
        
        // Setup event handlers
        function setupEventHandlers() {
            // Form configuration changes
            document.getElementById('form-name').addEventListener('input', updateFormConfig);
            document.getElementById('contract-type').addEventListener('change', updateFormConfig);
            document.getElementById('form-description').addEventListener('input', updateFormConfig);
            document.getElementById('form-status').addEventListener('change', updateFormConfig);
            document.getElementById('allow-client-access').addEventListener('change', updateFormConfig);
            
            // Layout options
            document.getElementById('form-layout').addEventListener('change', updateFormLayout);
            document.getElementById('form-style').addEventListener('change', updateFormStyle);
            
            // Action buttons
            document.getElementById('add-section').addEventListener('click', addSection);
            document.getElementById('clear-form').addEventListener('click', clearForm);
            document.getElementById('preview-form').addEventListener('click', previewForm);
            document.getElementById('save-draft').addEventListener('click', saveDraft);
            document.getElementById('publish-form').addEventListener('click', publishForm);
            
            // Modal handlers
            document.getElementById('save-field-config').addEventListener('click', saveFieldConfig);
            document.getElementById('save-section-config').addEventListener('click', saveSectionConfig);
            document.getElementById('add-condition').addEventListener('click', addConditionalRule);
        }
        
        // Setup sortable functionality
        function setupSortable() {
            new Sortable(formCanvas, {
                group: 'form-builder',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function(evt) {
                    updateFieldOrder();
                }
            });
        }
        
        // Add field to form
        function addFieldToForm(fieldType, fieldData = null) {
            const fieldId = 'field_' + (fieldIdCounter++);
            const field = fieldData || createDefaultField(fieldType, fieldId);
            
            const fieldElement = createFieldElement(field);
            
            // Hide empty message
            const emptyMessage = formCanvas.querySelector('.empty-canvas-message');
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            
            formCanvas.appendChild(fieldElement);
            formData.fields.push(field);
            
            // Open configuration modal for new fields
            if (!fieldData) {
                editField(fieldId);
            }
            
            updateFormPreview();
        }
        
        // Create default field configuration
        function createDefaultField(type, id) {
            const fieldTypes = {
                text: { icon: 'fas fa-font', label: 'Text Field' },
                textarea: { icon: 'fas fa-align-left', label: 'Text Area' },
                number: { icon: 'fas fa-hashtag', label: 'Number Field' },
                email: { icon: 'fas fa-at', label: 'Email Field' },
                currency: { icon: 'fas fa-dollar-sign', label: 'Currency Field' },
                percentage: { icon: 'fas fa-percent', label: 'Percentage Field' },
                select: { icon: 'fas fa-list', label: 'Dropdown Field' },
                multiselect: { icon: 'fas fa-tasks', label: 'Multi-select Field' },
                radio: { icon: 'fas fa-dot-circle', label: 'Radio Buttons' },
                checkbox: { icon: 'fas fa-check-square', label: 'Checkboxes' },
                date: { icon: 'fas fa-calendar', label: 'Date Picker' },
                datetime: { icon: 'fas fa-calendar-alt', label: 'Date Time Field' },
                'client-selector': { icon: 'fas fa-building', label: 'Client Selector' },
                'user-selector': { icon: 'fas fa-user', label: 'User Selector' },
                'asset-selector': { icon: 'fas fa-server', label: 'Asset Selector' },
                file: { icon: 'fas fa-file-upload', label: 'File Upload' },
                json: { icon: 'fas fa-code', label: 'JSON Editor' }
            };
            
            return {
                id: id,
                type: type,
                field_slug: type + '_field_' + Date.now(),
                label: fieldTypes[type]?.label || 'Field',
                placeholder: '',
                help_text: '',
                is_required: false,
                is_readonly: false,
                column_width: 'col-md-6',
                sort_order: formData.fields.length,
                section_id: null,
                ui_config: {},
                options: [],
                validation_rules: [],
                conditional_logic: []
            };
        }
        
        // Create field element for canvas
        function createFieldElement(field) {
            const fieldElement = document.createElement('div');
            fieldElement.className = `field-item ${field.column_width}`;
            fieldElement.dataset.fieldId = field.id;
            fieldElement.dataset.type = field.type;
            
            const fieldTypes = {
                text: 'fas fa-font',
                textarea: 'fas fa-align-left',
                number: 'fas fa-hashtag',
                email: 'fas fa-at',
                currency: 'fas fa-dollar-sign',
                percentage: 'fas fa-percent',
                select: 'fas fa-list',
                multiselect: 'fas fa-tasks',
                radio: 'fas fa-dot-circle',
                checkbox: 'fas fa-check-square',
                date: 'fas fa-calendar',
                datetime: 'fas fa-calendar-alt',
                'client-selector': 'fas fa-building',
                'user-selector': 'fas fa-user',
                'asset-selector': 'fas fa-server',
                file: 'fas fa-file-upload',
                json: 'fas fa-code'
            };
            
            fieldElement.innerHTML = `
                <div class="field-controls">
                    <button class="btn btn-primary btn-sm field-control-btn" onclick="editField('${field.id}')" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm field-control-btn" onclick="duplicateField('${field.id}')" title="Duplicate">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn btn-danger btn-sm field-control-btn" onclick="removeField('${field.id}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="field-preview">
                    <div class="d-flex align-items-center mb-2">
                        <i class="${fieldTypes[field.type] || 'fas fa-question'} field-icon me-2"></i>
                        <strong>${field.label || 'Untitled Field'}</strong>
                        ${field.is_required ? '<span class="badge bg-danger ms-2">Required</span>' : ''}
                    </div>
                    
                    <div class="field-preview-content">
                        ${generateFieldPreview(field)}
                    </div>
                    
                    ${field.help_text ? `<small class="text-muted">${field.help_text}</small>` : ''}
                </div>
            `;
            
            // Add click handler to select field
            fieldElement.addEventListener('click', function(e) {
                if (!e.target.closest('.field-controls')) {
                    selectField(field.id);
                }
            });
            
            return fieldElement;
        }
        
        // Generate field preview HTML
        function generateFieldPreview(field) {
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                case 'currency':
                case 'percentage':
                    return `<input type="text" class="form-control" placeholder="${field.placeholder || 'Enter value...'}" disabled>`;
                    
                case 'textarea':
                    return `<textarea class="form-control" rows="3" placeholder="${field.placeholder || 'Enter text...'}" disabled></textarea>`;
                    
                case 'select':
                    return `<select class="form-select" disabled>
                                <option>${field.placeholder || 'Select option...'}</option>
                                ${field.options.map(opt => `<option>${opt.label || opt}</option>`).join('')}
                            </select>`;
                            
                case 'multiselect':
                    return `<select class="form-select" multiple disabled>
                                ${field.options.map(opt => `<option>${opt.label || opt}</option>`).join('')}
                            </select>`;
                            
                case 'radio':
                    return field.options.map((opt, i) => `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" disabled>
                            <label class="form-check-label">${opt.label || opt}</label>
                        </div>
                    `).join('');
                    
                case 'checkbox':
                    return field.options.map((opt, i) => `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" disabled>
                            <label class="form-check-label">${opt.label || opt}</label>
                        </div>
                    `).join('');
                    
                case 'date':
                    return `<input type="date" class="form-control" disabled>`;
                    
                case 'datetime':
                    return `<input type="datetime-local" class="form-control" disabled>`;
                    
                case 'file':
                    return `<input type="file" class="form-control" disabled>`;
                    
                case 'client-selector':
                case 'user-selector':
                case 'asset-selector':
                    return `<select class="form-select" disabled>
                                <option>${field.placeholder || 'Select...'}</option>
                            </select>`;
                            
                case 'json':
                    return `<textarea class="form-control" rows="4" placeholder="JSON data..." disabled></textarea>`;
                    
                default:
                    return `<input type="text" class="form-control" placeholder="${field.placeholder || 'Field preview'}" disabled>`;
            }
        }
        
        // Edit field configuration
        function editField(fieldId) {
            const field = formData.fields.find(f => f.id === fieldId);
            if (!field) return;
            
            currentEditingField = field;
            populateFieldConfigModal(field);
            fieldConfigModal.show();
        }
        
        // Populate field configuration modal
        function populateFieldConfigModal(field) {
            const form = document.getElementById('field-config-form');
            
            // Basic settings
            form.querySelector('[name="label"]').value = field.label || '';
            form.querySelector('[name="field_slug"]').value = field.field_slug || '';
            form.querySelector('[name="placeholder"]').value = field.placeholder || '';
            form.querySelector('[name="help_text"]').value = field.help_text || '';
            form.querySelector('[name="is_required"]').checked = field.is_required || false;
            form.querySelector('[name="is_readonly"]').checked = field.is_readonly || false;
            
            // Layout settings
            form.querySelector('[name="column_width"]').value = field.column_width || 'col-md-6';
            form.querySelector('[name="sort_order"]').value = field.sort_order || 0;
            form.querySelector('[name="section_id"]').value = field.section_id || '';
            
            // Generate field-specific configuration
            generateFieldSpecificConfig(field);
            
            // Generate conditional logic UI
            generateConditionalLogicUI(field);
        }
        
        // Generate field-specific configuration options
        function generateFieldSpecificConfig(field) {
            const container = document.getElementById('field-specific-config');
            container.innerHTML = '';
            
            switch (field.type) {
                case 'select':
                case 'multiselect':
                case 'radio':
                case 'checkbox':
                    container.innerHTML = `
                        <div class="mb-3">
                            <label class="form-label">Options</label>
                            <div id="options-container">
                                ${(field.options || []).map((opt, i) => `
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control option-input" 
                                               value="${typeof opt === 'object' ? opt.label : opt}" 
                                               placeholder="Option ${i + 1}">
                                        <button type="button" class="btn btn-outline-danger remove-option">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-option">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                        ${field.type === 'multiselect' ? `
                            <div class="mb-3">
                                <label class="form-label">Maximum Selections</label>
                                <input type="number" class="form-control" name="max_selections" 
                                       value="${field.ui_config?.max_items || ''}" min="1">
                            </div>
                        ` : ''}
                    `;
                    
                    setupOptionsEditor(field);
                    break;
                    
                case 'number':
                case 'currency':
                case 'percentage':
                    container.innerHTML = `
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Minimum Value</label>
                                <input type="number" class="form-control" name="min_value" 
                                       value="${field.ui_config?.min || ''}" step="any">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Maximum Value</label>
                                <input type="number" class="form-control" name="max_value" 
                                       value="${field.ui_config?.max || ''}" step="any">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Step</label>
                            <input type="number" class="form-control" name="step_value" 
                                   value="${field.ui_config?.step || (field.type === 'percentage' ? '1' : '1')}" step="any">
                        </div>
                        ${field.type === 'currency' ? `
                            <div class="mt-3">
                                <label class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" name="currency_symbol" 
                                       value="${field.ui_config?.symbol || '$'}" maxlength="3">
                            </div>
                        ` : ''}
                    `;
                    break;
                    
                case 'textarea':
                    container.innerHTML = `
                        <div class="mb-3">
                            <label class="form-label">Rows</label>
                            <input type="number" class="form-control" name="textarea_rows" 
                                   value="${field.ui_config?.rows || '4'}" min="2" max="20">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="enable_wysiwyg" 
                                   ${field.ui_config?.wysiwyg ? 'checked' : ''}>
                            <label class="form-check-label">Enable WYSIWYG Editor</label>
                        </div>
                    `;
                    break;
                    
                case 'file':
                    container.innerHTML = `
                        <div class="mb-3">
                            <label class="form-label">Allowed File Types</label>
                            <input type="text" class="form-control" name="allowed_types" 
                                   value="${(field.ui_config?.accept_types || []).join(', ')}"
                                   placeholder="image/*, .pdf, .doc">
                            <small class="form-text text-muted">Comma-separated list of MIME types or extensions</small>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Max File Size (MB)</label>
                                <input type="number" class="form-control" name="max_file_size" 
                                       value="${field.ui_config?.max_file_size || '10'}" min="1">
                            </div>
                            <div class="col-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" name="allow_multiple" 
                                           ${field.ui_config?.multiple ? 'checked' : ''}>
                                    <label class="form-check-label">Allow Multiple Files</label>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                    
                case 'date':
                case 'datetime':
                    container.innerHTML = `
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label">Min Date</label>
                                <input type="date" class="form-control" name="min_date" 
                                       value="${field.ui_config?.min_date || ''}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Max Date</label>
                                <input type="date" class="form-control" name="max_date" 
                                       value="${field.ui_config?.max_date || ''}">
                            </div>
                        </div>
                    `;
                    break;
            }
        }
        
        // Setup options editor for select/radio/checkbox fields
        function setupOptionsEditor(field) {
            const addOptionBtn = document.getElementById('add-option');
            const optionsContainer = document.getElementById('options-container');
            
            if (addOptionBtn) {
                addOptionBtn.addEventListener('click', function() {
                    const optionHtml = `
                        <div class="input-group mb-2">
                            <input type="text" class="form-control option-input" placeholder="New option">
                            <button type="button" class="btn btn-outline-danger remove-option">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    optionsContainer.insertAdjacentHTML('beforeend', optionHtml);
                });
            }
            
            // Handle option removal
            optionsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-option')) {
                    e.target.closest('.input-group').remove();
                }
            });
        }
        
        // Generate conditional logic UI
        function generateConditionalLogicUI(field) {
            const container = document.getElementById('conditional-rules');
            const existingRules = container.querySelector('#add-condition');
            
            // Clear existing rules but keep add button
            container.innerHTML = '';
            container.appendChild(existingRules);
            
            (field.conditional_logic || []).forEach((rule, index) => {
                addConditionalRuleUI(rule, index);
            });
        }
        
        // Add conditional rule UI
        function addConditionalRuleUI(rule = null, index = null) {
            const container = document.getElementById('conditional-rules');
            const addButton = container.querySelector('#add-condition');
            
            const ruleHtml = `
                <div class="conditional-rule">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Field</label>
                            <select class="form-select condition-field">
                                <option value="">Select field...</option>
                                ${formData.fields.map(f => `
                                    <option value="${f.field_slug}" ${rule && rule.field === f.field_slug ? 'selected' : ''}>
                                        ${f.label}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Operator</label>
                            <select class="form-select condition-operator">
                                <option value="equals" ${rule && rule.operator === 'equals' ? 'selected' : ''}>Equals</option>
                                <option value="not_equals" ${rule && rule.operator === 'not_equals' ? 'selected' : ''}>Not Equals</option>
                                <option value="contains" ${rule && rule.operator === 'contains' ? 'selected' : ''}>Contains</option>
                                <option value="empty" ${rule && rule.operator === 'empty' ? 'selected' : ''}>Is Empty</option>
                                <option value="not_empty" ${rule && rule.operator === 'not_empty' ? 'selected' : ''}>Not Empty</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Value</label>
                            <input type="text" class="form-control condition-value" 
                                   value="${rule ? rule.value : ''}" placeholder="Comparison value">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select class="form-select condition-action">
                                <option value="show" ${rule && rule.action === 'show' ? 'selected' : ''}>Show</option>
                                <option value="hide" ${rule && rule.action === 'hide' ? 'selected' : ''}>Hide</option>
                                <option value="require" ${rule && rule.action === 'require' ? 'selected' : ''}>Require</option>
                                <option value="disable" ${rule && rule.action === 'disable' ? 'selected' : ''}>Disable</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-danger w-100 remove-condition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            addButton.insertAdjacentHTML('beforebegin', ruleHtml);
            
            // Add remove handler
            const newRule = container.lastElementChild.previousElementSibling;
            newRule.querySelector('.remove-condition').addEventListener('click', function() {
                newRule.remove();
            });
        }
        
        // Save field configuration
        function saveFieldConfig() {
            const form = document.getElementById('field-config-form');
            const formData = new FormData(form);
            
            if (!currentEditingField) return;
            
            // Update basic settings
            currentEditingField.label = formData.get('label') || '';
            currentEditingField.field_slug = formData.get('field_slug') || '';
            currentEditingField.placeholder = formData.get('placeholder') || '';
            currentEditingField.help_text = formData.get('help_text') || '';
            currentEditingField.is_required = formData.has('is_required');
            currentEditingField.is_readonly = formData.has('is_readonly');
            currentEditingField.column_width = formData.get('column_width') || 'col-md-6';
            currentEditingField.sort_order = parseInt(formData.get('sort_order')) || 0;
            currentEditingField.section_id = formData.get('section_id') || null;
            
            // Update field-specific settings
            updateFieldSpecificConfig(currentEditingField, form);
            
            // Update conditional logic
            updateConditionalLogic(currentEditingField);
            
            // Refresh field element
            refreshFieldElement(currentEditingField.id);
            
            fieldConfigModal.hide();
            currentEditingField = null;
        }
        
        // Update field-specific configuration
        function updateFieldSpecificConfig(field, form) {
            if (!field.ui_config) field.ui_config = {};
            
            switch (field.type) {
                case 'select':
                case 'multiselect':
                case 'radio':
                case 'checkbox':
                    // Collect options
                    const optionInputs = form.querySelectorAll('.option-input');
                    field.options = Array.from(optionInputs).map(input => input.value.trim()).filter(Boolean);
                    
                    if (field.type === 'multiselect') {
                        const maxSelections = form.querySelector('[name="max_selections"]');
                        if (maxSelections && maxSelections.value) {
                            field.ui_config.max_items = parseInt(maxSelections.value);
                        }
                    }
                    break;
                    
                case 'number':
                case 'currency':
                case 'percentage':
                    const minValue = form.querySelector('[name="min_value"]');
                    const maxValue = form.querySelector('[name="max_value"]');
                    const stepValue = form.querySelector('[name="step_value"]');
                    
                    if (minValue && minValue.value) field.ui_config.min = parseFloat(minValue.value);
                    if (maxValue && maxValue.value) field.ui_config.max = parseFloat(maxValue.value);
                    if (stepValue && stepValue.value) field.ui_config.step = parseFloat(stepValue.value);
                    
                    if (field.type === 'currency') {
                        const currencySymbol = form.querySelector('[name="currency_symbol"]');
                        if (currencySymbol) field.ui_config.symbol = currencySymbol.value || '$';
                    }
                    break;
                    
                case 'textarea':
                    const textareaRows = form.querySelector('[name="textarea_rows"]');
                    const enableWysiwyg = form.querySelector('[name="enable_wysiwyg"]');
                    
                    if (textareaRows) field.ui_config.rows = parseInt(textareaRows.value) || 4;
                    field.ui_config.wysiwyg = enableWysiwyg ? enableWysiwyg.checked : false;
                    break;
                    
                case 'file':
                    const allowedTypes = form.querySelector('[name="allowed_types"]');
                    const maxFileSize = form.querySelector('[name="max_file_size"]');
                    const allowMultiple = form.querySelector('[name="allow_multiple"]');
                    
                    if (allowedTypes && allowedTypes.value) {
                        field.ui_config.accept_types = allowedTypes.value.split(',').map(t => t.trim()).filter(Boolean);
                    }
                    if (maxFileSize) field.ui_config.max_file_size = parseInt(maxFileSize.value) || 10;
                    field.ui_config.multiple = allowMultiple ? allowMultiple.checked : false;
                    break;
                    
                case 'date':
                case 'datetime':
                    const minDate = form.querySelector('[name="min_date"]');
                    const maxDate = form.querySelector('[name="max_date"]');
                    
                    if (minDate && minDate.value) field.ui_config.min_date = minDate.value;
                    if (maxDate && maxDate.value) field.ui_config.max_date = maxDate.value;
                    break;
            }
        }
        
        // Update conditional logic
        function updateConditionalLogic(field) {
            const rules = [];
            const ruleElements = document.querySelectorAll('.conditional-rule');
            
            ruleElements.forEach(ruleEl => {
                const fieldSelect = ruleEl.querySelector('.condition-field');
                const operatorSelect = ruleEl.querySelector('.condition-operator');
                const valueInput = ruleEl.querySelector('.condition-value');
                const actionSelect = ruleEl.querySelector('.condition-action');
                
                if (fieldSelect.value) {
                    rules.push({
                        field: fieldSelect.value,
                        operator: operatorSelect.value,
                        value: valueInput.value,
                        action: actionSelect.value
                    });
                }
            });
            
            field.conditional_logic = rules;
        }
        
        // Refresh field element
        function refreshFieldElement(fieldId) {
            const field = formData.fields.find(f => f.id === fieldId);
            const oldElement = formCanvas.querySelector(`[data-field-id="${fieldId}"]`);
            
            if (field && oldElement) {
                const newElement = createFieldElement(field);
                oldElement.replaceWith(newElement);
            }
        }
        
        // Add conditional rule
        function addConditionalRule() {
            addConditionalRuleUI();
        }
        
        // Add section
        function addSection() {
            const sectionId = 'section_' + (sectionIdCounter++);
            const section = {
                id: sectionId,
                title: 'New Section',
                description: '',
                icon: 'fas fa-folder',
                is_collapsible: false,
                is_expanded: true,
                sort_order: formData.sections.length
            };
            
            formData.sections.push(section);
            
            // Update section selectors in field config
            updateSectionSelectors();
            
            // Open section config modal
            editSection(sectionId);
        }
        
        // Edit section
        function editSection(sectionId) {
            const section = formData.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            const form = document.getElementById('section-config-form');
            form.querySelector('[name="title"]').value = section.title || '';
            form.querySelector('[name="description"]').value = section.description || '';
            form.querySelector('[name="icon"]').value = section.icon || '';
            form.querySelector('[name="is_collapsible"]').checked = section.is_collapsible || false;
            form.querySelector('[name="is_expanded"]').checked = section.is_expanded || false;
            
            document.getElementById('save-section-config').onclick = function() {
                saveSectionConfig(sectionId);
            };
            
            sectionConfigModal.show();
        }
        
        // Save section configuration
        function saveSectionConfig(sectionId) {
            const form = document.getElementById('section-config-form');
            const formData = new FormData(form);
            
            const section = this.formData.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            section.title = formData.get('title') || '';
            section.description = formData.get('description') || '';
            section.icon = formData.get('icon') || '';
            section.is_collapsible = formData.has('is_collapsible');
            section.is_expanded = formData.has('is_expanded');
            
            updateSectionSelectors();
            sectionConfigModal.hide();
        }
        
        // Update section selectors
        function updateSectionSelectors() {
            const sectionSelects = document.querySelectorAll('[name="section_id"]');
            
            sectionSelects.forEach(select => {
                const currentValue = select.value;
                select.innerHTML = '<option value="">No Section</option>';
                
                formData.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.id;
                    option.textContent = section.title;
                    option.selected = currentValue === section.id;
                    select.appendChild(option);
                });
            });
        }
        
        // Global functions for field management
        window.editField = editField;
        window.duplicateField = function(fieldId) {
            const field = formData.fields.find(f => f.id === fieldId);
            if (field) {
                const duplicatedField = JSON.parse(JSON.stringify(field));
                duplicatedField.id = 'field_' + (fieldIdCounter++);
                duplicatedField.field_slug = duplicatedField.field_slug + '_copy';
                duplicatedField.label = duplicatedField.label + ' (Copy)';
                addFieldToForm(duplicatedField.type, duplicatedField);
            }
        };
        
        window.removeField = function(fieldId) {
            if (confirm('Are you sure you want to delete this field?')) {
                const fieldElement = formCanvas.querySelector(`[data-field-id="${fieldId}"]`);
                if (fieldElement) fieldElement.remove();
                
                formData.fields = formData.fields.filter(f => f.id !== fieldId);
                
                // Show empty message if no fields left
                if (formData.fields.length === 0) {
                    const emptyMessage = formCanvas.querySelector('.empty-canvas-message');
                    if (emptyMessage) emptyMessage.style.display = 'block';
                }
            }
        };
        
        // Auto-generate field slug from label
        document.addEventListener('input', function(e) {
            if (e.target.name === 'label') {
                const slugField = e.target.closest('form').querySelector('[name="field_slug"]');
                if (slugField && !slugField.dataset.manuallyEdited) {
                    slugField.value = e.target.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                }
            }
            
            if (e.target.name === 'field_slug') {
                e.target.dataset.manuallyEdited = 'true';
            }
        });
        
        // Form actions
        function updateFormConfig() {
            formData.config = {
                name: document.getElementById('form-name').value,
                contract_type: document.getElementById('contract-type').value,
                description: document.getElementById('form-description').value,
                status: document.getElementById('form-status').value,
                allow_client_access: document.getElementById('allow-client-access').checked
            };
        }
        
        function updateFormLayout() {
            const layout = document.getElementById('form-layout').value;
            // Update field widths based on layout
            formData.fields.forEach(field => {
                if (layout === 'single-column') {
                    field.column_width = 'col-12';
                } else if (layout === 'three-column') {
                    field.column_width = 'col-md-4';
                } else {
                    field.column_width = 'col-md-6';
                }
            });
            
            // Refresh all field elements
            formData.fields.forEach(field => {
                refreshFieldElement(field.id);
            });
        }
        
        function updateFormStyle() {
            const style = document.getElementById('form-style').value;
            const canvas = document.getElementById('form-canvas');
            
            canvas.className = canvas.className.replace(/\bform-style-\w+/g, '');
            canvas.classList.add('form-style-' + style);
        }
        
        function clearForm() {
            if (confirm('Are you sure you want to clear the entire form? This action cannot be undone.')) {
                formCanvas.innerHTML = '<div class="empty-canvas-message"><div class="text-center py-5"><i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i><h5 class="text-muted">Start Building Your Form</h5><p class="text-muted">Drag and drop components from the left panel to build your form.<br>You can also click "Add Section" to organize your fields.</p></div></div>';
                formData = { config: {}, sections: [], fields: [] };
                fieldIdCounter = 1;
                sectionIdCounter = 1;
            }
        }
        
        function previewForm() {
            const previewContainer = document.getElementById('form-preview-container');
            previewContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Generating preview...</div>';
            
            // Generate form preview HTML
            fetch('/admin/contract-forms/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.text())
            .then(html => {
                previewContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Preview error:', error);
                previewContainer.innerHTML = '<div class="text-danger text-center py-4">Error generating preview</div>';
            });
            
            formPreviewModal.show();
        }
        
        function saveDraft() {
            updateFormConfig();
            
            fetch('/admin/contract-forms/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ...formData, status: 'draft' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', 'Form saved as draft successfully');
                } else {
                    showMessage('error', data.message || 'Failed to save form');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showMessage('error', 'Failed to save form');
            });
        }
        
        function publishForm() {
            if (!formData.config.name) {
                showMessage('error', 'Please enter a form name before publishing');
                return;
            }
            
            if (!formData.config.contract_type) {
                showMessage('error', 'Please select a contract type before publishing');
                return;
            }
            
            if (formData.fields.length === 0) {
                showMessage('error', 'Please add at least one field before publishing');
                return;
            }
            
            if (confirm('Are you sure you want to publish this form? It will be available for use immediately.')) {
                updateFormConfig();
                
                fetch('/admin/contract-forms/publish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ...formData, status: 'active' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', 'Form published successfully');
                        document.getElementById('form-status').value = 'active';
                    } else {
                        showMessage('error', data.message || 'Failed to publish form');
                    }
                })
                .catch(error => {
                    console.error('Publish error:', error);
                    showMessage('error', 'Failed to publish form');
                });
            }
        }
        
        function loadExistingForm() {
            const urlParams = new URLSearchParams(window.location.search);
            const formId = urlParams.get('edit');
            
            if (formId) {
                fetch(`/admin/contract-forms/${formId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            formData = data.form;
                            populateFormFromData();
                        }
                    })
                    .catch(error => {
                        console.error('Load error:', error);
                        showMessage('error', 'Failed to load form');
                    });
            }
        }
        
        function populateFormFromData() {
            // Populate form configuration
            document.getElementById('form-name').value = formData.config.name || '';
            document.getElementById('contract-type').value = formData.config.contract_type || '';
            document.getElementById('form-description').value = formData.config.description || '';
            document.getElementById('form-status').value = formData.config.status || 'draft';
            document.getElementById('allow-client-access').checked = formData.config.allow_client_access || false;
            
            // Clear canvas and add fields
            const emptyMessage = formCanvas.querySelector('.empty-canvas-message');
            if (emptyMessage) emptyMessage.style.display = 'none';
            
            formData.fields.forEach(field => {
                const fieldElement = createFieldElement(field);
                formCanvas.appendChild(fieldElement);
            });
            
            // Update counters
            fieldIdCounter = Math.max(...formData.fields.map(f => parseInt(f.id.replace('field_', '')))) + 1 || 1;
            sectionIdCounter = Math.max(...formData.sections.map(s => parseInt(s.id.replace('section_', '')))) + 1 || 1;
            
            updateSectionSelectors();
        }
        
        function selectField(fieldId) {
            // Remove previous selection
            formCanvas.querySelectorAll('.field-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Add selection to clicked field
            const fieldElement = formCanvas.querySelector(`[data-field-id="${fieldId}"]`);
            if (fieldElement) {
                fieldElement.classList.add('selected');
            }
        }
        
        function updateFieldOrder() {
            const fieldElements = formCanvas.querySelectorAll('.field-item');
            fieldElements.forEach((element, index) => {
                const fieldId = element.dataset.fieldId;
                const field = formData.fields.find(f => f.id === fieldId);
                if (field) {
                    field.sort_order = index;
                }
            });
        }
        
        function updateFormPreview() {
            // This could trigger a live preview update
            // For now, we'll just update the field order
            updateFieldOrder();
        }
        
        function showMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.querySelector('.contract-form-designer').insertAdjacentHTML('afterbegin', alertHtml);
            
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }
    });
</script>
@endpush