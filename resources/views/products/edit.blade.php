@extends('layouts.app')

@php
    // Determine which model variable we're working with
    $item = $type === 'service' ? $service : $product;
@endphp

@section('title', $type === 'service' ? 'Edit Service' : 'Edit Product')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        :title="$type === 'service' ? 'Edit Service' : 'Edit Product'"
        :subtitle="'Update ' . ($type === 'service' ? 'service' : 'product') . ' details and configuration'"
        :back-route="$type === 'service' ? route('services.index') : route('products.index')"
        :back-label="'Back to ' . ($type === 'service' ? 'Services' : 'Products')"
    />

    <!-- Main Content -->
    <form method="POST" action="{{ $type === 'service' ? route('services.update', $item) : route('products.update', $item) }}" id="product-form">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column (Main Content - 2/3 width) -->
            <div class="lg:col-span-12-span-2 space-y-6">
                <!-- Basic Information -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Basic Information</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-12-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $type === 'service' ? 'Service' : 'Product' }} Name <span class="text-red-600">*</span></label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-500 @enderror" 
                                    id="name" name="name" value="{{ old('name', $item->name) }}" required>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-12-span-1">
                                <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                                <input type="text" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('sku') border-red-500 @enderror" 
                                    id="sku" name="sku" value="{{ old('sku', $item->sku) }}" placeholder="Auto-generated if empty">
                                @error('sku')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate from name</p>
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-500 @enderror" 
                                id="description" name="description" rows="3">{{ old('description', $item->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Type is fixed for editing - Hidden input -->
                        <input type="hidden" name="type" value="{{ $type }}">

                        <div>
                            <label for="short_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Short Description</label>
                            <textarea class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('short_description') border-red-500 @enderror" 
                                id="short_description" name="short_description" rows="2" maxlength="500">{{ old('short_description', $item->short_description) }}</textarea>
                            @error('short_description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Used in summaries and previews (max 500 characters)</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('category_id') border-red-500 @enderror" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $item->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="unit_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Type <span class="text-red-600">*</span></label>
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('unit_type') border-red-500 @enderror" id="unit_type" name="unit_type" required>
                                    <option value="units" {{ old('unit_type', $item->unit_type) === 'units' ? 'selected' : '' }}>Units</option>
                                    <option value="hours" {{ old('unit_type', $item->unit_type) === 'hours' ? 'selected' : '' }}>Hours</option>
                                    <option value="days" {{ old('unit_type', $item->unit_type) === 'days' ? 'selected' : '' }}>Days</option>
                                    <option value="weeks" {{ old('unit_type', $item->unit_type) === 'weeks' ? 'selected' : '' }}>Weeks</option>
                                    <option value="months" {{ old('unit_type', $item->unit_type) === 'months' ? 'selected' : '' }}>Months</option>
                                    <option value="years" {{ old('unit_type', $item->unit_type) === 'years' ? 'selected' : '' }}>Years</option>
                                    <option value="fixed" {{ old('unit_type', $item->unit_type) === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                    <option value="subscription" {{ old('unit_type', $item->unit_type) === 'subscription' ? 'selected' : '' }}>Subscription</option>
                                </select>
                                @error('unit_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </x-content-card>

                <!-- Pricing -->
                <x-content-card id="pricing-card">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                            <i class="fas fa-dollar-sign text-green-600 mr-2"></i>
                            Pricing
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="base_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Base Price <span class="text-red-600">*</span></label>
                                <div class="flex">
                                    <select class="rounded-l-md border-r-0 border-gray-300 dark:border-gray-600 px-3 py-2 text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" id="currency_code" name="currency_code">
                                        <option value="USD" {{ old('currency_code', $item->currency_code) === 'USD' ? 'selected' : '' }}>USD</option>
                                        <option value="EUR" {{ old('currency_code', $item->currency_code) === 'EUR' ? 'selected' : '' }}>EUR</option>
                                        <option value="GBP" {{ old('currency_code', $item->currency_code) === 'GBP' ? 'selected' : '' }}>GBP</option>
                                        <option value="CAD" {{ old('currency_code', $item->currency_code) === 'CAD' ? 'selected' : '' }}>CAD</option>
                                    </select>
                                    <input type="number" class="flex-1 rounded-r-md border-gray-300 dark:border-gray-600 px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('base_price') border-red-500 @enderror" 
                                        id="base_price" name="base_price" value="{{ old('base_price', $item->base_price) }}" 
                                        step="0.01" min="0" required>
                                </div>
                                @error('base_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cost</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cost') border-red-500 @enderror" 
                                    id="cost" name="cost" value="{{ old('cost', $item->cost) }}" step="0.01" min="0">
                                @error('cost')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Your cost for this {{ $type }}</p>
                            </div>
                            <div>
                                <label for="pricing_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pricing Model <span class="text-red-600">*</span></label>
                                <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('pricing_model') border-red-500 @enderror" id="pricing_model" name="pricing_model" required>
                                    <option value="fixed" {{ old('pricing_model', $item->pricing_model) === 'fixed' ? 'selected' : '' }}>Fixed Price</option>
                                    <option value="tiered" {{ old('pricing_model', $item->pricing_model) === 'tiered' ? 'selected' : '' }}>Tiered Pricing</option>
                                    <option value="volume" {{ old('pricing_model', $item->pricing_model) === 'volume' ? 'selected' : '' }}>Volume Discount</option>
                                    <option value="usage" {{ old('pricing_model', $item->pricing_model) === 'usage' ? 'selected' : '' }}>Usage Based</option>
                                    <option value="value" {{ old('pricing_model', $item->pricing_model) === 'value' ? 'selected' : '' }}>Value Based</option>
                                    <option value="custom" {{ old('pricing_model', $item->pricing_model) === 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                @error('pricing_model')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Service-Specific Fields -->
                        <div id="service-fields" class="{{ $type === 'service' ? 'block' : 'hidden' }}">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Service Configuration</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="billing_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Model</label>
                                    <select name="billing_model" id="billing_model" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="one_time" {{ old('billing_model', $item->billing_model) === 'one_time' ? 'selected' : '' }}>One-time</option>
                                        <option value="subscription" {{ old('billing_model', $item->billing_model) === 'subscription' ? 'selected' : '' }}>Subscription</option>
                                        <option value="usage_based" {{ old('billing_model', $item->billing_model) === 'usage_based' ? 'selected' : '' }}>Usage-based</option>
                                        <option value="hybrid" {{ old('billing_model', $item->billing_model) === 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Subscription Billing Fields -->
                            <div id="subscription-billing-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 {{ old('billing_model', $item->billing_model) === 'subscription' ? 'block' : 'hidden' }}">
                                <div>
                                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Cycle</label>
                                    <select name="billing_cycle" id="billing_cycle" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="one_time" {{ old('billing_cycle', $item->billing_cycle) === 'one_time' ? 'selected' : '' }}>One Time</option>
                                        <option value="hourly" {{ old('billing_cycle', $item->billing_cycle) === 'hourly' ? 'selected' : '' }}>Hourly</option>
                                        <option value="daily" {{ old('billing_cycle', $item->billing_cycle) === 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ old('billing_cycle', $item->billing_cycle) === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ old('billing_cycle', $item->billing_cycle) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="quarterly" {{ old('billing_cycle', $item->billing_cycle) === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                        <option value="semi_annually" {{ old('billing_cycle', $item->billing_cycle) === 'semi_annually' ? 'selected' : '' }}>Semi-Annually</option>
                                        <option value="annually" {{ old('billing_cycle', $item->billing_cycle) === 'annually' ? 'selected' : '' }}>Annually</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="billing_interval" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Billing Interval</label>
                                    <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="billing_interval" 
                                           name="billing_interval" value="{{ old('billing_interval', $item->billing_interval) }}" min="1" max="12">
                                    <p class="mt-1 text-sm text-gray-500">How often to bill (e.g., every 2 months)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Settings -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Tax & Policy Settings</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="is_taxable" name="is_taxable" 
                                            {{ old('is_taxable', $item->is_taxable) ? 'checked' : '' }}>
                                        <label class="ml-2 block text-sm text-gray-900 dark:text-white" for="is_taxable">
                                            Taxable
                                        </label>
                                    </div>

                                    <div class="flex items-center">
                                        <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="tax_inclusive" name="tax_inclusive" 
                                            {{ old('tax_inclusive', $item->tax_inclusive) ? 'checked' : '' }}>
                                        <label class="ml-2 block text-sm text-gray-900 dark:text-white" for="tax_inclusive">
                                            Tax Inclusive Pricing
                                        </label>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="allow_discounts" name="allow_discounts" 
                                            {{ old('allow_discounts', $item->allow_discounts) ? 'checked' : '' }}>
                                        <label class="ml-2 block text-sm text-gray-900 dark:text-white" for="allow_discounts">
                                            Allow Discounts
                                        </label>
                                    </div>

                                    <div class="flex items-center">
                                        <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="requires_approval" name="requires_approval" 
                                            {{ old('requires_approval', $item->requires_approval) ? 'checked' : '' }}>
                                        <label class="ml-2 block text-sm text-gray-900 dark:text-white" for="requires_approval">
                                            Requires Approval
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-content-card>


            </div>

            <!-- Right Column (Sidebar - 1/3 width) -->
            <div class="lg:col-span-12-span-1 space-y-6">
                <!-- Status & Settings -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Status & Settings</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center">
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="is_active" name="is_active" 
                                {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                            <div class="ml-3">
                                <label class="text-sm font-medium text-gray-900 dark:text-white" for="is_active">
                                    Active
                                </label>
                                <p class="text-sm text-gray-500">{{ $type === 'service' ? 'Service' : 'Product' }} is available for selection</p>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="is_featured" name="is_featured" 
                                {{ old('is_featured', $item->is_featured) ? 'checked' : '' }}>
                            <div class="ml-3">
                                <label class="text-sm font-medium text-gray-900 dark:text-white" for="is_featured">
                                    Featured {{ $type === 'service' ? 'Service' : 'Product' }}
                                </label>
                                <p class="text-sm text-gray-500">Highlight in lists</p>
                            </div>
                        </div>

                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                            <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('sort_order') border-red-500 @enderror" 
                                id="sort_order" name="sort_order" value="{{ old('sort_order', $item->sort_order) }}" min="0">
                            @error('sort_order')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Lower numbers appear first</p>
                        </div>
                    </div>
                </x-content-card>

                <!-- Inventory Management -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Inventory Management</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center">
                            <input class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 rounded" type="checkbox" id="track_inventory" name="track_inventory" 
                                {{ old('track_inventory', $item->track_inventory) ? 'checked' : '' }}>
                            <div class="ml-3">
                                <label class="text-sm font-medium text-gray-900 dark:text-white" for="track_inventory">
                                    Track Inventory
                                </label>
                                <p class="text-sm text-gray-500">Monitor stock levels for this {{ $type }}</p>
                            </div>
                        </div>

                        <div id="inventory-fields" class="space-y-4 {{ old('track_inventory', $item->track_inventory) ? 'block' : 'hidden' }}">
                            <div>
                                <label for="current_stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Stock</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('current_stock') border-red-500 @enderror" 
                                    id="current_stock" name="current_stock" value="{{ old('current_stock', $item->current_stock) }}" min="0">
                                @error('current_stock')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="min_stock_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Minimum Stock Level</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('min_stock_level') border-red-500 @enderror" 
                                    id="min_stock_level" name="min_stock_level" value="{{ old('min_stock_level', $item->min_stock_level) }}" min="0">
                                @error('min_stock_level')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Alert when stock falls below this level</p>
                            </div>

                            <div>
                                <label for="reorder_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reorder Level</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('reorder_level') border-red-500 @enderror" 
                                    id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level) }}" min="0">
                                @error('reorder_level')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Trigger reorder when stock reaches this level</p>
                            </div>

                            <div>
                                <label for="max_quantity_per_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Quantity Per Order</label>
                                <input type="number" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('max_quantity_per_order') border-red-500 @enderror" 
                                    id="max_quantity_per_order" name="max_quantity_per_order" value="{{ old('max_quantity_per_order', $item->max_quantity_per_order) }}" min="1">
                                @error('max_quantity_per_order')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Maximum quantity allowed per order</p>
                            </div>
                        </div>
                    </div>
                </x-content-card>

                <!-- Actions -->
                <x-content-card>
                    <div class="p-6">
                        <div class="space-y-3">
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-save mr-2"></i> Update {{ $type === 'service' ? 'Service' : 'Product' }}
                            </button>
                            <button type="button" class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="window.location.href='{{ $type === 'service' ? route('services.index') : route('products.index') }}'">
                                Cancel
                            </button>
                        </div>
                    </div>
                </x-content-card>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Pass server-side type to JavaScript
window.productType = @json($type ?? null);

// Legacy fallback - the ProductServiceBuilder component will override this if loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if ProductServiceBuilder is available
    if (window.productServiceBuilder) {
        return; // Use the advanced component instead
    }
    
    // Legacy functionality for basic form handling
    const trackInventoryCheckbox = document.getElementById('track_inventory');
    const inventoryFields = document.getElementById('inventory-fields');

    if (trackInventoryCheckbox) {
        trackInventoryCheckbox.addEventListener('change', function() {
            if (inventoryFields) {
                if (this.checked) {
                    inventoryFields.classList.remove('hidden');
                    inventoryFields.classList.add('block');
                } else {
                    inventoryFields.classList.remove('block');
                    inventoryFields.classList.add('hidden');
                }
            }
        });
    }

    // Service fields are always visible for services, hidden for products
    const serviceFields = document.getElementById('service-fields');
    if (serviceFields) {
        if (window.productType === 'service') {
            serviceFields.classList.remove('hidden');
            serviceFields.classList.add('block');
        } else {
            serviceFields.classList.remove('block');
            serviceFields.classList.add('hidden');
        }
    }

    // Billing model handling for services
    const billingModelSelect = document.getElementById('billing_model');
    const subscriptionBillingFields = document.getElementById('subscription-billing-fields');

    function toggleSubscriptionFields() {
        if (billingModelSelect && subscriptionBillingFields) {
            if (billingModelSelect.value === 'subscription') {
                subscriptionBillingFields.classList.remove('hidden');
                subscriptionBillingFields.classList.add('block');
            } else {
                subscriptionBillingFields.classList.remove('block');
                subscriptionBillingFields.classList.add('hidden');
            }
        }
    }

    if (billingModelSelect) {
        billingModelSelect.addEventListener('change', toggleSubscriptionFields);
    }

    // Initialize on page load
    toggleSubscriptionFields();
});
</script>
@endpush
@endsection
