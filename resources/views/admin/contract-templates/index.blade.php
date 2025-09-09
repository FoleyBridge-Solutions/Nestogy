@extends('layouts.app')

@section('title', 'Contract Templates')

@section('content')
<div class="container mx-auto-fluid">
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="h3 mb-0">Contract Templates</h1>
                    <p class="text-gray-600 dark:text-gray-400">Create and manage contract document templates</p>
                </div>
                <div>
                    <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary mr-2" id="importTemplateBtn">
                        <i class="fas fa-upload"></i> Import Template
                    </button>
                    <flux:modal.trigger name="templateModal">
    <flux:button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary"    >
                        <i class="fas fa-plus"></i> Create Template
                    </flux:button>
</flux:modal.trigger>
                </div>
            </div>

            <div class="flex flex-wrap -mx-4 mb-6">
                <div class="flex-1 px-6-md-3">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden bg-primary text-white">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                            <div class="flex justify-between">
                                <div>
                                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title">{{ $statistics['total_templates'] }}</h5>
                                    <p class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-text">Total Templates</p>
                                </div>
                                <i class="fas fa-file-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-1 px-6-md-3">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden bg-success text-white">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                            <div class="flex justify-between">
                                <div>
                                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title">{{ $statistics['active_templates'] }}</h5>
                                    <p class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-text">Active Templates</p>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-1 px-6-md-3">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden bg-info text-white">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                            <div class="flex justify-between">
                                <div>
                                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title">{{ $statistics['contracts_generated'] }}</h5>
                                    <p class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-text">Contracts Generated</p>
                                </div>
                                <i class="fas fa-file-contract fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-1 px-6-md-3">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden bg-warning text-white">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                            <div class="flex justify-between">
                                <div>
                                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title">{{ $statistics['pending_review'] }}</h5>
                                    <p class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-text">Pending Review</p>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="flex-1 px-6-lg-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                            <div class="flex justify-between items-center">
                                <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">Contract Templates</h5>
                                <div class="flex gap-2">
                                    <div class="flex">
                                        <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm" id="searchTemplates" 
                                               placeholder="Search templates...">
                                        <button class="btn border border-gray-600 text-gray-600 hover:bg-gray-50 px-6 py-2 font-medium rounded-md transition-colors-sm" type="button">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm" id="filterCategory">
                                        <option value="">All Categories</option>
                                        <option value="service">Service Agreements</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="licensing">Licensing</option>
                                        <option value="consulting">Consulting</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body p-0">
                            <div class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-responsive">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 min-w-full divide-y divide-gray-200 dark:divide-gray-700-hover mb-0">
                                    <thead class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-light">
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
                                                    <div class="flex items-center">
                                                        <div class="template-icon mr-4">
                                                            <i class="fas fa-file-alt text-blue-600 dark:text-blue-400 fa-2x"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold">{{ $template->name }}</div>
                                                            <div class="text-gray-600 dark:text-gray-400 small">{{ Str::limit($template->description, 50) }}</div>
                                                            <div class="small text-cyan-600 dark:text-cyan-400">{{ $template->contract_type }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary">{{ ucfirst($template->category) }}</span>
                                                    @if($template->is_default)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary ml-1">Default</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $template->status === 'active' ? 'success' : ($template->status === 'draft' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($template->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="font-monospace">v{{ $template->version }}</span>
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-info">{{ $template->usage_count ?? 0 }}</span>
                                                </td>
                                                <td>
                                                    <div class="px-6 py-2 font-medium rounded-md transition-colors-group">
                                                        <button type="button" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-primary preview-template" 
                                                                data-template-id="{{ $template->id }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-secondary edit-template" 
                                                                data-template-id="{{ $template->id }}">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-success clone-template" 
                                                                data-template-id="{{ $template->id }}">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                        <div class="dropdown">
                                                            <button class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-secondary dropdown-toggle" 
                                                                    type="button" >
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#" data-action="export" data-template-id="{{ $template->id }}">
                                                                    <i class="fas fa-download mr-2"></i>Export
                                                                </a></li>
                                                                <li><a class="dropdown-item" href="#" data-action="version" data-template-id="{{ $template->id }}">
                                                                    <i class="fas fa-code-branch mr-2"></i>Version History
                                                                </a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-red-600 dark:text-red-400" href="#" data-action="delete" data-template-id="{{ $template->id }}">
                                                                    <i class="fas fa-trash mr-2"></i>Delete
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-6">
                                                    <div class="empty-state">
                                                        <i class="fas fa-file-alt fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                                                        <h5 class="text-gray-600 dark:text-gray-400">No Contract Templates Found</h5>
                                                        <p class="text-gray-600 dark:text-gray-400">Create your first contract template to get started.</p>
                                                        <flux:modal.trigger name="templateModal">
    <flux:button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary"    >
                                                            <i class="fas fa-plus"></i> Create Template
                                                        </flux:button>
</flux:modal.trigger>
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

                <div class="flex-1 px-6-lg-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                            <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                            <div class="grid gap-2">
                                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary" id="createMSPTemplate">
                                    <i class="fas fa-server"></i> Create MSP Service Agreement
                                </button>
                                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" id="createMaintenanceTemplate">
                                    <i class="fas fa-tools"></i> Create Maintenance Contract
                                </button>
                                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-info" id="createConsultingTemplate">
                                    <i class="fas fa-handshake"></i> Create Consulting Agreement
                                </button>
                                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-success" id="importStandardTemplates">
                                    <i class="fas fa-magic"></i> Import Standard Templates
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mt-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                            <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">Template Variables</h6>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                            <p class="text-gray-600 dark:text-gray-400 small mb-6">Available variables for use in templates:</p>
                            
                            <div class="accordion accordion-flush" id="variablesAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed py-2" type="button"  >
                                            Company Variables
                                        </button>
                                    </h2>
                                    <div id="companyVars" class="accordion-collapse collapse" >
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
                                        <button class="accordion-button collapsed py-2" type="button"  >
                                            Client Variables
                                        </button>
                                    </h2>
                                    <div id="clientVars" class="accordion-collapse collapse" >
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
                                        <button class="accordion-button collapsed py-2" type="button"  >
                                            Contract Variables
                                        </button>
                                    </h2>
                                    <div id="contractVars" class="accordion-collapse collapse" >
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
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="templateModal" tabindex="-1">
    <div class="flex items-center justify-center min-h-screen fixed inset-0 z-50 overflow-y-auto-xl">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Create Contract Template</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <form id="templateForm">
                <div class="fixed inset-0 z-50 overflow-y-auto-body">
                    <div class="flex flex-wrap -mx-4">
                        <div class="flex-1 px-6-lg-8">
                            <div class="flex flex-wrap -mx-4">
                                <div class="flex-1 px-6-md-6">
                                    <div class="mb-6">
                                        <label for="templateName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Name <span class="text-red-600 dark:text-red-400">*</span></label>
                                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="templateName" name="name" required>
                                    </div>
                                </div>
                                <div class="flex-1 px-6-md-6">
                                    <div class="mb-6">
                                        <label for="contractType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contract Type <span class="text-red-600 dark:text-red-400">*</span></label>
                                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="contractType" name="contract_type" required>
                                            <option value="">Select contract type...</option>
                                            @foreach($contractTypes as $type)
                                                <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap -mx-4">
                                <div class="flex-1 px-6-md-6">
                                    <div class="mb-6">
                                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="category" name="category">
                                            <option value="general">General</option>
                                            <option value="service">Service Agreement</option>
                                            <option value="maintenance">Maintenance Contract</option>
                                            <option value="licensing">Software License</option>
                                            <option value="consulting">Consulting Agreement</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex-1 px-6-md-6">
                                    <div class="mb-6">
                                        <label for="version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Version</label>
                                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="version" name="version" value="1.0">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="description" name="description" rows="2" 
                                          placeholder="Brief description of this template..."></textarea>
                            </div>

                            <div class="mb-6">
                                <label for="templateContent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Template Content <span class="text-red-600 dark:text-red-400">*</span></label>
                                <div id="templateEditor" style="height: 400px; border: 1px solid #ddd;"></div>
                                <div class="form-text">Use variables like {{client.name}}, {{company.name}}, {{contract.value}}</div>
                            </div>
                        </div>

                        <div class="flex-1 px-6-lg-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                                    <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">Template Settings</h6>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                                    <div class="mb-6">
                                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                        <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="status" name="status">
                                            <option value="draft">Draft</option>
                                            <option value="active">Active</option>
                                            <option value="archived">Archived</option>
                                        </select>
                                    </div>

                                    <div class="flex items-center mb-6">
                                        <input class="flex items-center-input" type="checkbox" id="isDefault" name="is_default">
                                        <label class="flex items-center-label" for="isDefault">
                                            Default Template
                                        </label>
                                        <div class="form-text">Use as default for this contract type</div>
                                    </div>

                                    <hr>

                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Associated Clauses</label>
                                        <div id="clauseSelection" style="max-height: 200px; overflow-y: auto;">
                                            @foreach($clauses as $clause)
                                                <div class="flex items-center">
                                                    <input class="flex items-center-input" type="checkbox" 
                                                           id="clause_{{ $clause->id }}" name="clauses[]" value="{{ $clause->id }}">
                                                    <label class="flex items-center-label small" for="clause_{{ $clause->id }}">
                                                        {{ $clause->title }}
                                                        <div class="text-gray-600 dark:text-gray-400">{{ $clause->category }}</div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mt-6">
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                                    <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">Preview</h6>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                                    <div id="templatePreview" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #f9f9f9; font-size: 12px;">
                                        <p class="text-gray-600 dark:text-gray-400">Template preview will appear here as you type...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                    <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Cancel</button>
                    <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary" id="previewTemplateBtn">Preview</button>
                    <button type="submit" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Template Preview Modal --}}
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="previewModal" tabindex="-1">
    <div class="flex items-center justify-center min-h-screen fixed inset-0 z-50 overflow-y-auto-lg">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Template Preview</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                <div id="fullPreviewContent" style="min-height: 400px; padding: 20px; background: white; border: 1px solid #ddd;">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Close</button>
                <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-primary" id="downloadPreviewPDF">Download PDF</button>
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

        previewDiv.innerHTML = previewContent || '<p class="text-gray-600 dark:text-gray-400">Template preview will appear here as you type...</p>';
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
