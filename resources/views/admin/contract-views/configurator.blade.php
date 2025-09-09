@extends('layouts.app')

@section('title', 'Contract View Configurator')

@section('content')
<div class="contract-view-configurator">
    <div class="flex flex-wrap -mx-4">
        {{-- Configurator Header --}}
        <div class="flex-1 px-6-12">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2>Contract View Configurator</h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-0">Customize how contract details are displayed and organized</p>
                </div>
                <div class="view-actions">
                    <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary mr-2" id="preview-view">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-success mr-2" id="save-configuration">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                    <button class="btn px-6 py-2 font-medium rounded-md transition-colors-success" id="apply-configuration">
                        <i class="fas fa-check"></i> Apply to Contract Type
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Configuration Sidebar --}}
        <div class="flex-1 px-6-md-3">
            <div class="config-sidebar">
                {{-- View Configuration --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                        <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-cog"></i> View Configuration
                        </h6>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <form id="view-config">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="config-name">Configuration Name</label>
                                <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="config-name" placeholder="Enter configuration name" required>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="contract-type">Contract Type</label>
                                <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="contract-type" required>
                                    <option value="">Select contract type...</option>
                                    <option value="service_agreement">Service Agreement</option>
                                    <option value="maintenance_contract">Maintenance Contract</option>
                                    <option value="support_contract">Support Contract</option>
                                    <option value="custom">Custom Contract</option>
                                </select>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="view-layout">View Layout</label>
                                <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="view-layout">
                                    <option value="standard">Standard Layout</option>
                                    <option value="compact">Compact Layout</option>
                                    <option value="wide">Wide Layout</option>
                                </select>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="sidebar-position">Sidebar Position</label>
                                <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="sidebar-position">
                                    <option value="right">Right Sidebar</option>
                                    <option value="left">Left Sidebar</option>
                                    <option value="none">No Sidebar</option>
                                </select>
                            </div>
                            
                            <div class="flex items-center mb-2">
                                <input type="checkbox" class="flex items-center-input" id="show-tabs" checked>
                                <label class="flex items-center-label" for="show-tabs">
                                    Enable tabbed interface
                                </label>
                            </div>
                            
                            <div class="flex items-center mb-2">
                                <input type="checkbox" class="flex items-center-input" id="show-timeline" checked>
                                <label class="flex items-center-label" for="show-timeline">
                                    Show timeline
                                </label>
                            </div>
                            
                            <div class="flex items-center mb-2">
                                <input type="checkbox" class="flex items-center-input" id="show-comments" checked>
                                <label class="flex items-center-label" for="show-comments">
                                    Show comments
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" class="flex items-center-input" id="show-attachments" checked>
                                <label class="flex items-center-label" for="show-attachments">
                                    Show attachments
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
                
                {{-- Section Templates --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                        <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-th-large"></i> Section Templates
                        </h6>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body p-0">
                        <div class="section-templates">
                            <div class="template-category">
                                <div class="category-header">
                                    <strong>Common Sections</strong>
                                </div>
                                <div class="template-list">
                                    <div class="template-item" data-template="overview">
                                        <i class="fas fa-info-circle"></i> Overview
                                    </div>
                                    <div class="template-item" data-template="contract-details">
                                        <i class="fas fa-file-contract"></i> Contract Details
                                    </div>
                                    <div class="template-item" data-template="financial-info">
                                        <i class="fas fa-dollar-sign"></i> Financial Info
                                    </div>
                                    <div class="template-item" data-template="parties-involved">
                                        <i class="fas fa-handshake"></i> Parties Involved
                                    </div>
                                    <div class="template-item" data-template="key-dates">
                                        <i class="fas fa-calendar"></i> Key Dates
                                    </div>
                                </div>
                            </div>
                            
                            <div class="template-category">
                                <div class="category-header">
                                    <strong>Technical Sections</strong>
                                </div>
                                <div class="template-list">
                                    <div class="template-item" data-template="service-scope">
                                        <i class="fas fa-tasks"></i> Service Scope
                                    </div>
                                    <div class="template-item" data-template="sla-terms">
                                        <i class="fas fa-clock"></i> SLA Terms
                                    </div>
                                    <div class="template-item" data-template="technical-requirements">
                                        <i class="fas fa-server"></i> Technical Requirements
                                    </div>
                                    <div class="template-item" data-template="asset-coverage">
                                        <i class="fas fa-shield-alt"></i> Asset Coverage
                                    </div>
                                </div>
                            </div>
                            
                            <div class="template-category">
                                <div class="category-header">
                                    <strong>Custom Sections</strong>
                                </div>
                                <div class="template-list">
                                    <div class="template-item" data-template="custom-fields">
                                        <i class="fas fa-plus-circle"></i> Custom Fields
                                    </div>
                                    <div class="template-item" data-template="related-documents">
                                        <i class="fas fa-paperclip"></i> Related Documents
                                    </div>
                                    <div class="template-item" data-template="compliance-tracking">
                                        <i class="fas fa-check-circle"></i> Compliance Tracking
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Tab Configuration --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                        <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-tabs"></i> Tab Configuration
                        </h6>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div class="tab-config-list" id="tab-config-list">
                            <div class="tab-config-item">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <span>Overview</span>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="flex items-center-input" checked disabled>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-config-item">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <i class="fas fa-history mr-2"></i>
                                        <span>Timeline</span>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="flex items-center-input tab-toggle" data-tab="timeline" checked>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-config-item">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <i class="fas fa-paperclip mr-2"></i>
                                        <span>Attachments</span>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="flex items-center-input tab-toggle" data-tab="attachments" checked>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-config-item">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <i class="fas fa-comments mr-2"></i>
                                        <span>Comments</span>
                                    </div>
                                    <div>
                                        <input type="checkbox" class="flex items-center-input tab-toggle" data-tab="comments" checked>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button class="btn border border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-2 font-medium rounded-md transition-colors-sm w-100 mt-6" id="add-custom-tab">
                            <i class="fas fa-plus"></i> Add Custom Tab
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- View Designer Canvas --}}
        <div class="flex-1 px-6-md-9">
            <div class="view-designer-canvas">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header flex justify-between items-center">
                        <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-paint-brush"></i> View Designer
                        </h6>
                        <div class="canvas-actions">
                            <button class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-6 py-2 font-medium rounded-md transition-colors-sm mr-2" id="reset-view">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button class="btn border border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-2 font-medium rounded-md transition-colors-sm" id="add-section">
                                <i class="fas fa-plus"></i> Add Section
                            </button>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        {{-- View Canvas --}}
                        <div class="view-canvas" id="view-canvas">
                            <div class="empty-canvas-message">
                                <div class="text-center py-8">
                                    <i class="fas fa-layout fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                                    <h5 class="text-gray-600 dark:text-gray-400">Design Your Contract View</h5>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        Drag section templates from the left panel to build your contract detail view.
                                        <br>Each section can be customized with specific fields and layouts.
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

{{-- Section Configuration Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="sectionConfigModal" tabindex="-1">
    <div class="flex items-center justify-center min-h-screen fixed inset-0 z-50 overflow-y-auto-xl">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Configure Section</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                <div class="flex flex-wrap -mx-4">
                    {{-- Section Basic Settings --}}
                    <div class="flex-1 px-6-md-4">
                        <h6 class="border-b pb-2 mb-6">Basic Settings</h6>
                        <form id="section-basic-config">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="section-title">Section Title</label>
                                <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="section-title" name="title" required>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="section-description">Description</label>
                                <textarea class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="section-description" name="description" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="section-icon">Icon</label>
                                <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="section-icon" name="icon" placeholder="fas fa-info-circle">
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="layout-type">Layout Type</label>
                                <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="layout-type" name="layout">
                                    <option value="grid">Grid Layout</option>
                                    <option value="table">Table Layout</option>
                                    <option value="list">List Layout</option>
                                </select>
                            </div>
                            
                            <div class="flex flex-wrap -mx-4">
                                <div class="flex-1 px-6-6">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="flex items-center-input" id="collapsible" name="collapsible">
                                        <label class="flex items-center-label" for="collapsible">Collapsible</label>
                                    </div>
                                </div>
                                <div class="flex-1 px-6-6">
                                    <div class="flex items-center">
                                        <input type="checkbox" class="flex items-center-input" id="hide-empty" name="hide_empty">
                                        <label class="flex items-center-label" for="hide-empty">Hide Empty Fields</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    {{-- Available Fields --}}
                    <div class="flex-1 px-6-md-4">
                        <h6 class="border-b pb-2 mb-6">Available Fields</h6>
                        <div class="available-fields" id="available-fields">
                            <div class="field-search mb-6">
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm" 
                                       placeholder="Search fields..." id="field-search">
                            </div>
                            <div class="fields-list" style="max-height: 400px; overflow-y: auto;">
                                {{-- Populated dynamically --}}
                            </div>
                        </div>
                    </div>
                    
                    {{-- Selected Fields --}}
                    <div class="flex-1 px-6-md-4">
                        <h6 class="border-b pb-2 mb-6">Section Fields</h6>
                        <div class="selected-fields" id="selected-fields">
                            <div class="fields-container mx-auto" id="section-fields-container">
                                <div class="empty-fields-message text-center text-gray-600 dark:text-gray-400 py-6">
                                    <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                                    <p class="small mb-0">Drag fields from the left panel</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Cancel</button>
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary" id="save-section-config">Save Section</button>
            </div>
        </div>
    </div>
</div>

{{-- Field Configuration Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="fieldConfigModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Configure Field Display</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                <form id="field-display-config">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="display-label">Display Label</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="display-label" name="label" required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="display-type">Display Type</label>
                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="display-type" name="type">
                            <option value="text">Text</option>
                            <option value="currency">Currency</option>
                            <option value="date">Date</option>
                            <option value="datetime">Date Time</option>
                            <option value="boolean">Yes/No</option>
                            <option value="status">Status Badge</option>
                            <option value="progress">Progress Bar</option>
                            <option value="percentage">Percentage</option>
                            <option value="email">Email Link</option>
                            <option value="url">Web Link</option>
                            <option value="phone">Phone Link</option>
                            <option value="file">File Link</option>
                            <option value="json">JSON Data</option>
                            <option value="tags">Tags</option>
                            <option value="client">Client Link</option>
                            <option value="user">User Profile</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="column-size">Column Size</label>
                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="column-size" name="col_size">
                            <option value="w-12/12">Full Width</option>
                            <option value="md:w-1/2" selected>Half Width</option>
                            <option value="md:w-1/3">One Third</option>
                            <option value="md:w-1/4">One Quarter</option>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="help-text">Help Text</label>
                        <textarea class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="help-text" name="help_text" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-6" id="field-actions-config" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="actions-list">Field Actions</label>
                        <div class="actions-list" id="actions-list">
                            <button type="button" class="btn border border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-2 font-medium rounded-md transition-colors-sm" id="add-field-action">
                                <i class="fas fa-plus"></i> Add Action
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Cancel</button>
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary" id="save-field-config">Save Field</button>
            </div>
        </div>
    </div>
</div>

{{-- Custom Tab Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="customTabModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Add Custom Tab</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                <form id="custom-tab-form">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="tab-label">Tab Label</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="tab-label" name="label" required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="tab-icon">Tab Icon</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="tab-icon" name="icon" placeholder="fas fa-star">
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="content-type">Content Type</label>
                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="content-type" name="content_type">
                            <option value="sections">Sections (Configure below)</option>
                            <option value="component">Custom Component</option>
                            <option value="external">External Content (URL)</option>
                        </select>
                    </div>
                    
                    <div class="mb-6" id="component-config" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="component-path">Component Path</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="component-path" name="component" 
                               placeholder="components.contracts.custom-tab">
                    </div>
                    
                    <div class="mb-6" id="external-config" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="external-url">External URL</label>
                        <input type="url" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="external-url" name="external_url" 
                               placeholder="https://example.com/content/{contract_id}">
                    </div>
                </form>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Cancel</button>
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary" id="save-custom-tab">Add Tab</button>
            </div>
        </div>
    </div>
</div>

{{-- View Preview Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="viewPreviewModal" tabindex="-1">
    <div class="flex items-center justify-center min-h-screen fixed inset-0 z-50 overflow-y-auto-xl">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">View Configuration Preview</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                <div id="view-preview-container" style="max-height: 600px; overflow-y: auto;">
                    {{-- Preview content will be loaded here --}}
                </div>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Close</button>
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary" id="apply-from-preview">Apply Configuration</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .contract-view-configurator {
        min-height: calc(100vh - 200px);
    }
    
    .config-sidebar {
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 40px);
        overflow-y: auto;
    }
    
    .section-templates {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .template-category {
        border-bottom: 1px solid #e9ecef;
    }
    
    .category-header {
        padding: 0.75rem 1rem 0.5rem;
        background-color: #f8f9fa;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .template-list {
        padding: 0.5rem 0;
    }
    
    .template-item {
        padding: 0.75rem 1rem;
        cursor: grab;
        border-bottom: 1px solid #f1f3f4;
        transition: all 0.2s ease;
        user-select: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .template-item:hover {
        background-color: #e3f2fd;
        transform: translateX(4px);
    }
    
    .template-item:active {
        cursor: grabbing;
    }
    
    .template-item i {
        color: #1976d2;
        width: 16px;
    }
    
    .view-designer-canvas {
        min-height: 600px;
    }
    
    .view-canvas {
        min-height: 500px;
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .view-canvas.drag-over {
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
    
    .view-section {
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        background: white;
        position: relative;
        transition: all 0.2s ease;
    }
    
    .view-section:hover {
        border-color: #1976d2;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .section-header {
        padding: 1rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        display: flex;
        justify-content: between;
        align-items: center;
    }
    
    .section-content {
        padding: 1rem;
        min-height: 80px;
    }
    
    .section-controls {
        position: absolute;
        top: -8px;
        right: -8px;
        display: none;
        gap: 0.25rem;
    }
    
    .view-section:hover .section-controls {
        display: flex;
    }
    
    .section-control-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
    }
    
    .field-item {
        padding: 0.5rem;
        margin: 0.25rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-block;
    }
    
    .field-item:hover {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .field-item.selected {
        border-color: #1976d2;
        background-color: #e3f2fd;
    }
    
    .available-field {
        padding: 0.5rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        margin-bottom: 0.25rem;
        cursor: grab;
        transition: all 0.2s ease;
        background: white;
    }
    
    .available-field:hover {
        border-color: #1976d2;
        background-color: #e3f2fd;
        transform: translateX(2px);
    }
    
    .available-field:active {
        cursor: grabbing;
    }
    
    .tab-config-item {
        padding: 0.75rem;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .tab-config-item:last-child {
        border-bottom: none;
    }
    
    .sortable-ghost {
        opacity: 0.4;
        background-color: #e3f2fd !important;
    }
    
    .sortable-chosen {
        background-color: #e3f2fd !important;
    }
    
    .field-preview {
        font-size: 0.875rem;
        color: #666;
        font-style: italic;
    }
    
    .section-field-item {
        display: flex;
        justify-content: between;
        align-items: center;
        padding: 0.5rem;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        background: white;
    }
    
    .section-field-item:hover {
        border-color: #1976d2;
        background-color: #f8f9fa;
    }
    
    /* Drag placeholder */
    .drag-placeholder {
        height: 60px;
        border: 2px dashed #1976d2;
        border-radius: 0.375rem;
        background-color: #e3f2fd;
        margin: 0.5rem 0;
        display: none;
        align-items: center;
        justify-content: center;
        color: #1976d2;
        font-size: 0.875rem;
    }
    
    .drag-placeholder.active {
        display: flex;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .config-sidebar {
            position: static;
            max-height: none;
        }
        
        .template-item {
            padding: 0.5rem;
            font-size: 0.875rem;
        }
        
        .view-designer-canvas {
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
        const viewCanvas = document.getElementById('view-canvas');
        const templateItems = document.querySelectorAll('.template-item');
        const sectionConfigModal = new bootstrap.Modal(document.getElementById('sectionConfigModal'));
        const fieldConfigModal = new bootstrap.Modal(document.getElementById('fieldConfigModal'));
        const customTabModal = new bootstrap.Modal(document.getElementById('customTabModal'));
        const viewPreviewModal = new bootstrap.Modal(document.getElementById('viewPreviewModal'));
        
        let viewConfiguration = {
            config: {},
            sections: [],
            tabs: []
        };
        let draggedTemplate = null;
        let currentEditingSection = null;
        let currentEditingField = null;
        let sectionIdCounter = 1;
        let availableFields = [];
        
        // Initialize configurator
        initializeConfigurator();
        
        function initializeConfigurator() {
            setupDragAndDrop();
            setupEventHandlers();
            setupSortable();
            loadAvailableFields();
            loadExistingConfiguration();
        }
        
        // Setup drag and drop
        function setupDragAndDrop() {
            // Make template items draggable
            templateItems.forEach(item => {
                item.draggable = true;
                
                item.addEventListener('dragstart', function(e) {
                    draggedTemplate = this.dataset.template;
                    e.dataTransfer.effectAllowed = 'copy';
                });
            });
            
            // Setup drop zone
            viewCanvas.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('drag-over');
            });
            
            viewCanvas.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            viewCanvas.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                
                if (draggedTemplate) {
                    addSectionToView(draggedTemplate);
                    draggedTemplate = null;
                }
            });
        }
        
        // Setup event handlers
        function setupEventHandlers() {
            // Configuration changes
            document.getElementById('config-name').addEventListener('input', updateViewConfig);
            document.getElementById('contract-type').addEventListener('change', updateViewConfig);
            document.getElementById('view-layout').addEventListener('change', updateViewConfig);
            document.getElementById('sidebar-position').addEventListener('change', updateViewConfig);
            document.getElementById('show-tabs').addEventListener('change', updateViewConfig);
            document.getElementById('show-timeline').addEventListener('change', updateViewConfig);
            document.getElementById('show-comments').addEventListener('change', updateViewConfig);
            document.getElementById('show-attachments').addEventListener('change', updateViewConfig);
            
            // Action buttons
            document.getElementById('add-section').addEventListener('click', showSectionTemplates);
            document.getElementById('reset-view').addEventListener('click', resetView);
            document.getElementById('preview-view').addEventListener('click', previewView);
            document.getElementById('save-configuration').addEventListener('click', saveConfiguration);
            document.getElementById('apply-configuration').addEventListener('click', applyConfiguration);
            
            // Modal handlers
            document.getElementById('save-section-config').addEventListener('click', saveSectionConfig);
            document.getElementById('save-field-config').addEventListener('click', saveFieldConfig);
            document.getElementById('add-custom-tab').addEventListener('click', () => customTabModal.show());
            document.getElementById('save-custom-tab').addEventListener('click', saveCustomTab);
            
            // Tab toggles
            document.querySelectorAll('.tab-toggle').forEach(toggle => {
                toggle.addEventListener('change', updateTabConfiguration);
            });
            
            // Field search
            document.getElementById('field-search').addEventListener('input', filterAvailableFields);
            
            // Custom tab form changes
            document.querySelector('[name="content_type"]').addEventListener('change', function() {
                const componentConfig = document.getElementById('component-config');
                const externalConfig = document.getElementById('external-config');
                
                componentConfig.style.display = this.value === 'component' ? 'block' : 'none';
                externalConfig.style.display = this.value === 'external' ? 'block' : 'none';
            });
        }
        
        // Setup sortable functionality
        function setupSortable() {
            new Sortable(viewCanvas, {
                group: 'view-sections',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function(evt) {
                    updateSectionOrder();
                }
            });
        }
        
        // Load available fields from backend
        function loadAvailableFields() {
            fetch('/admin/contract-views/fields')
                .then(response => response.json())
                .then(data => {
                    availableFields = data.fields || [];
                    renderAvailableFields();
                })
                .catch(error => {
                    console.error('Error loading fields:', error);
                    // Fallback fields
                    availableFields = [
                        { key: 'name', label: 'Contract Name', type: 'text' },
                        { key: 'description', label: 'Description', type: 'textarea' },
                        { key: 'status', label: 'Status', type: 'status' },
                        { key: 'value', label: 'Contract Value', type: 'currency' },
                        { key: 'start_date', label: 'Start Date', type: 'date' },
                        { key: 'end_date', label: 'End Date', type: 'date' },
                        { key: 'client_id', label: 'Client', type: 'client' },
                        { key: 'created_at', label: 'Created At', type: 'datetime' },
                        { key: 'updated_at', label: 'Updated At', type: 'datetime' }
                    ];
                    renderAvailableFields();
                });
        }
        
        // Render available fields
        function renderAvailableFields() {
            const container = document.querySelector('.fields-list');
            container.innerHTML = '';
            
            availableFields.forEach(field => {
                const fieldElement = document.createElement('div');
                fieldElement.className = 'available-field';
                fieldElement.dataset.fieldKey = field.key;
                fieldElement.draggable = true;
                
                fieldElement.innerHTML = `
                    <div class="flex justify-between items-center">
                        <div>
                            <strong>${field.label}</strong>
                            <br><small class="text-gray-600 dark:text-gray-400">${field.key} (${field.type})</small>
                        </div>
                        <i class="fas fa-grip-vertical text-gray-600 dark:text-gray-400"></i>
                    </div>
                `;
                
                container.appendChild(fieldElement);
            });
            
            // Make fields draggable to section
            setupFieldDragAndDrop();
        }
        
        // Setup field drag and drop to sections
        function setupFieldDragAndDrop() {
            const availableFieldElements = document.querySelectorAll('.available-field');
            
            availableFieldElements.forEach(field => {
                field.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('field-key', this.dataset.fieldKey);
                });
            });
        }
        
        // Filter available fields
        function filterAvailableFields() {
            const searchTerm = document.getElementById('field-search').value.toLowerCase();
            const fieldElements = document.querySelectorAll('.available-field');
            
            fieldElements.forEach(element => {
                const fieldText = element.textContent.toLowerCase();
                element.style.display = fieldText.includes(searchTerm) ? 'block' : 'none';
            });
        }
        
        // Add section to view
        function addSectionToView(templateType, sectionData = null) {
            const sectionId = 'section_' + (sectionIdCounter++);
            const section = sectionData || createSectionFromTemplate(templateType, sectionId);
            
            const sectionElement = createSectionElement(section);
            
            // Hide empty message
            const emptyMessage = viewCanvas.querySelector('.empty-canvas-message');
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            
            viewCanvas.appendChild(sectionElement);
            viewConfiguration.sections.push(section);
            
            // Open configuration modal for new sections
            if (!sectionData) {
                editSection(sectionId);
            }
        }
        
        // Create section from template
        function createSectionFromTemplate(templateType, sectionId) {
            const templates = {
                'overview': {
                    title: 'Overview',
                    icon: 'fas fa-info-circle',
                    fields: ['name', 'description', 'status'],
                    layout: 'grid'
                },
                'contract-details': {
                    title: 'Contract Details',
                    icon: 'fas fa-file-contract',
                    fields: ['start_date', 'end_date', 'value'],
                    layout: 'grid'
                },
                'financial-info': {
                    title: 'Financial Information',
                    icon: 'fas fa-dollar-sign',
                    fields: ['value', 'payment_terms', 'billing_frequency'],
                    layout: 'table'
                },
                'parties-involved': {
                    title: 'Parties Involved',
                    icon: 'fas fa-handshake',
                    fields: ['client_id', 'primary_contact', 'account_manager'],
                    layout: 'grid'
                },
                'key-dates': {
                    title: 'Key Dates',
                    icon: 'fas fa-calendar',
                    fields: ['start_date', 'end_date', 'renewal_date', 'created_at'],
                    layout: 'table'
                },
                'service-scope': {
                    title: 'Service Scope',
                    icon: 'fas fa-tasks',
                    fields: ['service_description', 'deliverables', 'exclusions'],
                    layout: 'list'
                },
                'sla-terms': {
                    title: 'SLA Terms',
                    icon: 'fas fa-clock',
                    fields: ['response_time', 'resolution_time', 'uptime_guarantee'],
                    layout: 'grid'
                },
                'technical-requirements': {
                    title: 'Technical Requirements',
                    icon: 'fas fa-server',
                    fields: ['tech_requirements', 'software_licenses', 'hardware_specs'],
                    layout: 'list'
                },
                'asset-coverage': {
                    title: 'Asset Coverage',
                    icon: 'fas fa-shield-alt',
                    fields: ['covered_assets', 'asset_count', 'coverage_type'],
                    layout: 'grid'
                },
                'custom-fields': {
                    title: 'Custom Fields',
                    icon: 'fas fa-plus-circle',
                    fields: [],
                    layout: 'grid'
                },
                'related-documents': {
                    title: 'Related Documents',
                    icon: 'fas fa-paperclip',
                    fields: ['attachments', 'referenced_contracts'],
                    layout: 'list'
                },
                'compliance-tracking': {
                    title: 'Compliance Tracking',
                    icon: 'fas fa-check-circle',
                    fields: ['compliance_status', 'audit_date', 'certifications'],
                    layout: 'grid'
                }
            };
            
            const template = templates[templateType] || templates['overview'];
            
            return {
                id: sectionId,
                type: templateType,
                title: template.title,
                icon: template.icon,
                description: '',
                layout: template.layout,
                fields: template.fields.map(fieldKey => {
                    const fieldData = availableFields.find(f => f.key === fieldKey);
                    return {
                        key: fieldKey,
                        label: fieldData ? fieldData.label : fieldKey,
                        type: fieldData ? fieldData.type : 'text',
                        col_size: 'md:w-1/2'
                    };
                }),
                collapsible: false,
                hide_empty: false,
                sort_order: viewConfiguration.sections.length
            };
        }
        
        // Create section element
        function createSectionElement(section) {
            const sectionElement = document.createElement('div');
            sectionElement.className = 'view-section';
            sectionElement.dataset.sectionId = section.id;
            
            sectionElement.innerHTML = `
                <div class="section-controls">
                    <button class="btn bg-blue-600 text-white hover:bg-blue-700 px-4 py-1 text-sm section-control-px-6 py-2 font-medium rounded-md transition-colors" onclick="editSection('${section.id}')" title="Edit Section">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-4 py-1 text-sm section-control-px-6 py-2 font-medium rounded-md transition-colors" onclick="duplicateSection('${section.id}')" title="Duplicate">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button class="btn bg-red-600 text-white hover:bg-red-700 px-4 py-1 text-sm section-control-px-6 py-2 font-medium rounded-md transition-colors" onclick="removeSection('${section.id}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="section-header">
                    <div class="flex items-center">
                        <i class="${section.icon} mr-2"></i>
                        <div>
                            <h6 class="mb-0">${section.title}</h6>
                            ${section.description ? `<small class="text-gray-600 dark:text-gray-400">${section.description}</small>` : ''}
                        </div>
                    </div>
                    <div class="section-meta">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary">${section.layout}</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">${section.fields.length} fields</span>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="section-fields">
                        ${section.fields.length > 0 ? 
                            section.fields.map(field => `
                                <div class="field-item" data-field-key="${field.key}">
                                    <strong>${field.label}</strong>
                                    <div class="field-preview">${field.key} (${field.type})</div>
                                </div>
                            `).join('') : 
                            '<div class="text-gray-600 dark:text-gray-400 text-center py-6">No fields configured</div>'
                        }
                    </div>
                </div>
            `;
            
            // Add click handler for section selection
            sectionElement.addEventListener('click', function(e) {
                if (!e.target.closest('.section-controls')) {
                    selectSection(section.id);
                }
            });
            
            return sectionElement;
        }
        
        // Edit section
        function editSection(sectionId) {
            const section = viewConfiguration.sections.find(s => s.id === sectionId);
            if (!section) return;
            
            currentEditingSection = section;
            populateSectionConfigModal(section);
            sectionConfigModal.show();
        }
        
        // Populate section configuration modal
        function populateSectionConfigModal(section) {
            const form = document.getElementById('section-basic-config');
            
            form.querySelector('[name="title"]').value = section.title || '';
            form.querySelector('[name="description"]').value = section.description || '';
            form.querySelector('[name="icon"]').value = section.icon || '';
            form.querySelector('[name="layout"]').value = section.layout || 'grid';
            form.querySelector('[name="collapsible"]').checked = section.collapsible || false;
            form.querySelector('[name="hide_empty"]').checked = section.hide_empty || false;
            
            // Populate section fields
            renderSectionFields(section);
            
            // Setup field drop zone
            setupSectionFieldDropZone();
        }
        
        // Render section fields
        function renderSectionFields(section) {
            const container = document.getElementById('section-fields-container');
            
            if (section.fields.length === 0) {
                container.innerHTML = `
                    <div class="empty-fields-message text-center text-gray-600 dark:text-gray-400 py-6">
                        <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                        <p class="small mb-0">Drag fields from the left panel</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = section.fields.map(field => `
                <div class="section-field-item" data-field-key="${field.key}">
                    <div class="field-info">
                        <strong>${field.label}</strong>
                        <br><small class="text-gray-600 dark:text-gray-400">${field.key} (${field.type})</small>
                    </div>
                    <div class="field-actions">
                        <button class="btn border border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-2 font-medium rounded-md transition-colors-sm mr-1" onclick="editFieldInSection('${field.key}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn border border-red-600 text-red-600 hover:bg-red-50 px-6 py-2 font-medium rounded-md transition-colors-sm" onclick="removeFieldFromSection('${field.key}')" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `).join('');
            
            // Make sortable
            new Sortable(container, {
                animation: 150,
                onEnd: function(evt) {
                    updateSectionFieldOrder();
                }
            });
        }
        
        // Setup section field drop zone
        function setupSectionFieldDropZone() {
            const container = document.getElementById('section-fields-container');
            
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '#e3f2fd';
            });
            
            container.addEventListener('dragleave', function(e) {
                this.style.backgroundColor = '';
            });
            
            container.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.backgroundColor = '';
                
                const fieldKey = e.dataTransfer.getData('field-key');
                if (fieldKey && currentEditingSection) {
                    addFieldToSection(fieldKey);
                }
            });
        }
        
        // Add field to section
        function addFieldToSection(fieldKey) {
            if (!currentEditingSection) return;
            
            // Check if field already exists
            if (currentEditingSection.fields.find(f => f.key === fieldKey)) {
                showMessage('warning', 'Field already exists in this section');
                return;
            }
            
            const fieldData = availableFields.find(f => f.key === fieldKey);
            if (!fieldData) return;
            
            const field = {
                key: fieldKey,
                label: fieldData.label,
                type: fieldData.type,
                col_size: 'md:w-1/2',
                help_text: '',
                actions: []
            };
            
            currentEditingSection.fields.push(field);
            renderSectionFields(currentEditingSection);
        }
        
        // Edit field in section
        function editFieldInSection(fieldKey) {
            if (!currentEditingSection) return;
            
            const field = currentEditingSection.fields.find(f => f.key === fieldKey);
            if (!field) return;
            
            currentEditingField = field;
            populateFieldConfigModal(field);
            fieldConfigModal.show();
        }
        
        // Populate field configuration modal
        function populateFieldConfigModal(field) {
            const form = document.getElementById('field-display-config');
            
            form.querySelector('[name="label"]').value = field.label || '';
            form.querySelector('[name="type"]').value = field.type || 'text';
            form.querySelector('[name="col_size"]').value = field.col_size || 'md:w-1/2';
            form.querySelector('[name="help_text"]').value = field.help_text || '';
            
            // Show/hide field actions config based on field type
            const actionsConfig = document.getElementById('field-actions-config');
            const showActions = ['client', 'user', 'file', 'url', 'email'].includes(field.type);
            actionsConfig.style.display = showActions ? 'block' : 'none';
            
            if (showActions) {
                renderFieldActions(field);
            }
        }
        
        // Render field actions
        function renderFieldActions(field) {
            const container = document.getElementById('actions-list');
            const addButton = container.querySelector('#add-field-action');
            
            // Clear existing actions but keep add button
            container.innerHTML = '';
            container.appendChild(addButton);
            
            (field.actions || []).forEach((action, index) => {
                const actionHtml = `
                    <div class="field-action-item mb-2">
                        <div class="flex flex-wrap -mx-4">
                            <div class="flex-1 px-6-4">
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm" 
                                       placeholder="Label" value="${action.label || ''}" 
                                       data-action-prop="label" data-action-index="${index}">
                            </div>
                            <div class="flex-1 px-6-3">
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm" 
                                       placeholder="Icon" value="${action.icon || ''}"
                                       data-action-prop="icon" data-action-index="${index}">
                            </div>
                            <div class="flex-1 px-6-3">
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm" 
                                        data-action-prop="color" data-action-index="${index}">
                                    <option value="primary" ${action.color === 'primary' ? 'selected' : ''}>Primary</option>
                                    <option value="secondary" ${action.color === 'secondary' ? 'selected' : ''}>Secondary</option>
                                    <option value="success" ${action.color === 'success' ? 'selected' : ''}>Success</option>
                                    <option value="warning" ${action.color === 'warning' ? 'selected' : ''}>Warning</option>
                                    <option value="danger" ${action.color === 'danger' ? 'selected' : ''}>Danger</option>
                                </select>
                            </div>
                            <div class="flex-1 px-6-2">
                                <button type="button" class="btn border border-red-600 text-red-600 hover:bg-red-50 px-6 py-2 font-medium rounded-md transition-colors-sm w-100" 
                                        onclick="removeFieldAction(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                addButton.insertAdjacentHTML('beforebegin', actionHtml);
            });
            
            // Setup add action button
            addButton.onclick = function() {
                if (!field.actions) field.actions = [];
                field.actions.push({
                    label: 'Action',
                    icon: 'fas fa-link',
                    color: 'primary',
                    url: '#'
                });
                renderFieldActions(field);
            };
        }
        
        // Remove field action
        window.removeFieldAction = function(index) {
            if (currentEditingField && currentEditingField.actions) {
                currentEditingField.actions.splice(index, 1);
                renderFieldActions(currentEditingField);
            }
        };
        
        // Remove field from section
        window.removeFieldFromSection = function(fieldKey) {
            if (!currentEditingSection) return;
            
            currentEditingSection.fields = currentEditingSection.fields.filter(f => f.key !== fieldKey);
            renderSectionFields(currentEditingSection);
        };
        
        // Save section configuration
        function saveSectionConfig() {
            if (!currentEditingSection) return;
            
            const form = document.getElementById('section-basic-config');
            const formData = new FormData(form);
            
            currentEditingSection.title = formData.get('title') || '';
            currentEditingSection.description = formData.get('description') || '';
            currentEditingSection.icon = formData.get('icon') || '';
            currentEditingSection.layout = formData.get('layout') || 'grid';
            currentEditingSection.collapsible = formData.has('collapsible');
            currentEditingSection.hide_empty = formData.has('hide_empty');
            
            // Refresh section element
            refreshSectionElement(currentEditingSection.id);
            
            sectionConfigModal.hide();
            currentEditingSection = null;
        }
        
        // Save field configuration
        function saveFieldConfig() {
            if (!currentEditingField) return;
            
            const form = document.getElementById('field-display-config');
            const formData = new FormData(form);
            
            currentEditingField.label = formData.get('label') || '';
            currentEditingField.type = formData.get('type') || 'text';
            currentEditingField.col_size = formData.get('col_size') || 'md:w-1/2';
            currentEditingField.help_text = formData.get('help_text') || '';
            
            // Update actions if they exist
            const actionElements = document.querySelectorAll('[data-action-prop]');
            actionElements.forEach(element => {
                const prop = element.dataset.actionProp;
                const index = parseInt(element.dataset.actionIndex);
                
                if (currentEditingField.actions && currentEditingField.actions[index]) {
                    currentEditingField.actions[index][prop] = element.value;
                }
            });
            
            // Refresh section fields display
            if (currentEditingSection) {
                renderSectionFields(currentEditingSection);
            }
            
            fieldConfigModal.hide();
            currentEditingField = null;
        }
        
        // Refresh section element
        function refreshSectionElement(sectionId) {
            const section = viewConfiguration.sections.find(s => s.id === sectionId);
            const oldElement = viewCanvas.querySelector(`[data-section-id="${sectionId}"]`);
            
            if (section && oldElement) {
                const newElement = createSectionElement(section);
                oldElement.replaceWith(newElement);
            }
        }
        
        // Update section field order
        function updateSectionFieldOrder() {
            if (!currentEditingSection) return;
            
            const fieldElements = document.querySelectorAll('.section-field-item');
            const newOrder = Array.from(fieldElements).map(el => el.dataset.fieldKey);
            
            currentEditingSection.fields = newOrder.map(fieldKey => 
                currentEditingSection.fields.find(f => f.key === fieldKey)
            ).filter(Boolean);
        }
        
        // Global functions for section management
        window.editSection = editSection;
        
        window.duplicateSection = function(sectionId) {
            const section = viewConfiguration.sections.find(s => s.id === sectionId);
            if (section) {
                const duplicatedSection = JSON.parse(JSON.stringify(section));
                duplicatedSection.id = 'section_' + (sectionIdCounter++);
                duplicatedSection.title = duplicatedSection.title + ' (Copy)';
                addSectionToView(duplicatedSection.type, duplicatedSection);
            }
        };
        
        window.removeSection = function(sectionId) {
            if (confirm('Are you sure you want to remove this section?')) {
                const sectionElement = viewCanvas.querySelector(`[data-section-id="${sectionId}"]`);
                if (sectionElement) sectionElement.remove();
                
                viewConfiguration.sections = viewConfiguration.sections.filter(s => s.id !== sectionId);
                
                // Show empty message if no sections left
                if (viewConfiguration.sections.length === 0) {
                    const emptyMessage = viewCanvas.querySelector('.empty-canvas-message');
                    if (emptyMessage) emptyMessage.style.display = 'block';
                }
            }
        };
        
        // Select section
        function selectSection(sectionId) {
            // Remove previous selection
            viewCanvas.querySelectorAll('.view-section').forEach(section => {
                section.classList.remove('selected');
            });
            
            // Add selection to clicked section
            const sectionElement = viewCanvas.querySelector(`[data-section-id="${sectionId}"]`);
            if (sectionElement) {
                sectionElement.classList.add('selected');
            }
        }
        
        // Update section order
        function updateSectionOrder() {
            const sectionElements = viewCanvas.querySelectorAll('.view-section');
            sectionElements.forEach((element, index) => {
                const sectionId = element.dataset.sectionId;
                const section = viewConfiguration.sections.find(s => s.id === sectionId);
                if (section) {
                    section.sort_order = index;
                }
            });
        }
        
        // Update view configuration
        function updateViewConfig() {
            viewConfiguration.config = {
                name: document.getElementById('config-name').value,
                contract_type: document.getElementById('contract-type').value,
                layout: document.getElementById('view-layout').value,
                sidebar_position: document.getElementById('sidebar-position').value,
                show_tabs: document.getElementById('show-tabs').checked,
                show_timeline: document.getElementById('show-timeline').checked,
                show_comments: document.getElementById('show-comments').checked,
                show_attachments: document.getElementById('show-attachments').checked
            };
        }
        
        // Update tab configuration
        function updateTabConfiguration() {
            const tabToggles = document.querySelectorAll('.tab-toggle');
            const enabledTabs = Array.from(tabToggles)
                .filter(toggle => toggle.checked)
                .map(toggle => toggle.dataset.tab);
            
            viewConfiguration.config.enabled_tabs = enabledTabs;
        }
        
        // Save custom tab
        function saveCustomTab() {
            const form = document.getElementById('custom-tab-form');
            const formData = new FormData(form);
            
            const tab = {
                id: 'tab_' + Date.now(),
                label: formData.get('label'),
                icon: formData.get('icon') || 'fas fa-star',
                content_type: formData.get('content_type'),
                component: formData.get('component'),
                external_url: formData.get('external_url'),
                active: false
            };
            
            if (!viewConfiguration.tabs) viewConfiguration.tabs = [];
            viewConfiguration.tabs.push(tab);
            
            // Add to tab config list
            addTabToConfigList(tab);
            
            customTabModal.hide();
            form.reset();
        }
        
        // Add tab to config list
        function addTabToConfigList(tab) {
            const container = document.getElementById('tab-config-list');
            const tabHtml = `
                <div class="tab-config-item">
                    <div class="flex justify-between items-center">
                        <div>
                            <i class="${tab.icon} mr-2"></i>
                            <span>${tab.label}</span>
                        </div>
                        <div>
                            <input type="checkbox" class="flex items-center-input custom-tab-toggle" 
                                   data-tab-id="${tab.id}" ${tab.active ? 'checked' : ''}>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', tabHtml);
        }
        
        // Show section templates (for add section button)
        function showSectionTemplates() {
            // Focus on sidebar section templates
            document.querySelector('.section-templates').scrollIntoView({ behavior: 'smooth' });
            
            // Highlight templates temporarily
            const templates = document.querySelectorAll('.template-item');
            templates.forEach(template => {
                template.classList.add('highlight');
                setTimeout(() => template.classList.remove('highlight'), 2000);
            });
        }
        
        // Reset view
        function resetView() {
            if (confirm('Are you sure you want to reset the entire view configuration? This action cannot be undone.')) {
                viewCanvas.innerHTML = '<div class="empty-canvas-message"><div class="text-center py-8"><i class="fas fa-layout fa-3x text-gray-600 dark:text-gray-400 mb-6"></i><h5 class="text-gray-600 dark:text-gray-400">Design Your Contract View</h5><p class="text-gray-600 dark:text-gray-400">Drag section templates from the left panel to build your contract detail view.<br>Each section can be customized with specific fields and layouts.</p></div></div>';
                viewConfiguration = { config: {}, sections: [], tabs: [] };
                sectionIdCounter = 1;
            }
        }
        
        // Preview view
        function previewView() {
            updateViewConfig();
            
            const previewContainer = document.getElementById('view-preview-container');
            previewContainer.innerHTML = '<div class="text-center py-6"><i class="fas fa-spinner fa-spin"></i> Generating preview...</div>';
            
            fetch('/admin/contract-views/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(viewConfiguration)
            })
            .then(response => response.text())
            .then(html => {
                previewContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Preview error:', error);
                previewContainer.innerHTML = '<div class="text-red-600 dark:text-red-400 text-center py-6">Error generating preview</div>';
            });
            
            viewPreviewModal.show();
        }
        
        // Save configuration
        function saveConfiguration() {
            updateViewConfig();
            
            if (!viewConfiguration.config.name) {
                showMessage('error', 'Please enter a configuration name');
                return;
            }
            
            if (!viewConfiguration.config.contract_type) {
                showMessage('error', 'Please select a contract type');
                return;
            }
            
            fetch('/admin/contract-views/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(viewConfiguration)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', 'Configuration saved successfully');
                } else {
                    showMessage('error', data.message || 'Failed to save configuration');
                }
            })
            .catch(error => {
                console.error('Save error:', error);
                showMessage('error', 'Failed to save configuration');
            });
        }
        
        // Apply configuration
        function applyConfiguration() {
            updateViewConfig();
            
            if (!viewConfiguration.config.contract_type) {
                showMessage('error', 'Please select a contract type before applying');
                return;
            }
            
            if (viewConfiguration.sections.length === 0) {
                showMessage('error', 'Please add at least one section before applying');
                return;
            }
            
            if (confirm('Are you sure you want to apply this configuration to the selected contract type? This will affect how all contracts of this type are displayed.')) {
                fetch('/admin/contract-views/apply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(viewConfiguration)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('success', 'Configuration applied successfully');
                    } else {
                        showMessage('error', data.message || 'Failed to apply configuration');
                    }
                })
                .catch(error => {
                    console.error('Apply error:', error);
                    showMessage('error', 'Failed to apply configuration');
                });
            }
        }
        
        // Load existing configuration
        function loadExistingConfiguration() {
            const urlParams = new URLSearchParams(window.location.search);
            const configId = urlParams.get('edit');
            
            if (configId) {
                fetch(`/admin/contract-views/${configId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            viewConfiguration = data.configuration;
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
            document.getElementById('config-name').value = viewConfiguration.config.name || '';
            document.getElementById('contract-type').value = viewConfiguration.config.contract_type || '';
            document.getElementById('view-layout').value = viewConfiguration.config.layout || 'standard';
            document.getElementById('sidebar-position').value = viewConfiguration.config.sidebar_position || 'right';
            document.getElementById('show-tabs').checked = viewConfiguration.config.show_tabs !== false;
            document.getElementById('show-timeline').checked = viewConfiguration.config.show_timeline !== false;
            document.getElementById('show-comments').checked = viewConfiguration.config.show_comments !== false;
            document.getElementById('show-attachments').checked = viewConfiguration.config.show_attachments !== false;
            
            // Clear canvas and add sections
            const emptyMessage = viewCanvas.querySelector('.empty-canvas-message');
            if (emptyMessage) emptyMessage.style.display = 'none';
            
            viewConfiguration.sections.forEach(section => {
                const sectionElement = createSectionElement(section);
                viewCanvas.appendChild(sectionElement);
            });
            
            // Update counters
            sectionIdCounter = Math.max(...viewConfiguration.sections.map(s => parseInt(s.id.replace('section_', '')))) + 1 || 1;
            
            // Add custom tabs
            (viewConfiguration.tabs || []).forEach(tab => {
                addTabToConfigList(tab);
            });
        }
        
        // Utility function for showing messages
        function showMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} px-6 py-6 rounded mb-6-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
                </div>
            `;
            
            document.querySelector('.contract-view-configurator').insertAdjacentHTML('afterbegin', alertHtml);
            
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) alert.remove();
            }, 5000);
        }
        
        // Add CSS class for highlighted templates
        const style = document.createElement('style');
        style.textContent = `
            .template-item.highlight {
                background-color: #fff3cd !important;
                border-left: 4px solid #ffc107 !important;
                animation: pulse 1s ease-in-out 2;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.02); }
            }
        `;
        document.head.appendChild(style);
    });
</script>
@endpush
