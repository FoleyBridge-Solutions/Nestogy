@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <x-page-header>
        <x-slot name="title">{{ $bundle->name }}</x-slot>
        <x-slot name="description">Bundle details and configuration</x-slot>
        <x-slot name="actions">
            @can('update', $bundle)
                <a href="{{ route('bundles.edit', $bundle) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-edit"></i> Edit Bundle
                </a>
            @endcan
            <a href="{{ route('bundles.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                <i class="fas fa-arrow-left"></i> Back to Bundles
            </a>
        </x-slot>
    </x-page-header>

    <div class="flex flex-wrap -mx-4">
        <!-- Bundle Information -->
        <div class="md:w-2/3 px-4">
            <x-content-card>
                <div class="flex justify-between align-items-start mb-4">
                    <div>
                        <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-1">{{ $bundle->name }}</h5>
                        @if($bundle->sku)
                            <p class="text-gray-600 dark:text-gray-400 mb-0">SKU: {{ $bundle->sku }}</p>
                        @endif
                    </div>
                    <div>
                        @if($bundle->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-gray-600">Inactive</span>
                        @endif
                    </div>
                </div>

                @if($bundle->description)
                    <div class="mb-4">
                        <h6>Description</h6>
                        <p class="text-gray-600 dark:text-gray-400">{{ $bundle->description }}</p>
                    </div>
                @endif

                <!-- Bundle Products -->
                <h6 class="mb-3">Bundle Products</h6>
                @if($bundle->products->count() > 0)
                    <div class="min-w-full divide-y divide-gray-200-responsive">
                        <table class="table min-w-full divide-y divide-gray-200-sm">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Unit Price</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $bundleTotal = 0; @endphp
                                @foreach($bundle->products as $product)
                                    @php 
                                        $lineTotal = $product->price * $product->pivot->quantity;
                                        $bundleTotal += $lineTotal;
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $product->name }}</strong>
                                            @if($product->sku)
                                                <br><small class="text-muted">SKU: {{ $product->sku }}</small>
                                            @endif
                                        </td>
                                        <td>${{ number_format($product->price, 2) }}</td>
                                        <td>{{ $product->pivot->quantity }}</td>
                                        <td>${{ number_format($lineTotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th scope="row" colspan="3">Subtotal</th>
                                    <th scope="col">${{ number_format($bundleTotal, 2) }}</th>
                                </tr>
                                @if($bundle->discount_type)
                                    <tr>
                                        <th scope="row" colspan="3">
                                            Discount
                                            @if($bundle->discount_type === 'percentage')
                                                ({{ $bundle->discount_value }}%)
                                            @else
                                                (Fixed)
                                            @endif
                                        </th>
                                        <th scope="col" class="text-red-600">
                                            @if($bundle->discount_type === 'percentage')
                                                -${{ number_format($bundleTotal * $bundle->discount_value / 100, 2) }}
                                            @else
                                                -${{ number_format($bundle->discount_value, 2) }}
                                            @endif
                                        </th>
                                    </tr>
                                @endif
                                <tr class="table-primary">
                                    <th scope="row" colspan="3">Bundle Price</th>
                                    <th scope="col">
                                        @if($bundle->fixed_price)
                                            ${{ number_format($bundle->fixed_price, 2) }}
                                        @else
                                            @php
                                                $finalPrice = $bundleTotal;
                                                if ($bundle->discount_type === 'percentage') {
                                                    $finalPrice -= $bundleTotal * $bundle->discount_value / 100;
                                                } elseif ($bundle->discount_type === 'fixed') {
                                                    $finalPrice -= $bundle->discount_value;
                                                }
                                            @endphp
                                            ${{ number_format($finalPrice, 2) }}
                                        @endif
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No products in this bundle</h6>
                        <p class="text-muted">Add products to create a complete bundle.</p>
                        @can('update', $bundle)
                            <a href="{{ route('bundles.edit', $bundle) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus"></i> Add Products
                            </a>
                        @endcan
                    </div>
                @endif
            </x-content-card>
        </div>

        <!-- Bundle Summary -->
        <div class="md:w-1/3 px-4">
            <x-content-card>
                <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-4">Bundle Summary</h5>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-muted">Total Products</label>
                    <div class="fw-bold">{{ $bundle->products->count() }}</div>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 text-muted">Pricing Type</label>
                    <div class="fw-bold">
                        @if($bundle->fixed_price)
                            Fixed Price
                        @else
                            Calculated from Products
                        @endif
                    </div>
                </div>

                @if($bundle->discount_type)
                    <div class="mb-3">
                        <label class="form-label text-muted">Discount</label>
                        <div class="fw-bold text-info">
                            @if($bundle->discount_type === 'percentage')
                                {{ $bundle->discount_value }}% Off
                            @else
                                ${{ number_format($bundle->discount_value, 2) }} Off
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label text-muted">Bundle Price</label>
                    <div class="fw-bold text-green-600 fs-5">
                        @if($bundle->fixed_price)
                            ${{ number_format($bundle->fixed_price, 2) }}
                        @else
                            @php
                                $calculatedPrice = $bundle->products->sum(function($product) {
                                    return $product->price * $product->pivot->quantity;
                                });
                                if ($bundle->discount_type === 'percentage') {
                                    $calculatedPrice -= $calculatedPrice * $bundle->discount_value / 100;
                                } elseif ($bundle->discount_type === 'fixed') {
                                    $calculatedPrice -= $bundle->discount_value;
                                }
                            @endphp
                            ${{ number_format($calculatedPrice, 2) }}
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Status</label>
                    <div>
                        @if($bundle->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-gray-600">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Created</label>
                    <div class="fw-bold">{{ $bundle->created_at->format('M j, Y') }}</div>
                </div>

                @if($bundle->updated_at != $bundle->created_at)
                    <div class="mb-3">
                        <label class="form-label text-muted">Last Updated</label>
                        <div class="fw-bold">{{ $bundle->updated_at->format('M j, Y') }}</div>
                    </div>
                @endif

                <hr>

                <div class="d-grid gap-2">
                    @can('update', $bundle)
                        <a href="{{ route('bundles.edit', $bundle) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Bundle
                        </a>
                    @endcan
                    
                    @can('delete', $bundle)
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="confirmDelete('{{ $bundle->name }}', '{{ route('bundles.destroy', $bundle) }}')">
                            <i class="fas fa-trash"></i> Delete Bundle
                        </button>
                    @endcan
                </div>
            </x-content-card>
        </div>
    </div>
</div>

<script>
function confirmDelete(name, url) {
    if (confirm(`Are you sure you want to delete the bundle "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection