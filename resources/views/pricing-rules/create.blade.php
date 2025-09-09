@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <x-page-header>
        <x-slot name="title">Create Pricing Rule</x-slot>
        <x-slot name="description">Create a new client-specific pricing rule</x-slot>
        <x-slot name="actions">
            <a href="{{ route('pricing-rules.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <i class="fas fa-arrow-left"></i> Back to Pricing Rules
            </a>
        </x-slot>
    </x-page-header>

    <form method="POST" action="{{ route('pricing-rules.store') }}">
        @csrf
        
        <div class="flex flex-wrap -mx-4">
            <!-- Basic Information -->
            <div class="md:w-2/3 px-4">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Rule Information</h5>
                    
                    <div class="flex flex-wrap -mx-4">
                        <div class="md:w-2/3 px-4">
                            <div class="mb-3">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rule Name <span class="text-red-600">*</span></label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="md:w-1/3">
                            <div class="mb-3">
                                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('priority') border-red-500 @enderror" 
                                       id="priority" name="priority" value="{{ old('priority', 1) }}" min="1" max="100">
                                <small class="form-text text-gray-600 dark:text-gray-400">Higher numbers = higher priority</small>
                                @error('priority')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror" 
                                  id="description" name="description" rows="2">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Rule Type -->
                    <h6 class="mt-4 mb-3">Rule Type</h6>
                    <div class="flex flex-wrap">
                        <div class="md:w-1/2">
                            <div class="flex items-center mb-2">
                                <input class="flex items-center-input" type="radio" name="rule_type" 
                                       id="discount" value="discount" {{ old('rule_type', 'discount') === 'discount' ? 'checked' : '' }}>
                                <label class="flex items-center-label" for="discount">
                                    <strong>Discount</strong> - Reduce product prices
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input class="flex items-center-input" type="radio" name="rule_type" 
                                       id="markup" value="markup" {{ old('rule_type') === 'markup' ? 'checked' : '' }}>
                                <label class="flex items-center-label" for="markup">
                                    <strong>Markup</strong> - Increase product prices
                                </label>
                            </div>
                        </div>
                        <div class="md:w-1/2">
                            <div class="flex items-center mb-2">
                                <input class="flex items-center-input" type="radio" name="rule_type" 
                                       id="fixed_price" value="fixed_price" {{ old('rule_type') === 'fixed_price' ? 'checked' : '' }}>
                                <label class="flex items-center-label" for="fixed_price">
                                    <strong>Fixed Price</strong> - Set specific price
                                </label>
                            </div>
                            <div class="flex items-center mb-2">
                                <input class="flex items-center-input" type="radio" name="rule_type" 
                                       id="tiered" value="tiered" {{ old('rule_type') === 'tiered' ? 'checked' : '' }}>
                                <label class="flex items-center-label" for="tiered">
                                    <strong>Tiered Pricing</strong> - Volume-based pricing
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Rule Values -->
                    <div id="rule-values" class="mt-4">
                        <!-- Discount Rules -->
                        <div id="discount-fields" class="rule-type-fields">
                            <h6 class="mb-3">Discount Settings</h6>
                            <div class="flex flex-wrap">
                                <div class="md:w-1/2">
                                    <div class="mb-3">
                                        <label for="discount_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount Type</label>
                                        <select name="discount_type" id="discount_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="percentage" {{ old('discount_type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="md:w-1/2">
                                    <div class="mb-3">
                                        <label for="discount_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount Value</label>
                                        <div class="flex">
                                            <span class="flex-text" id="discount-symbol">%</span>
                                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="discount_value" 
                                                   name="discount_value" value="{{ old('discount_value') }}" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Markup Rules -->
                        <div id="markup-fields" class="rule-type-fields" style="display: none;">
                            <h6 class="mb-3">Markup Settings</h6>
                            <div class="flex flex-wrap">
                                <div class="md:w-1/2">
                                    <div class="mb-3">
                                        <label for="markup_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Markup Type</label>
                                        <select name="markup_type" id="markup_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="percentage" {{ old('markup_type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="fixed" {{ old('markup_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="md:w-1/2">
                                    <div class="mb-3">
                                        <label for="markup_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Markup Value</label>
                                        <div class="flex">
                                            <span class="flex-text" id="markup-symbol">%</span>
                                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="markup_value" 
                                                   name="markup_value" value="{{ old('markup_value') }}" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fixed Price Rules -->
                        <div id="fixed-price-fields" class="rule-type-fields" style="display: none;">
                            <h6 class="mb-3">Fixed Price Settings</h6>
                            <div class="flex flex-wrap">
                                <div class="md:w-1/2">
                                    <div class="mb-3">
                                        <label for="fixed_price_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fixed Price</label>
                                        <div class="flex">
                                            <span class="flex-text">$</span>
                                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="fixed_price_value" 
                                                   name="fixed_price" value="{{ old('fixed_price') }}" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tiered Pricing Rules -->
                        <div id="tiered-fields" class="rule-type-fields" style="display: none;">
                            <h6 class="mb-3">Tiered Pricing Settings</h6>
                            <div id="pricing-tiers">
                                <div class="pricing-tier-flex flex-wrap mb-3 p-3 border rounded">
                                    <div class="flex flex-wrap">
                                        <div class="md:w-1/4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Quantity</label>
                                            <input type="number" name="tiers[0][min_quantity]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="1" min="1">
                                        </div>
                                        <div class="md:w-1/4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Quantity</label>
                                            <input type="number" name="tiers[0][max_quantity]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Leave empty for unlimited">
                                        </div>
                                        <div class="md:w-1/4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price per Unit</label>
                                            <div class="flex">
                                                <span class="flex-text">$</span>
                                                <input type="number" name="tiers[0][price]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="md:w-1/4 flex items-end">
                                            <flux:button variant="ghost" color="red" class="px-3 py-1 text-sm remove-tier" type="button"   disabled>
                                                <i class="fas fa-trash"></i>
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <flux:button variant="ghost" class="px-3 py-1 text-sm" type="button" id="add-tier">
                                <i class="fas fa-plus"></i> Add Tier
                            </flux:button>
                        </div>
                    </div>
                </x-content-card>
            </div>

            <!-- Target & Settings -->
            <div class="md:w-1/3">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Target & Settings</h5>

                    <!-- Target Selection -->
                    <div class="mb-3">
                        <label for="target_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Apply To</label>
                        <select name="target_type" id="target_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('target_type') border-red-500 @enderror">
                            <option value="all" {{ old('target_type', 'all') === 'all' ? 'selected' : '' }}>All Products</option>
                            <option value="product" {{ old('target_type') === 'product' ? 'selected' : '' }}>Specific Product</option>
                            <option value="category" {{ old('target_type') === 'category' ? 'selected' : '' }}>Product Category</option>
                        </select>
                        @error('target_type')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="product-select" style="display: none;">
                        <label for="product_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Product</label>
                        <select name="product_id" id="product_id" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Choose a product...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} - ${{ number_format($product->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="category-select" style="display: none;">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Category</label>
                        <select name="category_id" id="category_id" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Choose a category...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Client Selection -->
                    <div class="mb-3">
                        <label for="client_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client (Optional)</label>
                        <select name="client_id" id="client_id" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-gray-600 dark:text-gray-400">Leave empty to apply to all clients</small>
                    </div>

                    <!-- Validity Period -->
                    <h6 class="mt-4 mb-3">Validity Period</h6>
                    <div class="mb-3">
                        <label for="valid_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valid From</label>
                        <input type="date" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valid_from') border-red-500 @enderror" 
                               id="valid_from" name="valid_from" value="{{ old('valid_from') }}">
                        @error('valid_from')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="valid_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valid To</label>
                        <input type="date" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valid_to') border-red-500 @enderror" 
                               id="valid_to" name="valid_to" value="{{ old('valid_to') }}">
                        @error('valid_to')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <div class="flex items-center">
                            <input class="flex items-center-input" type="checkbox" id="is_active" name="is_active" 
                                   value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="flex items-center-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save"></i> Create Pricing Rule
                        </button>
                        <a href="{{ route('pricing-rules.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </a>
                    </div>
                </x-content-card>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let tierIndex = 1;

    // Rule type switching
    document.querySelectorAll('input[name="rule_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.rule-type-fields').forEach(fields => {
                fields.style.display = 'none';
            });
            document.getElementById(this.value + '-fields').style.display = 'block';
        });
    });

    // Target type switching
    document.getElementById('target_type').addEventListener('change', function() {
        document.getElementById('product-select').style.display = this.value === 'product' ? 'block' : 'none';
        document.getElementById('category-select').style.display = this.value === 'category' ? 'block' : 'none';
    });

    // Discount type switching
    document.getElementById('discount_type').addEventListener('change', function() {
        document.getElementById('discount-symbol').textContent = this.value === 'percentage' ? '%' : '$';
    });

    // Markup type switching
    document.getElementById('markup_type').addEventListener('change', function() {
        document.getElementById('markup-symbol').textContent = this.value === 'percentage' ? '%' : '$';
    });

    // Add tier functionality
    document.getElementById('add-tier').addEventListener('click', function() {
        const container = document.getElementById('pricing-tiers');
        const newTier = document.createElement('div');
        newTier.className = 'pricing-tier-flex flex-wrap mb-3 p-3 border rounded';
        newTier.innerHTML = `
            <div class="flex flex-wrap">
                <div class="md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Quantity</label>
                    <input type="number" name="tiers[${tierIndex}][min_quantity]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="1" min="1">
                </div>
                <div class="md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Quantity</label>
                    <input type="number" name="tiers[${tierIndex}][max_quantity]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Leave empty for unlimited">
                </div>
                <div class="md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Price per Unit</label>
                    <div class="flex">
                        <span class="flex-text">$</span>
                        <input type="number" name="tiers[${tierIndex}][price]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" step="0.01" min="0">
                    </div>
                </div>
                <div class="md:w-1/4 flex items-end">
                    <flux:button variant="ghost" color="red" class="px-3 py-1 text-sm remove-tier" type="button">
                        <i class="fas fa-trash"></i>
                    </flux:button>
                </div>
            </div>
        `;
        container.appendChild(newTier);
        tierIndex++;
        updateRemoveTierButtons();
    });

    // Remove tier functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-tier')) {
            e.target.closest('.pricing-tier-flex flex-wrap').remove();
            updateRemoveTierButtons();
        }
    });

    function updateRemoveTierButtons() {
        const rows = document.querySelectorAll('.pricing-tier-flex flex-wrap');
        rows.forEach((flex flex-wrap, index) => {
            const removeBtn = flex flex-wrap.querySelector('.remove-tier');
            removeBtn.disabled = rows.length === 1;
        });
    }

    // Initialize display based on old values
    if (document.querySelector('input[name="rule_type"]:checked')) {
        document.querySelector('input[name="rule_type"]:checked').dispatchEvent(new Event('change'));
    }
    if (document.getElementById('target_type').value !== 'all') {
        document.getElementById('target_type').dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
