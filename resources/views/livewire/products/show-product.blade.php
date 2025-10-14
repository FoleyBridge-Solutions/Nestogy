<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Product Details Card -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Product Details</flux:heading>
            </flux:card.header>
            
            <div class="p-6 space-y-6">
                @if($product->description)
                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Description</flux:subheading>
                        <p class="text-gray-700 dark:text-gray-300">{{ $product->description }}</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Type</flux:subheading>
                        <flux:badge color="{{ $product->type === 'product' ? 'blue' : 'purple' }}">
                            {{ ucfirst($product->type) }}
                        </flux:badge>
                    </div>

                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Status</flux:subheading>
                        <flux:badge color="{{ $product->is_active ? 'green' : 'red' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </div>

                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Base Price</flux:subheading>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $product->currency_code }} {{ number_format($product->base_price, 2) }}
                        </p>
                    </div>

                    @if($product->cost)
                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Cost</flux:subheading>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $product->currency_code }} {{ number_format($product->cost, 2) }}
                        </p>
                    </div>
                    @endif

                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Billing Model</flux:subheading>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $product->billing_model)) }}</p>
                    </div>

                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Billing Cycle</flux:subheading>
                        <p class="text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $product->billing_cycle)) }}</p>
                    </div>

                    @if($product->category)
                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Category</flux:subheading>
                        <p class="text-gray-900 dark:text-white">{{ $product->category->name }}</p>
                    </div>
                    @endif

                    @if($product->track_inventory)
                    <div>
                        <flux:subheading class="text-gray-500 dark:text-gray-400 mb-2">Current Stock</flux:subheading>
                        <p class="text-gray-900 dark:text-white">{{ $product->current_stock ?? 0 }} units</p>
                    </div>
                    @endif
                </div>
            </div>
        </flux:card>

        <!-- Recent Sales Card -->
        @if(count($recentSales) > 0)
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Recent Sales</flux:heading>
            </flux:card.header>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentSales as $sale)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($sale->created_at)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $sale->quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                ${{ number_format($sale->price, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                ${{ number_format($sale->quantity * $sale->price, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Stats Card -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Quick Stats</flux:heading>
            </flux:card.header>
            
            <div class="p-6 space-y-4">
                <div>
                    <flux:subheading class="text-gray-500 dark:text-gray-400 mb-1">Total Sales</flux:subheading>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ count($recentSales) }}
                    </p>
                </div>

                @if(count($recentSales) > 0)
                <div>
                    <flux:subheading class="text-gray-500 dark:text-gray-400 mb-1">Revenue (Last 10)</flux:subheading>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format(collect($recentSales)->sum(fn($sale) => $sale->quantity * $sale->price), 2) }}
                    </p>
                </div>
                @endif

                @if($product->is_taxable)
                <div>
                    <flux:badge color="amber">Taxable</flux:badge>
                </div>
                @endif

                @if($product->allow_discounts)
                <div>
                    <flux:badge color="green">Discounts Allowed</flux:badge>
                </div>
                @endif
            </div>
        </flux:card>

        <!-- Metadata Card -->
        <flux:card>
            <flux:card.header>
                <flux:heading size="lg">Metadata</flux:heading>
            </flux:card.header>
            
            <div class="p-6 space-y-3">
                <div>
                    <flux:subheading class="text-gray-500 dark:text-gray-400 mb-1">Created</flux:subheading>
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $product->created_at->format('M d, Y g:i A') }}
                    </p>
                </div>

                <div>
                    <flux:subheading class="text-gray-500 dark:text-gray-400 mb-1">Last Updated</flux:subheading>
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $product->updated_at->format('M d, Y g:i A') }}
                    </p>
                </div>

                @if($product->sku)
                <div>
                    <flux:subheading class="text-gray-500 dark:text-gray-400 mb-1">SKU</flux:subheading>
                    <p class="text-sm font-mono text-gray-900 dark:text-white">
                        {{ $product->sku }}
                    </p>
                </div>
                @endif
            </div>
        </flux:card>
    </div>
</div>
