@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contract-templates';
$breadcrumbs = [
    ['name' => 'Financial', 'route' => 'financial.contracts.index'],
    ['name' => 'Contract Templates', 'route' => 'financial.contracts.templates.index'],
    ['name' => $template->name, 'route' => 'financial.contracts.templates.show', 'params' => $template],
    ['name' => 'Edit', 'active' => true]
];
@endphp

@section('title', 'Edit Template: ' . $template->name)

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="templateEditor(@json($template))">
    <!-- Header with Usage Impact Warning -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Template: {{ $template->name }}</h1>
                <p class="text-gray-600 mt-1">Modify this programmable contract template</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('financial.contracts.templates.show', $template) }}" 
                   class="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </a>
            </div>
        </div>
        
        <!-- Usage Impact Alert -->
        @if($template->contracts()->count() > 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 15c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800">Usage Impact Warning</h3>
                        <p class="text-sm text-yellow-700 mt-1">
                            This template is currently used by <strong>{{ $template->contracts()->count() }} contract(s)</strong>. 
                            Changes to billing models or variable fields may affect existing contracts.
                        </p>
                        <div class="mt-2">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="acknowledgeImpact" class="rounded text-yellow-600">
                                <span class="ml-2 text-sm text-yellow-700">I understand the impact of these changes</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Version Control Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-800">Template Version {{ $template->version }}</h3>
                    <p class="text-sm text-blue-700">Last modified {{ $template->updated_at->diffForHumans() }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="createNewVersion" class="rounded text-blue-600">
                    <span class="ml-2 text-sm text-blue-700">Save as new version</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white border border-gray-200 rounded-lg p-6">
        <form action="{{ route('financial.contracts.templates.update', $template) }}" method="POST" @submit="prepareSubmission">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="space-y-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Template Name -->
                    <div>
                        <label for="template-name" class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                        <input type="text" name="name" id="template-name" x-model="form.name" required
                               class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Template Type -->
                    <div>
                        <label for="template-type" class="block text-sm font-medium text-gray-700 mb-2">Template Type *</label>
                        <select name="template_type" id="template-type" x-model="form.template_type" required
                                class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            @foreach($templateTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('template_type')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="template-description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="template-description" x-model="form.description" rows="3"
                              class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    @error('description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                </div>

                <!-- Category and Status -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <input type="text" name="category" x-model="form.category"
                               class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" x-model="form.status"
                                class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Billing Model Configuration -->
            <div class="space-y-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Billing Model</h3>
                
                @if($template->contracts()->count() > 0)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                        <p class="text-sm text-amber-700">
                            <strong>Note:</strong> Billing model changes will affect {{ $template->contracts()->count() }} existing contract(s). 
                            Consider creating a new version if significant changes are needed.
                        </p>
                    </div>
                @endif

                <!-- Billing Model Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-6">Billing Model *</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($billingModels as $value => $label)
                        <div class="relative border-2 rounded-lg p-6 cursor-pointer transition-all"
                             :class="form.billing_model === '{{ $value }}' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                             @click="selectBillingModel('{{ $value }}')">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ $label }}</h4>
                                <div class="w-4 h-4 rounded-full border-2 transition-colors"
                                     :class="form.billing_model === '{{ $value }}' ? 'border-blue-500 bg-blue-500' : 'border-gray-300'">
                                    <div x-show="form.billing_model === '{{ $value }}'" 
                                         class="w-2 h-2 bg-white rounded-full mx-auto mt-0.5"></div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600">
                                @switch($value)
                                    @case('fixed') Traditional fixed-price contracts @break
                                    @case('per_asset') Bill based on devices managed @break
                                    @case('per_contact') Bill based on contacts/seats @break
                                    @case('tiered') Volume-based pricing tiers @break
                                    @case('hybrid') Combination billing model @break
                                @endswitch
                            </p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Asset Billing Rules -->
                <div x-show="['per_asset', 'hybrid'].includes(form.billing_model)" class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-700">Asset Billing Rules</h4>
                    <div class="space-y-3">
                        <template x-for="(rule, index) in assetBillingRules" :key="index">
                            <div class="flex items-center gap-3 bg-gray-50 p-6 rounded border">
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

                <!-- Contact Access Tiers -->
                <div x-show="['per_contact', 'hybrid'].includes(form.billing_model)" class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-700">Contact Access Tiers</h4>
                    <div class="space-y-3">
                        <template x-for="(tier, index) in contactAccessTiers" :key="index">
                            <div class="flex items-center gap-3 bg-gray-50 p-6 rounded border">
                                <div class="flex-1">
                                    <input type="text" placeholder="Tier Name" 
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

            <!-- Variable Fields -->
            <div class="space-y-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Variable Fields</h3>
                
                @if($template->contracts()->count() > 0)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                        <p class="text-sm text-amber-700">
                            <strong>Warning:</strong> Removing or changing variable field names may break existing contracts that use this template.
                        </p>
                    </div>
                @endif

                <div class="space-y-3">
                    <template x-for="(field, index) in variableFields" :key="index">
                        <div class="flex items-center gap-3 bg-gray-50 p-6 rounded border">
                            <div class="flex-1">
                                <input type="text" placeholder="Variable Name" 
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

            <!-- Note: Template content is now managed through clauses -->
            <div class="space-y-6 mb-8">
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
                                <p>This template uses the modern clause-based system. Content is managed through individual clauses that can be reused across templates. Use the clause management section to add and organize your contract clauses.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Version Control Options -->
            <div class="space-y-6 mb-8" x-show="createNewVersion">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Version Control</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Version Notes</label>
                    <textarea name="version_notes" x-model="versionNotes" rows="3"
                              class="w-full px-6 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Describe the changes made in this version..."></textarea>
                </div>
            </div>

            <!-- Hidden form fields for complex data -->
            <input type="hidden" name="variable_fields" x-model="variableFieldsJson">
            <input type="hidden" name="asset_billing_rules" x-model="assetBillingRulesJson">
            <input type="hidden" name="contact_billing_rules" x-model="contactBillingRulesJson">
            <input type="hidden" name="billing_model" x-model="form.billing_model">
            <input type="hidden" name="create_new_version" x-model="createNewVersion">
            <input type="hidden" name="version_notes" x-model="versionNotes">

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('financial.contracts.templates.show', $template) }}" 
                   class="px-6 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                    Cancel
                </a>
                
                <div class="flex gap-3">
                    @if($template->contracts()->count() > 0)
                        <button type="button" @click="createNewVersion = true" 
                                x-show="!createNewVersion"
                                class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                            Save as New Version
                        </button>
                    @endif
                    
                    <button type="submit" 
                            :disabled="!canSave"
                            :class="canSave ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                            class="px-6 py-2 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span x-text="createNewVersion ? 'Create New Version' : 'Update Template'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function templateEditor(template) {
    return {
        form: {
            name: template.name || '',
            template_type: template.template_type || '',
            description: template.description || '',
            category: template.category || '',
            status: template.status || 'draft',
            billing_model: template.billing_model || '',
        },
        variableFields: template.variable_fields || [],
        assetBillingRules: template.asset_billing_rules ? Object.keys(template.asset_billing_rules).map(key => ({
            asset_type: key,
            rate: template.asset_billing_rules[key].rate || ''
        })) : [],
        contactAccessTiers: template.contact_access_tiers || [],
        acknowledgeImpact: {{ $template->contracts()->count() > 0 ? 'false' : 'true' }},
        createNewVersion: false,
        versionNotes: '',
        
        get variableFieldsJson() {
            return JSON.stringify(this.variableFields);
        },
        
        get assetBillingRulesJson() {
            return JSON.stringify(this.assetBillingRules);
        },
        
        get contactBillingRulesJson() {
            return JSON.stringify(this.contactAccessTiers);
        },
        
        get canSave() {
            return this.form.name && 
                   this.form.template_type && 
                   this.form.billing_model && 
                   this.acknowledgeImpact;
        },
        
        selectBillingModel(model) {
            this.form.billing_model = model;
            
            // Initialize billing rules if needed
            if (['per_asset', 'hybrid'].includes(model) && this.assetBillingRules.length === 0) {
                this.addAssetRule();
            }
            if (['per_contact', 'hybrid'].includes(model) && this.contactAccessTiers.length === 0) {
                this.addContactTier();
            }
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
            // Validation is handled by x-model bindings and canSave computed property
        }
    }
}
</script>
@endsection
