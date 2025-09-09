@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <x-page-header>
        <x-slot name="title">Create Product Bundle</x-slot>
        <x-slot name="description">Create a new product bundle or package deal</x-slot>
        <x-slot name="actions">
            <a href="{{ route('bundles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <i class="fas fa-arrow-left"></i> Back to Bundles
            </a>
        </x-slot>
    </x-page-header>

    <form method="POST" action="{{ route('bundles.store') }}">
        @csrf
        
        <div class="flex flex-wrap -mx-4">
            <!-- Basic Information -->
            <div class="md:w-2/3 px-4">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Bundle Information</h5>
                    
                    <div class="flex flex-wrap -mx-4">
                        <div class="md:w-1/2 px-4">
                            <div class="mb-3">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bundle Name <span class="text-red-600">*</span></label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="md:w-1/2">
                            <div class="mb-3">
                                <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('sku') border-red-500 @enderror" 
                                       id="sku" name="sku" value="{{ old('sku') }}">
                                @error('sku')
                                    <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Product Selection -->
                    <h6 class="mt-4 mb-3">Bundle Products</h6>
                    <div id="bundle-products">
                        <div class="bundle-product-flex flex-wrap mb-2">
                            <div class="flex flex-wrap items-end">
                                <div class="md:w-1/2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product</label>
                                    <select name="products[0][product_id]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                        <option value="">Select a product...</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} - ${{ number_format($product->price, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:w-1/4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                                    <input type="number" name="products[0][quantity]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="1" min="1" required>
                                </div>
                                <div class="md:w-1/4">
                                    <flux:button variant="ghost" color="red" class="px-3 py-1 text-sm remove-product" type="button"   disabled>
                                        <i class="fas fa-trash"></i>
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <flux:button variant="ghost" class="px-3 py-1 text-sm" type="button" id="add-product">
                        <i class="fas fa-plus"></i> Add Product
                    </flux:button>
                </x-content-card>
            </div>

            <!-- Pricing & Settings -->
            <div class="md:w-1/3">
                <x-content-card>
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Pricing & Settings</h5>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pricing Type</label>
                        <div class="flex items-center">
                            <input class="flex items-center-input" type="radio" name="pricing_type" 
                                   id="calculated" value="calculated" checked>
                            <label class="flex items-center-label" for="calculated">
                                Calculate from products
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input class="flex items-center-input" type="radio" name="pricing_type" 
                                   id="fixed" value="fixed">
                            <label class="flex items-center-label" for="fixed">
                                Fixed price
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="fixed-price-group" style="display: none;">
                        <label for="fixed_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fixed Price</label>
                        <div class="flex">
                            <span class="flex-text">$</span>
                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('fixed_price') border-red-500 @enderror" 
                                   id="fixed_price" name="fixed_price" value="{{ old('fixed_price') }}" 
                                   step="0.01" min="0">
                        </div>
                        @error('fixed_price')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="discount_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount</label>
                        <select name="discount_type" id="discount_type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('discount_type') border-red-500 @enderror">
                            <option value="">No discount</option>
                            <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed amount</option>
                        </select>
                        @error('discount_type')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="discount-value-group" style="display: none;">
                        <label for="discount_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount Value</label>
                        <div class="flex">
                            <span class="flex-text" id="discount-symbol">%</span>
                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('discount_value') border-red-500 @enderror" 
                                   id="discount_value" name="discount_value" value="{{ old('discount_value') }}" 
                                   step="0.01" min="0">
                        </div>
                        @error('discount_value')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

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
                            <i class="fas fa-save"></i> Create Bundle
                        </button>
                        <a href="{{ route('bundles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
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
    let productIndex = 1;

    // Add product functionality
    document.getElementById('add-product').addEventListener('click', function() {
        const container = document.getElementById('bundle-products');
        const newRow = document.createElement('div');
        newRow.className = 'bundle-product-flex flex-wrap mb-2';
        newRow.innerHTML = `
            <div class="flex flex-wrap items-end">
                <div class="md:w-1/2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Product</label>
                    <select name="products[${productIndex}][product_id]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                        <option value="">Select a product...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} - ${{ number_format($product->price, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:w-1/4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                    <input type="number" name="products[${productIndex}][quantity]" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="1" min="1" required>
                </div>
                <div class="md:w-1/4">
                    <flux:button variant="ghost" color="red" class="px-3 py-1 text-sm remove-product" type="button">
                        <i class="fas fa-trash"></i>
                    </flux:button>
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
            e.target.closest('.bundle-product-flex flex-wrap').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.bundle-product-flex flex-wrap');
        rows.forEach((flex flex-wrap, index) => {
            const removeBtn = flex flex-wrap.querySelector('.remove-product');
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

    // Initialize discount display if there's old input
    if (document.getElementById('discount_type').value) {
        document.getElementById('discount_type').dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
