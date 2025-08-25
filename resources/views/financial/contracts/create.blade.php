@extends('layouts.app')

@php
$activeDomain = 'financial';
$activeItem = 'contracts';
$breadcrumbs = [
    ['name' => 'Financial', 'route' => 'financial.contracts.index'],
    ['name' => 'Contracts', 'route' => 'financial.contracts.index'],
    ['name' => 'Create Contract', 'active' => true]
];
@endphp

@section('title', 'Create Contract')

@section('content')
<div x-data="contractWizard()" @client-selected="handleClientSelected($event)">
    <!-- Compact Header -->
    <div class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Create Contract</h1>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Step <span x-text="currentStep"></span> of <span x-text="totalSteps"></span></span>
                </div>
                <button type="button" @click="cancelContract()" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="bg-gray-200 dark:bg-gray-700 h-1">
        <div class="bg-blue-600 h-1 transition-all duration-300" :style="`width: ${(currentStep / totalSteps) * 100}%`"></div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
            <form action="{{ route('financial.contracts.store') }}" method="POST" @submit="handleSubmission">
                @csrf
                
                <!-- Step 1: Template Selection -->
                <div x-show="currentStep === 1" x-transition class="px-4 pb-4">
                    <div>
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Template</span>
                                <div class="flex items-center space-x-2">
                                    <select x-model="templateFilter.category" @change="filterTemplates()" 
                                            class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                        <option value="">All Categories</option>
                                        <option value="msp">MSP</option>
                                        <option value="voip">VoIP</option>
                                        <option value="var">VAR</option>
                                        <option value="compliance">Compliance</option>
                                    </select>
                                    <select x-model="templateFilter.billingModel" @change="filterTemplates()"
                                            class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                        <option value="">All Models</option>
                                        <option value="fixed">Fixed</option>
                                        <option value="per_asset">Per Asset</option>
                                        <option value="per_contact">Per User</option>
                                        <option value="tiered">Tiered</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Professional Template Table -->
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <!-- Custom Contract Option -->
                                <div class="border-b border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
                                     :class="!selectedTemplate ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : ''"
                                     @click="selectTemplate(null)">
                                    <div class="px-6 py-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center"
                                                     :class="!selectedTemplate ? 'border-blue-500 bg-blue-500' : ''">
                                                    <div x-show="!selectedTemplate" class="w-2 h-2 bg-white rounded-full"></div>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <div class="p-2 bg-gray-600 rounded-lg text-white">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-medium text-gray-900 dark:text-white">Custom Contract</h4>
                                                        <p class="text-sm text-gray-600 dark:text-gray-400">Start from scratch with complete customization</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-4">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                    Manual
                                                </span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Template Options -->
                                <template x-for="template in filteredTemplates" :key="template.id">
                                    <div class="border-b border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors"
                                         :class="selectedTemplate && selectedTemplate.id === template.id ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500' : ''"
                                         @click="selectTemplate(template)">
                                        <div class="px-6 py-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center"
                                                         :class="selectedTemplate && selectedTemplate.id === template.id ? 'border-blue-500 bg-blue-500' : ''">
                                                        <div x-show="selectedTemplate && selectedTemplate.id === template.id" class="w-2 h-2 bg-white rounded-full"></div>
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <div class="p-2 rounded-lg text-white"
                                                             :class="getCategoryIconBg(template.category)">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                            </svg>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <h4 class="font-medium text-gray-900 dark:text-white truncate" x-text="template.name"></h4>
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate" x-text="template.description"></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-4">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium"
                                                          :class="getBillingModelStyle(template.billing_model)"
                                                          x-text="getBillingModelLabel(template.billing_model)"></span>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400"
                                                          x-text="template.category.charAt(0).toUpperCase() + template.category.slice(1)"></span>
                                                    <span x-show="template.usage_count > 0" class="text-sm text-gray-500 dark:text-gray-400" x-text="template.usage_count + ' uses'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <!-- Selected Template Details -->
                            <div x-show="selectedTemplate" x-transition class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700 p-4">
                                <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Template Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-blue-700 dark:text-blue-400">Billing Model:</span>
                                        <span class="text-blue-900 dark:text-blue-300 font-medium" x-text="selectedTemplate ? getBillingModelLabel(selectedTemplate.billing_model) : ''"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-blue-700 dark:text-blue-400">Variables:</span>
                                        <span class="text-blue-900 dark:text-blue-300 font-medium" x-text="selectedTemplate && selectedTemplate.variable_fields ? selectedTemplate.variable_fields.length + ' fields' : '0 fields'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-blue-700 dark:text-blue-400">Usage:</span>
                                        <span class="text-blue-900 dark:text-blue-300 font-medium" x-text="selectedTemplate ? selectedTemplate.usage_count + ' times' : '0 times'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Contract Details -->
                <div x-show="currentStep === 2" x-transition class="px-4 pb-4">
                    <div class="space-y-4">
                        
                        <!-- Essential Information Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <!-- Contract Title -->
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Contract Title *</label>
                                    <div class="relative">
                                        <input type="text" name="title" x-model="form.title" required
                                               class="w-full px-4 py-3 text-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200"
                                               placeholder="e.g., Comprehensive IT Support Agreement - Acme Corp"
                                               @input="$data.generateSuggestions && $data.generateSuggestions()">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg x-show="form.title" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    @error('title')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                </div>

                                <!-- Contract Type -->
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Contract Type *</label>
                                    <div class="relative">
                                        <select name="contract_type" x-model="form.contract_type" required
                                                class="w-full px-4 py-3 text-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                                            <option value="">Select contract type...</option>
                                            <option value="one_time_service">One-Time Service</option>
                                            <option value="recurring_service">Recurring Service</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="support">Support</option>
                                            <option value="managed_services">Managed Services</option>
                                        </select>
                                    </div>
                                    @error('contract_type')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                </div>

                                <!-- Client Selection with Search -->
                                <div class="group">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Client *</label>
                                    <div class="relative">
                                        <x-forms.client-search-field 
                                            name="client_id" 
                                            placeholder="Search and select client..." 
                                            required="true"
                                            class="w-full px-4 py-3 text-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400" />
                                    </div>
                                    @error('client_id')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <!-- Contract Dates -->
                                <div class="space-y-4">
                                    <!-- Start Date -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Start Date *</label>
                                        <input type="date" name="start_date" x-model="form.start_date" required
                                               class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                                        @error('start_date')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                    
                                    <!-- Contract Duration -->
                                    <div class="space-y-3">
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Contract Duration *</label>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Choose either a specific end date or term length in months</p>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                                                <input type="date" name="end_date" x-model="form.end_date"
                                                       @input="if(form.end_date) form.term_months = ''"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                                                @error('end_date')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">OR Term (Months)</label>
                                                <input type="number" name="term_months" x-model="form.term_months" min="1" max="120" placeholder="12"
                                                       @input="if(form.term_months) form.end_date = ''"
                                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                                                @error('term_months')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Configuration -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Currency</label>
                                        <select name="currency_code" x-model="form.currency_code"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                                            <option value="USD">🇺🇸 USD - US Dollar</option>
                                            <option value="EUR">🇪🇺 EUR - Euro</option>
                                            <option value="GBP">🇬🇧 GBP - British Pound</option>
                                            <option value="CAD">🇨🇦 CAD - Canadian Dollar</option>
                                            <option value="AUD">🇦🇺 AUD - Australian Dollar</option>
                                        </select>
                                        @error('currency_code')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Payment Terms</label>
                                        <select name="payment_terms" x-model="form.payment_terms"
                                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                                            <option value="">Select terms...</option>
                                            <option value="net_15">Net 15 days</option>
                                            <option value="net_30">Net 30 days</option>
                                            <option value="net_45">Net 45 days</option>
                                            <option value="net_60">Net 60 days</option>
                                            <option value="due_on_receipt">Due on receipt</option>
                                            <option value="advance_payment">Advance payment</option>
                                        </select>
                                        @error('payment_terms')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                    </div>
                                </div>

                                <!-- Contract Description -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Description</label>
                                    <textarea name="description" x-model="form.description" rows="4"
                                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 resize-none"
                                              placeholder="Brief description of the contract scope, objectives, and key deliverables..."></textarea>
                                    @error('description')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        <!-- Contract Value Estimation -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-6 border border-green-200 dark:border-green-700">
                            <div class="flex items-center space-x-4">
                                <div class="p-3 bg-green-600 rounded-xl text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-green-900 dark:text-green-300">Smart Contract Valuation</h4>
                                    <p class="text-green-700 dark:text-green-300">The contract value will be automatically calculated based on your selected billing model, assigned assets/users, and template configurations after creation. You can review and adjust pricing in the next steps.</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="estimatedValue"></div>
                                    <div class="text-sm text-green-700 dark:text-green-300">Estimated Monthly</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Contract Schedules Configuration -->
                <div x-show="currentStep === 3" x-transition class="px-4 pb-4">
                    <x-contracts.forms.schedule-configuration />
                </div>

                <!-- Step 4: Asset Assignment & Coverage -->
                <div x-show="currentStep === 4" x-transition class="px-4 pb-4">
                    <x-contracts.forms.asset-assignment />
                </div>

                <!-- Step 5: Review & Submit -->
                <div x-show="currentStep === 5" x-transition class="px-4 pb-4">
                    <x-contracts.forms.contract-review />
                </div>

                <!-- Hidden form fields for complex data -->
                <input type="hidden" name="template_id" :value="selectedTemplate ? selectedTemplate.id : ''">
                <input type="hidden" name="variable_values" :value="JSON.stringify(variableValues)">
                <input type="hidden" name="billing_config" :value="JSON.stringify(billingConfig)">
                <input type="hidden" name="infrastructure_schedule" :value="JSON.stringify(infrastructureSchedule)">
                <input type="hidden" name="pricing_schedule" :value="JSON.stringify(pricingSchedule)">
                <input type="hidden" name="additional_terms" :value="JSON.stringify(additionalTerms)">
                <input type="hidden" name="telecom_schedule" :value="JSON.stringify(telecomSchedule)">
                <input type="hidden" name="hardware_schedule" :value="JSON.stringify(hardwareSchedule)">
                <input type="hidden" name="compliance_schedule" :value="JSON.stringify(complianceSchedule)">

                <!-- Navigation Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <!-- Navigation Controls -->
                        <div class="flex items-center space-x-4">
                            <button type="button" @click="previousStep()" 
                                    x-show="currentStep > 1"
                                    class="inline-flex items-center px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 rounded-xl hover:bg-white dark:hover:bg-gray-600 transition-all duration-200 border border-gray-300 dark:border-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Previous Step
                            </button>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex items-center space-x-4">
                            <!-- Save Draft -->
                            <button type="button" @click="saveDraft()" 
                                    :disabled="!hasProgress()"
                                    class="inline-flex items-center px-6 py-3 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Save Draft
                            </button>
                            
                            <!-- Next/Create Button -->
                            <button type="button" @click="nextStep()" 
                                    x-show="currentStep < totalSteps"
                                    :disabled="!canProceedToNext()"
                                    class="inline-flex items-center px-8 py-3 text-white rounded-xl transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-blue-500/50"
                                    :class="canProceedToNext() ? 
                                            'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg' : 
                                            'bg-gray-400 cursor-not-allowed'">
                                <span x-text="getNextButtonText()"></span>
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            
                            <button type="submit" 
                                    x-show="currentStep === totalSteps"
                                    :disabled="!isFormValid()"
                                    class="inline-flex items-center px-10 py-3 text-white rounded-xl transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-green-500/50"
                                    :class="isFormValid() ? 
                                            'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-lg' : 
                                            'bg-gray-400 cursor-not-allowed'">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Create Contract
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function contractWizard() {
    return {
        // Core State
        currentStep: 1,
        totalSteps: 5,
        selectedTemplate: null,
        
        // Form Data
        form: {
            title: '',
            contract_type: '',
            client_id: '',
            description: '',
            start_date: '',
            end_date: '',
            term_months: '',
            currency_code: 'USD',
            payment_terms: ''
        },
        
        // Template & Configuration
        variableValues: {},
        billingConfig: {
            model: '',
            base_rate: '',
            auto_assign_assets: false,
            auto_assign_new_assets: false,
            auto_assign_contacts: false,
            auto_assign_new_contacts: false
        },
        
        // Schedule Configurations
        infrastructureSchedule: {
            supportedAssetTypes: [],
            sla: {
                serviceTier: '',
                responseTimeHours: '',
                resolutionTimeHours: '',
                uptimePercentage: ''
            },
            coverageRules: {
                businessHours: '8x5',
                emergencySupport: 'included',
                autoAssignNewAssets: true,
                includeRemoteSupport: true,
                includeOnsiteSupport: false
            },
            exclusions: {
                assetTypes: '',
                services: ''
            }
        },
        
        pricingSchedule: {
            billingModel: '',
            basePricing: {
                monthlyBase: '',
                setupFee: '',
                hourlyRate: ''
            },
            perUnitPricing: {
                perUser: ''
            },
            assetTypePricing: {
                hypervisor_node: { enabled: false, price: '' },
                workstation: { enabled: false, price: '' },
                server: { enabled: false, price: '' },
                network_device: { enabled: false, price: '' },
                mobile_device: { enabled: false, price: '' },
                printer: { enabled: false, price: '' },
                storage: { enabled: false, price: '' },
                security_device: { enabled: false, price: '' }
            },
            // Template-specific pricing structures
            telecomPricing: {
                perChannel: '',
                callingPlan: '',
                e911: '',
                internationalCalling: ''
            },
            hardwarePricing: {
                categoryMarkup: {},
                categoryMinMargin: {},
                installationRate: '',
                configurationRate: '',
                projectManagementRate: '',
                travelRate: ''
            },
            compliancePricing: {
                frameworkSetup: {},
                frameworkMonthly: {},
                externalAudit: '',
                penetrationTesting: '',
                trainingPerUser: ''
            },
            tiers: [
                {
                    minQuantity: '',
                    maxQuantity: '',
                    price: '',
                    discountPercentage: ''
                }
            ],
            additionalFees: [],
            paymentTerms: {
                billingFrequency: 'monthly',
                terms: 'net_30',
                lateFeePercentage: ''
            }
        },
        
        additionalTerms: {
            termination: {
                noticePeriod: '30_days',
                earlyTerminationFee: '',
                forCause: ''
            },
            liability: {
                capType: 'contract_value',
                capAmount: '',
                excludedDamages: []
            },
            dataProtection: {
                classification: 'confidential',
                retentionPeriod: 'contract_term',
                complianceStandards: []
            },
            disputeResolution: {
                method: 'negotiation',
                governingLaw: 'client_state'
            },
            customClauses: [],
            amendments: {
                process: 'mutual_written',
                noticePeriod: '',
                allowPriceChanges: false,
                requireMutualConsent: true
            }
        },
        
        // Template-Specific Schedules
        telecomSchedule: {
            channelCount: 10,
            callingPlan: 'local_long_distance',
            internationalCalling: 'additional',
            emergencyServices: 'enabled',
            qos: {
                meanOpinionScore: '4.2',
                jitterMs: 30,
                packetLossPercent: 0.1,
                uptimePercent: '99.9',
                maxOutageDuration: '4 hours',
                latencyMs: 80,
                responseTimeHours: 1,
                resolutionTimeHours: 8,
                supportCoverage: '24x7'
            },
            carrier: {
                primary: '',
                backup: ''
            },
            protocol: 'sip',
            codecs: ['G.711', 'G.722'],
            compliance: {
                fccCompliant: true,
                karisLaw: true,
                rayBaums: true
            },
            security: {
                encryption: true,
                fraudProtection: true,
                callRecording: false
            }
        },
        
        hardwareSchedule: {
            selectedCategories: [],
            procurementModel: 'direct_resale',
            leadTimeDays: 5,
            leadTimeType: 'business_days',
            services: {
                basicInstallation: false,
                rackAndStack: false,
                cabling: false,
                powerConfiguration: false,
                basicConfiguration: false,
                advancedConfiguration: false,
                customConfiguration: false,
                testing: false,
                projectManagement: false,
                training: false,
                documentation: false,
                migration: false
            },
            sla: {
                installationTimeline: 'Within 5 business days',
                configurationTimeline: 'Within 2 business days',
                supportResponse: '4_hours'
            },
            warranty: {
                hardwarePeriod: '1_year',
                supportPeriod: '1_year',
                onSiteSupport: false,
                advancedReplacement: false,
                extendedOptions: []
            },
            pricing: {
                markupModel: 'fixed_percentage',
                categoryMarkup: {},
                volumeTiers: [],
                installationRate: '',
                configurationRate: '',
                projectManagementRate: '',
                travelRate: '',
                hardwarePaymentTerms: 'net_30',
                servicePaymentTerms: 'net_30',
                taxExempt: false
            }
        },
        
        complianceSchedule: {
            selectedFrameworks: [],
            scope: '',
            riskLevel: 'medium',
            industrySector: '',
            audits: {
                internal: false,
                external: false,
                penetrationTesting: false,
                vulnerabilityScanning: false,
                riskAssessment: false
            },
            frequency: {
                comprehensive: 'annually',
                interim: 'quarterly',
                vulnerability: 'monthly'
            },
            deliverables: {
                executiveSummary: false,
                detailedFindings: false,
                remediationPlan: false,
                complianceMatrix: false,
                dashboardReporting: false
            },
            training: {
                selectedPrograms: [],
                deliveryMethod: 'online',
                frequency: 'annually',
                tracking: {
                    attendance: false,
                    assessments: false,
                    certifications: false
                },
                minimumScore: 80
            },
            monitoring: {
                siem: false,
                logManagement: false,
                fileIntegrity: false,
                accessMonitoring: false,
                changeManagement: false
            },
            alerting: {
                critical: false,
                high: false,
                medium: false,
                low: false
            },
            notifications: {
                email: false,
                sms: false,
                dashboard: false
            },
            reporting: {
                executiveFrequency: 'quarterly',
                technicalFrequency: 'monthly',
                dashboardUpdates: 'daily'
            },
            response: {
                criticalTime: '1_hour',
                highTime: '4_hours',
                standardTime: '24_hours'
            },
            remediation: {
                immediateContainment: false,
                rootCauseAnalysis: false,
                correctiveActions: false,
                preventiveActions: false,
                verification: false,
                documentation: false
            },
            penalties: {
                tier1: 5.0,
                tier2: 10.0,
                tier3: 15.0,
                tier4: 25.0
            }
        },
        
        
        // UI State
        templateFilter: {
            category: '',
            billingModel: ''
        },
        activeScheduleTab: 'schedule_a',
        templates: @json($templates),
        filteredTemplates: @json($templates),
        
        // Helper Data Arrays
        availableAssetTypes: [
            { value: 'hypervisor_node', label: 'Hypervisor Nodes', description: 'Proxmox, VMware, Hyper-V hosts', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' },
            { value: 'workstation', label: 'Workstations', description: 'Desktop & laptop computers', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
            { value: 'server', label: 'Servers', description: 'Physical & virtual servers', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' },
            { value: 'network_device', label: 'Network Devices', description: 'Routers, switches, firewalls', icon: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0' },
            { value: 'mobile_device', label: 'Mobile Devices', description: 'Phones, tablets, mobile endpoints', icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z' },
            { value: 'printer', label: 'Printers & Peripherals', description: 'Network printers, scanners, MFDs', icon: 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z' },
            { value: 'storage', label: 'Storage Systems', description: 'NAS, SAN, backup appliances', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2' },
            { value: 'security_device', label: 'Security Devices', description: 'Security cameras, access controls', icon: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' }
        ],
        
        billingModels: [
            { value: 'fixed', label: 'Fixed Rate', description: 'Single monthly fee' },
            { value: 'per_asset', label: 'Per Asset', description: 'Charge per device' },
            { value: 'per_user', label: 'Per User', description: 'Charge per user/contact' },
            { value: 'tiered', label: 'Tiered Pricing', description: 'Volume-based pricing' }
        ],
        
        serviceTiers: [
            { 
                value: 'bronze', 
                label: 'Bronze', 
                color: 'text-amber-600 dark:text-amber-400',
                responseTime: 8, 
                resolutionTime: 48, 
                uptime: 99.0, 
                coverage: '8x5'
            },
            { 
                value: 'silver', 
                label: 'Silver', 
                color: 'text-gray-600 dark:text-gray-400',
                responseTime: 4, 
                resolutionTime: 24, 
                uptime: 99.5, 
                coverage: '12x5'
            },
            { 
                value: 'gold', 
                label: 'Gold', 
                color: 'text-yellow-600 dark:text-yellow-400',
                responseTime: 2, 
                resolutionTime: 12, 
                uptime: 99.9, 
                coverage: '24x7'
            },
            { 
                value: 'platinum', 
                label: 'Platinum', 
                color: 'text-purple-600 dark:text-purple-400',
                responseTime: 1, 
                resolutionTime: 4, 
                uptime: 99.95, 
                coverage: '24x7'
            }
        ],
        
        // Hardware Categories for VAR Templates
        hardwareCategories: [
            { value: 'servers', label: 'Servers', description: 'Physical & virtual server hardware', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>' },
            { value: 'networking', label: 'Networking', description: 'Switches, routers, firewalls, wireless', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>' },
            { value: 'workstations', label: 'Workstations', description: 'Desktop & laptop computers', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>' },
            { value: 'storage', label: 'Storage', description: 'NAS, SAN, backup systems', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>' },
            { value: 'security', label: 'Security', description: 'Security appliances & cameras', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>' },
            { value: 'printers', label: 'Printers', description: 'Printers, scanners, MFDs', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>' }
        ],
        
        // Compliance Frameworks
        complianceFrameworks: [
            { value: 'hipaa', label: 'HIPAA', description: 'Health Insurance Portability and Accountability Act', scope: 'Healthcare', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' },
            { value: 'sox', label: 'SOX', description: 'Sarbanes-Oxley Act', scope: 'Financial', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>' },
            { value: 'pci_dss', label: 'PCI DSS', description: 'Payment Card Industry Data Security Standard', scope: 'Payment Processing', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>' },
            { value: 'gdpr', label: 'GDPR', description: 'General Data Protection Regulation', scope: 'Data Privacy', auditFrequency: 'Bi-annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>' },
            { value: 'nist', label: 'NIST Cybersecurity Framework', description: 'National Institute of Standards and Technology', scope: 'Cybersecurity', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' },
            { value: 'iso_27001', label: 'ISO 27001', description: 'Information Security Management System', scope: 'Information Security', auditFrequency: 'Annually', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' }
        ],
        
        // Wizard Steps Configuration
        steps: [
            { id: 1, title: 'Template', subtitle: 'Select template', completed: false },
            { id: 2, title: 'Details', subtitle: 'Contract details', completed: false },
            { id: 3, title: 'Configuration', subtitle: 'Settings', completed: false },
            { id: 4, title: 'Review', subtitle: 'Verify details', completed: false },
            { id: 5, title: 'Create', subtitle: 'Complete', completed: false }
        ],
        
        // Computed Properties
        get completedSteps() {
            return this.steps.filter(step => step.completed).length;
        },
        
        get estimatedValue() {
            if (!this.selectedTemplate) return '$---.--';
            
            // Simple estimation logic - would be more complex in real implementation
            const baseValues = {
                'fixed': 2500,
                'per_asset': 1800,
                'per_contact': 2200,
                'tiered': 3500,
                'hybrid': 4200
            };
            
            const value = baseValues[this.selectedTemplate.billing_model] || 2500;
            return '$' + value.toLocaleString();
        },
        
        // Initialization
        init() {
            console.log('Contract wizard initialized');
            console.log('Templates loaded:', this.templates.length);
            if (this.templates.length > 0) {
                console.log('First template:', this.templates[0]);
            }
            this.loadSavedProgress();
            this.setupAutoSave();
            this.filteredTemplates = this.templates;
        },
        
        // Step Navigation
        getStepTitle() {
            const titles = {
                1: 'Template Selection',
                2: 'Contract Details',
                3: 'Configuration',
                4: 'Review',
                5: 'Create Contract'
            };
            return titles[this.currentStep] || 'Contract Creation';
        },
        
        getStepDescription() {
            const descriptions = {
                1: 'Select a contract template or create a custom contract',
                2: 'Enter contract details and client information',
                3: 'Configure billing and contract settings',
                4: 'Review contract details before creation',
                5: 'Create the contract'
            };
            return descriptions[this.currentStep] || 'Complete contract setup';
        },
        
        getStepStyles(stepId) {
            if (stepId === this.currentStep) {
                return 'border-blue-500 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 ring-2 ring-blue-500/20';
            } else if (stepId < this.currentStep || this.steps[stepId - 1].completed) {
                return 'border-green-500 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20';
            } else {
                return 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-500';
            }
        },
        
        getStepIconStyles(stepId) {
            if (stepId === this.currentStep) {
                return 'bg-gradient-to-r from-blue-600 to-purple-600 text-white';
            } else if (stepId < this.currentStep || this.steps[stepId - 1].completed) {
                return 'bg-green-500 text-white';
            } else {
                return 'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400';
            }
        },
        
        navigateToStep(stepId) {
            if (this.canNavigateToStep(stepId)) {
                this.currentStep = stepId;
                this.saveProgress();
            }
        },
        
        canNavigateToStep(stepId) {
            // Can go to previous steps or next step if current is valid
            return stepId <= this.currentStep || (stepId === this.currentStep + 1 && this.canProceedToNext());
        },
        
        nextStep() {
            if (this.canProceedToNext()) {
                // Mark current step as completed
                this.steps[this.currentStep - 1].completed = true;
                this.currentStep++;
                this.saveProgress();
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },
        
        canProceedToNext() {
            switch (this.currentStep) {
                case 1:
                    return true; // Template selection is optional
                case 2:
                    // Check required fields AND the end_date/term_months requirement
                    const requiredFields = this.form.title && this.form.contract_type && this.form.client_id && this.form.start_date;
                    const dateValidation = this.form.end_date || this.form.term_months;
                    return requiredFields && dateValidation;
                case 3:
                    return this.validateScheduleConfiguration();
                case 4:
                    return this.validateAssetAssignment();
                default:
                    return true;
            }
        },
        
        isFormValid() {
            return this.canProceedToNext() && this.currentStep === this.totalSteps;
        },
        
        getNextButtonText() {
            const texts = {
                1: 'Continue',
                2: 'Continue',
                3: 'Continue',
                4: 'Continue'
            };
            return texts[this.currentStep] || 'Continue';
        },
        
        // Template Functions
        selectTemplate(template) {
            console.log('selectTemplate called with:', template);
            this.selectedTemplate = template;
            
            if (template) {
                console.log('Template selected:', template.name, 'Type:', template.template_type);
                
                // Initialize variable values
                this.variableValues = {};
                if (template.variable_fields && Array.isArray(template.variable_fields)) {
                    template.variable_fields.forEach(field => {
                        this.variableValues[field.name] = field.default_value || '';
                    });
                }
                
                // Auto-populate form fields from template
                const mappedType = this.mapTemplateTypeToContractType(template.template_type);
                console.log('Mapping', template.template_type, 'to', mappedType);
                
                // Force reactive updates by replacing the entire form object
                this.form = {
                    ...this.form,
                    contract_type: mappedType,
                    title: template.name
                };
                
                console.log('Form updated - contract_type:', this.form.contract_type, 'title:', this.form.title);
                
                // Also directly update the DOM elements to ensure they show the values
                this.$nextTick(() => {
                    const contractTypeSelect = document.querySelector('select[name="contract_type"]');
                    const titleInput = document.querySelector('input[name="title"]');
                    
                    if (contractTypeSelect) {
                        contractTypeSelect.value = mappedType;
                        contractTypeSelect.dispatchEvent(new Event('change'));
                    }
                    
                    if (titleInput) {
                        titleInput.value = template.name;
                        titleInput.dispatchEvent(new Event('input'));
                    }
                    
                    console.log('DOM elements updated directly');
                });
                
                // Auto-fill form fields from template defaults
                if (template.default_values) {
                    Object.keys(template.default_values).forEach(key => {
                        if (this.form.hasOwnProperty(key)) {
                            this.form[key] = template.default_values[key];
                        }
                    });
                }
            } else {
                console.log('Custom contract selected - clearing fields');
                // Reset form when selecting custom contract
                this.form = {
                    ...this.form,
                    contract_type: '',
                    title: ''
                };
                
                // Also clear DOM elements directly
                this.$nextTick(() => {
                    const contractTypeSelect = document.querySelector('select[name="contract_type"]');
                    const titleInput = document.querySelector('input[name="title"]');
                    
                    if (contractTypeSelect) {
                        contractTypeSelect.value = '';
                        contractTypeSelect.dispatchEvent(new Event('change'));
                    }
                    
                    if (titleInput) {
                        titleInput.value = '';
                        titleInput.dispatchEvent(new Event('input'));
                    }
                    
                    console.log('DOM elements cleared for custom contract');
                });
            }
            
            this.saveProgress();
        },
        
        mapTemplateTypeToContractType(templateType) {
            // Map template types from seeder to contract type form options
            // Valid contract types: one_time_service, recurring_service, maintenance, support, managed_services
            const mapping = {
                'managed_services': 'managed_services',
                'cybersecurity_services': 'recurring_service',
                'backup_dr': 'recurring_service', 
                'cloud_migration': 'one_time_service',
                'm365_management': 'managed_services',
                'break_fix': 'maintenance',
                'enterprise_managed': 'managed_services',
                'mdr_services': 'recurring_service',
                'hosted_pbx': 'recurring_service',
                'sip_trunking': 'recurring_service',
                'unified_communications': 'recurring_service',
                'international_calling': 'recurring_service',
                'contact_center': 'recurring_service',
                'e911_services': 'recurring_service',
                'number_porting': 'one_time_service',
                'hardware_procurement': 'one_time_service',
                'software_licensing': 'recurring_service',
                'vendor_partner': 'recurring_service',
                'solution_integration': 'one_time_service',
                'professional_services': 'one_time_service',
                'business_associate': 'support',
                'data_processing': 'support',
                'consumption_based': 'recurring_service'
            };
            
            return mapping[templateType] || 'recurring_service';
        },
        
        filterTemplates() {
            this.filteredTemplates = this.templates.filter(template => {
                const categoryMatch = !this.templateFilter.category || template.category === this.templateFilter.category;
                const billingMatch = !this.templateFilter.billingModel || template.billing_model === this.templateFilter.billingModel;
                return categoryMatch && billingMatch;
            });
        },
        
        // Utility Functions
        getBillingModelLabel(model) {
            const labels = {
                'fixed': 'Fixed Price',
                'per_asset': 'Per Asset/Device',
                'per_contact': 'Per User/Seat',
                'tiered': 'Tiered Pricing',
                'hybrid': 'Hybrid Model'
            };
            return labels[model] || model;
        },
        
        getBillingModelStyle(model) {
            const styles = {
                'fixed': 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300',
                'per_asset': 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300',
                'per_contact': 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300',
                'tiered': 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300',
                'hybrid': 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-300'
            };
            return styles[model] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300';
        },
        
        getCategoryGradient(category) {
            const gradients = {
                'msp': 'bg-gradient-to-br from-blue-500 to-cyan-500',
                'voip': 'bg-gradient-to-br from-purple-500 to-pink-500',
                'var': 'bg-gradient-to-br from-green-500 to-emerald-500',
                'compliance': 'bg-gradient-to-br from-red-500 to-orange-500',
                'general': 'bg-gradient-to-br from-gray-500 to-slate-500'
            };
            return gradients[category] || gradients['general'];
        },
        
        getCategoryIconBg(category) {
            const styles = {
                'msp': 'bg-gradient-to-r from-blue-600 to-cyan-600',
                'voip': 'bg-gradient-to-r from-purple-600 to-pink-600',
                'var': 'bg-gradient-to-r from-green-600 to-emerald-600',
                'compliance': 'bg-gradient-to-r from-red-600 to-orange-600',
                'general': 'bg-gradient-to-r from-gray-600 to-slate-600'
            };
            return styles[category] || styles['general'];
        },
        
        // Event Handlers
        onClientSelected() {
            this.saveProgress();
        },
        
        handleClientSelected(event) {
            console.log('handleClientSelected called with:', event.detail);
            if (event.detail && event.detail.client) {
                this.form.client_id = event.detail.client.id;
                console.log('Updated form.client_id to:', this.form.client_id);
                this.saveProgress();
            }
        },
        
        // Validation
        validateConfiguration() {
            if (this.selectedTemplate && this.selectedTemplate.variable_fields && Array.isArray(this.selectedTemplate.variable_fields)) {
                const requiredFields = this.selectedTemplate.variable_fields.filter(f => f.required);
                return requiredFields.every(field => this.variableValues[field.name]);
            }
            return true;
        },
        
        validateScheduleConfiguration() {
            // Schedule configuration is always valid for now
            // In production, would validate that schedules are properly configured
            return true;
        },
        
        validateAssetAssignment() {
            // Asset assignment configuration is always valid
            // Assignment rules are optional
            return true;
        },
        
        // Persistence
        hasProgress() {
            return this.form.title || this.form.client_id || this.selectedTemplate;
        },
        
        saveProgress() {
            const progressData = {
                currentStep: this.currentStep,
                steps: this.steps,
                selectedTemplate: this.selectedTemplate,
                form: this.form,
                variableValues: this.variableValues,
                billingConfig: this.billingConfig,
                infrastructureSchedule: this.infrastructureSchedule,
                pricingSchedule: this.pricingSchedule,
                additionalTerms: this.additionalTerms,
                templateFilter: this.templateFilter,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('contract_wizard_progress', JSON.stringify(progressData));
        },
        
        loadSavedProgress() {
            const saved = localStorage.getItem('contract_wizard_progress');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    
                    // Only restore if data is recent (within 24 hours)
                    const dataAge = new Date() - new Date(data.timestamp);
                    if (dataAge < 24 * 60 * 60 * 1000) {
                        this.currentStep = data.currentStep || 1;
                        this.steps = data.steps || this.steps;
                        this.selectedTemplate = data.selectedTemplate;
                        this.form = { ...this.form, ...data.form };
                        this.variableValues = data.variableValues || {};
                        this.billingConfig = { ...this.billingConfig, ...data.billingConfig };
                        this.infrastructureSchedule = { ...this.infrastructureSchedule, ...data.infrastructureSchedule };
                        this.pricingSchedule = { ...this.pricingSchedule, ...data.pricingSchedule };
                        this.additionalTerms = { ...this.additionalTerms, ...data.additionalTerms };
                        this.templateFilter = { ...this.templateFilter, ...data.templateFilter };
                    }
                } catch (e) {
                    console.warn('Failed to load saved progress:', e);
                }
            }
        },
        
        setupAutoSave() {
            // Auto-save every 30 seconds
            setInterval(() => {
                if (this.hasProgress()) {
                    this.saveProgress();
                }
            }, 30000);
        },
        
        quickSave() {
            if (!this.hasProgress()) return;
            
            this.saveProgress();
            this.showNotification('Progress saved locally!', 'success');
        },
        
        clearProgress() {
            // Clear localStorage
            localStorage.removeItem('contract_wizard_progress');
            
            // Reset all form state to initial values
            this.currentStep = 1;
            this.selectedTemplate = null;
            this.form = {
                title: '',
                contract_type: '',
                client_id: '',
                description: '',
                start_date: '',
                end_date: '',
                term_months: '',
                currency_code: 'USD',
                payment_terms: ''
            };
            this.variableValues = {};
            this.billingConfig = {
                model: '',
                base_rate: '',
                auto_assign_assets: false,
                auto_assign_new_assets: false,
                auto_assign_contacts: false,
                auto_assign_new_contacts: false
            };
            this.infrastructureSchedule = {
                supportedAssetTypes: [],
                sla: {
                    serviceTier: '',
                    responseTimeHours: '',
                    resolutionTimeHours: '',
                    uptimePercentage: ''
                },
                coverageRules: {
                    businessHours: '8x5',
                    emergencySupport: 'included',
                    autoAssignNewAssets: true,
                    includeRemoteSupport: true,
                    includeOnsiteSupport: false
                },
                exclusions: {
                    assetTypes: '',
                    services: ''
                }
            };
            this.pricingSchedule = {
                billingModel: '',
                basePricing: {
                    monthlyBase: '',
                    setupFee: '',
                    hourlyRate: ''
                },
                perUnitPricing: {
                    perUser: ''
                },
                assetTypePricing: {
                    hypervisor_node: { enabled: false, price: '' },
                    workstation: { enabled: false, price: '' },
                    server: { enabled: false, price: '' },
                    network_device: { enabled: false, price: '' },
                    mobile_device: { enabled: false, price: '' },
                    printer: { enabled: false, price: '' },
                    storage: { enabled: false, price: '' },
                    security_device: { enabled: false, price: '' }
                },
                tiers: [
                    {
                        minQuantity: '',
                        maxQuantity: '',
                        price: '',
                        discountPercentage: ''
                    }
                ],
                additionalFees: [],
                paymentTerms: {
                    billingFrequency: 'monthly',
                    terms: 'net_30',
                    lateFeePercentage: ''
                }
            };
            this.additionalTerms = {
                termination: {
                    noticePeriod: '30_days',
                    earlyTerminationFee: '',
                    forCause: ''
                },
                liability: {
                    capType: 'contract_value',
                    capAmount: '',
                    excludedDamages: []
                },
                dataProtection: {
                    classification: 'confidential',
                    retentionPeriod: 'contract_term',
                    complianceStandards: []
                },
                disputeResolution: {
                    method: 'negotiation',
                    governingLaw: 'client_state'
                },
                customClauses: [],
                amendments: {
                    process: 'mutual_written',
                    noticePeriod: '',
                    allowPriceChanges: false,
                    requireMutualConsent: true
                }
            };
            this.templateFilter = {
                category: '',
                billingModel: ''
            };
            
            // Reset step completion status
            this.steps = this.steps.map(step => ({ ...step, completed: false }));
            
            // Reset filtered templates
            this.filteredTemplates = this.templates;
            
            console.log('Progress cleared');
        },
        
        cancelContract() {
            if (this.hasProgress()) {
                // Show confirmation dialog for unsaved progress
                const confirmed = confirm('Are you sure you want to cancel?\n\nAll unsaved progress will be lost and cannot be recovered.');
                if (confirmed) {
                    this.clearProgress();
                    window.location.href = "{{ route('financial.contracts.index') }}";
                }
            } else {
                // No progress to lose, just navigate away
                window.location.href = "{{ route('financial.contracts.index') }}";
            }
        },

        saveDraft() {
            // Implementation for saving as draft to server
            this.showNotification('Draft saved successfully!', 'success');
        },
        
        // Notifications
        showNotification(message, type = 'info') {
            // Create and show notification
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-4 rounded-xl shadow-2xl z-50 transform translate-x-full transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-medium">${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 4000);
        },
        
        // Schedule Management Functions
        addPricingTier() {
            this.pricingSchedule.tiers.push({
                minQuantity: '',
                maxQuantity: '',
                price: '',
                discountPercentage: ''
            });
        },
        
        removePricingTier(index) {
            if (this.pricingSchedule.tiers.length > 1) {
                this.pricingSchedule.tiers.splice(index, 1);
            }
        },
        
        addAdditionalFee() {
            this.pricingSchedule.additionalFees.push({
                name: '',
                amount: '',
                frequency: 'one_time',
                type: 'fixed',
                description: ''
            });
        },
        
        removeAdditionalFee(index) {
            this.pricingSchedule.additionalFees.splice(index, 1);
        },
        
        addCustomClause() {
            this.additionalTerms.customClauses.push({
                title: '',
                content: ''
            });
        },
        
        removeCustomClause(index) {
            this.additionalTerms.customClauses.splice(index, 1);
        },
        
        updateSlaFromTier(tier) {
            this.infrastructureSchedule.sla.responseTimeHours = tier.responseTime;
            this.infrastructureSchedule.sla.resolutionTimeHours = tier.resolutionTime;
            this.infrastructureSchedule.sla.uptimePercentage = tier.uptime;
            this.infrastructureSchedule.coverageRules.businessHours = tier.coverage;
        },

        // Form Submission
        handleSubmission(event) {
            if (!this.isFormValid()) {
                event.preventDefault();
                this.showNotification('Please complete all required fields', 'error');
                return;
            }
            
            // Clear saved progress on successful submission
            localStorage.removeItem('contract_wizard_progress');
            this.showNotification('Creating your contract...', 'success');
        },

        // Template-Specific Schedule Methods
        getScheduleType() {
            if (!this.selectedTemplate) return 'infrastructure';
            
            const templateType = this.selectedTemplate.type;
            
            // VoIP/Telecommunications templates
            if (['sip_trunking', 'unified_communications', 'international_calling', 'contact_center', 'e911', 'number_porting'].includes(templateType)) {
                return 'telecom';
            }
            
            // VAR/Hardware templates  
            if (['hardware_procurement', 'hardware_maintenance', 'hardware_installation', 'equipment_leasing', 'warranty_services'].includes(templateType)) {
                return 'hardware';
            }
            
            // Compliance templates
            if (['hipaa_compliance', 'sox_compliance', 'pci_compliance', 'gdpr_compliance', 'security_audit'].includes(templateType)) {
                return 'compliance';
            }
            
            // Default to infrastructure for MSP templates
            return 'infrastructure';
        },
        
        getScheduleALabel() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Schedule A - Telecommunications & Service Levels';
                case 'hardware':
                    return 'Schedule A - Hardware Products & Services';
                case 'compliance':
                    return 'Schedule A - Compliance Framework & Requirements';
                default:
                    return 'Schedule A - Infrastructure & SLA';
            }
        },
        
        getScheduleDescription() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Configure telecommunications services, QoS metrics, and compliance requirements';
                case 'hardware':
                    return 'Configure hardware procurement, installation services, and warranty terms';
                case 'compliance':
                    return 'Configure regulatory compliance requirements and audit schedules';
                default:
                    return 'Configure infrastructure coverage, pricing, and additional terms';
            }
        },
        
        getScheduleTypeLabel() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Telecommunications Schedule Configuration';
                case 'hardware':
                    return 'Hardware & VAR Schedule Configuration';
                case 'compliance':
                    return 'Compliance Framework Configuration';
                default:
                    return 'Infrastructure & SLA Configuration';
            }
        },
        
        getScheduleTypeDetails() {
            const scheduleType = this.getScheduleType();
            
            switch (scheduleType) {
                case 'telecom':
                    return 'Channel capacity, calling plans, QoS metrics, and telecom compliance';
                case 'hardware':
                    return 'Product categories, installation services, warranty terms, and pricing models';
                case 'compliance':
                    return 'Regulatory frameworks, audit schedules, training programs, and monitoring';
                default:
                    return 'Asset coverage, service level agreements, and support configurations';
            }
        }
    }
}
</script>

<style>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom gradient for progress bar */
.text-gradient-to-r {
    background: linear-gradient(to right, #2563eb, #7c3aed);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>
@endsection