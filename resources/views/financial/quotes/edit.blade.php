@extends('layouts.app')

@section('title', 'Edit Quote #' . $quote->getFullNumber())

@section('content')
<div class="container mx-auto px-4 mx-auto px-4 mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Quote #{{ $quote->getFullNumber() }}</h1>
                <p class="text-gray-600 mt-1">{{ $quote->client->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('financial.quotes.show', $quote) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Quote
                </a>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('financial.quotes.update', $quote) }}" id="quoteForm">
            @csrf
            @method('PUT')

            <!-- Quote Information -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quote Information</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                            <select id="client_id" name="client_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select a client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $quote->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}{{ $client->company_name ? ' - ' . $client->company_name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                            <select id="category_id" name="category_id"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $quote->category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700">Quote Date *</label>
                            <input type="date" id="date" name="date" value="{{ old('date', $quote->date->format('Y-m-d')) }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-gray-700">Valid Until</label>
                            <input type="date" id="valid_until" name="valid_until" 
                                   value="{{ old('valid_until', $quote->valid_until?->format('Y-m-d')) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('valid_until')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pricing_model" class="block text-sm font-medium text-gray-700">Pricing Model</label>
                            <select id="pricing_model" name="pricing_model" onchange="togglePricingOptions()"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select pricing model</option>
                                <option value="flat_rate" {{ old('pricing_model', $quote->pricing_model) == 'flat_rate' ? 'selected' : '' }}>Flat Rate</option>
                                <option value="tiered" {{ old('pricing_model', $quote->pricing_model) == 'tiered' ? 'selected' : '' }}>Tiered Pricing</option>
                                <option value="usage_based" {{ old('pricing_model', $quote->pricing_model) == 'usage_based' ? 'selected' : '' }}>Usage Based</option>
                                <option value="hybrid" {{ old('pricing_model', $quote->pricing_model) == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                            @error('pricing_model')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="template_name" class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input type="text" id="template_name" name="template_name" 
                                   value="{{ old('template_name', $quote->template_name) }}"
                                   placeholder="Save as template (optional)"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            @error('template_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="auto_renew" name="auto_renew" value="1"
                                   {{ old('auto_renew', $quote->auto_renew) ? 'checked' : '' }}
                                   onchange="toggleAutoRenewOptions()"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="auto_renew" class="ml-2 block text-sm text-gray-900">
                                Enable auto-renewal
                            </label>
                        </div>

                        <div id="autoRenewDays" class="{{ old('auto_renew', $quote->auto_renew) ? '' : 'hidden' }}">
                            <label for="auto_renew_days" class="block text-sm font-medium text-gray-700">Auto-renewal period (days)</label>
                            <input type="number" id="auto_renew_days" name="auto_renew_days" 
                                   value="{{ old('auto_renew_days', $quote->auto_renew_days ?? 30) }}" min="1" max="365"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- VoIP Configuration -->
            <div class="bg-white shadow rounded-lg mb-6" id="voipConfig">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">VoIP Configuration</h3>
                        <div class="flex items-center">
                            <input type="checkbox" id="enable_voip" name="enable_voip" value="1"
                                   {{ old('enable_voip', !empty($quote->voip_config)) ? 'checked' : '' }}
                                   onchange="toggleVoipConfig()"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="enable_voip" class="ml-2 block text-sm text-gray-900">
                                Enable VoIP Configuration
                            </label>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 {{ old('enable_voip', !empty($quote->voip_config)) ? '' : 'hidden' }}" id="voipConfigContent">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="voip_extensions" class="block text-sm font-medium text-gray-700">Extensions</label>
                            <input type="number" id="voip_extensions" name="voip_config[extensions]" 
                                   value="{{ old('voip_config.extensions', $quote->voip_config['extensions'] ?? '') }}" 
                                   min="1" max="10000"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="voip_concurrent_calls" class="block text-sm font-medium text-gray-700">Concurrent Calls</label>
                            <input type="number" id="voip_concurrent_calls" name="voip_config[concurrent_calls]" 
                                   value="{{ old('voip_config.concurrent_calls', $quote->voip_config['concurrent_calls'] ?? '') }}" 
                                   min="1" max="1000"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Features</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @php
                                $voipFeatures = [
                                    'call_forwarding' => 'Call Forwarding',
                                    'voicemail' => 'Voicemail',
                                    'conference_calling' => 'Conference Calling',
                                    'call_recording' => 'Call Recording',
                                    'auto_attendant' => 'Auto Attendant',
                                    'call_analytics' => 'Call Analytics',
                                    'mobile_app' => 'Mobile App',
                                    'sms_messaging' => 'SMS Messaging'
                                ];
                            @endphp
                            @foreach($voipFeatures as $key => $label)
                            <div class="flex items-center">
                                <input type="checkbox" id="voip_{{ $key }}" name="voip_config[features][{{ $key }}]" value="1"
                                       {{ old("voip_config.features.{$key}", $quote->voip_config['features'][$key] ?? false) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="voip_{{ $key }}" class="ml-2 block text-sm text-gray-900">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Equipment</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            @php
                                $equipmentTypes = [
                                    'desk_phones' => 'Desk Phones',
                                    'conference_phones' => 'Conference Phones',
                                    'headsets' => 'Headsets',
                                    'gateways' => 'Gateways',
                                    'switches' => 'Switches',
                                    'routers' => 'Routers'
                                ];
                            @endphp
                            @foreach($equipmentTypes as $key => $label)
                            <div>
                                <label for="voip_{{ $key }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                                <input type="number" id="voip_{{ $key }}" name="voip_config[equipment][{{ $key }}]" 
                                       value="{{ old("voip_config.equipment.{$key}", $quote->voip_config['equipment'][$key] ?? 0) }}" 
                                       min="0" max="1000"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quote Items -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Quote Items</h3>
                        <button type="button" onclick="addQuoteItem()" 
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                            <svg class="-ml-1 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div id="quoteItems">
                        @if(old('items'))
                            @foreach(old('items') as $index => $item)
                                <div class="quote-item border border-gray-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-900">Item #{{ $index + 1 }}</h4>
                                        <button type="button" onclick="removeQuoteItem(this)" 
                                                class="text-red-600 hover:text-red-800">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="lg:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700">Item Name *</label>
                                            <input type="text" name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" 
                                                   min="0.01" step="0.01" required onchange="calculateItemTotal(this)"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Price *</label>
                                            <input type="number" name="items[{{ $index }}][price]" value="{{ $item['price'] ?? '' }}" 
                                                   min="0" step="0.01" required onchange="calculateItemTotal(this)"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Description</label>
                                            <textarea name="items[{{ $index }}][description]" rows="2"
                                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ $item['description'] ?? '' }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Discount</label>
                                            <input type="number" name="items[{{ $index }}][discount]" value="{{ $item['discount'] ?? 0 }}" 
                                                   min="0" step="0.01" onchange="calculateItemTotal(this)"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($quote->items->count() > 0)
                            @foreach($quote->items as $index => $item)
                                <div class="quote-item border border-gray-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-900">Item #{{ $index + 1 }}</h4>
                                        <button type="button" onclick="removeQuoteItem(this)" 
                                                class="text-red-600 hover:text-red-800">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="lg:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700">Item Name *</label>
                                            <input type="text" name="items[{{ $index }}][name]" value="{{ $item->name }}" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                                            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" 
                                                   min="0.01" step="0.01" required onchange="calculateItemTotal(this)"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Price *</label>
                                            <input type="number" name="items[{{ $index }}][price]" value="{{ $item->price }}" 
                                                   min="0" step="0.01" required onchange="calculateItemTotal(this)"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Description</label>
                                            <textarea name="items[{{ $index }}][description]" rows="2"
                                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ $item->description }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Discount</label>
                                            <input type="number" name="items[{{ $index }}][discount]" value="{{ $item->discount }}" 
                                                   min="0" step="0.01" onchange="calculateItemTotal(this)"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                </div>
                            @endforeach
                        @endif
                    </div>
                    
                    @if(old('items', $quote->items)->isEmpty())
                    <div class="text-center py-12" id="noItemsMessage">
                        <p class="text-gray-500">No items added yet. Click "Add Item" to get started.</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quote Totals and Adjustments -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Quote Adjustments</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="discount_type" class="block text-sm font-medium text-gray-700">Discount Type</label>
                            <select id="discount_type" name="discount_type" onchange="calculateTotals()"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">No Discount</option>
                                <option value="percentage" {{ old('discount_type', $quote->discount_type) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                <option value="fixed" {{ old('discount_type', $quote->discount_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                            </select>
                        </div>

                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700">
                                Discount <span id="discountLabel">Amount</span>
                            </label>
                            <input type="number" id="discount_amount" name="discount_amount" 
                                   value="{{ old('discount_amount', $quote->discount_amount) }}" 
                                   min="0" step="0.01" onchange="calculateTotals()"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                            <input type="number" id="tax_rate" name="tax_rate" 
                                   value="{{ old('tax_rate', $quote->tax_rate) }}" 
                                   min="0" max="100" step="0.01" onchange="calculateTotals()"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Totals Display -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <div class="max-w-md ml-auto">
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm text-gray-900" id="subtotalDisplay">$0.00</span>
                            </div>
                            <div class="flex justify-between py-2" id="discountRow" style="display: none;">
                                <span class="text-sm text-gray-600">Discount:</span>
                                <span class="text-sm text-gray-900" id="discountDisplay">-$0.00</span>
                            </div>
                            <div class="flex justify-between py-2" id="taxRow" style="display: none;">
                                <span class="text-sm text-gray-600">Tax:</span>
                                <span class="text-sm text-gray-900" id="taxDisplay">$0.00</span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-gray-200 font-medium">
                                <span class="text-sm text-gray-900">Total:</span>
                                <span class="text-sm text-gray-900" id="totalDisplay">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes and Terms -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Additional Information</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="note" class="block text-sm font-medium text-gray-700">Internal Notes</label>
                            <textarea id="note" name="note" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                      placeholder="Internal notes (not visible to client)">{{ old('note', $quote->note) }}</textarea>
                            @error('note')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="terms" class="block text-sm font-medium text-gray-700">Terms and Conditions</label>
                            <textarea id="terms" name="terms" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                      placeholder="Terms and conditions (visible to client)">{{ old('terms', $quote->terms) }}</textarea>
                            @error('terms')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('financial.quotes.show', $quote) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" name="action" value="save"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Update Quote
                </button>
                @if($quote->isDraft())
                <button type="submit" name="action" value="save_and_submit"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Update & Submit for Approval
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = {{ old('items', $quote->items)->count() }};

function togglePricingOptions() {
    // Additional logic for pricing model changes can be added here
    calculateTotals();
}

function toggleAutoRenewOptions() {
    const autoRenew = document.getElementById('auto_renew').checked;
    const autoRenewDays = document.getElementById('autoRenewDays');
    
    if (autoRenew) {
        autoRenewDays.classList.remove('hidden');
    } else {
        autoRenewDays.classList.add('hidden');
    }
}

function toggleVoipConfig() {
    const enableVoip = document.getElementById('enable_voip').checked;
    const voipConfigContent = document.getElementById('voipConfigContent');
    
    if (enableVoip) {
        voipConfigContent.classList.remove('hidden');
    } else {
        voipConfigContent.classList.add('hidden');
    }
}

function addQuoteItem() {
    const noItemsMessage = document.getElementById('noItemsMessage');
    if (noItemsMessage) {
        noItemsMessage.style.display = 'none';
    }

    const quoteItems = document.getElementById('quoteItems');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'quote-item border border-gray-200 rounded-lg p-4 mb-4';
    itemDiv.innerHTML = `
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-medium text-gray-900">Item #${itemIndex + 1}</h4>
            <button type="button" onclick="removeQuoteItem(this)"
                    class="text-red-600 hover:text-red-800">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Item Name *</label>
                <input type="text" name="items[${itemIndex}][name]" required
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                <input type="number" name="items[${itemIndex}][quantity]" value="1"
                       min="0.01" step="0.01" required onchange="calculateItemTotal(this)"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Price *</label>
                <input type="number" name="items[${itemIndex}][price]" value="0"
                       min="0" step="0.01" required onchange="calculateItemTotal(this)"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="items[${itemIndex}][description]" rows="2"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Discount</label>
                <input type="number" name="items[${itemIndex}][discount]" value="0"
                       min="0" step="0.01" onchange="calculateItemTotal(this)"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    `;
    
    quoteItems.appendChild(itemDiv);
    itemIndex++;
    calculateTotals();
}

function removeQuoteItem(button) {
    const itemDiv = button.closest('.quote-item');
    itemDiv.remove();
    
    // Update item numbers
    const items = document.querySelectorAll('.quote-item h4');
    items.forEach((item, index) => {
        item.textContent = `Item #${index + 1}`;
    });
    
    // Show no items message if no items left
    const quoteItems = document.getElementById('quoteItems');
    const noItemsMessage = document.getElementById('noItemsMessage');
    if (quoteItems.children.length === 0 && noItemsMessage) {
        noItemsMessage.style.display = 'block';
    }
    
    calculateTotals();
}

function calculateItemTotal(input) {
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    
    // Calculate subtotal from all items
    const items = document.querySelectorAll('.quote-item');
    items.forEach(item => {
        const quantity = parseFloat(item.querySelector('input[name*="[quantity]"]').value) || 0;
        const price = parseFloat(item.querySelector('input[name*="[price]"]').value) || 0;
        const discount = parseFloat(item.querySelector('input[name*="[discount]"]').value) || 0;
        
        subtotal += (quantity * price) - discount;
    });
    
    // Apply overall discount
    const discountType = document.getElementById('discount_type').value;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    let discountValue = 0;
    
    if (discountType && discountAmount > 0) {
        if (discountType === 'percentage') {
            discountValue = subtotal * (discountAmount / 100);
        } else {
            discountValue = discountAmount;
        }
    }
    
    const afterDiscount = subtotal - discountValue;
    
    // Apply tax
    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const taxAmount = afterDiscount * (taxRate / 100);
    
    const total = afterDiscount + taxAmount;
    
    // Update displays
    document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toFixed(2);
    
    const discountRow = document.getElementById('discountRow');
    const discountDisplay = document.getElementById('discountDisplay');
    if (discountValue > 0) {
        discountDisplay.textContent = '-$' + discountValue.toFixed(2);
        discountRow.style.display = 'flex';
    } else {
        discountRow.style.display = 'none';
    }
    
    const taxRow = document.getElementById('taxRow');
    const taxDisplay = document.getElementById('taxDisplay');
    if (taxAmount > 0) {
        taxDisplay.textContent = '$' + taxAmount.toFixed(2);
        taxRow.style.display = 'flex';
    } else {
        taxRow.style.display = 'none';
    }
    
    document.getElementById('totalDisplay').textContent = '$' + total.toFixed(2);
    
    // Update discount label
    const discountLabel = document.getElementById('discountLabel');
    if (discountType === 'percentage') {
        discountLabel.textContent = 'Percentage';
    } else {
        discountLabel.textContent = 'Amount';
    }
}

// Initialize calculations and UI state
document.addEventListener('DOMContentLoaded', function() {
    toggleAutoRenewOptions();
    toggleVoipConfig();
    calculateTotals();
    
    // Update discount type display
    document.getElementById('discount_type').addEventListener('change', function() {
        const discountLabel = document.getElementById('discountLabel');
        if (this.value === 'percentage') {
            discountLabel.textContent = 'Percentage';
        } else {
            discountLabel.textContent = 'Amount';
        }
        calculateTotals();
    });
});

// Form validation
document.getElementById('quoteForm').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('.quote-item');
    if (items.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the quote.');
        return false;
    }
    
    // Validate VoIP configuration if enabled
    const enableVoip = document.getElementById('enable_voip').checked;
    if (enableVoip) {
        const extensions = parseInt(document.getElementById('voip_extensions').value) || 0;
        const equipmentTotal = Array.from(document.querySelectorAll('input[name^="voip_config[equipment]"]'))
            .reduce((sum, input) => sum + (parseInt(input.value) || 0), 0);
        
        if (equipmentTotal > extensions) {
            e.preventDefault();
            alert('Total equipment cannot exceed the number of extensions.');
            return false;
        }
    }
});
</script>
@endsection