@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <x-page-header>
        <x-slot name="title">Edit Pricing Rule: {{ $pricingRule->name }}</x-slot>
        <x-slot name="description">Update pricing rule configuration</x-slot>
        <x-slot name="actions">
            <a href="{{ route('pricing-rules.show', $pricingRule) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <i class="fas fa-arrow-left"></i> Back to Rule
            </a>
        </x-slot>
    </x-page-header>

    <form method="POST" action="{{ route('pricing-rules.update', $pricingRule) }}">
        @csrf
        @method('PUT')
        
        <div class="flex flex-wrap -mx-4">
            <!-- Basic Information -->
            <div class="md:w-2/3 px-4">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Rule Information</h5>
                    
                    <div class="flex flex-wrap -mx-4">
                        <div class="md:w-2/3 px-4">
                            <div class="mb-3">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rule Name <span class="text-red-600">*</span></label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $pricingRule->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('priority') is-invalid @enderror" 
                                       id="priority" name="priority" value="{{ old('priority', $pricingRule->priority) }}" min="1" max="100">
                                <small class="form-text text-gray-600 dark:text-gray-400">Higher numbers = higher priority</small>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="2">{{ old('description', $pricingRule->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Rule Type -->
                    <h6 class="mt-4 mb-3">Rule Type</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="rule_type" 
                                       id="discount" value="discount" {{ old('rule_type', $pricingRule->rule_type) === 'discount' ? 'checked' : '' }}>
                                <label class="form-check-label" for="discount">
                                    <strong>Discount</strong> - Reduce product prices
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="rule_type" 
                                       id="markup" value="markup" {{ old('rule_type', $pricingRule->rule_type) === 'markup' ? 'checked' : '' }}>
                                <label class="form-check-label" for="markup">
                                    <strong>Markup</strong> - Increase product prices
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="rule_type" 
                                       id="fixed_price" value="fixed_price" {{ old('rule_type', $pricingRule->rule_type) === 'fixed_price' ? 'checked' : '' }}>
                                <label class="form-check-label" for="fixed_price">
                                    <strong>Fixed Price</strong> - Set specific price
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="rule_type" 
                                       id="tiered" value="tiered" {{ old('rule_type', $pricingRule->rule_type) === 'tiered' ? 'checked' : '' }}>
                                <label class="form-check-label" for="tiered">
                                    <strong>Tiered Pricing</strong> - Volume-based pricing
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Rule Values -->
                    <div id="rule-values" class="mt-4">
                        <!-- Discount Rules -->
                        <div id="discount-fields" class="rule-type-fields" style="display: {{ $pricingRule->rule_type === 'discount' ? 'block' : 'none' }};">
                            <h6 class="mb-3">Discount Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="discount_type" class="form-label">Discount Type</label>
                                        <select name="discount_type" id="discount_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="percentage" {{ old('discount_type', $pricingRule->discount_type) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="fixed" {{ old('discount_type', $pricingRule->discount_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="discount_value" class="form-label">Discount Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="discount-symbol">{{ $pricingRule->discount_type === 'percentage' ? '%' : '$' }}</span>
                                            <input type="number" class="form-control" id="discount_value" 
                                                   name="discount_value" value="{{ old('discount_value', $pricingRule->discount_value) }}" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Markup Rules -->
                        <div id="markup-fields" class="rule-type-fields" style="display: {{ $pricingRule->rule_type === 'markup' ? 'block' : 'none' }};">
                            <h6 class="mb-3">Markup Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="markup_type" class="form-label">Markup Type</label>
                                        <select name="markup_type" id="markup_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <option value="percentage" {{ old('markup_type', $pricingRule->markup_type) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="fixed" {{ old('markup_type', $pricingRule->markup_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="markup_value" class="form-label">Markup Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text" id="markup-symbol">{{ $pricingRule->markup_type === 'percentage' ? '%' : '$' }}</span>
                                            <input type="number" class="form-control" id="markup_value" 
                                                   name="markup_value" value="{{ old('markup_value', $pricingRule->markup_value) }}" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fixed Price Rules -->
                        <div id="fixed-price-fields" class="rule-type-fields" style="display: {{ $pricingRule->rule_type === 'fixed_price' ? 'block' : 'none' }};">
                            <h6 class="mb-3">Fixed Price Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="fixed_price_value" class="form-label">Fixed Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="fixed_price_value" 
                                                   name="fixed_price" value="{{ old('fixed_price', $pricingRule->fixed_price) }}" 
                                                   step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tiered Pricing Rules -->
                        <div id="tiered-fields" class="rule-type-fields" style="display: {{ $pricingRule->rule_type === 'tiered' ? 'block' : 'none' }};">
                            <h6 class="mb-3">Tiered Pricing Settings</h6>
                            <div id="pricing-tiers">
                                @if($pricingRule->rule_type === 'tiered' && $pricingRule->pricing_tiers)
                                    @foreach($pricingRule->pricing_tiers as $index => $tier)
                                        <div class="pricing-tier-row mb-3 p-3 border rounded">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label class="form-label">Min Quantity</label>
                                                    <input type="number" name="tiers[{{ $index }}][min_quantity]" class="form-control" 
                                                           value="{{ $tier['min_quantity'] }}" min="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Max Quantity</label>
                                                    <input type="number" name="tiers[{{ $index }}][max_quantity]" class="form-control" 
                                                           value="{{ $tier['max_quantity'] ?? '' }}" placeholder="Leave empty for unlimited">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Price per Unit</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">$</span>
                                                        <input type="number" name="tiers[{{ $index }}][price]" class="form-control" 
                                                               value="{{ $tier['price'] }}" step="0.01" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-3 flex align-items-end">
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-tier" 
                                                            {{ count($pricingRule->pricing_tiers) == 1 ? 'disabled' : '' }}>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="pricing-tier-row mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Min Quantity</label>
                                                <input type="number" name="tiers[0][min_quantity]" class="form-control" value="1" min="1">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Max Quantity</label>
                                                <input type="number" name="tiers[0][max_quantity]" class="form-control" placeholder="Leave empty for unlimited">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Price per Unit</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" name="tiers[0][price]" class="form-control" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-3 flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-tier" disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" id="add-tier" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Tier
                            </button>
                        </div>
                    </div>
                </x-content-card>
            </div>

            <!-- Target & Settings -->
            <div class="col-md-4">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Target & Settings</h5>

                    <!-- Target Selection -->
                    <div class="mb-3">
                        <label for="target_type" class="form-label">Apply To</label>
                        <select name="target_type" id="target_type" class="form-select @error('target_type') is-invalid @enderror">
                            <option value="all" {{ old('target_type', $pricingRule->target_type) === 'all' ? 'selected' : '' }}>All Products</option>
                            <option value="product" {{ old('target_type', $pricingRule->target_type) === 'product' ? 'selected' : '' }}>Specific Product</option>
                            <option value="category" {{ old('target_type', $pricingRule->target_type) === 'category' ? 'selected' : '' }}>Product Category</option>
                        </select>
                        @error('target_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="product-select" style="display: {{ $pricingRule->target_type === 'product' ? 'block' : 'none' }};">
                        <label for="product_id" class="form-label">Select Product</label>
                        <select name="product_id" id="product_id" class="form-select">
                            <option value="">Choose a product...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id', $pricingRule->product_id) == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} - ${{ number_format($product->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3" id="category-select" style="display: {{ $pricingRule->target_type === 'category' ? 'block' : 'none' }};">
                        <label for="category_id" class="form-label">Select Category</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Choose a category...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $pricingRule->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Client Selection -->
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Client (Optional)</label>
                        <select name="client_id" id="client_id" class="form-select">
                            <option value="">All Clients</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $pricingRule->client_id) == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-gray-600 dark:text-gray-400">Leave empty to apply to all clients</small>
                    </div>

                    <!-- Validity Period -->
                    <h6 class="mt-4 mb-3">Validity Period</h6>
                    <div class="mb-3">
                        <label for="valid_from" class="form-label">Valid From</label>
                        <input type="date" class="form-control @error('valid_from') is-invalid @enderror" 
                               id="valid_from" name="valid_from" value="{{ old('valid_from', $pricingRule->valid_from?->format('Y-m-d')) }}">
                        @error('valid_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="valid_to" class="form-label">Valid To</label>
                        <input type="date" class="form-control @error('valid_to') is-invalid @enderror" 
                               id="valid_to" name="valid_to" value="{{ old('valid_to', $pricingRule->valid_to?->format('Y-m-d')) }}">
                        @error('valid_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   value="1" {{ old('is_active', $pricingRule->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save"></i> Update Pricing Rule
                        </button>
                        <a href="{{ route('pricing-rules.show', $pricingRule) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
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
    let tierIndex = {{ $pricingRule->rule_type === 'tiered' && $pricingRule->pricing_tiers ? count($pricingRule->pricing_tiers) : 1 }};

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
        newTier.className = 'pricing-tier-row mb-3 p-3 border rounded';
        newTier.innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Min Quantity</label>
                    <input type="number" name="tiers[${tierIndex}][min_quantity]" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max Quantity</label>
                    <input type="number" name="tiers[${tierIndex}][max_quantity]" class="form-control" placeholder="Leave empty for unlimited">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price per Unit</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="tiers[${tierIndex}][price]" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-tier">
                        <i class="fas fa-trash"></i>
                    </button>
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
            e.target.closest('.pricing-tier-row').remove();
            updateRemoveTierButtons();
        }
    });

    function updateRemoveTierButtons() {
        const rows = document.querySelectorAll('.pricing-tier-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-tier');
            removeBtn.disabled = rows.length === 1;
        });
    }
});
</script>
@endsection