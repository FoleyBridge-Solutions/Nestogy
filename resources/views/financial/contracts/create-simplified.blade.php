@extends('layouts.app')

@section('title', 'Create Contract')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-6xl mx-auto px-6 sm:px-6 lg:px-8" x-data="contractWizard">
        
        <!-- Wizard Header -->
        <x-contracts.forms.wizard-header />
        
        <!-- Main Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <form @submit="handleSubmission" method="POST" action="{{ route('financial.contracts.store') }}">
                @csrf
                
                <!-- Step 1: Template Selection -->
                <div x-show="currentStep === 1" x-transition class="px-6 pb-4">
                    <x-contracts.forms.template-selector :templates="$templates" />
                </div>

                <!-- Step 2: Basic Details -->
                <div x-show="currentStep === 2" x-transition class="px-6 pb-4">
                    <x-contracts.forms.basic-details 
                        :clients="$clients" 
                        :contract-types="[
                            'one_time_service' => 'One-Time Service',
                            'recurring_service' => 'Recurring Service', 
                            'maintenance' => 'Maintenance',
                            'support' => 'Support',
                            'managed_services' => 'Managed Services'
                        ]" />
                </div>

                <!-- Step 3: Contract Schedules Configuration -->
                <div x-show="currentStep === 3" x-transition class="px-6 pb-4">
                    <x-contracts.forms.schedule-configuration />
                </div>

                <!-- Step 4: Asset Assignment & Coverage -->
                <div x-show="currentStep === 4" x-transition class="px-6 pb-4">
                    <x-contracts.forms.asset-assignment />
                </div>

                <!-- Step 5: Review & Submit -->
                <div x-show="currentStep === 5" x-transition class="px-6 pb-4">
                    <x-contracts.forms.contract-review />
                </div>

                <!-- Hidden form fields for complex data -->
                <input type="hidden" name="template_id" :value="selectedTemplate ? selectedTemplate.id : ''">
                <input type="hidden" name="variable_values" :value="JSON.stringify(variableValues)">
                <input type="hidden" name="billing_config" :value="JSON.stringify(billingConfig)">
                <input type="hidden" name="infrastructure_schedule" :value="JSON.stringify(infrastructureSchedule)">
                <input type="hidden" name="telecom_schedule" :value="JSON.stringify(telecomSchedule)">
                <input type="hidden" name="hardware_schedule" :value="JSON.stringify(hardwareSchedule)">
                <input type="hidden" name="compliance_schedule" :value="JSON.stringify(complianceSchedule)">
                <input type="hidden" name="pricing_schedule" :value="JSON.stringify(pricingSchedule)">
                <input type="hidden" name="additional_terms" :value="JSON.stringify(additionalTerms)">

                <!-- Navigation Footer -->
                <div class="px-6 py-6 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <!-- Navigation Controls -->
                        <div class="flex items-center space-x-4">
                            <button type="button" @click="previousStep()" 
                                    x-show="currentStep > 1"
                                    class="inline-flex items-center px-6 py-6 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300 rounded-xl hover:bg-white dark:hover:bg-gray-600 transition-all duration-200 border border-gray-300 dark:border-gray-600">
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
                                    class="inline-flex items-center px-6 py-6 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Save Draft
                            </button>
                            
                            <!-- Next/Create Button -->
                            <button type="button" @click="nextStep()" 
                                    x-show="currentStep < totalSteps"
                                    :disabled="!canProceedToNext()"
                                    class="inline-flex items-center px-8 py-6 text-white rounded-xl transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-blue-500/50"
                                    :class="canProceedToNext() ? 
                                            'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg' : 
                                            'bg-gray-400 cursor-not-allowed'">
                                <span x-text="getNextButtonText()">Continue</span>
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                            
                            <button type="submit" 
                                    x-show="currentStep === totalSteps"
                                    :disabled="!isFormValid()"
                                    class="inline-flex items-center px-10 py-6 text-white rounded-xl transition-all duration-200 transform hover:scale-105 focus:ring-4 focus:ring-green-500/50"
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
@endsection

@push('scripts')
@vite('resources/js/contract-wizard.js')
@endpush
