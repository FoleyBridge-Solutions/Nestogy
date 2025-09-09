@extends('layouts.app')

@section('title', 'Contract Builder')

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Contract Builder</h1>
        <div class="flex items-center space-x-4">
            <button type="button" id="save-draft" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600">
                Save Draft
            </button>
            <button type="button" id="preview-contract" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600">
                Preview
            </button>
            <button type="button" id="save-contract" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600">
                Create Contract
            </button>
        </div>
    </div>
@endsection

@section('content')
<div x-data="contractBuilder()" class="max-w-7xl mx-auto">
    <!-- Contract Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label for="contract_title" class="block text-sm font-medium text-gray-700 mb-2">Contract Title *</label>
                <input type="text" id="contract_title" x-model="contract.title" 
                       class="w-full border border-gray-300 rounded-md px-6 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Enter contract title">
            </div>
            
            <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                <select id="client_id" x-model="contract.client_id" 
                        class="w-full border border-gray-300 rounded-md px-6 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="contract_type" class="block text-sm font-medium text-gray-700 mb-2">Contract Type</label>
                <select id="contract_type" x-model="contract.type" 
                        class="w-full border border-gray-300 rounded-md px-6 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="service">Service Agreement</option>
                    <option value="maintenance">Maintenance Contract</option>
                    <option value="support">Support Contract</option>
                    <option value="custom">Custom Agreement</option>
                </select>
            </div>
            
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
                <input type="date" id="start_date" x-model="contract.start_date" 
                       class="w-full border border-gray-300 rounded-md px-6 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" id="end_date" x-model="contract.end_date" 
                       class="w-full border border-gray-300 rounded-md px-6 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="billing_frequency" class="block text-sm font-medium text-gray-700 mb-2">Billing Frequency</label>
                <select id="billing_frequency" x-model="contract.billing_frequency" 
                        class="w-full border border-gray-300 rounded-md px-6 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annually">Annually</option>
                    <option value="one-time">One-time</option>
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Component Library -->
        <div class="lg:flex-1 px-6-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Component Library</h3>
                
                <!-- Category Tabs -->
                <div class="mb-6">
                    <div class="flex flex-wrap gap-1">
                        <template x-for="category in categories" :key="category.key">
                            <button type="button" 
                                    @click="activeCategory = category.key"
                                    :class="activeCategory === category.key ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    class="px-6 py-1 rounded-md text-sm font-medium transition-colors"
                                    x-text="category.name">
                            </button>
                        </template>
                    </div>
                </div>
                
                <!-- Component Search -->
                <div class="mb-6">
                    <input type="text" x-model="componentSearch" 
                           placeholder="Search components..."
                           class="w-full border border-gray-300 rounded-md px-6 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                
                <!-- Available Components -->
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <template x-for="component in filteredComponents" :key="component.id">
                        <div class="border border-gray-200 rounded-md p-6 cursor-pointer hover:bg-gray-50 transition-colors"
                             @click="addComponent(component)"
                             draggable="true"
                             @dragstart="dragStart(component)">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-medium text-sm text-gray-900" x-text="component.name"></h4>
                                <span class="text-xs text-gray-500" x-text="formatPrice(component.pricing_model)"></span>
                            </div>
                            <p class="text-xs text-gray-600" x-text="component.description"></p>
                            <div class="flex items-center mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                                      x-text="component.category"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Contract Builder -->
        <div class="lg:flex-1 px-6-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Contract Components</h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Total Components:</span>
                        <span class="font-medium text-gray-900" x-text="selectedComponents.length"></span>
                    </div>
                </div>
                
                <!-- Drop Zone -->
                <div class="min-h-96 border-2 border-dashed border-gray-300 rounded-lg p-6"
                     @drop="handleDrop($event)"
                     @dragover.prevent
                     @dragenter.prevent
                     :class="isDragging ? 'border-blue-500 bg-blue-50' : ''"
                     @dragenter="isDragging = true"
                     @dragleave="isDragging = false">
                    
                    <template x-if="selectedComponents.length === 0">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No components added</h3>
                            <p class="mt-1 text-sm text-gray-500">Drag components from the library or click to add</p>
                        </div>
                    </template>
                    
                    <!-- Selected Components -->
                    <div class="space-y-4">
                        <template x-for="(assignment, index) in selectedComponents" :key="assignment.id">
                            <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                <!-- Component Header -->
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                                  :class="getCategoryColor(assignment.component.category)"
                                                  x-text="assignment.component.category"></span>
                                        </div>
                                        <h4 class="font-medium text-gray-900" x-text="assignment.component.name"></h4>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-green-600" 
                                              x-text="'$' + calculateComponentPrice(assignment).toFixed(2)"></span>
                                        <button type="button" @click="removeComponent(index)"
                                                class="text-red-500 hover:text-red-700">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Component Variables -->
                                <template x-if="assignment.component.variables && assignment.component.variables.length > 0">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                        <template x-for="variable in assignment.component.variables" :key="variable.name">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1" 
                                                       x-text="variable.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())"></label>
                                                
                                                <template x-if="variable.type === 'select'">
                                                    <select x-model="assignment.variable_values[variable.name]"
                                                            @change="updatePricing()"
                                                            class="w-full border border-gray-300 rounded-md px-6 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                        <option value="">Select option</option>
                                                        <template x-if="assignment.component.configuration[variable.name]">
                                                            <template x-for="option in assignment.component.configuration[variable.name]" :key="option">
                                                                <option :value="option" x-text="option.replace('_', ' ')"></option>
                                                            </template>
                                                        </template>
                                                    </select>
                                                </template>
                                                
                                                <template x-if="variable.type === 'number'">
                                                    <input type="number" x-model="assignment.variable_values[variable.name]"
                                                           @input="updatePricing()"
                                                           class="w-full border border-gray-300 rounded-md px-6 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </template>
                                                
                                                <template x-if="variable.type === 'text'">
                                                    <input type="text" x-model="assignment.variable_values[variable.name]"
                                                           class="w-full border border-gray-300 rounded-md px-6 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                
                                <!-- Pricing Override -->
                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" x-model="assignment.has_pricing_override"
                                               @change="updatePricing()"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Custom pricing</span>
                                    </label>
                                    
                                    <template x-if="assignment.has_pricing_override">
                                        <div class="flex items-center space-x-2">
                                            <select x-model="assignment.pricing_override.type"
                                                    @change="updatePricing()"
                                                    class="border border-gray-300 rounded-md px-2 py-1 text-sm">
                                                <option value="fixed">Fixed Amount</option>
                                                <option value="percentage">Percentage</option>
                                            </select>
                                            
                                            <input type="number" step="0.01"
                                                   x-model="assignment.pricing_override.amount"
                                                   @input="updatePricing()"
                                                   class="w-24 border border-gray-300 rounded-md px-2 py-1 text-sm">
                                            
                                            <span class="text-sm text-gray-500" 
                                                  x-text="assignment.pricing_override.type === 'percentage' ? '%' : '$'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Summary -->
        <div class="lg:flex-1 px-6-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Pricing Summary</h3>
                
                <!-- Component Breakdown -->
                <div class="space-y-3 mb-6">
                    <template x-for="assignment in selectedComponents" :key="assignment.id">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 truncate" x-text="assignment.component.name"></span>
                            <span class="font-medium text-gray-900" 
                                  x-text="'$' + calculateComponentPrice(assignment).toFixed(2)"></span>
                        </div>
                    </template>
                </div>
                
                <!-- Total -->
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex items-center justify-between text-lg font-semibold">
                        <span class="text-gray-900">Total Value</span>
                        <span class="text-green-600" x-text="'$' + calculateTotalPrice().toFixed(2)"></span>
                    </div>
                    
                    <div class="mt-2 text-sm text-gray-500">
                        <div class="flex justify-between">
                            <span>Per {{ contract.billing_frequency }}</span>
                            <span x-text="'$' + calculateTotalPrice().toFixed(2)"></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="mt-6 space-y-2">
                    <button type="button" @click="clearAll()" 
                            class="w-full bg-red-50 text-red-700 border border-red-200 rounded-md px-6 py-2 text-sm hover:bg-red-100">
                        Clear All Components
                    </button>
                    
                    <button type="button" @click="loadTemplate()" 
                            class="w-full bg-blue-50 text-blue-700 border border-blue-200 rounded-md px-6 py-2 text-sm hover:bg-blue-100">
                        Load from Template
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Component Templates Modal -->
<div x-show="showTemplateModal" x-cloak
     class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
     @click.away="showTemplateModal = false">
    <div class="relative top-20 mx-auto p-8 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Load Contract Template</h3>
            
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($templates ?? [] as $template)
                <div class="border border-gray-200 rounded-lg p-6 cursor-pointer hover:bg-gray-50"
                     @click="loadTemplateComponents({{ $template->id }})">
                    <h4 class="font-medium text-gray-900">{{ $template->name }}</h4>
                    <p class="text-sm text-gray-600 mt-1">{{ $template->description }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-sm text-gray-500">{{ $template->components_count ?? 0 }} components</span>
                        <span class="text-sm font-medium text-green-600">${{ number_format($template->estimated_value ?? 0, 2) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="flex justify-end mt-6 space-x-2">
                <button type="button" @click="showTemplateModal = false"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function contractBuilder() {
    return {
        // State
        contract: {
            title: '',
            client_id: '',
            type: 'service',
            start_date: '',
            end_date: '',
            billing_frequency: 'monthly'
        },
        components: @json($components),
        selectedComponents: [],
        activeCategory: 'service',
        componentSearch: '',
        isDragging: false,
        showTemplateModal: false,
        
        // Categories
        categories: [
            { key: 'service', name: 'Services' },
            { key: 'billing', name: 'Billing' },
            { key: 'sla', name: 'SLA' },
            { key: 'legal', name: 'Legal' }
        ],
        
        // Computed
        get filteredComponents() {
            let filtered = this.components.filter(c => c.category === this.activeCategory);
            
            if (this.componentSearch) {
                const search = this.componentSearch.toLowerCase();
                filtered = filtered.filter(c => 
                    c.name.toLowerCase().includes(search) ||
                    c.description.toLowerCase().includes(search)
                );
            }
            
            return filtered;
        },
        
        // Methods
        init() {
            // Set default dates
            const today = new Date();
            this.contract.start_date = today.toISOString().split('T')[0];
            
            const nextYear = new Date(today);
            nextYear.setFullYear(nextYear.getFullYear() + 1);
            this.contract.end_date = nextYear.toISOString().split('T')[0];
        },
        
        addComponent(component) {
            // Check if already added
            const exists = this.selectedComponents.find(sc => sc.component.id === component.id);
            if (exists) {
                alert('Component already added to contract');
                return;
            }
            
            const assignment = {
                id: Date.now() + Math.random(),
                component: { ...component },
                variable_values: {},
                has_pricing_override: false,
                pricing_override: {
                    type: 'fixed',
                    amount: 0
                }
            };
            
            // Initialize variable values with defaults
            if (component.variables) {
                component.variables.forEach(variable => {
                    if (variable.type === 'number') {
                        assignment.variable_values[variable.name] = 1;
                    }
                });
            }
            
            this.selectedComponents.push(assignment);
            this.updatePricing();
        },
        
        removeComponent(index) {
            this.selectedComponents.splice(index, 1);
            this.updatePricing();
        },
        
        calculateComponentPrice(assignment) {
            if (assignment.has_pricing_override && assignment.pricing_override.amount > 0) {
                if (assignment.pricing_override.type === 'fixed') {
                    return parseFloat(assignment.pricing_override.amount) || 0;
                } else if (assignment.pricing_override.type === 'percentage') {
                    const basePrice = this.calculateBasePrice(assignment);
                    const percentage = parseFloat(assignment.pricing_override.amount) || 100;
                    return basePrice * (percentage / 100);
                }
            }
            
            return this.calculateBasePrice(assignment);
        },
        
        calculateBasePrice(assignment) {
            const pricing = assignment.component.pricing_model;
            if (!pricing || !pricing.type) return 0;
            
            switch (pricing.type) {
                case 'fixed':
                    return parseFloat(pricing.amount) || 0;
                    
                case 'per_unit':
                    const units = assignment.variable_values.units || assignment.variable_values.quantity || 1;
                    const rate = parseFloat(pricing.rate) || 0;
                    return units * rate;
                    
                case 'tiered':
                    const quantity = assignment.variable_values.quantity || 1;
                    const tiers = pricing.tiers || [];
                    
                    for (let tier of tiers) {
                        if (quantity <= (tier.max || Infinity)) {
                            return quantity * (parseFloat(tier.rate) || 0);
                        }
                    }
                    return 0;
                    
                default:
                    return 0;
            }
        },
        
        calculateTotalPrice() {
            return this.selectedComponents.reduce((total, assignment) => {
                return total + this.calculateComponentPrice(assignment);
            }, 0);
        },
        
        updatePricing() {
            // Trigger reactivity
            this.$nextTick();
        },
        
        formatPrice(pricing) {
            if (!pricing || !pricing.type) return 'Free';
            
            switch (pricing.type) {
                case 'fixed':
                    return '$' + (parseFloat(pricing.amount) || 0).toFixed(2);
                case 'per_unit':
                    return '$' + (parseFloat(pricing.rate) || 0).toFixed(2) + '/' + (pricing.unit || 'unit');
                case 'tiered':
                    return 'Tiered pricing';
                default:
                    return 'Custom';
            }
        },
        
        getCategoryColor(category) {
            const colors = {
                'service': 'bg-blue-100 text-blue-800',
                'billing': 'bg-green-100 text-green-800',
                'sla': 'bg-yellow-100 text-yellow-800',
                'legal': 'bg-purple-100 text-purple-800'
            };
            return colors[category] || 'bg-gray-100 text-gray-800';
        },
        
        // Drag and Drop
        dragStart(component) {
            this.draggedComponent = component;
        },
        
        handleDrop(event) {
            event.preventDefault();
            this.isDragging = false;
            
            if (this.draggedComponent) {
                this.addComponent(this.draggedComponent);
                this.draggedComponent = null;
            }
        },
        
        // Actions
        clearAll() {
            if (confirm('Are you sure you want to clear all components?')) {
                this.selectedComponents = [];
                this.updatePricing();
            }
        },
        
        loadTemplate() {
            this.showTemplateModal = true;
        },
        
        loadTemplateComponents(templateId) {
            // This would typically make an API call to load template components
            console.log('Loading template:', templateId);
            this.showTemplateModal = false;
        },
        
        saveContract() {
            if (!this.contract.title || !this.contract.client_id || !this.contract.start_date) {
                alert('Please fill in all required fields');
                return;
            }
            
            if (this.selectedComponents.length === 0) {
                alert('Please add at least one component');
                return;
            }
            
            const contractData = {
                ...this.contract,
                components: this.selectedComponents,
                total_value: this.calculateTotalPrice(),
                is_programmable: true
            };
            
            // Submit to server
            fetch('{{ route("contracts.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(contractData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Error creating contract: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating contract');
            });
        },
        
        saveDraft() {
            const draftData = {
                contract: this.contract,
                components: this.selectedComponents
            };
            
            localStorage.setItem('contract_draft', JSON.stringify(draftData));
            alert('Draft saved locally');
        },
        
        previewContract() {
            // Open preview in new window/modal
            const contractData = {
                ...this.contract,
                components: this.selectedComponents,
                total_value: this.calculateTotalPrice()
            };
            
            // This would open a preview modal or new window
            console.log('Preview contract:', contractData);
        }
    }
}

// Auto-save draft every 30 seconds
setInterval(() => {
    const builderInstance = window.contractBuilderInstance;
    if (builderInstance && builderInstance.selectedComponents.length > 0) {
        builderInstance.saveDraft();
    }
}, 30000);
</script>
@endpush
@endsection
