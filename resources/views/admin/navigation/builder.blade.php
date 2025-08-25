@extends('layouts.app')

@section('title', 'Navigation Builder')

@section('content')
<div class="navigation-builder">
    <div class="row">
        {{-- Builder Header --}}
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Contract Navigation Builder</h2>
                    <p class="text-muted mb-0">Design custom navigation menus and breadcrumb systems</p>
                </div>
                <div class="nav-actions">
                    <button class="btn btn-outline-secondary me-2" id="preview-navigation">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button class="btn btn-outline-success me-2" id="save-navigation">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                    <button class="btn btn-success" id="publish-navigation">
                        <i class="fas fa-rocket"></i> Publish Navigation
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Navigation Configuration Sidebar --}}
        <div class="col-md-3">
            <div class="nav-config-sidebar">
                {{-- Navigation Settings --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cog"></i> Navigation Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="navigation-config">
                            <div class="mb-3">
                                <label class="form-label">Configuration Name</label>
                                <input type="text" class="form-control" id="nav-config-name" placeholder="Enter configuration name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contract Type</label>
                                <select class="form-select" id="nav-contract-type" required>
                                    <option value="">Select contract type...</option>
                                    <option value="service_agreement">Service Agreement</option>
                                    <option value="maintenance_contract">Maintenance Contract</option>
                                    <option value="support_contract">Support Contract</option>
                                    <option value="custom">Custom Contract</option>
                                    <option value="global">Global (All Types)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Navigation Style</label>
                                <select class="form-select" id="nav-style">
                                    <option value="horizontal">Horizontal Tabs</option>
                                    <option value="vertical">Vertical Menu</option>
                                    <option value="dropdown">Dropdown Menu</option>
                                    <option value="breadcrumb">Breadcrumb Only</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <select class="form-select" id="nav-position">
                                    <option value="top">Top of Page</option>
                                    <option value="left">Left Sidebar</option>
                                    <option value="right">Right Sidebar</option>
                                    <option value="floating">Floating Menu</option>
                                </select>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="enable-breadcrumbs" checked>
                                <label class="form-check-label" for="enable-breadcrumbs">
                                    Enable breadcrumbs
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="enable-search">
                                <label class="form-check-label" for="enable-search">
                                    Include search functionality
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="responsive-menu" checked>
                                <label class="form-check-label" for="responsive-menu">
                                    Responsive mobile menu
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="permission-based">
                                <label class="form-check-label" for="permission-based">
                                    Permission-based visibility
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
                
                {{-- Navigation Components --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-puzzle-piece"></i> Navigation Components
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="nav-components">
                            {{-- Standard Pages --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Standard Pages</strong>
                                </div>
                                <div class="component-list">
                                    <div class="nav-component-item" data-type="overview">
                                        <i class="fas fa-info-circle"></i> Overview
                                    </div>
                                    <div class="nav-component-item" data-type="details">
                                        <i class="fas fa-file-contract"></i> Contract Details
                                    </div>
                                    <div class="nav-component-item" data-type="timeline">
                                        <i class="fas fa-history"></i> Timeline
                                    </div>
                                    <div class="nav-component-item" data-type="attachments">
                                        <i class="fas fa-paperclip"></i> Attachments
                                    </div>
                                    <div class="nav-component-item" data-type="comments">
                                        <i class="fas fa-comments"></i> Comments
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Management Actions --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Management</strong>
                                </div>
                                <div class="component-list">
                                    <div class="nav-component-item" data-type="edit">
                                        <i class="fas fa-edit"></i> Edit Contract
                                    </div>
                                    <div class="nav-component-item" data-type="duplicate">
                                        <i class="fas fa-copy"></i> Duplicate
                                    </div>
                                    <div class="nav-component-item" data-type="versions">
                                        <i class="fas fa-code-branch"></i> Versions
                                    </div>
                                    <div class="nav-component-item" data-type="audit">
                                        <i class="fas fa-search"></i> Audit Log
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Financial --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Financial</strong>
                                </div>
                                <div class="component-list">
                                    <div class="nav-component-item" data-type="invoices">
                                        <i class="fas fa-file-invoice"></i> Invoices
                                    </div>
                                    <div class="nav-component-item" data-type="payments">
                                        <i class="fas fa-credit-card"></i> Payments
                                    </div>
                                    <div class="nav-component-item" data-type="billing">
                                        <i class="fas fa-calculator"></i> Billing
                                    </div>
                                    <div class="nav-component-item" data-type="reports">
                                        <i class="fas fa-chart-bar"></i> Reports
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Communication --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Communication</strong>
                                </div>
                                <div class="component-list">
                                    <div class="nav-component-item" data-type="notifications">
                                        <i class="fas fa-bell"></i> Notifications
                                    </div>
                                    <div class="nav-component-item" data-type="messages">
                                        <i class="fas fa-envelope"></i> Messages
                                    </div>
                                    <div class="nav-component-item" data-type="meetings">
                                        <i class="fas fa-calendar"></i> Meetings
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Custom Items --}}
                            <div class="component-category">
                                <div class="category-header">
                                    <strong>Custom</strong>
                                </div>
                                <div class="component-list">
                                    <div class="nav-component-item" data-type="custom-page">
                                        <i class="fas fa-plus"></i> Custom Page
                                    </div>
                                    <div class="nav-component-item" data-type="external-link">
                                        <i class="fas fa-external-link-alt"></i> External Link
                                    </div>
                                    <div class="nav-component-item" data-type="separator">
                                        <i class="fas fa-minus"></i> Separator
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Quick Actions --}}
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-lightning-bolt"></i> Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-outline-primary btn-sm w-100 mb-2" id="add-submenu">
                            <i class="fas fa-plus"></i> Add Submenu
                        </button>
                        <button class="btn btn-outline-secondary btn-sm w-100 mb-2" id="import-template">
                            <i class="fas fa-download"></i> Import Template
                        </button>
                        <button class="btn btn-outline-info btn-sm w-100" id="export-config">
                            <i class="fas fa-upload"></i> Export Config
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Navigation Builder Canvas --}}
        <div class="col-md-9">
            <div class="nav-builder-canvas">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-sitemap"></i> Navigation Structure
                        </h6>
                        <div class="canvas-actions">
                            <button class="btn btn-outline-secondary btn-sm me-2" id="collapse-all">
                                <i class="fas fa-compress-alt"></i> Collapse All
                            </button>
                            <button class="btn btn-outline-secondary btn-sm me-2" id="expand-all">
                                <i class="fas fa-expand-alt"></i> Expand All
                            </button>
                            <button class="btn btn-outline-danger btn-sm" id="clear-navigation">
                                <i class="fas fa-trash"></i> Clear All
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Navigation Tree --}}
                        <div class="navigation-tree" id="navigation-tree">
                            <div class="empty-navigation-message">
                                <div class="text-center py-5">
                                    <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Build Your Navigation</h5>
                                    <p class="text-muted">
                                        Drag navigation components from the left panel to create your menu structure.
                                        <br>You can create nested submenus and organize items hierarchically.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Breadcrumb Configuration --}}
                <div class="card mt-4" id="breadcrumb-config-card" style="display: none;">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-arrow-right"></i> Breadcrumb Configuration
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="breadcrumb-builder" id="breadcrumb-builder">
                            <div class="breadcrumb-item-template">
                                <div class="breadcrumb-preview mb-3">
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb" id="breadcrumb-preview">
                                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                                            <li class="breadcrumb-item"><a href="#">Contracts</a></li>
                                            <li class="breadcrumb-item active" aria-current="page">Contract Details</li>
                                        </ol>
                                    </nav>
                                </div>
                                
                                <div class="breadcrumb-settings">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Separator</label>
                                            <select class="form-select form-select-sm" id="breadcrumb-separator">
                                                <option value="/">/</option>
                                                <option value=">" selected>></option>
                                                <option value="|">|</option>
                                                <option value="•">•</option>
                                                <option value="→">→</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Max Items</label>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="breadcrumb-max-items" value="5" min="3" max="10">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check mt-4">
                                                <input type="checkbox" class="form-check-input" id="breadcrumb-icons" checked>
                                                <label class="form-check-label" for="breadcrumb-icons">
                                                    Show icons
                                                </label>
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

{{-- Navigation Item Configuration Modal --}}
<div class="modal fade" id="navItemConfigModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configure Navigation Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="nav-item-config-form">
                    <div class="row">
                        {{-- Basic Settings --}}
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Basic Settings</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Label</label>
                                <input type="text" class="form-control" name="label" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Icon</label>
                                <input type="text" class="form-control" name="icon" placeholder="fas fa-home">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">URL/Route</label>
                                <input type="text" class="form-control" name="url" placeholder="/contracts/{id}/overview">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                        </div>
                        
                        {{-- Advanced Settings --}}
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Advanced Settings</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Target</label>
                                <select class="form-select" name="target">
                                    <option value="_self">Same Window</option>
                                    <option value="_blank">New Window</option>
                                    <option value="_modal">Modal</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Badge</label>
                                <input type="text" class="form-control" name="badge" placeholder="New">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Badge Color</label>
                                <select class="form-select" name="badge_color">
                                    <option value="primary">Primary</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="success">Success</option>
                                    <option value="warning">Warning</option>
                                    <option value="danger">Danger</option>
                                    <option value="info">Info</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_active">
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_visible">
                                        <label class="form-check-label">Visible</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Permissions & Conditions --}}
                        <div class="col-12 mt-3">
                            <h6 class="border-bottom pb-2 mb-3">Permissions & Conditions</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Required Permission</label>
                                    <select class="form-select" name="permission">
                                        <option value="">No permission required</option>
                                        <option value="view_contracts">View Contracts</option>
                                        <option value="edit_contracts">Edit Contracts</option>
                                        <option value="delete_contracts">Delete Contracts</option>
                                        <option value="manage_contracts">Manage Contracts</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Display Condition</label>
                                    <select class="form-select" name="condition">
                                        <option value="">Always show</option>
                                        <option value="has_data">Only if data exists</option>
                                        <option value="contract_active">Only if contract is active</option>
                                        <option value="user_is_owner">Only if user is owner</option>
                                        <option value="custom">Custom condition</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-3" id="custom-condition-config" style="display: none;">
                                <label class="form-label">Custom Condition Code</label>
                                <textarea class="form-control" name="custom_condition" rows="3" 
                                          placeholder="PHP code that returns boolean (e.g., $contract->status === 'active')"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-nav-item">Save Item</button>
            </div>
        </div>
    </div>
</div>

{{-- Navigation Preview Modal --}}
<div class="modal fade" id="navPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Navigation Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="preview-container">
                    <div class="preview-styles mb-3">
                        <label class="form-label">Preview Style:</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="preview-style" id="preview-desktop" value="desktop" checked>
                            <label class="btn btn-outline-primary" for="preview-desktop">Desktop</label>
                            
                            <input type="radio" class="btn-check" name="preview-style" id="preview-tablet" value="tablet">
                            <label class="btn btn-outline-primary" for="preview-tablet">Tablet</label>
                            
                            <input type="radio" class="btn-check" name="preview-style" id="preview-mobile" value="mobile">
                            <label class="btn btn-outline-primary" for="preview-mobile">Mobile</label>
                        </div>
                    </div>
                    
                    <div id="navigation-preview-container" class="border rounded p-3">
                        {{-- Preview content will be loaded here --}}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="apply-from-preview">Apply Configuration</button>
            </div>
        </div>
    </div>
</div>

{{-- Import Template Modal --}}
<div class="modal fade" id="importTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Navigation Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="template-options">
                    <div class="template-option" data-template="standard">
                        <h6>Standard Contract Navigation</h6>
                        <p class="text-muted small">Overview, Details, Timeline, Attachments, Edit</p>
                    </div>
                    <div class="template-option" data-template="financial">
                        <h6>Financial Contract Navigation</h6>
                        <p class="text-muted small">Overview, Financial, Invoices, Payments, Reports</p>
                    </div>
                    <div class="template-option" data-template="service">
                        <h6>Service Contract Navigation</h6>
                        <p class="text-muted small">Overview, Service Details, SLA, Assets, Performance</p>
                    </div>
                    <div class="template-option" data-template="minimal">
                        <h6>Minimal Navigation</h6>
                        <p class="text-muted small">Overview, Edit, History</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="import-selected-template">Import Template</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .navigation-builder {
        min-height: calc(100vh - 200px);
    }
    
    .nav-config-sidebar {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }
    
    .nav-components {
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
    
    .nav-component-item {
        padding: 0.75rem 1rem;
        cursor: grab;
        border-bottom: 1px solid #f1f3f4;
        transition: all 0.2s ease;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .nav-component-item:hover {
        background-color: #e3f2fd;
        transform: translateX(4px);
    }
    
    .nav-component-item:active {
        cursor: grabbing;
    }
    
    .nav-component-item i {
        color: #1976d2;
        width: 16px;
    }
    
    .nav-builder-canvas {
        min-height: 600px;
    }
    
    .navigation-tree {
        min-height: 400px;
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .navigation-tree.drag-over {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .empty-navigation-message {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
    }
    
    .nav-tree-item {
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        background: white;
        margin-bottom: 0.5rem;
        transition: all 0.2s ease;
    }
    
    .nav-tree-item:hover {
        border-color: #1976d2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .nav-item-header {
        padding: 0.75rem 1rem;
        display: flex;
        justify-content: between;
        align-items: center;
        cursor: pointer;
    }
    
    .nav-item-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-grow: 1;
    }
    
    .nav-item-controls {
        display: flex;
        gap: 0.25rem;
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .nav-tree-item:hover .nav-item-controls {
        opacity: 1;
    }
    
    .nav-item-control-btn {
        width: 24px;
        height: 24px;
        padding: 0;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    .nav-item-children {
        padding: 0 1rem 0.5rem 2rem;
        border-top: 1px solid #f1f3f4;
        background-color: #f8f9fa;
    }
    
    .nav-item-badge {
        font-size: 0.75rem;
        padding: 0.125rem 0.5rem;
    }
    
    .sortable-ghost {
        opacity: 0.4;
        background-color: #e3f2fd !important;
    }
    
    .sortable-chosen {
        background-color: #e3f2fd !important;
    }
    
    .template-option {
        padding: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .template-option:hover {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .template-option.selected {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .breadcrumb-preview {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.375rem;
    }
    
    .preview-container {
        min-height: 400px;
    }
    
    .preview-device-frame {
        transition: all 0.3s ease;
    }
    
    .preview-device-frame.desktop {
        width: 100%;
    }
    
    .preview-device-frame.tablet {
        width: 768px;
        margin: 0 auto;
    }
    
    .preview-device-frame.mobile {
        width: 375px;
        margin: 0 auto;
    }
    
    /* Navigation item type specific styling */
    .nav-tree-item[data-type="separator"] {
        background-color: #f8f9fa;
        border-style: dashed;
    }
    
    .nav-tree-item[data-type="external-link"] .nav-item-content::after {
        content: "🔗";
        margin-left: 0.5rem;
        opacity: 0.6;
    }
    
    .nav-tree-item.disabled {
        opacity: 0.6;
        background-color: #f8f9fa;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .nav-config-sidebar {
            position: static;
            max-height: none;
        }
        
        .nav-component-item {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .nav-builder-canvas {
            margin-top: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" 
        integrity="sha512-Eezs+g9Lq4TCCq0wae01s9PuNWzHYoCMkE97e2qdkYthpI0pzC3UGB03lgEHn2XM85hDNKVvNiMU63mg9JuM8w==" 
        crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navigationTree = document.getElementById('navigation-tree');
        const navComponentItems = document.querySelectorAll('.nav-component-item');
        const navItemConfigModal = new bootstrap.Modal(document.getElementById('navItemConfigModal'));
        const navPreviewModal = new bootstrap.Modal(document.getElementById('navPreviewModal'));
        const importTemplateModal = new bootstrap.Modal(document.getElementById('importTemplateModal'));
        
        let navigationConfiguration = {
            config: {},
            items: [],
            breadcrumb: {}
        };
        let draggedComponentType = null;
        let currentEditingItem = null;
        let navItemIdCounter = 1;
        
        // Initialize navigation builder
        initializeBuilder();
        
        function initializeBuilder() {
            setupDragAndDrop();
            setupEventHandlers();
            setupSortable();
            loadExistingConfiguration();
        }
        
        // Setup drag and drop
        function setupDragAndDrop() {
            // Make component items draggable
            navComponentItems.forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedComponentType = this.dataset.type;
                    e.dataTransfer.effectAllowed = 'copy';
                });
            });
            
            // Setup drop zone
            navigationTree.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('drag-over');
            });
            
            navigationTree.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            navigationTree.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                if (draggedComponentType) {
                    addNavigationItem(draggedComponentType);
                    draggedComponentType = null;
                }
            });
        }
        
        // Setup event handlers
        function setupEventHandlers() {
            // Configuration changes
            document.getElementById('nav-config-name').addEventListener('input', updateNavigationConfig);
            document.getElementById('nav-contract-type').addEventListener('change', updateNavigationConfig);
            document.getElementById('nav-style').addEventListener('change', updateNavigationConfig);
            document.getElementById('nav-position').addEventListener('change', updateNavigationConfig);
            document.getElementById('enable-breadcrumbs').addEventListener('change', toggleBreadcrumbConfig);
            document.getElementById('enable-search').addEventListener('change', updateNavigationConfig);
            document.getElementById('responsive-menu').addEventListener('change', updateNavigationConfig);
            document.getElementById('permission-based').addEventListener('change', updateNavigationConfig);
            
            // Action buttons
            document.getElementById('add-submenu').addEventListener('click', addSubmenu);
            document.getElementById('import-template').addEventListener('click', () => importTemplateModal.show());
            document.getElementById('export-config').addEventListener('click', exportConfiguration);
            document.getElementById('collapse-all').addEventListener('click', collapseAllItems);
            document.getElementById('expand-all').addEventListener('click', expandAllItems);
            document.getElementById('clear-navigation').addEventListener('click', clearNavigation);
            document.getElementById('preview-navigation').addEventListener('click', previewNavigation);
            document.getElementById('save-navigation').addEventListener('click', saveNavigation);
            document.getElementById('publish-navigation').addEventListener('click', publishNavigation);
            
            // Modal handlers
            document.getElementById('save-nav-item').addEventListener('click', saveNavigationItem);
            document.getElementById('import-selected-template').addEventListener('click', importSelectedTemplate);
            
            // Preview style changes
            document.querySelectorAll('input[name="preview-style"]').forEach(radio => {
                radio.addEventListener('change', updatePreviewStyle);
            });
            
            // Template selection
            document.querySelectorAll('.template-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.template-option').forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                });
            });
            
            // Breadcrumb settings
            document.getElementById('breadcrumb-separator').addEventListener('change', updateBreadcrumbPreview);
            document.getElementById('breadcrumb-max-items').addEventListener('change', updateBreadcrumbPreview);
            document.getElementById('breadcrumb-icons').addEventListener('change', updateBreadcrumbPreview);
            
            // Custom condition toggle
            document.querySelector('[name="condition"]').addEventListener('change', function() {
                const customConfig = document.getElementById('custom-condition-config');
                customConfig.style.display = this.value === 'custom' ? 'block' : 'none';
            });
        }
        
        // Setup sortable functionality
        function setupSortable() {
            new Sortable(navigationTree, {
                group: 'navigation-items',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function(evt) {
                    updateNavigationOrder();
                }
            });
        }
        
        // Add navigation item
        function addNavigationItem(componentType, itemData = null, parentId = null) {
            const itemId = 'nav_item_' + (navItemIdCounter++);
            const item = itemData || createDefaultNavigationItem(componentType, itemId);
            
            const itemElement = createNavigationItemElement(item);
            
            // Hide empty message
            const emptyMessage = navigationTree.querySelector('.empty-navigation-message');
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            
            if (parentId) {
                // Add to parent's children
                const parentElement = navigationTree.querySelector(`[data-item-id="${parentId}"]`);
                if (parentElement) {
                    let childrenContainer = parentElement.querySelector('.nav-item-children');
                    if (!childrenContainer) {
                        childrenContainer = document.createElement('div');
                        childrenContainer.className = 'nav-item-children';
                        parentElement.appendChild(childrenContainer);
                    }
                    childrenContainer.appendChild(itemElement);
                }
            } else {
                navigationTree.appendChild(itemElement);
            }
            
            navigationConfiguration.items.push(item);
            
            // Open configuration modal for new items
            if (!itemData) {
                editNavigationItem(itemId);
            }
        }
        
        // Create default navigation item
        function createDefaultNavigationItem(type, id) {
            const itemTypes = {
                overview: { label: 'Overview', icon: 'fas fa-info-circle', url: '/contracts/{id}/overview' },
                details: { label: 'Contract Details', icon: 'fas fa-file-contract', url: '/contracts/{id}/details' },
                timeline: { label: 'Timeline', icon: 'fas fa-history', url: '/contracts/{id}/timeline' },
                attachments: { label: 'Attachments', icon: 'fas fa-paperclip', url: '/contracts/{id}/attachments' },
                comments: { label: 'Comments', icon: 'fas fa-comments', url: '/contracts/{id}/comments' },
                edit: { label: 'Edit Contract', icon: 'fas fa-edit', url: '/contracts/{id}/edit' },
                duplicate: { label: 'Duplicate', icon: 'fas fa-copy', url: '/contracts/{id}/duplicate' },
                versions: { label: 'Versions', icon: 'fas fa-code-branch', url: '/contracts/{id}/versions' },
                audit: { label: 'Audit Log', icon: 'fas fa-search', url: '/contracts/{id}/audit' },
                invoices: { label: 'Invoices', icon: 'fas fa-file-invoice', url: '/contracts/{id}/invoices' },
                payments: { label: 'Payments', icon: 'fas fa-credit-card', url: '/contracts/{id}/payments' },
                billing: { label: 'Billing', icon: 'fas fa-calculator', url: '/contracts/{id}/billing' },
                reports: { label: 'Reports', icon: 'fas fa-chart-bar', url: '/contracts/{id}/reports' },
                notifications: { label: 'Notifications', icon: 'fas fa-bell', url: '/contracts/{id}/notifications' },
                messages: { label: 'Messages', icon: 'fas fa-envelope', url: '/contracts/{id}/messages' },
                meetings: { label: 'Meetings', icon: 'fas fa-calendar', url: '/contracts/{id}/meetings' },
                'custom-page': { label: 'Custom Page', icon: 'fas fa-plus', url: '#' },
                'external-link': { label: 'External Link', icon: 'fas fa-external-link-alt', url: '#' },
                separator: { label: '— Separator —', icon: 'fas fa-minus', url: null }
            };
            
            const template = itemTypes[type] || itemTypes['custom-page'];
            
            return {
                id: id,
                type: type,
                label: template.label,
                icon: template.icon,
                url: template.url,
                description: '',
                target: '_self',
                badge: '',
                badge_color: 'primary',
                is_active: true,
                is_visible: true,
                permission: '',
                condition: '',
                custom_condition: '',
                parent_id: null,
                sort_order: navigationConfiguration.items.length,
                children: []
            };
        }
        
        // Create navigation item element
        function createNavigationItemElement(item) {
            const itemElement = document.createElement('div');
            itemElement.className = 'nav-tree-item';
            itemElement.dataset.itemId = item.id;
            itemElement.dataset.type = item.type;
            
            if (!item.is_active) {
                itemElement.classList.add('disabled');
            }
            
            itemElement.innerHTML = `
                <div class="nav-item-header">
                    <div class="nav-item-content">
                        ${item.type !== 'separator' ? `<i class="${item.icon}"></i>` : ''}
                        <span class="nav-item-label">${item.label}</span>
                        ${item.badge ? `<span class="badge bg-${item.badge_color} nav-item-badge">${item.badge}</span>` : ''}
                        ${!item.is_visible ? '<i class="fas fa-eye-slash text-muted ms-2" title="Hidden"></i>' : ''}
                        ${item.permission ? '<i class="fas fa-lock text-warning ms-2" title="Permission Required"></i>' : ''}
                    </div>
                    <div class="nav-item-controls">
                        ${item.type !== 'separator' ? `
                            <button class="btn btn-outline-success btn-sm nav-item-control-btn" onclick="addChildItem('${item.id}')" title="Add Child">
                                <i class="fas fa-plus"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-outline-primary btn-sm nav-item-control-btn" onclick="editNavigationItem('${item.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm nav-item-control-btn" onclick="duplicateNavigationItem('${item.id}')" title="Duplicate">
                            <i class="fas fa-copy"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm nav-item-control-btn" onclick="removeNavigationItem('${item.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            // Add children container if item has children
            if (item.children && item.children.length > 0) {
                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'nav-item-children';
                
                item.children.forEach(childItem => {
                    const childElement = createNavigationItemElement(childItem);
                    childrenContainer.appendChild(childElement);
                });
                
                itemElement.appendChild(childrenContainer);
            }
            
            return itemElement;
        }
        
        // Edit navigation item
        function editNavigationItem(itemId) {
            const item = findNavigationItem(itemId);
            if (!item) return;
            
            currentEditingItem = item;
            populateNavigationItemModal(item);
            navItemConfigModal.show();
        }
        
        // Find navigation item by ID (recursive)
        function findNavigationItem(itemId, items = null) {
            const searchItems = items || navigationConfiguration.items;
            
            for (const item of searchItems) {
                if (item.id === itemId) {
                    return item;
                }
                if (item.children && item.children.length > 0) {
                    const found = findNavigationItem(itemId, item.children);
                    if (found) return found;
                }
            }
            
            return null;
        }
        
        // Populate navigation item modal
        function populateNavigationItemModal(item) {
            const form = document.getElementById('nav-item-config-form');
            
            form.querySelector('[name="label"]').value = item.label || '';
            form.querySelector('[name="icon"]').value = item.icon || '';
            form.querySelector('[name="url"]').value = item.url || '';
            form.querySelector('[name="description"]').value = item.description || '';
            form.querySelector('[name="target"]').value = item.target || '_self';
            form.querySelector('[name="badge"]').value = item.badge || '';
            form.querySelector('[name="badge_color"]').value = item.badge_color || 'primary';
            form.querySelector('[name="is_active"]').checked = item.is_active !== false;
            form.querySelector('[name="is_visible"]').checked = item.is_visible !== false;
            form.querySelector('[name="permission"]').value = item.permission || '';
            form.querySelector('[name="condition"]').value = item.condition || '';
            form.querySelector('[name="custom_condition"]').value = item.custom_condition || '';
            
            // Show/hide custom condition
            const customConfig = document.getElementById('custom-condition-config');
            customConfig.style.display = item.condition === 'custom' ? 'block' : 'none';
        }
        
        // Save navigation item
        function saveNavigationItem() {
            if (!currentEditingItem) return;
            
            const form = document.getElementById('nav-item-config-form');
            const formData = new FormData(form);
            
            currentEditingItem.label = formData.get('label') || '';
            currentEditingItem.icon = formData.get('icon') || '';
            currentEditingItem.url = formData.get('url') || '';
            currentEditingItem.description = formData.get('description') || '';
            currentEditingItem.target = formData.get('target') || '_self';
            currentEditingItem.badge = formData.get('badge') || '';
            currentEditingItem.badge_color = formData.get('badge_color') || 'primary';
            currentEditingItem.is_active = formData.has('is_active');
            currentEditingItem.is_visible = formData.has('is_visible');
            currentEditingItem.permission = formData.get('permission') || '';
            currentEditingItem.condition = formData.get('condition') || '';
            currentEditingItem.custom_condition = formData.get('custom_condition') || '';
            
            // Refresh item element
            refreshNavigationItemElement(currentEditingItem.id);
            
            navItemConfigModal.hide();
            currentEditingItem = null;
        }
        
        // Refresh navigation item element
        function refreshNavigationItemElement(itemId) {
            const item = findNavigationItem(itemId);
            const oldElement = navigationTree.querySelector(`[data-item-id="${itemId}"]`);
            
            if (item && oldElement) {
                const newElement = createNavigationItemElement(item);
                oldElement.replaceWith(newElement);
            }
        }
        
        // Global functions for navigation item management
        window.editNavigationItem = editNavigationItem;
        
        window.addChildItem = function(parentId) {
            const parentItem = findNavigationItem(parentId);
            if (parentItem) {
                const childId = 'nav_item_' + (navItemIdCounter++);
                const childItem = createDefaultNavigationItem('custom-page', childId);
                childItem.parent_id = parentId;
                
                if (!parentItem.children) parentItem.children = [];
                parentItem.children.push(childItem);
                
                // Refresh parent element to show new child
                refreshNavigationItemElement(parentId);
                
                // Open configuration for new child
                editNavigationItem(childId);
            }
        };
        
        window.duplicateNavigationItem = function(itemId) {
            const item = findNavigationItem(itemId);
            if (item) {
                const duplicatedItem = JSON.parse(JSON.stringify(item));
                duplicatedItem.id = 'nav_item_' + (navItemIdCounter++);
                duplicatedItem.label = duplicatedItem.label + ' (Copy)';
                
                // Clear children IDs for duplicated item
                if (duplicatedItem.children) {
                    duplicatedItem.children = duplicatedItem.children.map(child => {
                        child.id = 'nav_item_' + (navItemIdCounter++);
                        return child;
                    });
                }
                
                addNavigationItem(duplicatedItem.type, duplicatedItem);
            }
        };
        
        window.removeNavigationItem = function(itemId) {
            if (confirm('Are you sure you want to remove this navigation item and all its children?')) {
                // Remove from configuration
                removeNavigationItemRecursive(itemId);
                
                // Remove from DOM
                const itemElement = navigationTree.querySelector(`[data-item-id="${itemId}"]`);
                if (itemElement) itemElement.remove();
                
                // Show empty message if no items left
                if (navigationConfiguration.items.length === 0) {
                    const emptyMessage = navigationTree.querySelector('.empty-navigation-message');
                    if (emptyMessage) emptyMessage.style.display = 'block';
                }
            }
        };
        
        // Remove navigation item recursively
        function removeNavigationItemRecursive(itemId, items = null) {
            const searchItems = items || navigationConfiguration.items;
            
            for (let i = 0; i < searchItems.length; i++) {
                if (searchItems[i].id === itemId) {
                    searchItems.splice(i, 1);
                    return true;
                }
                if (searchItems[i].children) {
                    if (removeNavigationItemRecursive(itemId, searchItems[i].children)) {
                        return true;
                    }
                }
            }
            
            return false;
        }
        
        // Toggle breadcrumb configuration
        function toggleBreadcrumbConfig() {
            const breadcrumbCard = document.getElementById('breadcrumb-config-card');
            const enableBreadcrumbs = document.getElementById('enable-breadcrumbs').checked;
            
            breadcrumbCard.style.display = enableBreadcrumbs ? 'block' : 'none';
            
            if (enableBreadcrumbs) {
                updateBreadcrumbPreview();
            }
        }
        
        // Update breadcrumb preview
        function updateBreadcrumbPreview() {
            const separator = document.getElementById('breadcrumb-separator').value;
            const maxItems = parseInt(document.getElementById('breadcrumb-max-items').value);
            const showIcons = document.getElementById('breadcrumb-icons').checked;
            const preview = document.getElementById('breadcrumb-preview');
            
            // Sample breadcrumb items
            const items = [
                { label: 'Home', icon: 'fas fa-home', url: '/' },
                { label: 'Contracts', icon: 'fas fa-file-contract', url: '/contracts' },
                { label: 'Service Agreement', icon: 'fas fa-handshake', url: '/contracts/types/service' },
                { label: 'Contract #12345', icon: 'fas fa-file', url: null }
            ];
            
            const displayItems = items.slice(0, maxItems);
            
            preview.innerHTML = displayItems.map((item, index) => {
                const isLast = index === displayItems.length - 1;
                const iconHtml = showIcons ? `<i class="${item.icon}"></i> ` : '';
                
                if (isLast) {
                    return `<li class="breadcrumb-item active" aria-current="page">${iconHtml}${item.label}</li>`;
                } else {
                    return `<li class="breadcrumb-item"><a href="${item.url}">${iconHtml}${item.label}</a></li>`;
                }
            }).join('');
            
            // Update separator style
            const style = document.querySelector('#breadcrumb-separator-style') || document.createElement('style');
            style.id = 'breadcrumb-separator-style';
            style.textContent = `
                .breadcrumb-item + .breadcrumb-item::before {
                    content: "${separator}";
                }
            `;
            document.head.appendChild(style);
        }
        
        // Update navigation order
        function updateNavigationOrder() {
            const itemElements = navigationTree.querySelectorAll('.nav-tree-item');
            itemElements.forEach((element, index) => {
                const itemId = element.dataset.itemId;
                const item = findNavigationItem(itemId);
                if (item) {
                    item.sort_order = index;
                }
            });
        }
        
        // Update navigation configuration
        function updateNavigationConfig() {
            navigationConfiguration.config = {
                name: document.getElementById('nav-config-name').value,
                contract_type: document.getElementById('nav-contract-type').value,
                style: document.getElementById('nav-style').value,
                position: document.getElementById('nav-position').value,
                enable_breadcrumbs: document.getElementById('enable-breadcrumbs').checked,
                enable_search: document.getElementById('enable-search').checked,
                responsive_menu: document.getElementById('responsive-menu').checked,
                permission_based: document.getElementById('permission-based').checked
            };
            
            // Update breadcrumb configuration
            if (navigationConfiguration.config.enable_breadcrumbs) {
                navigationConfiguration.breadcrumb = {
                    separator: document.getElementById('breadcrumb-separator').value,
                    max_items: parseInt(document.getElementById('breadcrumb-max-items').value),
                    show_icons: document.getElementById('breadcrumb-icons').checked
                };
            }
        }
        
        // Add submenu
        function addSubmenu() {
            if (navigationConfiguration.items.length === 0) {
                showMessage('warning', 'Please add at least one navigation item first');
                return;
            }
            
            // Find the last item and add a child to it
            const lastItem = navigationConfiguration.items[navigationConfiguration.items.length - 1];
            window.addChildItem(lastItem.id);
        }
        
        // Import selected template
        function importSelectedTemplate() {
            const selectedTemplate = document.querySelector('.template-option.selected');
            if (!selectedTemplate) {
                showMessage('warning', 'Please select a template');
                return;
            }
            
            const templateType = selectedTemplate.dataset.template;
            loadNavigationTemplate(templateType);
            
            importTemplateModal.hide();
        }
        
        // Load navigation template
        function loadNavigationTemplate(templateType) {
            const templates = {
                standard: [
                    { type: 'overview', label: 'Overview', icon: 'fas fa-info-circle' },
                    { type: 'details', label: 'Details', icon: 'fas fa-file-contract' },
                    { type: 'timeline', label: 'Timeline', icon: 'fas fa-history' },
                    { type: 'attachments', label: 'Attachments', icon: 'fas fa-paperclip' },
                    { type: 'edit', label: 'Edit', icon: 'fas fa-edit' }
                ],
                financial: [
                    { type: 'overview', label: 'Overview', icon: 'fas fa-info-circle' },
                    { type: 'details', label: 'Financial Details', icon: 'fas fa-dollar-sign' },
                    { type: 'invoices', label: 'Invoices', icon: 'fas fa-file-invoice' },
                    { type: 'payments', label: 'Payments', icon: 'fas fa-credit-card' },
                    { type: 'reports', label: 'Reports', icon: 'fas fa-chart-bar' }
                ],
                service: [
                    { type: 'overview', label: 'Overview', icon: 'fas fa-info-circle' },
                    { type: 'details', label: 'Service Details', icon: 'fas fa-tools' },
                    { type: 'custom-page', label: 'SLA Terms', icon: 'fas fa-clock' },
                    { type: 'custom-page', label: 'Assets', icon: 'fas fa-server' },
                    { type: 'custom-page', label: 'Performance', icon: 'fas fa-chart-line' }
                ],
                minimal: [
                    { type: 'overview', label: 'Overview', icon: 'fas fa-info-circle' },
                    { type: 'edit', label: 'Edit', icon: 'fas fa-edit' },
                    { type: 'timeline', label: 'History', icon: 'fas fa-history' }
                ]
            };
            
            const template = templates[templateType] || templates.standard;
            
            // Clear existing navigation
            clearNavigation(false);
            
            // Add template items
            template.forEach((itemConfig, index) => {
                const item = createDefaultNavigationItem(itemConfig.type, 'nav_item_' + (navItemIdCounter++));
                item.label = itemConfig.label;
                item.icon = itemConfig.icon;
                item.sort_order = index;
                
                addNavigationItem(item.type, item);
            });
            
            showMessage('success', `${templateType.charAt(0).toUpperCase() + templateType.slice(1)} template imported successfully`);
        }
        
        // Export configuration
        function exportConfiguration() {
            updateNavigationConfig();
            
            const config = JSON.stringify(navigationConfiguration, null, 2);
            const blob = new Blob([config], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `navigation-config-${navigationConfiguration.config.name || 'untitled'}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            showMessage('success', 'Configuration exported successfully');
        }
        
        // Collapse all items
        function collapseAllItems() {
            const childrenContainers = navigationTree.querySelectorAll('.nav-item-children');
            childrenContainers.forEach(container => {
                container.style.display = 'none';
            });
        }
        
        // Expand all items
        function expandAllItems() {
            const childrenContainers = navigationTree.querySelectorAll('.nav-item-children');
            childrenContainers.forEach(container => {
                container.style.display = 'block';
            });
        }
        
        // Clear navigation
        function clearNavigation(confirm = true) {
            if (confirm && !window.confirm('Are you sure you want to clear the entire navigation? This action cannot be undone.')) {
                return;
            }
            
            navigationTree.innerHTML = '<div class="empty-navigation-message"><div class="text-center py-5"><i class="fas fa-sitemap fa-3x text-muted mb-3"></i><h5 class="text-muted">Build Your Navigation</h5><p class="text-muted">Drag navigation components from the left panel to create your menu structure.<br>You can create nested submenus and organize items hierarchically.</p></div></div>';
            navigationConfiguration = { config: {}, items: [], breadcrumb: {} };
            navItemIdCounter = 1;
        }
        
        // Preview navigation
        function previewNavigation() {
            updateNavigationConfig();
            
            const previewContainer = document.getElementById('navigation-preview-container');
            previewContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Generating preview...</div>';
            
            fetch('/admin/navigation/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(navigationConfiguration)
            })
            .then(response => response.text())
            .then(html => {
                previewContainer.innerHTML = html;
                updatePreviewStyle(); // Apply current style
            })
            .catch(error => {
                console.error('Preview error:', error);
                previewContainer.innerHTML = '<div class="text-danger text-center py-4">Error generating preview</div>';
            });
            
            navPreviewModal.show();
        }
        
        // Update preview style
        function updatePreviewStyle() {
            const selectedStyle = document.querySelector('input[name="preview-style"]:checked').value;
            const previewContainer = document.getElementById('navigation-preview-container');
            
            previewContainer.className = `preview-device-frame ${selectedStyle} border rounded p-3`;
        }
        
        // Save navigation
        function saveNavigation() {
            updateNavigationConfig();
            
            if (!navigationConfiguration.config.name) {
                showMessage('error', 'Please enter a configuration name');
                return;
            }
            
            if (!navigationConfiguration.config.contract_type) {
                showMessage('error', 'Please select a contract type');
                return;
            }
            
            fetch('/admin/navigation/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(navigationConfiguration)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', 'Navigation configuration saved successfully');
                } else {
                    showMessage('error', data.message || 'Failed to save configuration');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showMessage('error', 'Failed to save configuration');
            });
        }
        
        // Publish navigation
        function publishNavigation() {
            updateNavigationConfig();
            
            if (!navigationConfiguration.config.name) {
                showMessage('error', 'Please enter a configuration name');
                return;
            }
            
            if (!navigationConfiguration.config.contract_type) {
                showMessage('error', 'Please select a contract type');
                return;
            }
            
            if (navigationConfiguration.items.length === 0) {
                showMessage('error', 'Please add at least one navigation item');
                return;
            }
            
            if (confirm('Are you sure you want to publish this navigation configuration? It will be applied immediately to the selected contract type.')) {
                fetch('/admin/navigation/publish', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(navigationConfiguration)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', 'Navigation published successfully');
                    } else {
                        showMessage('error', data.message || 'Failed to publish navigation');
                    }
                })
                .catch(error => {
                    console.error('Publish error:', error);
                    showMessage('error', 'Failed to publish navigation');
                });
            }
        }
        
        // Load existing configuration
        function loadExistingConfiguration() {
            const urlParams = new URLSearchParams(window.location.search);
            const configId = urlParams.get('edit');
            
            if (configId) {
                fetch(`/admin/navigation/${configId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            navigationConfiguration = data.configuration;
                            populateConfigurationFromData();
                        }
                    })
                    .catch(error => {
                        console.error('Load error:', error);
                        showMessage('error', 'Failed to load configuration');
                    });
            }
        }
        
        // Populate configuration from loaded data
        function populateConfigurationFromData() {
            // Populate form configuration
            document.getElementById('nav-config-name').value = navigationConfiguration.config.name || '';
            document.getElementById('nav-contract-type').value = navigationConfiguration.config.contract_type || '';
            document.getElementById('nav-style').value = navigationConfiguration.config.style || 'horizontal';
            document.getElementById('nav-position').value = navigationConfiguration.config.position || 'top';
            document.getElementById('enable-breadcrumbs').checked = navigationConfiguration.config.enable_breadcrumbs !== false;
            document.getElementById('enable-search').checked = navigationConfiguration.config.enable_search || false;
            document.getElementById('responsive-menu').checked = navigationConfiguration.config.responsive_menu !== false;
            document.getElementById('permission-based').checked = navigationConfiguration.config.permission_based || false;
            
            // Populate breadcrumb configuration
            if (navigationConfiguration.breadcrumb) {
                document.getElementById('breadcrumb-separator').value = navigationConfiguration.breadcrumb.separator || '>';
                document.getElementById('breadcrumb-max-items').value = navigationConfiguration.breadcrumb.max_items || 5;
                document.getElementById('breadcrumb-icons').checked = navigationConfiguration.breadcrumb.show_icons !== false;
            }
            
            // Toggle breadcrumb config visibility
            toggleBreadcrumbConfig();
            
            // Clear canvas and add items
            const emptyMessage = navigationTree.querySelector('.empty-navigation-message');
            if (emptyMessage) emptyMessage.style.display = 'none';
            
            navigationConfiguration.items.forEach(item => {
                const itemElement = createNavigationItemElement(item);
                navigationTree.appendChild(itemElement);
            });
            
            // Update counter
            navItemIdCounter = Math.max(...navigationConfiguration.items.map(item => parseInt(item.id.replace('nav_item_', '')))) + 1 || 1;
        }
        
        // Utility function for showing messages
        function showMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.querySelector('.navigation-builder').insertAdjacentHTML('afterbegin', alertHtml);
            
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }
    });
</script>
@endpush