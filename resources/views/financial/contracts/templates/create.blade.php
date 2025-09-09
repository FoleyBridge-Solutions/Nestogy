@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contract-templates';
$breadcrumbs = [
    ['name' => 'Financial', 'route' => 'financial.contracts.index'],
    ['name' => 'Contract Templates', 'route' => 'financial.contracts.templates.index'],
    ['name' => 'Create Template', 'active' => true]
];
@endphp

@section('title', 'Create Contract Template')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="templateCreator()">
    <!-- Header -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Contract Template</h1>
                <p class="text-gray-600 mt-1">Build a programmable contract template with automated billing</p>
            </div>
            <a href="{{ route('financial.contracts.templates.index') }}" 
               class="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancel
            </a>
        </div>
    </div>

    <!-- Progress Wizard -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <!-- Step 1: Basic Info -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                         :class="currentStep >= 1 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-400'">
                        <span class="text-sm font-medium">1</span>
                    </div>
                    <span class="ml-2 text-sm font-medium" 
                          :class="currentStep >= 1 ? 'text-gray-900' : 'text-gray-400'">Basic Info</span>
                </div>
                
                <div class="h-px bg-gray-300 flex-1 max-w-16"></div>
                
                <!-- Step 2: Billing Model -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                         :class="currentStep >= 2 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-400'">
                        <span class="text-sm font-medium">2</span>
                    </div>
                    <span class="ml-2 text-sm font-medium" 
                          :class="currentStep >= 2 ? 'text-gray-900' : 'text-gray-400'">Billing Model</span>
                </div>
                
                <div class="h-px bg-gray-300 flex-1 max-w-16"></div>
                
                <!-- Step 3: Content -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                         :class="currentStep >= 3 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-400'">
                        <span class="text-sm font-medium">3</span>
                    </div>
                    <span class="ml-2 text-sm font-medium" 
                          :class="currentStep >= 3 ? 'text-gray-900' : 'text-gray-400'">Content</span>
                </div>
                
                <div class="h-px bg-gray-300 flex-1 max-w-16"></div>
                
                <!-- Step 4: Automation -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 transition-colors"
                         :class="currentStep >= 4 ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-400'">
                        <span class="text-sm font-medium">4</span>
                    </div>
                    <span class="ml-2 text-sm font-medium" 
                          :class="currentStep >= 4 ? 'text-gray-900' : 'text-gray-400'">Automation</span>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form action="{{ route('financial.contracts.templates.store') }}" method="POST" @submit="prepareSubmission">
            @csrf
            
            <!-- Step 1: Basic Information -->
            <div x-show="currentStep === 1" x-transition class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Template Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                        <input type="text" name="name" x-model="form.name" required
                               class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., Standard MSP Agreement">
                        @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Template Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Template Type *</label>
                        <select name="template_type" x-model="form.template_type" required
                                class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select type...</option>
                            @foreach($templateTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('template_type')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" x-model="form.description" rows="3"
                              class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describe what this template is used for..."></textarea>
                    @error('description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                </div>

                <!-- Category and Tags -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" x-model="form.category"
                               class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., IT Services">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tags (comma-separated)</label>
                        <input type="text" x-model="tagsInput" @input="updateTags"
                               class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g., managed services, monitoring, support">
                    </div>
                </div>
            </div>

            <!-- Step 2: Billing Model -->
            <div x-show="currentStep === 2" x-transition class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Choose Billing Model</h3>
                    <p class="text-sm text-gray-600 mb-6">Select how this contract will be billed. You can configure detailed pricing rules after selection.</p>
                </div>

                <!-- Billing Model Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($billingModels as $value => $label)
                    <div class="relative border-2 rounded-lg p-6 cursor-pointer transition-all"
                         :class="form.billing_model === '{{ $value }}' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                         @click="selectBillingModel('{{ $value }}')">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="font-medium text-gray-900">{{ $label }}</h4>
                            <div class="w-4 h-4 rounded-full border-2 transition-colors"
                                 :class="form.billing_model === '{{ $value }}' ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                                <div x-show="form.billing_model === '{{ $value }}'" 
                                     class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-600 mb-6">
                            @switch($value)
                                @case('fixed')
                                    Traditional fixed-price contracts with set monthly/annual fees
                                    @break
                                @case('per_asset')
                                    Bill based on devices managed (workstations, servers, etc.)
                                    @break
                                @case('per_contact')
                                    Bill based on number of contacts/seats with portal access
                                    @break
                                @case('tiered')
                                    Volume-based pricing with multiple tiers and thresholds
                                    @break
                                @case('hybrid')
                                    Combination of multiple billing models for complex scenarios
                                    @break
                            @endswitch
                        </p>
                        
                        <div class="text-xs text-gray-500">
                            @if($value === 'per_asset')
                                <span class="inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-700 mr-1">Per Device</span>
                                <span class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-700">Automated</span>
                            @elseif($value === 'per_contact')
                                <span class="inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-700 mr-1">Per Seat</span>
                                <span class="inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-700">Access Control</span>
                            @elseif($value === 'hybrid')
                                <span class="inline-flex items-center px-2 py-1 rounded bg-yellow-100 text-yellow-700 mr-1">Complex</span>
                                <span class="inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-700">Advanced</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700">Traditional</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Billing Configuration -->
                <div x-show="form.billing_model && form.billing_model !== 'fixed'" class="mt-8">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="font-medium text-gray-900 mb-6">Billing Configuration</h4>
                        
                        <!-- Asset Billing Rules -->
                        <div x-show="['per_asset', 'hybrid'].includes(form.billing_model)" class="mb-6">
                            <h5 class="text-sm font-medium text-gray-700 mb-6">Asset Billing Rules</h5>
                            <div class="space-y-3">
                                <template x-for="(rule, index) in assetBillingRules" :key="index">
                                    <div class="flex items-center gap-3 bg-white p-6 rounded border">
                                        <div class="flex-1">
                                            <select class="w-full px-2 py-1 text-sm border border-gray-300 rounded" 
                                                    x-model="rule.asset_type">
                                                <option value="workstation">Workstation</option>
                                                <option value="server">Server</option>
                                                <option value="network_device">Network Device</option>
                                                <option value="mobile_device">Mobile Device</option>
                                            </select>
                                        </div>
                                        <div class="flex-1">
                                            <input type="number" step="0.01" placeholder="Monthly Rate" 
                                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                                                   x-model="rule.rate">
                                        </div>
                                        <button type="button" @click="removeAssetRule(index)"
                                                class="text-red-500 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addAssetRule()"
                                        class="text-sm text-blue-600 hover:text-blue-800">
                                    + Add Asset Rule
                                </button>
                            </div>
                        </div>

                        <!-- Contact Billing Rules -->
                        <div x-show="['per_contact', 'hybrid'].includes(form.billing_model)">
                            <h5 class="text-sm font-medium text-gray-700 mb-6">Contact Access Tiers</h5>
                            <div class="space-y-3">
                                <template x-for="(tier, index) in contactAccessTiers" :key="index">
                                    <div class="flex items-center gap-3 bg-white p-6 rounded border">
                                        <div class="flex-1">
                                            <input type="text" placeholder="Tier Name (e.g., Basic)" 
                                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                                                   x-model="tier.name">
                                        </div>
                                        <div class="flex-1">
                                            <input type="number" step="0.01" placeholder="Monthly Rate" 
                                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                                                   x-model="tier.rate">
                                        </div>
                                        <div class="flex-1">
                                            <input type="text" placeholder="Permissions" 
                                                   class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                                                   x-model="tier.permissions">
                                        </div>
                                        <button type="button" @click="removeContactTier(index)"
                                                class="text-red-500 hover:text-red-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addContactTier()"
                                        class="text-sm text-blue-600 hover:text-blue-800">
                                    + Add Access Tier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Content -->
            <div x-show="currentStep === 3" x-transition class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Template Content</h3>
                    <p class="text-sm text-gray-600 mb-6">Define the contract content using variables for dynamic values.</p>
                </div>

                <!-- Variable Fields -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h4 class="font-medium text-gray-900 mb-6">Variable Fields</h4>
                    <p class="text-sm text-gray-600 mb-6">Define variables that can be customized for each contract. Use {{variable_name}} in the content.</p>
                    
                    <div class="space-y-3">
                        <template x-for="(field, index) in variableFields" :key="index">
                            <div class="flex items-center gap-3 bg-white p-6 rounded border">
                                <div class="flex-1">
                                    <input type="text" placeholder="Variable Name (e.g., client_name)" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                                           x-model="field.name">
                                </div>
                                <div class="flex-1">
                                    <select class="w-full px-2 py-1 text-sm border border-gray-300 rounded" 
                                            x-model="field.type">
                                        <option value="text">Text</option>
                                        <option value="number">Number</option>
                                        <option value="date">Date</option>
                                        <option value="currency">Currency</option>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <input type="text" placeholder="Default Value" 
                                           class="w-full px-2 py-1 text-sm border border-gray-300 rounded"
                                           x-model="field.default_value">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="field.required" class="rounded">
                                    <label class="ml-1 text-xs text-gray-600">Required</label>
                                </div>
                                <button type="button" @click="removeVariableField(index)"
                                        class="text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="addVariableField()"
                                class="text-sm text-blue-600 hover:text-blue-800">
                            + Add Variable Field
                        </button>
                    </div>
                </div>

                <!-- Note: Templates now use clause-based content -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Modern Clause-Based Templates
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>After creating the template, you'll be able to add content by selecting from existing clauses or creating new ones. This modular approach makes templates more flexible and maintainable.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Automation -->
            <div x-show="currentStep === 4" x-transition class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Automation & Rules</h3>
                    <p class="text-sm text-gray-600 mb-6">Configure automated behaviors and calculation rules for this template.</p>
                </div>

                <!-- Auto Assignment Rules -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="font-medium text-gray-900 mb-6">Auto Assignment Rules</h4>
                    <p class="text-sm text-gray-600 mb-6">Define rules for automatically assigning assets and contacts to contracts.</p>
                    
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" x-model="automationSettings.auto_assign_new_assets" class="rounded">
                            <span class="ml-2 text-sm text-gray-700">Automatically assign new client assets to this contract</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" x-model="automationSettings.auto_assign_new_contacts" class="rounded">
                            <span class="ml-2 text-sm text-gray-700">Automatically assign new client contacts to this contract</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" x-model="automationSettings.auto_generate_invoices" class="rounded">
                            <span class="ml-2 text-sm text-gray-700">Automatically generate invoices based on usage calculations</span>
                        </label>
                    </div>
                </div>

                <!-- Calculation Formulas -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="font-medium text-gray-900 mb-6">Custom Calculation Formulas</h4>
                    <p class="text-sm text-gray-600 mb-6">Define custom formulas for complex billing calculations (optional).</p>
                    
                    <textarea x-model="calculationFormulasText" rows="5"
                              class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                              placeholder="Enter custom calculation formulas in JSON format (for advanced users only)"></textarea>
                </div>
            </div>

            <!-- Hidden form fields for complex data -->
            <input type="hidden" name="variable_fields" x-model="variableFieldsJson">
            <input type="hidden" name="asset_billing_rules" x-model="assetBillingRulesJson">
            <input type="hidden" name="contact_billing_rules" x-model="contactBillingRulesJson">
            <input type="hidden" name="calculation_formulas" x-model="calculationFormulasJson">
            <input type="hidden" name="automation_settings" x-model="automationSettingsJson">
            <input type="hidden" name="billing_model" x-model="form.billing_model">

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button type="button" @click="previousStep()" 
                        x-show="currentStep > 1"
                        class="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Previous
                </button>
                
                <div class="flex gap-2">
                    <button type="button" @click="nextStep()" 
                            x-show="currentStep < 4"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Next
                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button type="submit" 
                            x-show="currentStep === 4"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Create Template
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function templateCreator() {
    return {
        currentStep: 1,
        form: {
            name: '',
            template_type: '',
            description: '',
            category: '',
            billing_model: '',
        },
        tagsInput: '',
        variableFields: [],
        assetBillingRules: [],
        contactAccessTiers: [],
        automationSettings: {
            auto_assign_new_assets: false,
            auto_assign_new_contacts: false,
            auto_generate_invoices: false
        },
        calculationFormulasText: '',
        
        get variableFieldsJson() {
            return JSON.stringify(this.variableFields);
        },
        
        get assetBillingRulesJson() {
            return JSON.stringify(this.assetBillingRules);
        },
        
        get contactBillingRulesJson() {
            return JSON.stringify(this.contactAccessTiers);
        },
        
        get calculationFormulasJson() {
            try {
                return this.calculationFormulasText ? JSON.stringify(JSON.parse(this.calculationFormulasText)) : '';
            } catch {
                return '';
            }
        },
        
        get automationSettingsJson() {
            return JSON.stringify(this.automationSettings);
        },
        
        nextStep() {
            if (this.validateCurrentStep()) {
                this.currentStep++;
            }
        },
        
        previousStep() {
            this.currentStep--;
        },
        
        validateCurrentStep() {
            switch (this.currentStep) {
                case 1:
                    return this.form.name && this.form.template_type;
                case 2:
                    return this.form.billing_model;
                case 3:
                    return true; // Template content now managed through clauses
                case 4:
                    return true;
                default:
                    return true;
            }
        },
        
        selectBillingModel(model) {
            this.form.billing_model = model;
            
            // Initialize billing rules based on model
            if (['per_asset', 'hybrid'].includes(model) && this.assetBillingRules.length === 0) {
                this.addAssetRule();
            }
            if (['per_contact', 'hybrid'].includes(model) && this.contactAccessTiers.length === 0) {
                this.addContactTier();
            }
        },
        
        updateTags() {
            // Tags input handling if needed
        },
        
        addVariableField() {
            this.variableFields.push({
                name: '',
                type: 'text',
                default_value: '',
                required: false
            });
        },
        
        removeVariableField(index) {
            this.variableFields.splice(index, 1);
        },
        
        addAssetRule() {
            this.assetBillingRules.push({
                asset_type: 'workstation',
                rate: ''
            });
        },
        
        removeAssetRule(index) {
            this.assetBillingRules.splice(index, 1);
        },
        
        addContactTier() {
            this.contactAccessTiers.push({
                name: '',
                rate: '',
                permissions: ''
            });
        },
        
        removeContactTier(index) {
            this.contactAccessTiers.splice(index, 1);
        },
        
        prepareSubmission(event) {
            // Ensure all JSON fields are properly set before submission
            // The x-model bindings handle this automatically
        }
    }
}
</script>
@endsection
