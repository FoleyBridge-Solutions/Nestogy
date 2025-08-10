@extends('layouts.app')

@section('title', 'Create Quote')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Quote</h1>
                <p class="text-gray-600 mt-1">Create a new quote for client proposal</p>
            </div>
            <a href="{{ route('financial.quotes.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Quotes
            </a>
        </div>

        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('financial.quotes.store') }}" class="space-y-6 p-6" id="quote-form">
                @csrf

                <!-- Template Selection (if available) -->
                @if($templates->count() > 0)
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                    <h3 class="text-lg font-medium text-blue-900 mb-3">Start from Template</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="template_id" class="block text-sm font-medium text-blue-700">Select Template</label>
                            <select name="template_id" id="template_id" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-blue-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">None - Create from scratch</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" {{ ($selectedTemplate && $selectedTemplate->id === $template->id) ? 'selected' : '' }}>
                                        {{ $template->name }} ({{ $template->getCategoryLabel() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="load-template" 
                                    class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50">
                                Load Template
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Basic Quote Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Client -->
                    <div>
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                        <select name="client_id" id="client_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('client_id') border-red-300 @enderror">
                            <option value="">Select client</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ (old('client_id', $selectedClient?->id) == $client->id) ? 'selected' : '' }}>
                                    {{ $client->name }}{{ $client->company_name ? ' (' . $client->company_name . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                        <select name="category_id" id="category_id" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('category_id') border-red-300 @enderror">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Quote Date -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700">Quote Date *</label>
                        <input type="date" name="date" id="date" required
                               value="{{ old('date', now()->format('Y-m-d')) }}"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('date') border-red-300 @enderror">
                        @error('date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Expiration Date -->
                    <div>
                        <label for="expire_date" class="block text-sm font-medium text-gray-700">Expiration Date</label>
                        <input type="date" name="expire_date" id="expire_date"
                               value="{{ old('expire_date', now()->addDays(30)->format('Y-m-d')) }}"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('expire_date') border-red-300 @enderror">
                        @error('expire_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="currency_code" class="block text-sm font-medium text-gray-700">Currency *</label>
                        <select name="currency_code" id="currency_code" required
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md @error('currency_code') border-red-300 @enderror">
                            <option value="USD" {{ old('currency_code', 'USD') == 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ old('currency_code') == 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ old('currency_code') == 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="CAD" {{ old('currency_code') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                            <option value="AUD" {{ old('currency_code') == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                        </select>
                        @error('currency_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Scope/Description -->
                    <div class="md:col-span-2">
                        <label for="scope" class="block text-sm font-medium text-gray-700">Quote Scope</label>
                        <input type="text" name="scope" id="scope" maxlength="255"
                               value="{{ old('scope') }}"
                               placeholder="Brief description of services or products being quoted"
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('scope') border-red-300 @enderror">
                        @error('scope')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Discount Section -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Discount Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="discount_type" class="block text-sm font-medium text-gray-700">Discount Type</label>
                            <select name="discount_type" id="discount_type"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="fixed" {{ old('discount_type', 'fixed') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>
                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700">Discount Amount</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm" id="discount-symbol">$</span>
                                </div>
                                <input type="number" name="discount_amount" id="discount_amount" step="0.01" min="0"
                                       value="{{ old('discount_amount', '0.00') }}"
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md @error('discount_amount') border-red-300 @enderror"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VoIP Configuration -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">VoIP Configuration</h3>
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="enable-voip" name="enable_voip" value="1" {{ old('enable_voip') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">Enable VoIP Configuration</span>
                        </label>
                    </div>

                    <div id="voip-config" class="space-y-6 {{ old('enable_voip') ? '' : 'hidden' }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="voip_extensions" class="block text-sm font-medium text-gray-700">Extensions</label>
                                <input type="number" name="voip_config[extensions]" id="voip_extensions" min="1" max="1000"
                                       value="{{ old('voip_config.extensions', 5) }}"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label for="voip_concurrent" class="block text-sm font-medium text-gray-700">Concurrent Calls</label>
                                <input type="number" name="voip_config[concurrent_calls]" id="voip_concurrent" min="1" max="500"
                                       value="{{ old('voip_config.concurrent_calls', 3) }}"
                                       class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">VoIP Features</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="voip_config[features][voicemail]" value="1" {{ old('voip_config.features.voicemail', true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-2 text-sm text-gray-700">Voicemail</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="voip_config[features][call_forwarding]" value="1" {{ old('voip_config.features.call_forwarding', true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-2 text-sm text-gray-700">Call Forwarding</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="voip_config[features][conference_calling]" value="1" {{ old('voip_config.features.conference_calling') ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-2 text-sm text-gray-700">Conference Calling</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="voip_config[features][auto_attendant]" value="1" {{ old('voip_config.features.auto_attendant') ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-2 text-sm text-gray-700">Auto Attendant</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Equipment Requirements</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="voip_desk_phones" class="block text-sm font-medium text-gray-600">Desk Phones</label>
                                    <input type="number" name="voip_config[equipment][desk_phones]" id="voip_desk_phones" min="0"
                                           value="{{ old('voip_config.equipment.desk_phones', 0) }}"
                                           class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label for="voip_wireless_phones" class="block text-sm font-medium text-gray-600">Wireless Phones</label>
                                    <input type="number" name="voip_config[equipment][wireless_phones]" id="voip_wireless_phones" min="0"
                                           value="{{ old('voip_config.equipment.wireless_phones', 0) }}"
                                           class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label for="voip_conference_phones" class="block text-sm font-medium text-gray-600">Conference Phones</label>
                                    <input type="number" name="voip_config[equipment][conference_phone]" id="voip_conference_phones" min="0"
                                           value="{{ old('voip_config.equipment.conference_phone', 0) }}"
                                           class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Model -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing Model</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pricing_type" class="block text-sm font-medium text-gray-700">Pricing Type</label>
                            <select name="pricing_model[type]" id="pricing_type"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="flat_rate" {{ old('pricing_model.type', 'flat_rate') == 'flat_rate' ? 'selected' : '' }}>Flat Rate</option>
                                <option value="tiered" {{ old('pricing_model.type') == 'tiered' ? 'selected' : '' }}>Tiered Pricing</option>
                                <option value="usage_based" {{ old('pricing_model.type') == 'usage_based' ? 'selected' : '' }}>Usage Based</option>
                                <option value="hybrid" {{ old('pricing_model.type') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="setup_fee" class="block text-sm font-medium text-gray-700">Setup Fee</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" name="pricing_model[setup_fee]" id="setup_fee" step="0.01" min="0"
                                           value="{{ old('pricing_model.setup_fee', '0.00') }}"
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="0.00">
                                </div>
                            </div>
                            <div>
                                <label for="monthly_recurring" class="block text-sm font-medium text-gray-700">Monthly Recurring</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" name="pricing_model[monthly_recurring]" id="monthly_recurring" step="0.01" min="0"
                                           value="{{ old('pricing_model.monthly_recurring', '0.00') }}"
                                           class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md"
                                           placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Auto-Renewal Settings -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Auto-Renewal Settings</h3>
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="auto-renew" name="auto_renew" value="1" {{ old('auto_renew') ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">Enable Auto-Renewal</span>
                        </label>
                    </div>

                    <div id="auto-renew-options" class="grid grid-cols-1 md:grid-cols-2 gap-6 {{ old('auto_renew') ? '' : 'hidden' }}">
                        <div>
                            <label for="auto_renew_days" class="block text-sm font-medium text-gray-700">Renewal Period (Days)</label>
                            <input type="number" name="auto_renew_days" id="auto_renew_days" min="1" max="365"
                                   value="{{ old('auto_renew_days', 30) }}"
                                   class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Notes and Terms -->
                <div class="border-t border-gray-200 pt-6 space-y-6">
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700">Quote Notes</label>
                        <textarea name="note" id="note" rows="3"
                                  class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('note') border-red-300 @enderror"
                                  placeholder="Additional notes or comments for this quote...">{{ old('note') }}</textarea>
                        @error('note')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="terms_conditions" class="block text-sm font-medium text-gray-700">Terms and Conditions</label>
                        <textarea name="terms_conditions" id="terms_conditions" rows="4"
                                  class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('terms_conditions') border-red-300 @enderror"
                                  placeholder="Terms and conditions for this quote...">{{ old('terms_conditions') }}</textarea>
                        @error('terms_conditions')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Hidden Status Field -->
                <input type="hidden" name="status" value="Draft">
                <input type="hidden" name="approval_status" value="pending">

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row justify-end items-center pt-6 border-t border-gray-200 gap-3">
                    <a href="{{ route('financial.quotes.index') }}"
                       class="w-full sm:w-auto inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Create Quote
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set expiration date automatically when quote date changes
    const quoteDate = document.getElementById('date');
    const expireDate = document.getElementById('expire_date');
    
    quoteDate.addEventListener('change', function() {
        if (this.value) {
            const date = new Date(this.value);
            date.setDate(date.getDate() + 30); // Default 30 days validity
            expireDate.value = date.toISOString().split('T')[0];
        }
    });
    
    // Toggle discount symbol based on type
    const discountType = document.getElementById('discount_type');
    const discountSymbol = document.getElementById('discount-symbol');
    
    discountType.addEventListener('change', function() {
        discountSymbol.textContent = this.value === 'percentage' ? '%' : '$';
    });
    
    // Toggle VoIP configuration
    const enableVoip = document.getElementById('enable-voip');
    const voipConfig = document.getElementById('voip-config');
    
    enableVoip.addEventListener('change', function() {
        voipConfig.classList.toggle('hidden', !this.checked);
    });
    
    // Toggle auto-renewal options
    const autoRenew = document.getElementById('auto-renew');
    const autoRenewOptions = document.getElementById('auto-renew-options');
    
    autoRenew.addEventListener('change', function() {
        autoRenewOptions.classList.toggle('hidden', !this.checked);
    });
    
    // Load template functionality
    const loadTemplateBtn = document.getElementById('load-template');
    const templateSelect = document.getElementById('template_id');
    
    if (loadTemplateBtn && templateSelect) {
        loadTemplateBtn.addEventListener('click', function() {
            const templateId = templateSelect.value;
            if (templateId) {
                if (confirm('Loading a template will replace current form data. Continue?')) {
                    window.location.href = `{{ route('financial.quotes.create') }}?template_id=${templateId}`;
                }
            } else {
                alert('Please select a template first.');
            }
        });
    }
    
    // Validate VoIP equipment doesn't exceed extensions
    const extensionsInput = document.getElementById('voip_extensions');
    const deskPhonesInput = document.getElementById('voip_desk_phones');
    const wirelessPhonesInput = document.getElementById('voip_wireless_phones');
    
    function validateEquipment() {
        if (extensionsInput && deskPhonesInput && wirelessPhonesInput) {
            const extensions = parseInt(extensionsInput.value) || 0;
            const deskPhones = parseInt(deskPhonesInput.value) || 0;
            const wirelessPhones = parseInt(wirelessPhonesInput.value) || 0;
            const totalPhones = deskPhones + wirelessPhones;
            
            if (totalPhones > extensions) {
                alert('Total phones cannot exceed the number of extensions.');
                return false;
            }
        }
        return true;
    }
    
    [deskPhonesInput, wirelessPhonesInput].forEach(input => {
        if (input) {
            input.addEventListener('blur', validateEquipment);
        }
    });
    
    // Form validation before submit
    document.getElementById('quote-form').addEventListener('submit', function(e) {
        if (!validateEquipment()) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
@endsection