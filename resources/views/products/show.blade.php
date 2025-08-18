@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <x-page-header 
        :title="$product->name"
        :subtitle="'SKU: ' . $product->sku . ($product->category ? ' • Category: ' . $product->category->name : '')"
        :back-route="route('products.index')"
        back-label="Back to Products"
    >
        <x-slot name="actions">
            <div class="flex gap-3">
                @can('update', $product)
                <a href="{{ route('products.edit', $product) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Product
                </a>
                @endcan
            </div>
        </x-slot>
    </x-page-header>

    <!-- Main Content Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column (Main Content - 2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Product Details -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Product Details</h3>
                    </div>
                    <div class="px-6 py-4">
                        @if($product->description)
                            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $product->description }}</p>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $product->type === 'product' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ ucfirst($product->type) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Base Price</dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">${{ number_format($product->base_price, 2) }}</dd>
                                </div>
                                @if($product->cost)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Cost</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">${{ number_format($product->cost, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Margin</dt>
                                    <dd class="mt-1 text-sm font-medium text-blue-600">{{ $product->getFormattedProfitMargin() }}</dd>
                                </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Pricing Model</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $product->pricing_model)) }}</dd>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Billing Model</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $product->billing_model === 'one_time' ? 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' : 'bg-green-100 text-green-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $product->billing_model)) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Unit Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($product->unit_type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }}">
                                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        @if($product->is_featured)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                                Featured
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Taxable</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->is_taxable ? 'Yes' : 'No' }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-content-card>

                @if($recentSales && $recentSales->count() > 0)
                <!-- Recent Sales -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Sales</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                                @foreach($recentSales as $sale)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($sale->created_at)->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ number_format($sale->quantity, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        ${{ number_format($sale->price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        ${{ number_format($sale->quantity * $sale->price, 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-content-card>
                @endif
            </div>

            <!-- Right Column (Sidebar - 1/3 width) -->
            <div class="lg:col-span-1 space-y-6">
                
                @if($product->track_inventory)
                <!-- Inventory Status -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Inventory Status</h3>
                    </div>
                    <div class="px-6 py-4">
                        @php
                            $available = $product->current_stock - $product->reserved_stock;
                            $stockColorClass = $available <= 0 ? 'text-red-600' : ($available <= $product->min_stock_level ? 'text-yellow-600' : 'text-green-600');
                            $stockBgClass = $available <= 0 ? 'bg-red-100 text-red-800' : ($available <= $product->min_stock_level ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                        @endphp
                        
                        <div class="text-center mb-6">
                            <div class="text-4xl font-bold {{ $stockColorClass }}">{{ $available }}</div>
                            <div class="text-sm text-gray-500">Available</div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Total Stock:</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $product->current_stock }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Reserved:</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $product->reserved_stock }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Available:</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $stockBgClass }}">
                                    {{ $available }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Min Level:</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $product->min_stock_level }}</span>
                            </div>
                            @if($product->reorder_level)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Reorder Level:</span>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $product->reorder_level }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </x-content-card>
                @endif

                <!-- Product Summary -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Product Summary</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    @if($product->is_featured)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                                            Featured
                                        </span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Allow Discounts</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->allow_discounts ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Requires Approval</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->requires_approval ? 'Yes' : 'No' }}</dd>
                            </div>
                            @if($product->max_quantity_per_order)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Max Quantity per Order</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->max_quantity_per_order }}</dd>
                            </div>
                            @endif
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->created_at->format('M j, Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $product->updated_at->format('M j, Y') }}</dd>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-content-card>

                @if($product->tags && count($product->tags) > 0)
                <!-- Tags -->
                <x-content-card>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tags</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($product->tags as $tag)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </x-content-card>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection