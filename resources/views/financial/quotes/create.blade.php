@extends('layouts.app')

@section('title', 'Create Quote')

@push('styles')
<style>
[x-cloak] { display: none !important; }
.step-indicator { transition: all 0.3s ease; }
.price-display { font-family: 'Courier New', monospace; }
.item-row:hover { background-color: rgba(0,123,255,0.1); }

/* Mobile Optimizations */
@media (max-width: 768px) {
    /* Mobile-first layout adjustments */
    .mobile-stack { flex-direction: column !important; }
    .mobile-full-width { width: 100% !important; margin-bottom: 1rem; }
    .mobile-hide { display: none !important; }
    .mobile-show { display: block !important; }
    
    /* Touch-friendly sizing */
    .mobile-touch-target {
        min-height: 48px !important;
        padding: 12px 16px !important;
    }
    
    /* Mobile step indicator */
    .mobile-step-compact {
        gap: 0.5rem !important;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    
    .mobile-step-compact .step-indicator {
        flex-shrink: 0;
        font-size: 0.875rem;
    }
    
    .mobile-step-compact .step-indicator span {
        display: none;
    }
    
    /* Mobile item management */
    .mobile-item-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .mobile-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .mobile-item-fields {
        display: grid;
        grid-template-columns: 1fr 80px 100px;
        gap: 0.5rem;
        align-items: center;
    }
    
    /* Mobile form groups */
    .mobile-form-group {
        margin-bottom: 1.5rem;
    }
    
    .mobile-form-row {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    /* Mobile buttons */
    .mobile-button-full {
        width: 100%;
        justify-content: center;
    }
    
    .mobile-button-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    /* Mobile navigation */
    .mobile-nav-sticky {
        position: sticky;
        bottom: 0;
        background: white;
        border-top: 1px solid #e5e7eb;
        padding: 1rem;
        margin: -1rem -1rem 0 -1rem;
        z-index: 10;
    }
}

/* Tablet optimizations */
@media (min-width: 769px) and (max-width: 1024px) {
    .tablet-grid-2 { 
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .tablet-grid-3 { 
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 1rem;
    }
}
</style>
@endpush

@section('content')
<div class="w-full px-4 px-4 py-4" x-data="quoteBuilder()" x-init="init()" x-cloak @products-selected.window="syncSelectedItems($event.detail)">
    <!-- Page Header -->
    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center mb-4 gap-4">
        <div class="flex-1">
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-1">Create Quote</h1>
            <p class="text-gray-600 text-sm lg:text-base">
                @if($copyData && $copyFromQuote)
                    Copying quote <strong>#{{ $copyFromQuote->getFullNumber() }}</strong> - you can modify any details before saving
                @else
                    Create a new quote for your client
                @endif
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            @if($copyData && $copyFromQuote)
                <a href="{{ route('financial.quotes.show', $copyFromQuote) }}" class="inline-flex items-center justify-center px-4 py-2 border border-blue-300 text-blue-600 bg-white rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mobile-touch-target">
                    <i class="fas fa-eye mr-2"></i>View Original
                </a>
            @endif
            <button type="button" 
                    @click="clearLocalStorage(); $dispatch('notification', { type: 'success', message: 'Draft cleared from local storage' });"
                    class="inline-flex items-center justify-center px-3 py-2 border border-yellow-300 text-yellow-600 bg-white rounded-md hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 mobile-touch-target"
                    title="Clear saved draft">
                <i class="fas fa-trash text-sm"></i>
                <span class="hidden lg:inline ml-1">Clear Draft</span>
            </button>
            
            <a href="{{ route('financial.quotes.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-gray-600 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mobile-touch-target">
                <i class="fas fa-arrow-left mr-2"></i>Back to Quotes
            </a>
        </div>
    </div>

    @if($copyData && $copyFromQuote)
    <!-- Copy Notice -->
    <div class="px-4 py-3 rounded bg-cyan-100 border border-cyan-400 text-cyan-700 flex items-center mb-4">
        <i class="fas fa-copy mr-3"></i>
        <div>
            <strong>Copying Quote #{{ $copyFromQuote->getFullNumber() }}</strong><br>
            <small>All data has been pre-filled from the original quote. You can modify any details before saving the new quote.</small>
        </div>
    </div>
    @endif

    <!-- Progress Steps -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm mb-4">
        <div class="p-6 py-3">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <div class="flex items-center gap-2 lg:gap-4 mobile-step-compact">
                    <div class="flex items-center step-indicator" 
                         :class="currentStep >= 1 ? 'text-blue-600' : 'text-gray-600'">
                        <div class="rounded-full flex items-center justify-center mr-2"
                             :class="currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-100'"
                             style="width: 32px; height: 32px;">
                            <i class="fas fa-info-circle text-sm" x-show="currentStep >= 1"></i>
                            <span x-show="currentStep < 1" class="text-sm">1</span>
                        </div>
                        <span class="font-semibold hidden sm:inline">Details</span>
                    </div>
                    
                    <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    
                    <div class="flex items-center step-indicator"
                         :class="currentStep >= 2 ? 'text-blue-600' : 'text-gray-500'">
                        <div class="rounded-full flex items-center justify-center mr-2"
                             :class="currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-100'"
                             style="width: 32px; height: 32px;">
                            <i class="fas fa-shopping-cart text-sm" x-show="currentStep >= 2"></i>
                            <span x-show="currentStep < 2" class="text-sm">2</span>
                        </div>
                        <span class="font-semibold hidden sm:inline">Items</span>
                    </div>
                    
                    <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    
                    <div class="flex items-center step-indicator"
                         :class="currentStep >= 3 ? 'text-blue-600' : 'text-gray-500'">
                        <div class="rounded-full flex items-center justify-center mr-2"
                             :class="currentStep >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-100'"
                             style="width: 32px; height: 32px;">
                            <i class="fas fa-check text-sm" x-show="currentStep >= 3"></i>
                            <span x-show="currentStep < 3" class="text-sm">3</span>
                        </div>
                        <span class="font-semibold hidden sm:inline">Review</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-center lg:justify-end gap-3">
                    <div x-show="saving" class="text-gray-500 text-sm">
                        <i class="fas fa-spinner fa-spin"></i> <span class="hidden sm:inline">Saving...</span>
                    </div>
                    <div x-show="!saving && lastSaved" class="text-green-600 text-sm">
                        <i class="fas fa-check-circle"></i> <span class="hidden sm:inline">Saved</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm">
        <form @submit.prevent="submitQuote()">
            
            <!-- Step 1: Quote Details -->
            <div x-show="currentStep === 1">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Quote Details</h5>
                </div>
                
                <div class="p-4 lg:p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                        <!-- Client Selection -->
                        <div class="mobile-form-group">
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Client <span class="text-red-600">*</span>
                            </label>
                            <select id="client_id" 
                                    class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                    x-model="quote.client_id"
                                    @change="handleClientChange()"
                                    :class="errors.client_id ? 'border-red-500' : ''">
                                <option value="">Select a client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}"
                                            {{ ($selectedClient && $selectedClient->id == $client->id) ? 'selected' : '' }}>
                                        {{ $client->name }}@if($client->company_name) ({{ $client->company_name }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="text-red-600 text-sm mt-1" x-text="errors.client_id" x-show="errors.client_id"></div>
                        </div>
                        
                        <!-- Category Selection -->
                        <div class="mobile-form-group">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Category <span class="text-red-600">*</span>
                            </label>
                            <select id="category_id" 
                                    class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                    x-model="quote.category_id"
                                    :class="errors.category_id ? 'border-red-500' : ''">
                                <option value="">Select a category...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-red-600 text-sm mt-1" x-text="errors.category_id" x-show="errors.category_id"></div>
                        </div>
                    </div>
                    
                    <!-- Date and Currency Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6 mt-6">
                        <div class="mobile-form-group">
                            <label for="quote_date" class="block text-sm font-medium text-gray-700 mb-2">Quote Date</label>
                            <input type="date" 
                                   id="quote_date" 
                                   class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                   x-model="quote.date">
                        </div>
                        
                        <div class="mobile-form-group">
                            <label for="expire_date" class="block text-sm font-medium text-gray-700 mb-2">Valid Until</label>
                            <input type="date" 
                                   id="expire_date" 
                                   class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                   x-model="quote.expire_date"
                                   :class="errors.expire_date ? 'border-red-500' : ''">
                            <div class="text-red-600 text-sm mt-1" x-text="errors.expire_date" x-show="errors.expire_date"></div>
                        </div>
                        
                        <div class="mobile-form-group">
                            <label for="currency_code" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                            <select id="currency_code" 
                                    class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                    x-model="quote.currency_code">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="CAD">CAD - Canadian Dollar</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mobile-form-group mt-6">
                        <label for="scope" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="scope" 
                                  class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                  rows="4" 
                                  x-model="quote.scope"
                                  placeholder="Brief description of the quote..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Step 2: Items -->
            <div x-show="currentStep === 2">
                <div class="px-4 lg:px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart mr-2"></i>Quote Items</h5>
                </div>
                
                <div class="p-4 lg:p-6">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <!-- Product Selection and Quick Add -->
                        <div class="xl:col-span-2 order-2 xl:order-1">
                            <!-- Mobile-Optimized Quick Add Item -->
                            <div class="bg-gray-50 border border-gray-200 rounded-lg mb-6">
                                <div class="px-4 py-3 border-b border-gray-200 bg-gray-100">
                                    <h6 class="mb-0 font-semibold">Add Item Quickly</h6>
                                </div>
                                <div class="p-4">
                                    <!-- Mobile: Stack vertically -->
                                    <div class="lg:hidden space-y-3">
                                        <input type="text" 
                                               x-model="newItem.name"
                                               class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                               placeholder="Item name"
                                               @keydown.enter="addQuickItem()">
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="number" 
                                                   x-model="newItem.quantity"
                                                   class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                                   placeholder="Quantity"
                                                   min="1" 
                                                   step="1">
                                            <input type="number" 
                                                   x-model="newItem.price"
                                                   class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                                   placeholder="Price"
                                                   min="0" 
                                                   step="0.01">
                                        </div>
                                        <button type="button" 
                                                @click="addQuickItem()"
                                                class="w-full inline-flex justify-center items-center px-4 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mobile-touch-target">
                                            <i class="fas fa-plus mr-2"></i> Add Item
                                        </button>
                                    </div>
                                    
                                    <!-- Desktop: Horizontal layout -->
                                    <div class="hidden lg:flex gap-3">
                                        <div class="flex-1">
                                            <input type="text" 
                                                   x-model="newItem.name"
                                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                                   placeholder="Item name"
                                                   @keydown.enter="addQuickItem()">
                                        </div>
                                        <div class="w-24">
                                            <input type="number" 
                                                   x-model="newItem.quantity"
                                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                                   placeholder="Qty"
                                                   min="1" 
                                                   step="1">
                                        </div>
                                        <div class="w-28">
                                            <input type="number" 
                                                   x-model="newItem.price"
                                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                                   placeholder="Price"
                                                   min="0" 
                                                   step="0.01">
                                        </div>
                                        <button type="button" 
                                                @click="addQuickItem()"
                                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-plus mr-2"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Selector Component -->
                            <x-product-selector />
                            
                            <!-- Selected Items List -->
                            <div class="mt-4">
                                <div class="flex justify-between items-center mb-3">
                                    <h6>Selected Items (<span x-text="items.length"></span>)</h6>
                                    <button type="button" 
                                            @click="clearAllItems()"
                                            class="inline-flex items-center px-3 py-1 border border-red-300 text-red-600 bg-white rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 text-sm mobile-touch-target"
                                            x-show="items.length > 0">
                                        <i class="fas fa-trash"></i> <span class="hidden sm:inline ml-1">Clear All</span>
                                    </button>
                                </div>
                                
                                <!-- Mobile Card Layout -->
                                <div class="lg:hidden space-y-4" x-show="items.length > 0">
                                    <template x-for="(item, index) in items" :key="item.id">
                                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="flex-1 mr-3">
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Item Name</label>
                                                    <input type="text" 
                                                           x-model="item.name"
                                                           @input="updatePricing()"
                                                           class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target"
                                                           placeholder="Item description">
                                                </div>
                                                <button type="button" 
                                                        @click="removeItem(index)"
                                                        class="inline-flex items-center justify-center w-12 h-12 border border-red-300 text-red-600 bg-white rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 mobile-touch-target">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Quantity</label>
                                                    <input type="number" 
                                                           x-model="item.quantity"
                                                           @input="updatePricing()"
                                                           class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base text-center mobile-touch-target"
                                                           min="1" 
                                                           step="1"
                                                           placeholder="1">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-500 mb-1">Unit Price</label>
                                                    <input type="number" 
                                                           x-model="item.price"
                                                           @input="updatePricing()"
                                                           class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base text-right mobile-touch-target"
                                                           min="0" 
                                                           step="0.01"
                                                           placeholder="0.00">
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3 pt-3 border-t border-gray-200">
                                                <div class="flex justify-between items-center">
                                                    <span class="text-sm font-medium text-gray-600">Subtotal:</span>
                                                    <span class="text-lg font-bold text-gray-900" x-text="formatCurrency(item.quantity * item.price)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                
                                <!-- Desktop Table Layout -->
                                <div class="hidden lg:block overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Qty</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Price</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Subtotal</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <template x-for="(item, index) in items" :key="item.id">
                                                <tr class="item-row hover:bg-blue-50">
                                                    <td class="px-4 py-3">
                                                        <input type="text" 
                                                               x-model="item.name"
                                                               @input="updatePricing()"
                                                               class="block w-full border-0 p-0 text-sm focus:ring-0 focus:border-0 bg-transparent">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="number" 
                                                               x-model="item.quantity"
                                                               @input="updatePricing()"
                                                               class="block w-full px-2 py-1 border border-gray-300 rounded text-center text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                               min="1" 
                                                               step="1">
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <input type="number" 
                                                               x-model="item.price"
                                                               @input="updatePricing()"
                                                               class="block w-full px-2 py-1 border border-gray-300 rounded text-right text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                                               min="0" 
                                                               step="0.01">
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <span class="font-semibold" x-text="formatCurrency(item.quantity * item.price)"></span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <button type="button" 
                                                                @click="removeItem(index)"
                                                                class="inline-flex items-center p-1 border border-red-300 text-red-600 bg-white rounded hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                            <i class="fas fa-times text-xs"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Empty State (Mobile & Desktop) -->
                                <div x-show="items.length === 0" class="border border-gray-200 rounded-lg bg-gray-50">
                                    <div class="px-4 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox fa-2x mb-2 block"></i>
                                        <div class="font-medium">No items added yet</div>
                                        <div class="text-sm">Add your first item to get started</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pricing Summary -->
                        <div class="order-1 xl:order-2">
                            <div class="bg-white border border-gray-200 rounded-lg sticky top-4">
                                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                    <h6 class="mb-0 font-semibold">Pricing Summary</h6>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm">Subtotal:</span>
                                        <span class="text-sm font-medium" x-text="formatCurrency(pricing.subtotal)"></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>
                                        <div class="flex">
                                            <input type="number" 
                                                   x-model="quote.discount_amount"
                                                   @input="updatePricing()"
                                                   class="block w-full px-3 py-3 lg:py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base lg:text-sm mobile-touch-target" 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            <select x-model="quote.discount_type" 
                                                    @change="updatePricing()"
                                                    class="border border-l-0 border-gray-300 rounded-r-md px-3 py-3 lg:py-2 bg-white text-base lg:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 mobile-touch-target">
                                                <option value="fixed">$</option>
                                                <option value="percentage">%</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div x-show="pricing.discount > 0" class="flex justify-between mb-2 text-green-600">
                                        <span class="text-sm">Discount:</span>
                                        <span class="text-sm font-medium" x-text="'-' + formatCurrency(pricing.discount)"></span>
                                    </div>
                                    
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm">Tax:</span>
                                        <span class="text-sm font-medium" x-text="formatCurrency(pricing.tax)"></span>
                                    </div>
                                    
                                    <hr class="border-gray-200 my-3">
                                    
                                    <div class="flex justify-between">
                                        <strong class="text-lg">Total:</strong>
                                        <strong class="text-lg" x-text="formatCurrency(pricing.total)"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Review -->
            <div x-show="currentStep === 3">
                <div class="px-4 lg:px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h5 class="mb-0"><i class="fas fa-check mr-2"></i>Review & Submit</h5>
                </div>
                
                <div class="p-4 lg:p-6">
                    <!-- Mobile Layout -->
                    <div class="lg:hidden space-y-6">
                        <!-- Mobile Final Pricing (Priority) -->
                        <div class="bg-white border border-gray-200 rounded-lg">
                            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                <h6 class="mb-0 font-semibold">Final Pricing</h6>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between mb-2">
                                    <span class="text-sm">Subtotal:</span>
                                    <span class="text-sm font-medium" x-text="formatCurrency(pricing.subtotal)"></span>
                                </div>
                                
                                <div x-show="pricing.discount > 0" class="flex justify-between mb-2 text-green-600">
                                    <span class="text-sm">Discount:</span>
                                    <span class="text-sm font-medium" x-text="'-' + formatCurrency(pricing.discount)"></span>
                                </div>
                                
                                <div class="flex justify-between mb-2">
                                    <span class="text-sm">Tax:</span>
                                    <span class="text-sm font-medium" x-text="formatCurrency(pricing.tax)"></span>
                                </div>
                                
                                <hr class="border-gray-200 my-3">
                                
                                <div class="flex justify-between mb-4">
                                    <strong class="text-lg">Total:</strong>
                                    <strong class="text-lg price-display" x-text="formatCurrency(pricing.total)"></strong>
                                </div>
                                
                                <div class="space-y-3">
                                    <button type="button" 
                                            @click="saveAsDraft()"
                                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-gray-300 text-gray-600 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mobile-touch-target"
                                            :disabled="saving">
                                        <i class="fas fa-save mr-2"></i> Save as Draft
                                    </button>
                                    
                                    <button type="submit" 
                                            class="w-full inline-flex justify-center items-center px-4 py-3 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mobile-touch-target"
                                            :disabled="saving || !isValid()">
                                        <i class="fas fa-check mr-2"></i>
                                        <span x-show="!saving">Create Quote</span>
                                        <span x-show="saving">Creating...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile Quote Summary -->
                        <div class="bg-white border border-gray-200 rounded-lg">
                            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                <h6 class="mb-0 font-semibold">Quote Summary</h6>
                            </div>
                            <div class="p-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">Client:</span>
                                        <span class="text-right" x-text="getSelectedClientName()"></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">Items:</span>
                                        <span x-text="`${items.length} items`"></span>
                                    </div>
                                    
                                    <div class="flex justify-between">
                                        <span class="font-medium text-gray-600">Valid Until:</span>
                                        <span x-text="quote.expire_date || 'No expiration'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mobile Additional Notes -->
                        <div class="space-y-4">
                            <div>
                                <label for="note_mobile" class="block text-sm font-medium text-gray-700 mb-2">Internal Notes</label>
                                <textarea id="note_mobile" 
                                          class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                          rows="3" 
                                          x-model="quote.note"
                                          placeholder="Internal notes (optional)..."></textarea>
                            </div>
                            
                            <div>
                                <label for="terms_conditions_mobile" class="block text-sm font-medium text-gray-700 mb-2">Terms & Conditions</label>
                                <textarea id="terms_conditions_mobile" 
                                          class="block w-full px-3 py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-base mobile-touch-target" 
                                          rows="3" 
                                          x-model="quote.terms_conditions"
                                          placeholder="Terms and conditions (optional)..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desktop Layout -->
                    <div class="hidden lg:flex flex-wrap -mx-4">
                        <div class="lg:w-2/3 px-4">
                            <!-- Desktop Quote Summary -->
                            <div class="bg-white border border-gray-200 rounded-lg mb-3">
                                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                    <h6 class="mb-0">Quote Summary</h6>
                                </div>
                                <div class="p-4">
                                    <dl class="grid grid-cols-3 gap-2">
                                        <dt class="font-medium text-gray-600">Client:</dt>
                                        <dd class="col-span-2" x-text="getSelectedClientName()"></dd>
                                        
                                        <dt class="font-medium text-gray-600">Items:</dt>
                                        <dd class="col-span-2" x-text="`${items.length} items`"></dd>
                                        
                                        <dt class="font-medium text-gray-600">Total:</dt>
                                        <dd class="col-span-2" x-text="formatCurrency(pricing.total)"></dd>
                                        
                                        <dt class="font-medium text-gray-600">Valid Until:</dt>
                                        <dd class="col-span-2" x-text="quote.expire_date || 'No expiration'"></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <!-- Desktop Additional Notes -->
                            <div class="flex flex-wrap -mx-2">
                                <div class="w-1/2 px-2 mb-3">
                                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
                                    <textarea id="note" 
                                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                              rows="3" 
                                              x-model="quote.note"
                                              placeholder="Internal notes (optional)..."></textarea>
                                </div>
                                
                                <div class="w-1/2 px-2 mb-3">
                                    <label for="terms_conditions" class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
                                    <textarea id="terms_conditions" 
                                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                              rows="3" 
                                              x-model="quote.terms_conditions"
                                              placeholder="Terms and conditions (optional)..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="lg:w-1/3 px-4">
                            <!-- Desktop Final Pricing -->
                            <div class="bg-white border border-gray-200 rounded-lg sticky top-4">
                                <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                                    <h6 class="mb-0">Final Pricing</h6>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between mb-2">
                                        <span>Subtotal:</span>
                                        <span x-text="formatCurrency(pricing.subtotal)"></span>
                                    </div>
                                    
                                    <div x-show="pricing.discount > 0" class="flex justify-between mb-2 text-green-600">
                                        <span>Discount:</span>
                                        <span x-text="'-' + formatCurrency(pricing.discount)"></span>
                                    </div>
                                    
                                    <div class="flex justify-between mb-2">
                                        <span>Tax:</span>
                                        <span x-text="formatCurrency(pricing.tax)"></span>
                                    </div>
                                    
                                    <hr class="border-gray-200 my-3">
                                    
                                    <div class="flex justify-between mb-3">
                                        <strong>Total:</strong>
                                        <strong class="price-display" x-text="formatCurrency(pricing.total)"></strong>
                                    </div>
                                    
                                    <div class="space-y-2">
                                        <button type="button" 
                                                @click="saveAsDraft()"
                                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-gray-600 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                                                :disabled="saving">
                                            <i class="fas fa-save mr-2"></i> Save as Draft
                                        </button>
                                        
                                        <button type="submit" 
                                                class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                :disabled="saving || !isValid()">
                                            <i class="fas fa-check mr-2"></i>
                                            <span x-show="!saving">Create Quote</span>
                                            <span x-show="saving">Creating...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Footer -->
            <div class="px-4 lg:px-6 py-4 border-t border-gray-200 bg-gray-50">
                <!-- Mobile Navigation Layout -->
                <div class="lg:hidden">
                    <div class="flex flex-col space-y-3">
                        <!-- Save Draft Button (Always Visible on Mobile) -->
                        <button type="button" 
                                class="w-full inline-flex justify-center items-center px-4 py-3 border border-blue-300 text-blue-600 bg-white rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mobile-touch-target" 
                                @click="saveAsDraft()"
                                :disabled="saving">
                            <i class="fas fa-save mr-2"></i> 
                            <span x-show="!saving">Save Draft</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                        
                        <!-- Navigation Buttons -->
                        <div class="flex gap-3">
                            <button type="button" 
                                    class="flex-1 inline-flex justify-center items-center px-4 py-3 border border-gray-300 text-gray-600 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mobile-touch-target" 
                                    @click="prevStep()" 
                                    :disabled="currentStep === 1">
                                <i class="fas fa-arrow-left mr-2"></i> Previous
                            </button>
                            
                            <button type="button" 
                                    class="flex-1 inline-flex justify-center items-center px-4 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mobile-touch-target" 
                                    @click="nextStep()" 
                                    x-show="currentStep < 3"
                                    :disabled="!canProceed()">
                                Next <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            
                            <button type="submit" 
                                    class="flex-1 inline-flex justify-center items-center px-4 py-3 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 mobile-touch-target" 
                                    x-show="currentStep === 3"
                                    :disabled="saving || !isValid()">
                                <i class="fas fa-check mr-2"></i>
                                <span x-show="!saving">Create</span>
                                <span x-show="saving">Creating...</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Desktop Navigation Layout -->
                <div class="hidden lg:flex justify-between">
                    <button type="button" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-600 bg-white rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" 
                            @click="prevStep()" 
                            :disabled="currentStep === 1">
                        <i class="fas fa-arrow-left mr-2"></i> Previous
                    </button>
                    
                    <div class="flex gap-2">
                        <button type="button" 
                                class="inline-flex items-center px-4 py-2 border border-blue-300 text-blue-600 bg-white rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                                @click="saveAsDraft()"
                                :disabled="saving">
                            <i class="fas fa-save mr-2"></i> Save Draft
                        </button>
                        
                        <button type="button" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                                @click="nextStep()" 
                                x-show="currentStep < 3"
                                :disabled="!canProceed()">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" 
                                x-show="currentStep === 3"
                                :disabled="saving || !isValid()">
                            <i class="fas fa-check mr-2"></i>
                            <span x-show="!saving">Create Quote</span>
                            <span x-show="saving">Creating...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function quoteBuilder() {
    return {
        // Core state
        currentStep: 1,
        saving: false,
        lastSaved: null,
        errors: {},
        
        // Quote data
        quote: {
            client_id: '',
            category_id: '',
            date: new Date().toISOString().split('T')[0],
            expire_date: '',
            currency_code: 'USD',
            scope: '',
            discount_amount: 0,
            discount_type: 'fixed',
            note: '',
            terms_conditions: ''
        },
        
        // Items
        items: [],
        newItem: {
            name: '',
            quantity: 1,
            price: 0
        },
        
        // Pricing
        pricing: {
            subtotal: 0,
            discount: 0,
            tax: 0,
            total: 0
        },
        
        // Tax breakdown from advanced calculation
        taxBreakdown: [],
        
        // Client data
        clients: {!! json_encode($clients->map(function($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'display_name' => $client->name . ($client->company_name ? ' (' . $client->company_name . ')' : '')
            ];
        })) !!},
        
        init() {
            // Load from local storage first
            this.loadFromLocalStorage();
            
            // Handle copy data if present (overwrites local storage)
            @if($copyData)
                this.loadCopyData({!! json_encode($copyData) !!});
            @else
                // Set default expire date (30 days from now) only if not loaded from storage
                if (!this.quote.expire_date) {
                    const expireDate = new Date();
                    expireDate.setDate(expireDate.getDate() + 30);
                    this.quote.expire_date = expireDate.toISOString().split('T')[0];
                }
                
                // Pre-select client if provided (from query parameter or session)
                @if(request('client_id') || $selectedClient)
                    if (!this.quote.client_id) {
                        this.quote.client_id = '{{ request('client_id') ?: ($selectedClient ? $selectedClient->id : '') }}';
                        this.handleClientChange();
                    }
                @endif
            @endif
            
            // Initial pricing calculation
            this.updatePricing();
            
            // Set up periodic auto-save to local storage (every 10 seconds)
            setInterval(() => {
                this.saveToLocalStorage();
            }, 10000);
            
            // Save to local storage on any change
            this.$watch('quote', () => this.saveToLocalStorage());
            this.$watch('items', () => this.saveToLocalStorage());
            this.$watch('currentStep', () => this.saveToLocalStorage());
        },
        
        // Local Storage Management
        getStorageKey() {
            return 'nestogy_quote_draft_' + (window.location.pathname || 'new');
        },
        
        saveToLocalStorage() {
            try {
                const data = {
                    quote: this.quote,
                    items: this.items,
                    currentStep: this.currentStep,
                    pricing: this.pricing,
                    timestamp: new Date().toISOString(),
                    version: '1.0'
                };
                
                localStorage.setItem(this.getStorageKey(), JSON.stringify(data));
                console.log('Quote progress saved to local storage');
            } catch (error) {
                console.error('Failed to save to local storage:', error);
            }
        },
        
        loadFromLocalStorage() {
            try {
                const storageKey = this.getStorageKey();
                const savedData = localStorage.getItem(storageKey);
                
                if (savedData) {
                    const data = JSON.parse(savedData);
                    
                    // Check if data is recent (within 24 hours)
                    const savedTime = new Date(data.timestamp);
                    const now = new Date();
                    const hoursDiff = (now - savedTime) / (1000 * 60 * 60);
                    
                    if (hoursDiff > 24) {
                        console.log('Local storage data is too old, clearing it');
                        localStorage.removeItem(storageKey);
                        return;
                    }
                    
                    // Load the data
                    if (data.quote) {
                        Object.assign(this.quote, data.quote);
                    }
                    
                    if (data.items && Array.isArray(data.items)) {
                        this.items = data.items;
                    }
                    
                    if (data.currentStep) {
                        this.currentStep = data.currentStep;
                    }
                    
                    if (data.pricing) {
                        Object.assign(this.pricing, data.pricing);
                    }
                    
                    console.log('Quote progress loaded from local storage:', data);
                    
                    // Show notification that progress was restored
                    this.$dispatch('notification', {
                        type: 'info',
                        message: 'Previous quote progress restored from local storage'
                    });
                }
            } catch (error) {
                console.error('Failed to load from local storage:', error);
                // Clear corrupted data
                try {
                    localStorage.removeItem(this.getStorageKey());
                } catch (e) {
                    console.error('Failed to clear corrupted local storage data:', e);
                }
            }
        },
        
        clearLocalStorage() {
            try {
                const storageKey = this.getStorageKey();
                localStorage.removeItem(storageKey);
                console.log('Local storage cleared');
            } catch (error) {
                console.error('Failed to clear local storage:', error);
            }
        },
        
        // Copy data loading
        loadCopyData(copyData) {
            console.log('Loading copy data:', copyData);
            
            // Load quote data
            Object.keys(this.quote).forEach(key => {
                if (copyData[key] !== undefined) {
                    this.quote[key] = copyData[key];
                }
            });
            
            // Load items data
            if (copyData.items && Array.isArray(copyData.items)) {
                this.items = copyData.items.map((item, index) => ({
                    id: Date.now() + index, // Generate temporary ID for the form
                    product_id: item.product_id || null,
                    service_id: item.service_id || null,
                    bundle_id: item.bundle_id || null,
                    name: item.name || '',
                    description: item.description || '',
                    quantity: parseFloat(item.quantity) || 1,
                    price: parseFloat(item.price) || 0,
                    discount: parseFloat(item.discount) || 0,
                    category_id: item.category_id || null,
                    service_type: item.service_type || 'general',
                    service_data: item.service_data || null,
                    
                    // Preserve original item type for proper categorization
                    type: this.determineItemType(item),
                    
                    // Calculate totals
                    subtotal: parseFloat(item.original_subtotal) || 0,
                    tax: parseFloat(item.original_tax) || 0,
                    total: parseFloat(item.original_total) || 0
                }));
                
                // Recalculate pricing for all copied items
                this.items.forEach(item => this.calculateItemTotals(item));
            }
            
            // Handle client selection if it was copied
            if (this.quote.client_id) {
                this.handleClientChange();
            }
            
            // Show copy notification
            if (copyData.copy_from_quote_number) {
                this.showNotification('Quote copied from ' + copyData.copy_from_quote_number + '. You can modify any details before saving.', 'info');
            }
            
            // After loading the items, we need to inform the product selector component
            // about the copied items so they appear in the correct tabs
            this.$nextTick(() => {
                this.syncCopiedItemsWithProductSelector();
            });
        },
        
        // Sync copied items with the product selector component
        syncCopiedItemsWithProductSelector() {
            // Prepare items in the format expected by the product selector
            const selectedItems = this.items.map(item => ({
                id: item.product_id || item.service_id || item.bundle_id || item.id,
                type: item.type || 'product',
                name: item.name,
                sku: item.sku || '',
                quantity: item.quantity,
                base_price: item.price,
                unit_price: item.price,
                subtotal: item.subtotal || (item.quantity * item.price),
                billing_model: item.billing_model || 'one_time',
                billing_cycle: item.billing_cycle || 'monthly',
                service_type: item.service_type || 'general',
                service_data: item.service_data,
                category_id: item.category_id,
                product_id: item.product_id,
                service_id: item.service_id,
                bundle_id: item.bundle_id,
                description: item.description || ''
            }));
            
            // Dispatch event to notify the product selector component
            // This will make the items appear as "selected" in the appropriate tabs
            window.dispatchEvent(new CustomEvent('sync-copied-items', {
                detail: {
                    items: selectedItems,
                    total: selectedItems.reduce((sum, item) => sum + (item.subtotal || 0), 0)
                }
            }));
        },
        
        // Determine item type based on the copied item data
        determineItemType(item) {
            if (item.service_id) {
                return 'service';
            } else if (item.bundle_id) {
                return 'bundle';
            } else if (item.product_id) {
                return 'product';
            } else {
                // For manually added items, determine by service_type or category
                if (item.service_type && item.service_type !== 'general') {
                    return 'service';
                }
                return 'product'; // Default to product for generic items
            }
        },
        
        // Navigation
        nextStep() {
            if (this.canProceed()) {
                this.currentStep++;
            }
        },
        
        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },
        
        canProceed() {
            switch (this.currentStep) {
                case 1:
                    return this.quote.client_id && this.quote.category_id;
                case 2:
                    return this.items.length > 0;
                default:
                    return true;
            }
        },
        
        isValid() {
            return this.quote.client_id && 
                   this.quote.category_id && 
                   this.items.length > 0 &&
                   this.items.every(item => item.name && item.quantity > 0 && item.price >= 0);
        },
        
        // Client handling
        handleClientChange() {
            this.errors.client_id = '';
            
            // Notify product selector component about client selection
            if (this.quote.client_id) {
                this.$dispatch('client-selected', { 
                    clientId: this.quote.client_id 
                });
            }
        },
        
        getSelectedClientName() {
            const client = this.clients.find(c => c.id == this.quote.client_id);
            return client ? client.display_name : 'No client selected';
        },
        
        // Sync items from product selector
        syncSelectedItems(eventData) {
            if (eventData && eventData.items) {
                // Clear existing items and add new ones
                this.items = eventData.items.map(item => ({
                    id: item.id || Date.now() + Math.random(),
                    name: item.name || '',
                    quantity: item.quantity || 1,
                    price: item.unit_price || item.price || 0,
                    type: item.type || 'product',
                    sku: item.sku || '',
                    description: item.description || ''
                }));
                
                // Update pricing with new items
                this.updatePricing();
            }
        },
        
        // Item management
        addQuickItem() {
            if (!this.newItem.name.trim()) return;
            
            const item = {
                id: Date.now(),
                name: this.newItem.name,
                quantity: this.newItem.quantity || 1,
                price: this.newItem.price || 0
            };
            
            this.items.push(item);
            this.updatePricing();
            
            // Reset form
            this.newItem = { name: '', quantity: 1, price: 0 };
        },
        
        removeItem(index) {
            this.items.splice(index, 1);
            this.updatePricing();
        },
        
        clearAllItems() {
            if (confirm('Are you sure you want to remove all items?')) {
                this.items = [];
                this.updatePricing();
            }
        },
        
        // Pricing calculations
        updatePricing() {
            // Calculate subtotal
            this.pricing.subtotal = this.items.reduce((sum, item) => {
                return sum + (parseFloat(item.quantity || 0) * parseFloat(item.price || 0));
            }, 0);
            
            // Calculate discount
            if (this.quote.discount_type === 'percentage') {
                this.pricing.discount = this.pricing.subtotal * (parseFloat(this.quote.discount_amount || 0) / 100);
            } else {
                this.pricing.discount = parseFloat(this.quote.discount_amount || 0);
            }
            
            // Calculate tax using advanced tax engine
            const afterDiscount = this.pricing.subtotal - this.pricing.discount;
            
            // Use advanced tax calculation if client and items are available
            if (this.quote.client_id && this.items && this.items.length > 0) {
                // Temporarily disabled due to missing tax_categories table
                // TODO: Re-enable after running tax table migrations
                // this.calculateAdvancedTax(afterDiscount);
                this.pricing.tax = 0;
            } else {
                // Fallback to zero tax if no client/items
                this.pricing.tax = 0;
            }
            
            // Calculate total (will be updated again when tax calculation completes)
            this.pricing.total = this.pricing.subtotal - this.pricing.discount + this.pricing.tax;
        },
        
        // Calculate advanced tax using tax engine API
        async calculateAdvancedTax(taxableAmount) {
            try {
                // Prepare items for bulk tax calculation
                const taxItems = this.items.map(item => ({
                    base_price: item.quantity * (item.price || item.unit_price || 0),
                    quantity: 1, // Already calculated
                    name: item.name,
                    category_id: item.category_id,
                    product_id: item.product_id,
                    tax_data: item.service_data || {}
                }));

                const response = await fetch('/api/tax-engine/calculate-bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        items: taxItems,
                        customer_id: this.quote.client_id,
                        calculation_type: 'preview'
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    this.pricing.tax = result.data.summary.total_tax;
                    this.taxBreakdown = result.data.items; // Store detailed breakdown
                    
                    // Recalculate total after tax calculation
                    this.pricing.total = this.pricing.subtotal - this.pricing.discount + this.pricing.tax;
                    
                    console.log('Advanced tax calculation successful:', {
                        taxable_amount: taxableAmount,
                        calculated_tax: this.pricing.tax,
                        effective_rate: result.data.summary.effective_tax_rate
                    });
                } else {
                    console.error('Tax calculation failed:', result.error);
                    this.pricing.tax = 0; // Fallback to zero tax
                    // Recalculate total with zero tax
                    this.pricing.total = this.pricing.subtotal - this.pricing.discount + this.pricing.tax;
                }
            } catch (error) {
                console.error('Error calculating advanced tax:', error);
                this.pricing.tax = 0; // Fallback to zero tax
                // Recalculate total with zero tax
                this.pricing.total = this.pricing.subtotal - this.pricing.discount + this.pricing.tax;
            }
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: this.quote.currency_code || 'USD'
            }).format(amount || 0);
        },
        
        // Save functions
        async saveAsDraft() {
            await this.saveQuote('Draft');
        },
        
        async submitQuote() {
            if (!this.isValid()) {
                let errorMessages = [];
                
                // Clear previous errors
                this.errors = {};
                
                // Set field-specific errors and collect messages
                if (!this.quote.client_id) {
                    this.errors.client_id = 'Please select a client';
                    errorMessages.push('• Please select a client');
                }
                if (!this.quote.category_id) {
                    this.errors.category_id = 'Please select a category';
                    errorMessages.push('• Please select a category');
                }
                if (this.items.length === 0) {
                    errorMessages.push('• Please add at least one item');
                }
                if (this.items.some(item => !item.name)) {
                    errorMessages.push('• All items must have a name');
                }
                if (this.items.some(item => item.quantity <= 0)) {
                    errorMessages.push('• All items must have a quantity greater than 0');
                }
                if (this.items.some(item => item.price < 0)) {
                    errorMessages.push('• All items must have a price of 0 or greater');
                }
                
                alert('Please fix the following issues before submitting:\n\n' + errorMessages.join('\n'));
                return;
            }
            
            await this.saveQuote('Sent');
        },
        
        async saveQuote(status = 'Draft') {
            this.saving = true;
            this.errors = {};
            
            try {
                // Use XMLHttpRequest - more reliable than fetch()
                this.submitViaXHR(status);
            } catch (error) {
                console.error('Save error:', error);
                alert('An error occurred while saving the quote. Please try again.');
                this.saving = false;
            }
        },
        
        // XMLHttpRequest method - very reliable
        submitViaXHR(status = 'Draft') {
            // Start performance timer
            const startTime = Date.now();
            // Clean up items to ensure proper field names
            const cleanItems = this.items.map(item => ({
                id: item.id,
                name: item.name,
                description: item.description || '',
                quantity: item.quantity || 1,
                price: item.price || item.unit_price || 0,
                type: item.type || 'product',
                sku: item.sku || ''
            }));

            // Remove any items from quote object to avoid conflicts
            const { items: quoteItems, ...quoteWithoutItems } = this.quote;

            // Convert empty strings to null for validation
            const sanitizedQuote = {};
            for (const [key, value] of Object.entries(quoteWithoutItems)) {
                if (value === '') {
                    sanitizedQuote[key] = null;
                } else {
                    sanitizedQuote[key] = value;
                }
            }

            const data = {
                ...sanitizedQuote,
                items: cleanItems,
                pricing: this.pricing,
                status: status,
                skip_complex_calculations: status === 'Draft' // Only skip for drafts, full calculation for final quotes
            };
            
            console.log('Submitting via XHR:', data);
            
            const xhr = new XMLHttpRequest();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            
            xhr.open('POST', '{{ route("financial.quotes.store") }}', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = () => {
                if (xhr.readyState === 4) {
                    this.saving = false;
                    const endTime = Date.now();
                    const duration = endTime - startTime;
                    console.log(`Request completed in ${duration}ms`);
                    
                    if (xhr.status === 200 || xhr.status === 201) {
                        try {
                            const result = JSON.parse(xhr.responseText);
                            console.log('Success response:', result);
                            
                            // Clear local storage on successful save
                            this.clearLocalStorage();
                            
                            if (status === 'Sent') {
                                // Redirect to quote view
                                const quoteId = result.data?.id || result.id;
                                if (quoteId) {
                                    window.location.href = `/financial/quotes/${quoteId}`;
                                } else {
                                    window.location.href = '/financial/quotes';
                                }
                            } else {
                                // Show success message for draft
                                alert('Quote saved as draft successfully!');
                                this.$dispatch('notification', {
                                    type: 'success',
                                    message: 'Quote saved as draft successfully'
                                });
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            // Still clear local storage on apparent success
                            this.clearLocalStorage();
                            alert('Quote saved successfully!');
                        }
                    } else if (xhr.status === 422) {
                        // Validation errors
                        try {
                            const result = JSON.parse(xhr.responseText);
                            console.error('Validation errors:', result);
                            this.errors = result.errors || {};
                            
                            let errorMsg = 'The quote could not be saved due to the following issues:\n\n';
                            if (result.errors) {
                                Object.keys(result.errors).forEach(field => {
                                    const fieldName = field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    errorMsg += `• ${fieldName}: ${result.errors[field][0]}\n`;
                                });
                            } else {
                                errorMsg += 'Please check all required fields and try again.';
                            }
                            
                            // Show detailed error info if available
                            if (result.debug_info) {
                                console.log('Debug info:', result.debug_info);
                            }
                            
                            alert(errorMsg);
                        } catch (e) {
                            console.error('Error parsing validation response:', e);
                            alert('Validation failed. Please ensure all required fields are filled and try again.');
                        }
                    } else {
                        console.error('Server error:', xhr.status, xhr.statusText, xhr.responseText);
                        alert(`Server error (${xhr.status}): ${xhr.statusText}. Please try again or contact support.`);
                    }
                }
            };
            
            xhr.onerror = () => {
                this.saving = false;
                console.error('Network error occurred');
                alert('Network error: Could not connect to server. Please check your connection and try again.');
            };
            
            xhr.ontimeout = () => {
                this.saving = false;
                console.error('Request timed out');
                alert('Request timed out. Please try again.');
            };
            
            xhr.timeout = 15000; // 15 second timeout for faster feedback
            
            try {
                xhr.send(JSON.stringify(data));
            } catch (sendError) {
                this.saving = false;
                console.error('Send error:', sendError);
                alert('Failed to send request. Please try again.');
            }
        },
    };
}
</script>
@endpush
@endsection