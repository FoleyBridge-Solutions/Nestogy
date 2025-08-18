@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <x-page-header>
        <x-slot name="title">Edit Bundle: {{ $bundle->name }}</x-slot>
        <x-slot name="description">Update bundle information and products</x-slot>
        <x-slot name="actions">
            <a href="{{ route('bundles.show', $bundle) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <i class="fas fa-arrow-left"></i> Back to Bundle
            </a>
        </x-slot>
    </x-page-header>

    <form method="POST" action="{{ route('bundles.update', $bundle) }}">
        @csrf
        @method('PUT')
        
        <div class="flex flex-wrap -mx-4">
            <!-- Basic Information -->
            <div class="md:w-2/3 px-4">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Bundle Information</h5>
                    
                    <div class="flex flex-wrap -mx-4">
                        <div class="md:w-1/2 px-4">
                            <div class="mb-3">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bundle Name <span class="text-red-600">*</span></label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $bundle->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('sku') is-invalid @enderror" 
                                       id="sku" name="sku" value="{{ old('sku', $bundle->sku) }}">
                                @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $bundle->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Product Selection -->
                    <h6 class="mt-4 mb-3">Bundle Products</h6>
                    <div id="bundle-products">
                        @forelse($bundle->products as $index => $product)
                            <div class="bundle-product-row mb-2">
                                <div class="row align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label">Product</label>
                                        <select name="products[{{ $index }}][product_id]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                            <option value="">Select a product...</option>
                                            @foreach($products as $availableProduct)
                                                <option value="{{ $availableProduct->id }}" 
                                                        {{ $product->id == $availableProduct->id ? 'selected' : '' }}>
                                                    {{ $availableProduct->name }} - ${{ number_format($availableProduct->price, 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="products[{{ $index }}][quantity]" 
                                               class="form-control" value="{{ $product->pivot->quantity }}" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-product" 
                                                {{ $bundle->products->count() == 1 ? 'disabled' : '' }}>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="bundle-product-row mb-2">
                                <div class="row align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label">Product</label>
                                        <select name="products[0][product_id]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                            <option value="">Select a product...</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} - ${{ number_format($product->price, 2) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="products[0][quantity]" class="form-control" value="1" min="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-product" disabled>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    
                    <button type="button" id="add-product" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </x-content-card>
            </div>

            <!-- Pricing & Settings -->
            <div class="col-md-4">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Pricing & Settings</h5>

                    <div class="mb-3">
                        <label class="form-label">Pricing Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pricing_type" 
                                   id="calculated" value="calculated" 
                                   {{ old('pricing_type', $bundle->fixed_price ? 'fixed' : 'calculated') === 'calculated' ? 'checked' : '' }}>
                            <label class="form-check-label" for="calculated">
                                Calculate from products
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pricing_type" 
                                   id="fixed" value="fixed"
                                   {{ old('pricing_type', $bundle->fixed_price ? 'fixed' : 'calculated') === 'fixed' ? 'checked' : '' }}>
                            <label class="form-check-label" for="fixed">
                                Fixed price
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="fixed-price-group" style="display: {{ $bundle->fixed_price ? 'block' : 'none' }};">
                        <label for="fixed_price" class="form-label">Fixed Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control @error('fixed_price') is-invalid @enderror" 
                                   id="fixed_price" name="fixed_price" value="{{ old('fixed_price', $bundle->fixed_price) }}" 
                                   step="0.01" min="0">
                        </div>
                        @error('fixed_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="discount_type" class="form-label">Discount</label>
                        <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                            <option value="">No discount</option>
                            <option value="percentage" {{ old('discount_type', $bundle->discount_type) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                            <option value="fixed" {{ old('discount_type', $bundle->discount_type) === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                        </select>
                        @error('discount_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="discount-value-group" style="display: {{ $bundle->discount_type ? 'block' : 'none' }};">
                        <label for="discount_value" class="form-label">Discount Value</label>
                        <div class="input-group">
                            <span class="input-group-text" id="discount-symbol">{{ $bundle->discount_type === 'percentage' ? '%' : '$' }}</span>
                            <input type="number" class="form-control @error('discount_value') is-invalid @enderror" 
                                   id="discount_value" name="discount_value" value="{{ old('discount_value', $bundle->discount_value) }}" 
                                   step="0.01" min="0">
                        </div>
                        @error('discount_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   value="1" {{ old('is_active', $bundle->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save"></i> Update Bundle
                        </button>
                        <a href="{{ route('bundles.show', $bundle) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
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
    let productIndex = {{ $bundle->products->count() > 0 ? $bundle->products->count() : 1 }};

    // Add product functionality
    document.getElementById('add-product').addEventListener('click', function() {
        const container = document.getElementById('bundle-products');
        const newRow = document.createElement('div');
        newRow.className = 'bundle-product-row mb-2';
        newRow.innerHTML = `
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Product</label>
                    <select name="products[${productIndex}][product_id]" class="form-select" required>
                        <option value="">Select a product...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} - ${{ number_format($product->price, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="products[${productIndex}][quantity]" class="form-control" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-product">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(newRow);
        productIndex++;
        updateRemoveButtons();
    });

    // Remove product functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-product')) {
            e.target.closest('.bundle-product-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.bundle-product-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-product');
            removeBtn.disabled = rows.length === 1;
        });
    }

    // Pricing type toggle
    document.querySelectorAll('input[name="pricing_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const fixedPriceGroup = document.getElementById('fixed-price-group');
            if (this.value === 'fixed') {
                fixedPriceGroup.style.display = 'block';
            } else {
                fixedPriceGroup.style.display = 'none';
            }
        });
    });

    // Discount type toggle
    document.getElementById('discount_type').addEventListener('change', function() {
        const discountValueGroup = document.getElementById('discount-value-group');
        const discountSymbol = document.getElementById('discount-symbol');
        
        if (this.value) {
            discountValueGroup.style.display = 'block';
            discountSymbol.textContent = this.value === 'percentage' ? '%' : '$';
        } else {
            discountValueGroup.style.display = 'none';
        }
    });
});
</script>
@endsection