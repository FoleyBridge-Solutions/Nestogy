@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="w-full px-4 px-4 py-4">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Invoice</h1>
            <p class="text-gray-600 mb-0">Create a new invoice for billing</p>
        </div>
        <a href="{{ route('financial.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
    </div>

    <!-- Invoice Form -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm"
         x-data="{
             document: {
                 client_id: '{{ session('selected_client_id') }}',
                 category_id: '',
                 date: new Date().toISOString().split('T')[0],
                 due_date: '',
                 currency_code: 'USD',
                 scope: '',
                 note: '',
                 terms_conditions: '',
                 discount_amount: 0,
                 discount_type: 'fixed',
                 tax_rate: 0
             },
             clients: @js($clients->map(function($client) {
                 return [
                     'id' => $client->id,
                     'name' => $client->name,
                     'company_name' => $client->company_name,
                     'currency_code' => $client->currency_code ?? 'USD',
                     'net_terms' => $client->net_terms ?? 30,
                     'display_name' => $client->name . ($client->company_name ? ' (' . $client->company_name . ')' : '')
                 ];
             })),
             categories: @js($categories->map(function($category) {
                 return ['id' => $category->id, 'name' => $category->name];
             })),
             currentStep: 1,
             totalSteps: 4,
             selectedItems: [],
             pricing: {},
             billingConfig: {},
             errors: {},
             saving: false,
             
             selectClient(clientId) {
                 const client = this.clients.find(c => c.id == clientId);
                 if (client) {
                     this.document.currency_code = client.currency_code;
                     const dueDate = new Date();
                     dueDate.setDate(dueDate.getDate() + client.net_terms);
                     this.document.due_date = dueDate.toISOString().split('T')[0];
                     
                     // Notify product selector of client selection
                     this.$dispatch('client-selected', { 
                         clientId: clientId,
                         client: client 
                     });
                 }
             },
             
             nextStep() {
                 if (this.currentStep < this.totalSteps) {
                     this.currentStep++;
                 }
             },
             
             prevStep() {
                 if (this.currentStep > 1) {
                     this.currentStep--;
                 }
             },
             
             async save() {
                 this.saving = true;
                 try {
                     const formData = {
                         ...this.document,
                         items: this.selectedItems,
                         pricing: this.pricing,
                         billing_config: this.billingConfig
                     };
                     
                     const response = await fetch('/api/financial/invoices', {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                         },
                         body: JSON.stringify(formData)
                     });
                     
                     if (response.ok) {
                         window.location.href = '/financial/invoices';
                     } else {
                         const data = await response.json();
                         this.errors = data.errors || {};
                     }
                 } catch (error) {
                     console.error('Error saving invoice:', error);
                 } finally {
                     this.saving = false;
                 }
             },
             
             formatCurrency(amount) {
                 return new Intl.NumberFormat('en-US', {
                     style: 'currency',
                     currency: this.document.currency_code
                 }).format(amount || 0);
             },
             
             init() {
                 // If a client is pre-selected, notify the product selector
                 if (this.document.client_id) {
                     this.$nextTick(() => {
                         this.selectClient(this.document.client_id);
                     });
                 }
                 
                 // Trigger product selector initialization
                 this.$nextTick(() => {
                     this.$dispatch('invoice-initialized');
                 });
             }
         }"
         x-init="init()"
         @products-selected.window="selectedItems = $event.detail.items; pricing = $event.detail;"
         @billing-configured.window="billingConfig = $event.detail.configuration"
         @pricing-calculated.window="pricing = $event.detail">
        
        <form @submit.prevent="save()">
            <!-- Step Indicator -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 bg-gray-100">
                <div class="flex justify-between items-center">
                    <div class="d-flex align-items-center">
                        <div class="mr-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-full d-flex align-items-center justify-center"
                                     :class="currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white'"
                                     style="width: 32px; height: 32px;">
                                    <span class="block text-center w-100">1</span>
                                </div>
                                <span class="ml-2" :class="currentStep >= 1 ? 'fw-semibold' : ''">Details</span>
                            </div>
                        </div>
                        <div class="me-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-full d-flex align-items-center justify-center"
                                     :class="currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white'"
                                     style="width: 32px; height: 32px;">
                                    <span class="block text-center w-100">2</span>
                                </div>
                                <span class="ml-2" :class="currentStep >= 2 ? 'fw-semibold' : ''">Products & Services</span>
                            </div>
                        </div>
                        <div class="me-4">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-center"
                                     :class="currentStep >= 3 ? 'bg-primary text-white' : 'bg-secondary text-white'"
                                     style="width: 32px; height: 32px;">
                                    <span class="d-block text-center w-100">3</span>
                                </div>
                                <span class="ms-2" :class="currentStep >= 3 ? 'fw-semibold' : ''">Billing Config</span>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-center"
                                     :class="currentStep >= 4 ? 'bg-primary text-white' : 'bg-secondary text-white'"
                                     style="width: 32px; height: 32px;">
                                    <span class="d-block text-center w-100">4</span>
                                </div>
                                <span class="ms-2" :class="currentStep >= 4 ? 'fw-semibold' : ''">Review</span>
                            </div>
                        </div>
                    </div>
                    <div x-show="saving" class="text-gray-600">
                        <i class="fas fa-spinner fa-spin me-1"></i> Saving...
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Step 1: Invoice Details -->
                <div x-show="currentStep === 1">
                    <h5 class="mb-4">Invoice Details</h5>
                    
                    <div class="flex flex-wrap -mx-4 g-3">
                        <div class="md:w-1/2 px-4">
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client <span class="text-red-600">*</span></label>
                            <select id="client_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                    x-model="document.client_id" 
                                    @change="selectClient($event.target.value)"
                                    :class="errors.client_id ? 'is-invalid' : ''">
                                <option value="">Select a client...</option>
                                <template x-for="client in clients" :key="client.id">
                                    <option :value="client.id" x-text="client.display_name"></option>
                                </template>
                            </select>
                            <div class="invalid-feedback" x-text="errors.client_id"></div>
                        </div>
                        
                        <div class="md:w-1/2 px-4">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-600">*</span></label>
                            <select id="category_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                    x-model="document.category_id"
                                    :class="errors.category_id ? 'is-invalid' : ''">
                                <option value="">Select a category...</option>
                                <template x-for="category in categories" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                            <div class="invalid-feedback" x-text="errors.category_id"></div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="date" class="form-label">Invoice Date</label>
                            <input type="date" id="date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="document.date">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" id="due_date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="document.due_date">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="currency_code" class="form-label">Currency</label>
                            <select id="currency_code" class="form-select" x-model="document.currency_code">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="CAD">CAD - Canadian Dollar</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="scope" class="form-label">Description / Scope</label>
                            <textarea id="scope" class="form-control" rows="2" 
                                      x-model="document.scope" 
                                      placeholder="Brief description of the invoice..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Products & Services Selection -->
                <div x-show="currentStep === 2">
                    <h5 class="mb-4">Select Products & Services</h5>
                    
                    <div class="flex flex-wrap -mx-4">
                        <div class="col-lg-8">
                            <x-product-selector />
                        </div>
                        <div class="col-lg-4">
                            <x-pricing-display />
                        </div>
                    </div>
                </div>
                
                <!-- Step 3: Billing Configuration -->
                <div x-show="currentStep === 3">
                    <h5 class="mb-4">Billing Configuration</h5>
                    
                    <x-billing-configuration />
                </div>

                <!-- Step 4: Review & Finalize -->
                <div x-show="currentStep === 4">
                    <h5 class="mb-4">Review Invoice</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <!-- Selected Items Summary -->
                            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-3">
                                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                    <h6 class="mb-0">Selected Items (<span x-text="selectedItems.length"></span>)</h6>
                                </div>
                                <div class="p-6">
                                    <div x-show="selectedItems.length === 0" class="text-muted text-center py-3">
                                        No items selected
                                    </div>
                                    <template x-for="item in selectedItems" :key="item.id">
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <strong x-text="item.name"></strong>
                                                <div class="small text-muted">
                                                    Qty: <span x-text="item.quantity"></span> × 
                                                    <span x-text="formatCurrency(item.unit_price)"></span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <strong x-text="formatCurrency(item.subtotal)"></strong>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Billing Configuration Summary -->
                            <div class="card mb-3" x-show="billingConfig.billing_options">
                                <div class="card-header">
                                    <h6 class="mb-0">Billing Configuration</h6>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4">Billing Model:</dt>
                                        <dd class="col-sm-8" x-text="billingConfig.billing_options?.model"></dd>
                                        
                                        <dt class="col-sm-4" x-show="billingConfig.billing_options?.cycle">Billing Cycle:</dt>
                                        <dd class="col-sm-8" x-show="billingConfig.billing_options?.cycle" x-text="billingConfig.billing_options?.cycle"></dd>
                                        
                                        <dt class="col-sm-4">Payment Terms:</dt>
                                        <dd class="col-sm-8"><span x-text="billingConfig.billing_options?.paymentTerms || 30"></span> days</dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="note" class="form-label">Notes</label>
                                <textarea id="note" class="form-control" rows="3" 
                                          x-model="document.note" 
                                          placeholder="Additional notes (optional)..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                <textarea id="terms_conditions" class="form-control" rows="3" 
                                          x-model="document.terms_conditions" 
                                          placeholder="Payment terms and conditions (optional)..."></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Pricing Summary -->
                            <div class="card bg-gray-100">
                                <div class="card-body">
                                    <h6 class="mb-3">Invoice Summary</h6>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <strong x-text="formatCurrency(pricing.subtotal || 0)"></strong>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2" x-show="pricing.discount > 0">
                                        <span>Discount:</span>
                                        <span class="text-green-600">-<span x-text="formatCurrency(pricing.discount || 0)"></span></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2" x-show="pricing.savings > 0">
                                        <span>Savings:</span>
                                        <span class="text-green-600">-<span x-text="formatCurrency(pricing.savings || 0)"></span></span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Tax:</span>
                                        <span x-text="formatCurrency(pricing.tax || 0)"></span>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong class="text-blue-600" x-text="formatCurrency(pricing.total || 0)"></strong>
                                    </div>
                                    
                                    <!-- Recurring Revenue Display -->
                                    <div x-show="pricing.recurring" class="mt-3 pt-3 border-top">
                                        <h6 class="small mb-2">Recurring Revenue</h6>
                                        <div class="d-flex justify-content-between small">
                                            <span>Monthly (MRR):</span>
                                            <span x-text="formatCurrency(pricing.recurring?.monthly || 0)"></span>
                                        </div>
                                        <div class="d-flex justify-content-between small">
                                            <span>Annual (ARR):</span>
                                            <span x-text="formatCurrency(pricing.recurring?.annual || 0)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" 
                            @click="prevStep()" 
                            :disabled="currentStep === 1">
                        <i class="fas fa-arrow-left me-2"></i>Previous
                    </button>
                    
                    <div>
                        <button type="button" class="btn btn-outline-primary me-2" 
                                @click="saveAsDraft()">
                            Save as Draft
                        </button>
                        
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                                @click="nextStep()" 
                                x-show="currentStep < totalSteps">
                            Next<i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" 
                                x-show="currentStep === totalSteps"
                                :disabled="saving">
                            <i class="fas fa-save me-2"></i>Create Invoice
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection