@extends('layouts.app')

@section('title', 'Create Invoice - Advanced')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.css">
<style>
    .step-wizard { display: flex; justify-content: space-between; margin-bottom: 2rem; }
    .step-wizard .step { flex: 1; text-align: center; position: relative; }
    .step-wizard .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -50%;
        width: 100%;
        height: 2px;
        background: #dee2e6;
        z-index: -1;
    }
    .step-wizard .step.active::after { background: #0d6efd; }
    .step-wizard .step.completed::after { background: #198754; }
    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #dee2e6;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
        font-weight: bold;
    }
    .step.active .step-circle { background: #0d6efd; }
    .step.completed .step-circle { background: #198754; }
    
    .line-item-row { transition: all 0.3s ease; }
    .line-item-row:hover { background-color: #f8f9fa; }
    .line-item-row.dragging { opacity: 0.5; }
    
    .template-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .template-card:hover { 
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .template-card.selected { 
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    
    .custom-field-input {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .attachment-item {
        display: inline-block;
        padding: 0.5rem 1rem;
        margin: 0.25rem;
        background: #f8f9fa;
        border-radius: 0.25rem;
        position: relative;
    }
    .attachment-item .remove-btn {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #dc3545;
        color: white;
        border: none;
        font-size: 12px;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="w-full px-4 px-4 py-4"
     x-data="financialDocumentBuilderAdvanced({
         type: 'invoice',
         mode: 'create',
         clients: @js($clients),
         categories: @js($categories),
         taxes: @js($taxes ?? []),
         templates: @js($templates ?? []),
         currencies: @js($currencies ?? [])
     })">

    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Invoice</h1>
            <p class="text-gray-600 mb-0">Advanced invoice creation with templates and automation</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" @click="showTemplateModal = true">
                <i class="fas fa-file-import mr-2"></i>Use Template
            </button>
            <a href="{{ route('financial.invoices.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
        </div>
    </div>

    <!-- Step Wizard -->
    <div class="step-wizard">
        <div class="step" :class="{'active': currentStep === 1, 'completed': currentStep > 1}">
            <div class="step-circle">1</div>
            <div>Client & Details</div>
        </div>
        <div class="step" :class="{'active': currentStep === 2, 'completed': currentStep > 2}">
            <div class="step-circle">2</div>
            <div>Line Items</div>
        </div>
        <div class="step" :class="{'active': currentStep === 3, 'completed': currentStep > 3}">
            <div class="step-circle">3</div>
            <div>Settings & Terms</div>
        </div>
        <div class="step" :class="{'active': currentStep === 4}">
            <div class="step-circle">4</div>
            <div>Review & Send</div>
        </div>
    </div>

    <!-- Main Form -->
    <form @submit.prevent="save()">
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm">
            <div class="p-6">
                
                <!-- Step 1: Client & Details -->
                <div x-show="currentStep === 1">
                    <h4 class="mb-4">Invoice Details</h4>
                    
                    <div class="flex flex-wrap -mx-4 g-3">
                        <!-- Client Selection with Search -->
                        <div class="md:w-1/2 px-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-600">*</span></label>
                            <select id="client-select" x-model="document.client_id" 
                                    @change="selectClient($event.target.value)"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Select or search client...</option>
                                <template x-for="client in clients" :key="client.id">
                                    <option :value="client.id" x-text="client.display_name"></option>
                                </template>
                            </select>
                            <div class="text-red-600 small mt-1" x-show="errors.client_id" x-text="errors.client_id"></div>
                            
                            <!-- Client Quick Info -->
                            <div class="mt-2 p-2 bg-gray-100 rounded" x-show="document.client_id">
                                <small class="text-gray-600">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span x-text="getClientInfo()"></span>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Invoice Number -->
                        <div class="md:w-1/2 px-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                            <div class="input-group">
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="INV-" readonly>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="document.number" 
                                       placeholder="Auto-generated">
                            </div>
                        </div>
                        
                        <!-- Dates -->
                        <div class="col-md-4">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" class="form-control" x-model="document.date">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Due Date</label>
                            <div class="input-group">
                                <input type="date" class="form-control" x-model="document.due_date">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" 
                                        x-data="{ open: false }" @click="open = !open">
                                    <i class="fas fa-calendar"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" @click="setPaymentTerms('immediate')">Due on Receipt</a></li>
                                    <li><a class="dropdown-item" @click="setPaymentTerms('net15')">Net 15</a></li>
                                    <li><a class="dropdown-item" @click="setPaymentTerms('net30')">Net 30</a></li>
                                    <li><a class="dropdown-item" @click="setPaymentTerms('net45')">Net 45</a></li>
                                    <li><a class="dropdown-item" @click="setPaymentTerms('net60')">Net 60</a></li>
                                    <li><a class="dropdown-item" @click="setPaymentTerms('eom')">End of Month</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Currency -->
                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="document.currency_code" 
                                    @change="updateExchangeRate()">
                                <template x-for="currency in currencies" :key="currency.code">
                                    <option :value="currency.code">
                                        <span x-text="`${currency.code} - ${currency.name}`"></span>
                                    </option>
                                </template>
                            </select>
                            <small class="text-muted" x-show="document.exchange_rate != 1">
                                Exchange rate: <span x-text="document.exchange_rate"></span>
                            </small>
                        </div>
                        
                        <!-- Category & Tags -->
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select class="form-select" x-model="document.category_id">
                                <option value="">Select category...</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tags</label>
                            <input type="text" id="tags-input" class="form-control" 
                                   placeholder="Add tags...">
                        </div>
                        
                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">Description / Project</label>
                            <textarea class="form-control" rows="2" x-model="document.scope"
                                      placeholder="Brief description of the invoice..."></textarea>
                        </div>
                        
                        <!-- Recurring Settings -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       x-model="document.recurring.enabled"
                                       @change="setupRecurring()">
                                <label class="form-check-label">Make this a recurring invoice</label>
                            </div>
                            
                            <div class="flex flex-wrap -mx-4 g-3 mt-2" x-show="document.recurring.enabled">
                                <div class="col-md-3">
                                    <label class="form-label">Frequency</label>
                                    <select class="form-select" x-model="document.recurring.frequency">
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Interval</label>
                                    <input type="number" class="form-control" 
                                           x-model="document.recurring.interval" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" 
                                           x-model="document.recurring.start_date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date (Optional)</label>
                                    <input type="date" class="form-control" 
                                           x-model="document.recurring.end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Line Items -->
                <div x-show="currentStep === 2">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="mb-0">Line Items</h4>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2"
                                    @click="bulkUpdateItems({tax_rate: document.tax_rate})">
                                <i class="fas fa-percentage me-1"></i>Apply Tax to All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2"
                                    onclick="document.getElementById('csv-import').click()">
                                <i class="fas fa-file-csv me-1"></i>Import CSV
                            </button>
                            <input type="file" id="csv-import" class="hidden" accept=".csv"
                                   @change="importItemsFromCSV($event.target.files[0])">
                            <button type="button" class="btn btn-sm btn-primary" @click="addLineItem()">
                                <i class="fas fa-plus me-1"></i>Add Item
                            </button>
                        </div>
                    </div>
                    
                    <div class="min-w-full divide-y divide-gray-200-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th width="35%">Description</th>
                                    <th width="10%">Qty</th>
                                    <th width="10%">Unit</th>
                                    <th width="15%">Rate</th>
                                    <th width="10%">Tax %</th>
                                    <th width="15%">Amount</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in document.items" :key="item.id">
                                    <tr class="line-item-row">
                                        <td class="text-center">
                                            <i class="fas fa-grip-vertical text-muted" style="cursor: grab;"></i>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   x-model="item.description"
                                                   placeholder="Item description...">
                                            <input type="text" class="form-control form-control-sm mt-1" 
                                                   x-model="item.notes"
                                                   placeholder="Additional notes (optional)...">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                   x-model="item.quantity"
                                                   @input="calculateLineItem(index)"
                                                   min="0" step="0.01">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" x-model="item.unit">
                                                <option value="hours">Hours</option>
                                                <option value="days">Days</option>
                                                <option value="units">Units</option>
                                                <option value="fixed">Fixed</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" 
                                                       x-model="item.rate"
                                                       @input="calculateLineItem(index)"
                                                       min="0" step="0.01">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" 
                                                   x-model="item.tax_rate"
                                                   @input="calculateLineItem(index)"
                                                   min="0" max="100" step="0.01">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   :value="formatCurrency(item.amount)" readonly>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-secondary"
                                                        @click="duplicateLineItem(index)" title="Duplicate">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger"
                                                        @click="removeLineItem(index)" 
                                                        :disabled="document.items.length <= 1" title="Remove">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end">Subtotal:</td>
                                    <td colspan="2"><strong x-text="formatCurrency(subtotal)"></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Step 3: Settings & Terms -->
                <div x-show="currentStep === 3">
                    <h4 class="mb-4">Settings & Terms</h4>
                    
                    <div class="row g-3">
                        <!-- Tax & Discount -->
                        <div class="col-md-6">
                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">Tax & Discount</div>
                                <div class="p-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tax Type</label>
                                        <select class="form-select" x-model="document.tax_type">
                                            <option value="exclusive">Tax Exclusive (added to total)</option>
                                            <option value="inclusive">Tax Inclusive (included in prices)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Default Tax Rate (%)</label>
                                        <input type="number" class="form-control" 
                                               x-model="document.tax_rate"
                                               @input="calculateTotals()"
                                               min="0" max="100" step="0.01">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Discount</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" 
                                                   x-model="document.discount_amount"
                                                   @input="calculateTotals()"
                                                   min="0" step="0.01">
                                            <select class="form-select" x-model="document.discount_type"
                                                    @change="calculateTotals()" style="max-width: 100px;">
                                                <option value="fixed">$</option>
                                                <option value="percentage">%</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Settings -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">Payment Settings</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Terms</label>
                                        <select class="form-select" x-model="document.payment_terms"
                                                @change="updateDueDate()">
                                            <option value="immediate">Due on Receipt</option>
                                            <option value="net15">Net 15 Days</option>
                                            <option value="net30">Net 30 Days</option>
                                            <option value="net45">Net 45 Days</option>
                                            <option value="net60">Net 60 Days</option>
                                            <option value="eom">End of Month</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Accepted Payment Methods</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Bank Transfer</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Credit Card</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox">
                                            <label class="form-check-label">PayPal</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox">
                                            <label class="form-check-label">Check</label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               x-model="document.payment_reminder_enabled">
                                        <label class="form-check-label">Enable automatic payment reminders</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notes & Terms -->
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Client Notes</label>
                                <textarea class="form-control" rows="3" x-model="document.note"
                                          placeholder="Notes visible to client..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Internal Notes</label>
                                <textarea class="form-control" rows="2" x-model="document.internal_note"
                                          placeholder="Private notes (not visible to client)..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Terms & Conditions</label>
                                <textarea class="form-control" rows="3" x-model="document.terms_conditions"
                                          placeholder="Payment terms and conditions..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Custom Fields -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Custom Fields</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            @click="addCustomField()">
                                        <i class="fas fa-plus"></i> Add Field
                                    </button>
                                </div>
                                <div class="card-body">
                                    <template x-for="(field, key) in document.custom_fields" :key="key">
                                        <div class="custom-field-input">
                                            <input type="text" class="form-control form-control-sm me-2" 
                                                   :value="key" placeholder="Field name..." style="width: 200px;">
                                            <input type="text" class="form-control form-control-sm me-2" 
                                                   x-model="document.custom_fields[key]" placeholder="Value...">
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    @click="removeCustomField(key)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attachments -->
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">Attachments</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" multiple
                                               @change="handleAttachments($event.target.files)">
                                        <small class="text-muted">Max 5 files, 10MB each</small>
                                    </div>
                                    <div class="attachments-list">
                                        <template x-for="(attachment, index) in document.attachments" :key="index">
                                            <div class="attachment-item">
                                                <i class="fas fa-file me-1"></i>
                                                <span x-text="attachment.name"></span>
                                                <button type="button" class="remove-btn"
                                                        @click="removeAttachment(index)">×</button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review & Send -->
                <div x-show="currentStep === 4">
                    <h4 class="mb-4">Review Invoice</h4>
                    
                    <div class="row g-3">
                        <!-- Preview -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Invoice Preview</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            @click="generatePDF()">
                                        <i class="fas fa-file-pdf me-1"></i>Preview PDF
                                    </button>
                                </div>
                                <div class="card-body">
                                    <!-- Invoice preview content here -->
                                    <div class="invoice-preview">
                                        <h5>Invoice #<span x-text="document.number || 'AUTO'"></span></h5>
                                        <p>Date: <span x-text="formatDate(document.date)"></span></p>
                                        <p>Due: <span x-text="formatDate(document.due_date)"></span></p>
                                        <hr>
                                        <h6>Bill To:</h6>
                                        <p x-text="getClientName()"></p>
                                        <hr>
                                        <h6>Items:</h6>
                                        <template x-for="item in document.items" :key="item.id">
                                            <div class="d-flex justify-content-between">
                                                <span x-text="item.description"></span>
                                                <span x-text="formatCurrency(item.amount)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Summary & Actions -->
                        <div class="col-md-4">
                            <div class="card mb-3">
                                <div class="card-header">Invoice Summary</div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <strong x-text="formatCurrency(subtotal)"></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2" x-show="discountAmount > 0">
                                        <span>Discount:</span>
                                        <span class="text-danger">-<span x-text="formatCurrency(discountAmount)"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2" x-show="taxAmount > 0">
                                        <span>Tax:</span>
                                        <span x-text="formatCurrency(taxAmount)"></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong class="text-blue-600 h5" x-text="formatCurrency(total)"></strong>
                                    </div>
                                    
                                    <div class="mt-3" x-show="document.exchange_rate != 1">
                                        <small class="text-muted">
                                            Base Currency: <span x-text="formatCurrency(totalInBaseCurrency)"></span>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">Send Options</div>
                                <div class="card-body">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               x-model="emailSettings.sendCopy">
                                        <label class="form-check-label">Send me a copy</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               x-model="emailSettings.attachPdf" checked>
                                        <label class="form-check-label">Attach PDF</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               x-model="document.requires_approval">
                                        <label class="form-check-label">Requires approval</label>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" @click="saveAndSend()">
                                            <i class="fas fa-paper-plane me-2"></i>Save & Send
                                        </button>
                                        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" @click="save()">
                                            <i class="fas fa-save me-2"></i>Save Invoice
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" @click="saveAsDraft()">
                                            Save as Draft
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <!-- Navigation Footer -->
            <div class="card-footer bg-gray-100">
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" 
                            @click="currentStep > 1 && currentStep--"
                            :disabled="currentStep === 1">
                        <i class="fas fa-arrow-left me-2"></i>Previous
                    </button>
                    
                    <div>
                        <button type="button" class="btn btn-outline-primary me-2" 
                                @click="saveDraft()">
                            Save Draft
                        </button>
                        
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                                @click="currentStep < 4 ? currentStep++ : save()"
                                x-text="currentStep < 4 ? 'Next' : 'Create Invoice'">
                            Next<i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Template Modal -->
    <div class="modal fade" :class="{'show block': showTemplateModal}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Choose Template</h5>
                    <button type="button" class="btn-close" @click="showTemplateModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <template x-for="template in templates" :key="template.id">
                            <div class="col-md-6">
                                <div class="card template-card" @click="applyTemplate(template.id)">
                                    <div class="card-body">
                                        <h6 x-text="template.name"></h6>
                                        <p class="text-muted small mb-0" x-text="template.description"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
// Initialize Tom Select for client search
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect('#client-select', {
        create: false,
        sortField: 'text',
        placeholder: 'Search for a client...'
    });
    
    new TomSelect('#tags-input', {
        create: true,
        createOnBlur: true,
        plugins: ['remove_button'],
        placeholder: 'Add tags...'
    });
});
</script>
@endpush
@endsection