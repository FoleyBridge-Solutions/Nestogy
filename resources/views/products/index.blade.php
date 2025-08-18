@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        title="Products"
        subtitle="Manage your products and services"
    >
        <x-slot name="actions">
            <div class="flex gap-2">
                <a href="{{ route('products.import') }}" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white dark:bg-gray-800 hover:bg-blue-50">
                    <i class="fas fa-upload mr-2"></i> Import
                </a>
                <a href="{{ route('products.export') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                    <i class="fas fa-download mr-2"></i> Export
                </a>
                <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i> Add Product
                </a>
            </div>
        </x-slot>
    </x-page-header>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                            <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="search" name="search" 
                                value="{{ request('search') }}" placeholder="Name, SKU, or description">
                        </div>
                        <div class="md:col-span-1">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="category_id" name="category_id">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="type" name="type">
                                <option value="">All Products</option>
                                <option value="product" {{ request('type') === 'product' ? 'selected' : '' }}>Product</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label for="billing_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Model</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="billing_model" name="billing_model">
                                <option value="">All Models</option>
                                <option value="one_time" {{ request('billing_model') === 'one_time' ? 'selected' : '' }}>One Time</option>
                                <option value="subscription" {{ request('billing_model') === 'subscription' ? 'selected' : '' }}>Subscription</option>
                                <option value="usage_based" {{ request('billing_model') === 'usage_based' ? 'selected' : '' }}>Usage Based</option>
                                <option value="hybrid" {{ request('billing_model') === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="is_active" name="is_active">
                                <option value="">All Status</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-4" id="bulk-actions" style="display: none;">
                <div class="p-6">
                    <form method="POST" action="{{ route('products.bulk-update') }}" id="bulk-form">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                            <div class="md:col-span-1">
                                <label for="bulk-action" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bulk Action</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="bulk-action" name="action" required>
                                    <option value="">Select Action</option>
                                    <option value="activate">Activate</option>
                                    <option value="deactivate">Deactivate</option>
                                    <option value="update_category">Update Category</option>
                                    <option value="delete">Delete</option>
                                </select>
                            </div>
                            <div class="md:col-span-1" id="category-select" style="display: none;">
                                <label for="bulk-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Category</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="bulk-category" name="category_id">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-3 flex gap-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white font-medium rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500" id="bulk-submit">Apply</button>
                                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" onclick="clearBulkSelection()">Clear</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <x-content-card :no-padding="true">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="group inline-flex items-center space-x-1 text-gray-500 hover:text-gray-700 dark:text-gray-300">
                                        <span>Name</span>
                                        @if(request('sort_by') === 'name')
                                            <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }} text-xs"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'base_price', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="group inline-flex items-center space-x-1 text-gray-500 hover:text-gray-700 dark:text-gray-300">
                                        <span>Price</span>
                                        @if(request('sort_by') === 'base_price')
                                            <i class="fas fa-sort-{{ request('sort_order') === 'asc' ? 'up' : 'down' }} text-xs"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <span class="tooltip-container">
                                        Margin
                                        <div class="tooltip">Profit Margin: (Price - Cost) / Price × 100%<br>Green: ≥ {{ $setting->profitability_tracking_settings['goal_margin'] ?? 25 }}% goal, Red: < {{ $setting->profitability_tracking_settings['goal_margin'] ?? 25 }}% goal</div>
                                    </span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Model</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                            @forelse($products as $product)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" 
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded product-checkbox">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($product->image_url)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" 
                                                    class="h-8 w-8 rounded-full object-cover mr-3">
                                            @endif
                                            <div>
                                                <a href="{{ route('products.show', $product) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600">
                                                    {{ $product->name }}
                                                </a>
                                                @if($product->is_featured)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">Featured</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="text-sm text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $product->sku }}</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $product->type === 'product' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ ucfirst($product->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->currency_code }} {{ number_format($product->base_price, 2) }}</div>
                                        @if($product->pricing_model !== 'fixed')
                                            <div class="text-sm text-gray-500">{{ ucfirst($product->pricing_model) }} pricing</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $margin = $product->getProfitMargin();
                                            $goalMargin = $setting->profitability_tracking_settings['goal_margin'] ?? 25; // Default to 25% if not set
                                            $marginClass = 'text-gray-500';
                                            
                                            if ($margin !== null) {
                                                if ($margin >= $goalMargin) {
                                                    $marginClass = 'text-green-600 font-semibold';
                                                } else {
                                                    $marginClass = 'text-red-600 font-semibold';
                                                }
                                            }
                                        @endphp
                                        
                                        @if($margin !== null)
                                            <div class="text-sm {{ $marginClass }}">
                                                {{ number_format($margin, 1) }}%
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                Cost: {{ $product->currency_code }} {{ number_format($product->cost, 2) }}
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-400">
                                                No cost data
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $product->billing_model === 'one_time' ? 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' : 'bg-green-100 text-green-800' }}">
                                            {{ str_replace('_', ' ', ucfirst($product->billing_model)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($product->track_inventory)
                                            @php
                                                $available = $product->current_stock - $product->reserved_stock;
                                                $stockClass = $available <= 0 ? 'bg-red-100 text-red-800' : ($available <= $product->min_stock_level ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $stockClass }}">{{ $available }}</span>
                                        @else
                                            <span class="text-sm text-gray-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('products.edit', $product) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white dark:text-white">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('products.duplicate', $product) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-indigo-600 hover:text-indigo-900" title="Duplicate">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-box-open text-5xl mb-4 text-gray-400"></i>
                                            <p class="text-lg mb-4">No products found.</p>
                                            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Create your first product</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($products->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-center">
                            {{ $products->links() }}
                        </div>
                    </div>
                @endif
            </x-content-card>
</div>

@push('styles')
<style>
.tooltip-container {
    position: relative;
    cursor: help;
}

.tooltip-container:hover .tooltip {
    opacity: 1;
    visibility: visible;
}

.tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #1f2937;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s, visibility 0.3s;
    z-index: 10;
    margin-bottom: 5px;
}

.tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #1f2937;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActions = document.getElementById('bulk-actions');
    const bulkForm = document.getElementById('bulk-form');
    const bulkActionSelect = document.getElementById('bulk-action');
    const categorySelect = document.getElementById('category-select');

    // Select all functionality
    selectAll.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    // Individual checkbox change
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkActions();
        });
    });

    // Bulk action type change
    bulkActionSelect.addEventListener('change', function() {
        if (this.value === 'update_category') {
            categorySelect.style.display = 'block';
        } else {
            categorySelect.style.display = 'none';
        }
    });

    // Bulk form submission
    bulkForm.addEventListener('submit', function(e) {
        const selectedProducts = getSelectedProducts();
        if (selectedProducts.length === 0) {
            e.preventDefault();
            alert('Please select at least one product.');
            return;
        }

        const action = bulkActionSelect.value;
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected products? This action cannot be undone.')) {
                e.preventDefault();
                return;
            }
        }

        // Add selected product IDs to form
        selectedProducts.forEach(productId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = productId;
            this.appendChild(input);
        });
    });

    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
        const totalCount = productCheckboxes.length;
        
        selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCount;
        selectAll.checked = checkedCount === totalCount && totalCount > 0;
    }

    function updateBulkActions() {
        const selectedCount = getSelectedProducts().length;
        if (selectedCount > 0) {
            bulkActions.style.display = 'block';
        } else {
            bulkActions.style.display = 'none';
        }
    }

    function getSelectedProducts() {
        return Array.from(document.querySelectorAll('.product-checkbox:checked')).map(checkbox => checkbox.value);
    }

    window.clearBulkSelection = function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updateBulkActions();
    };
});
</script>
@endpush
@endsection