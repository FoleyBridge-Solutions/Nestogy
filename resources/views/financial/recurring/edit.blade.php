@extends('layouts.app')

@section('title', 'Edit Recurring Billing')

@php
$pageTitle = 'Edit Recurring Billing';
@endphp

@section('content')
<div class="container mx-auto mx-auto px-4 mx-auto px-4 mx-auto px-6 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <p class="text-gray-600 mt-1">Update VoIP recurring billing service</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('financial.recurring.show', $recurring) }}" 
                   class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Details
                </a>
                <a href="{{ route('financial.recurring.index') }}" 
                   class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('financial.recurring.update', $recurring) }}" class="space-y-6 p-6">
                @csrf
                @method('PATCH')

                <!-- Basic Information -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Client -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                            <select name="client_id" id="client_id" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('client_id') border-red-300 @enderror">
                                <option value="">Select client</option>
                                @foreach($clients ?? [] as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $recurring->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}{{ $client->company_name ? ' (' . $client->company_name . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Service Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Service Name *</label>
                            <input type="text" name="name" id="name" required maxlength="255"
                                   value="{{ old('name', $recurring->name) }}"
                                   placeholder="e.g., VoIP Service - Monthly"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('name') border-red-300 @enderror">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Service Type -->
                        <div>
                            <label for="service_type" class="block text-sm font-medium text-gray-700">Service Type *</label>
                            <select name="service_type" id="service_type" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('service_type') border-red-300 @enderror">
                                <option value="">Select service type</option>
                                <option value="hosted_pbx" {{ old('service_type', $recurring->service_type) == 'hosted_pbx' ? 'selected' : '' }}>Hosted PBX</option>
                                <option value="sip_trunking" {{ old('service_type', $recurring->service_type) == 'sip_trunking' ? 'selected' : '' }}>SIP Trunking</option>
                                <option value="did_numbers" {{ old('service_type', $recurring->service_type) == 'did_numbers' ? 'selected' : '' }}>DID Numbers</option>
                                <option value="long_distance" {{ old('service_type', $recurring->service_type) == 'long_distance' ? 'selected' : '' }}>Long Distance</option>
                                <option value="international" {{ old('service_type', $recurring->service_type) == 'international' ? 'selected' : '' }}>International</option>
                                <option value="local_calling" {{ old('service_type', $recurring->service_type) == 'local_calling' ? 'selected' : '' }}>Local Calling</option>
                                <option value="toll_free" {{ old('service_type', $recurring->service_type) == 'toll_free' ? 'selected' : '' }}>Toll Free</option>
                                <option value="unified_communications" {{ old('service_type', $recurring->service_type) == 'unified_communications' ? 'selected' : '' }}>Unified Communications</option>
                            </select>
                            @error('service_type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                            <select name="category_id" id="category_id"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('category_id') border-red-300 @enderror">
                                <option value="">Select category</option>
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $recurring->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="md:flex-1 px-6-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3" maxlength="1000"
                                      placeholder="Detailed description of the service..."
                                      class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('description') border-red-300 @enderror">{{ old('description', $recurring->description) }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Billing Configuration -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Billing Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Billing Type -->
                        <div>
                            <label for="billing_type" class="block text-sm font-medium text-gray-700">Billing Type *</label>
                            <select name="billing_type" id="billing_type" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('billing_type') border-red-300 @enderror">
                                <option value="">Select billing type</option>
                                <option value="flat" {{ old('billing_type', $recurring->billing_type) == 'flat' ? 'selected' : '' }}>Flat Rate</option>
                                <option value="usage_based" {{ old('billing_type', $recurring->billing_type) == 'usage_based' ? 'selected' : '' }}>Usage Based</option>
                                <option value="tiered" {{ old('billing_type', $recurring->billing_type) == 'tiered' ? 'selected' : '' }}>Tiered Pricing</option>
                                <option value="hybrid" {{ old('billing_type', $recurring->billing_type) == 'hybrid' ? 'selected' : '' }}>Hybrid (Base + Usage)</option>
                                <option value="volume_discount" {{ old('billing_type', $recurring->billing_type) == 'volume_discount' ? 'selected' : '' }}>Volume Discount</option>
                            </select>
                            @error('billing_type')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Pricing Model -->
                        <div>
                            <label for="pricing_model" class="block text-sm font-medium text-gray-700">Pricing Model *</label>
                            <select name="pricing_model" id="pricing_model" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('pricing_model') border-red-300 @enderror">
                                <option value="">Select pricing model</option>
                                <option value="flat" {{ old('pricing_model', $recurring->pricing_model) == 'flat' ? 'selected' : '' }}>Flat Rate</option>
                                <option value="usage_based" {{ old('pricing_model', $recurring->pricing_model) == 'usage_based' ? 'selected' : '' }}>Usage Based</option>
                                <option value="tiered" {{ old('pricing_model', $recurring->pricing_model) == 'tiered' ? 'selected' : '' }}>Tiered Pricing</option>
                                <option value="hybrid" {{ old('pricing_model', $recurring->pricing_model) == 'hybrid' ? 'selected' : '' }}>Hybrid (Base + Usage)</option>
                                <option value="volume_discount" {{ old('pricing_model', $recurring->pricing_model) == 'volume_discount' ? 'selected' : '' }}>Volume Discount</option>
                            </select>
                            @error('pricing_model')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Base Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Base Amount *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="amount" id="amount" step="0.01" min="0" required
                                       value="{{ old('amount', $recurring->amount) }}"
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('amount') border-red-300 @enderror"
                                       placeholder="0.00">
                            </div>
                            @error('amount')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency_code" class="block text-sm font-medium text-gray-700">Currency *</label>
                            <select name="currency_code" id="currency_code" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('currency_code') border-red-300 @enderror">
                                <option value="USD" {{ old('currency_code', $recurring->currency_code) == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="EUR" {{ old('currency_code', $recurring->currency_code) == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                <option value="GBP" {{ old('currency_code', $recurring->currency_code) == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                <option value="CAD" {{ old('currency_code', $recurring->currency_code) == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                <option value="AUD" {{ old('currency_code', $recurring->currency_code) == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                            </select>
                            @error('currency_code')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Billing Cycle -->
                        <div>
                            <label for="billing_cycle" class="block text-sm font-medium text-gray-700">Billing Cycle *</label>
                            <select name="billing_cycle" id="billing_cycle" required
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('billing_cycle') border-red-300 @enderror">
                                <option value="">Select billing cycle</option>
                                <option value="weekly" {{ old('billing_cycle', $recurring->billing_cycle) == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="bi_weekly" {{ old('billing_cycle', $recurring->billing_cycle) == 'bi_weekly' ? 'selected' : '' }}>Bi-Weekly</option>
                                <option value="monthly" {{ old('billing_cycle', $recurring->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ old('billing_cycle', $recurring->billing_cycle) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="semi_annually" {{ old('billing_cycle', $recurring->billing_cycle) == 'semi_annually' ? 'selected' : '' }}>Semi-Annually</option>
                                <option value="annually" {{ old('billing_cycle', $recurring->billing_cycle) == 'annually' ? 'selected' : '' }}>Annually</option>
                            </select>
                            @error('billing_cycle')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Status & Auto Send -->
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="status" id="status" value="1" {{ old('status', $recurring->status) ? 'checked' : '' }}
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="status" class="font-medium text-gray-700">Active Service</label>
                                    <p class="text-gray-500">Enable billing for this service</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="auto_send" id="auto_send" value="1" {{ old('auto_send', $recurring->auto_send) ? 'checked' : '' }}
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="auto_send" class="font-medium text-gray-700">Auto Send Invoices</label>
                                    <p class="text-gray-500">Automatically send invoices when generated</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Billing Schedule</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Start Date -->
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                            <input type="date" name="start_date" id="start_date" required
                                   value="{{ old('start_date', $recurring->start_date) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('start_date') border-red-300 @enderror">
                            @error('start_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- End Date -->
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" id="end_date"
                                   value="{{ old('end_date', $recurring->end_date) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('end_date') border-red-300 @enderror">
                            @error('end_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Next Billing Date -->
                        <div>
                            <label for="next_billing_date" class="block text-sm font-medium text-gray-700">Next Billing Date *</label>
                            <input type="date" name="next_billing_date" id="next_billing_date" required
                                   value="{{ old('next_billing_date', $recurring->next_billing_date) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('next_billing_date') border-red-300 @enderror">
                            @error('next_billing_date')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- VoIP Features -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">VoIP Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Contract Escalation -->
                        <div class="flex items-center">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="contract_escalation_enabled" id="contract_escalation_enabled" value="1" {{ old('contract_escalation_enabled', $recurring->contract_escalation_enabled) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="contract_escalation_enabled" class="font-medium text-gray-700">Contract Escalations</label>
                                <p class="text-gray-500">Enable automatic price increases</p>
                            </div>
                        </div>

                        <!-- Proration -->
                        <div class="flex items-center">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="proration_enabled" id="proration_enabled" value="1" {{ old('proration_enabled', $recurring->proration_enabled) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="proration_enabled" class="font-medium text-gray-700">Proration</label>
                                <p class="text-gray-500">Enable mid-cycle adjustments</p>
                            </div>
                        </div>

                        <!-- Tax Calculation -->
                        <div class="flex items-center">
                            <div class="flex items-center h-5">
                                <input type="checkbox" name="tax_calculation_enabled" id="tax_calculation_enabled" value="1" {{ old('tax_calculation_enabled', $recurring->tax_calculation_enabled) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="tax_calculation_enabled" class="font-medium text-gray-700">VoIP Tax Calculation</label>
                                <p class="text-gray-500">Enable automatic tax calculation</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Tiers (Dynamic) -->
                <div id="serviceTiersSection" class="border-b border-gray-200 pb-6" style="display: none;">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Service Tiers</h3>
                        <button type="button" id="addServiceTier" class="inline-flex items-center px-6 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-0.5 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Tier
                        </button>
                    </div>
                    <div id="serviceTiersList"></div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex flex-flex-1 px-6 sm:flex-flex flex-wrap -mx-4 justify-end items-center pt-6 border-t border-gray-200 gap-3">
                    <a href="{{ route('financial.recurring.show', $recurring) }}"
                       class="w-full sm:w-auto inline-flex justify-center px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Recurring Billing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const billingType = document.getElementById('billing_type');
    const pricingModel = document.getElementById('pricing_model');
    const serviceTiersSection = document.getElementById('serviceTiersSection');
    const addServiceTierBtn = document.getElementById('addServiceTier');
    const serviceTiersList = document.getElementById('serviceTiersList');
    let tierCount = 0;

    // Existing service tiers from server
    const existingTiers = {!! json_encode(old('service_tiers', $recurring->service_tiers ?? [])) !!};

    // Show/hide service tiers based on billing type
    function toggleServiceTiers() {
        const showTiers = ['usage_based', 'tiered', 'hybrid'].includes(billingType.value);
        serviceTiersSection.style.display = showTiers ? 'block' : 'none';
        
        if (showTiers && serviceTiersList.children.length === 0) {
            loadExistingTiers();
        }
    }

    // Load existing service tiers
    function loadExistingTiers() {
        existingTiers.forEach(function(tier, index) {
            addServiceTierWithData(tier, index);
        });
    }

    // Sync pricing model with billing type
    function syncPricingModel() {
        if (billingType.value && pricingModel.value !== billingType.value) {
            pricingModel.value = billingType.value;
        }
    }

    billingType.addEventListener('change', function() {
        toggleServiceTiers();
        syncPricingModel();
    });

    pricingModel.addEventListener('change', function() {
        if (this.value && billingType.value !== this.value) {
            billingType.value = this.value;
            toggleServiceTiers();
        }
    });

    // Add service tier with data
    function addServiceTierWithData(data = {}, index = null) {
        const tierIndex = index !== null ? index : tierCount;
        const tierHtml = `
            <div class="service-tier border border-gray-200 rounded-lg p-6 mb-6" data-tier="${tierIndex}">
                <div class="flex justify-between items-start mb-6">
                    <h4 class="text-md font-medium text-gray-900">Service Tier ${tierIndex + 1}</h4>
                    <button type="button" class="remove-tier text-red-600 hover:text-red-900">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Service Type *</label>
                        <input type="text" name="service_tiers[${tierIndex}][service_type]" required
                               value="${data.service_type || ''}"
                               class="mt- 1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="e.g., minutes, extensions">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Monthly Allowance *</label>
                        <input type="number" name="service_tiers[${tierIndex}][monthly_allowance]" step="0.01" min="0" required
                               value="${data.monthly_allowance || ''}"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                               placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Base Rate *</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="service_tiers[${tierIndex}][base_rate]" step="0.01" min="0" required
                                   value="${data.base_rate || ''}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                                   placeholder="0.00">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Overage Rate *</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" name="service_tiers[${tierIndex}][overage_rate]" step="0.01" min="0" required
                                   value="${data.overage_rate || ''}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        serviceTiersList.insertAdjacentHTML('beforeend', tierHtml);
        if (index === null) {
            tierCount++;
        } else {
            tierCount = Math.max(tierCount, index + 1);
        }
    }

    // Add service tier
    addServiceTierBtn.addEventListener('click', function() {
        addServiceTierWithData();
    });

    // Remove service tier
    serviceTiersList.addEventListener('click', function(e) {
        if (e.target.closest('.remove-tier')) {
            e.target.closest('.service-tier').remove();
        }
    });

    // Initial setup
    toggleServiceTiers();
});
</script>
@endpush
@endsection
