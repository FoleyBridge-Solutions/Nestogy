<div class="space-y-6">
    <!-- Filters Card -->
    <flux:card>
        <div>
            <flux:heading size="lg">Filters</flux:heading>
            <flux:subheading>Search and filter products</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="md:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, SKU, or description..."
                    type="search" />
            </div>

            <!-- Category Filter -->
            <flux:select wire:model.live="categoryFilter" placeholder="All Categories">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>

            <!-- Type Filter -->
            <flux:select wire:model.live="typeFilter" placeholder="All Types">
                <option value="">All Types</option>
                <option value="product">Product</option>
                <option value="service">Service</option>
            </flux:select>

            <!-- Status Filter -->
            <flux:select wire:model.live="statusFilter" placeholder="All Status">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </flux:select>

            <!-- Billing Model Filter -->
            <flux:select wire:model.live="billingModelFilter" placeholder="All Billing Models">
                <option value="">All Billing Models</option>
                <option value="one_time">One Time</option>
                <option value="subscription">Subscription</option>
                <option value="usage_based">Usage Based</option>
                <option value="hybrid">Hybrid</option>
            </flux:select>

            <!-- Clear Filters -->
            <div class="md:col-span-2 flex items-end">
                <flux:button wire:click="clearFilters" variant="ghost">
                    Clear Filters
                </flux:button>
            </div>
        </div>
    </flux:card>

    <!-- Products Table -->
    <flux:card>
        <flux:table :paginate="$products">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortField === 'name'" :direction="$sortDirection" wire:click="sortByColumn('name')">Name</flux:table.column>
                <flux:table.column>SKU</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column sortable :sorted="$sortField === 'base_price'" :direction="$sortDirection" wire:click="sortByColumn('base_price')">Price</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($products as $product)
                    <flux:table.row :key="$product->id">
                        <flux:table.cell>
                            <div>
                                <div class="font-medium">{{ $product->name }}</div>
                                @if($product->description)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                        {{ Str::limit($product->description, 50) }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="font-mono text-sm">
                            {{ $product->sku ?: 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            {{ $product->category->name ?? 'N/A' }}
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ $product->type === 'product' ? 'blue' : 'purple' }}" inset="top bottom">
                                {{ ucfirst($product->type) }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell variant="strong">
                            <div>{{ $product->currency_code }} {{ number_format($product->base_price, 2) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                {{ ucfirst(str_replace('_', ' ', $product->billing_model)) }}
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ $product->is_active ? 'green' : 'red' }}" inset="top bottom">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:button size="sm" variant="ghost" :href="route('products.show', $product)">View</flux:button>
                                @can('update', $product)
                                    <flux:button size="sm" variant="ghost" :href="route('products.edit', $product)">Edit</flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No products found</p>
                                @if($search || $categoryFilter || $typeFilter || $statusFilter || $billingModelFilter)
                                    <p class="text-sm mt-1 text-gray-500 dark:text-gray-400">Try adjusting your filters</p>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
