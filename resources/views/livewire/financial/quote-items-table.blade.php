<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quote Items</h3>
            @if($this->hasItems)
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $this->itemsCount }} items • Total: {{ $this->formattedItemsTotal }}
                </p>
            @endif
        </div>
        <div class="flex space-x-2">
            <flux:button 
                wire:click="toggleProductSelector"
                variant="ghost"
                size="sm"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Browse Products
            </flux:button>
            <flux:button 
                wire:click="addItem"
                variant="primary"
                size="sm"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Item
            </flux:button>
        </div>
    </div>

    <!-- Product Selector Modal -->
    @if($showProductSelector)
        <div class="fixed inset-0 bg-gray-500/75 flex items-center justify-center z-50" wire:click.self="toggleProductSelector">
            <flux:card class="w-full max-w-2xl max-h-[80vh] overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Select Products</h3>
                        <flux:button 
                            wire:click="toggleProductSelector"
                            variant="ghost"
                            size="sm"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </flux:button>
                    </div>

                    <!-- Search -->
                    <div class="mb-4">
                        <flux:input 
                            wire:model.live="searchProducts"
                            placeholder="Search products..."
                            class="w-full"
                        >
                            <x-slot name="iconTrailing">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </x-slot>
                        </flux:input>
                    </div>

                    <!-- Products List -->
                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @forelse($availableProducts as $product)
                            <div class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                 wire:click="addProductToQuote({{ $product['id'] }})">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</h4>
                                        @if($product['description'])
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $product['description'] }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right ml-4">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $this->formatCurrency($product['price'] ?? 0) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-gray-500 dark:text-gray-400">No products found.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </flux:card>
        </div>
    @endif

    <!-- New Item Form -->
    <flux:card class="p-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Add New Item</h4>
        
        <form wire:submit.prevent="addItem">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Item Name -->
                <div class="md:col-span-12-span-2">
                    <flux:field>
                        <flux:label>Item Name *</flux:label>
                        <flux:input 
                            wire:model="newItem.name"
                            placeholder="Enter item name..."
                            required
                        />
                        @error('newItem.name') 
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Quantity -->
                <div>
                    <flux:field>
                        <flux:label>Quantity *</flux:label>
                        <flux:input 
                            wire:model="newItem.quantity"
                            type="number"
                            step="1"
                            min="1"
                            required
                        />
                        @error('newItem.quantity') 
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Unit Price -->
                <div>
                    <flux:field>
                        <flux:label>Unit Price *</flux:label>
                        <flux:input 
                            wire:model="newItem.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                        />
                        @error('newItem.unit_price') 
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Discount -->
                <div>
                    <flux:field>
                        <flux:label>Discount</flux:label>
                        <flux:input 
                            wire:model="newItem.discount"
                            type="number"
                            step="0.01"
                            min="0"
                        />
                        @error('newItem.discount') 
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>

                <!-- Add Button -->
                <div class="flex items-end">
                    <flux:button type="submit" variant="primary" class="w-full">
                        Add Item
                    </flux:button>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-4">
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea 
                        wire:model="newItem.description"
                        placeholder="Enter item description..."
                        rows="2"
                    />
                </flux:field>
            </div>

            <!-- Advanced Options -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:field>
                        <flux:label>Type</flux:label>
                        <flux:select wire:model="newItem.type">
                            <flux:select.option value="product">Product</flux:select.option>
                            <flux:select.option value="service">Service</flux:select.option>
                            <flux:select.option value="bundle">Bundle</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Billing Cycle</flux:label>
                        <flux:select wire:model="newItem.billing_cycle">
                            <flux:select.option value="one_time">One Time</flux:select.option>
                            <flux:select.option value="monthly">Monthly</flux:select.option>
                            <flux:select.option value="quarterly">Quarterly</flux:select.option>
                            <flux:select.option value="annually">Annually</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>Tax Rate (%)</flux:label>
                        <flux:input 
                            wire:model="newItem.tax_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                        />
                        @error('newItem.tax_rate') 
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </form>
    </flux:card>

    <!-- Items Table -->
    @if($this->hasItems)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900 rounded-lg">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Item Details
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Qty
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Unit Price
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Discount
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Subtotal
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($items as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <!-- Item Details -->
                            <td class="px-6 py-4">
                                @if($editingIndex === $index)
                                    <div class="space-y-2">
                                        <flux:input 
                                            wire:model.live="items.{{ $index }}.name"
                                            wire:blur="stopEditing"
                                            size="sm"
                                            class="font-medium"
                                        />
                                        <flux:input 
                                            wire:model.live="items.{{ $index }}.description"
                                            wire:blur="stopEditing"
                                            size="sm"
                                            placeholder="Description..."
                                        />
                                    </div>
                                @else
                                    <div wire:click="startEditing({{ $index }})" class="cursor-pointer">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $item['name'] }}
                                        </div>
                                        @if($item['description'])
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $item['description'] }}
                                            </div>
                                        @endif
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            {{ ucfirst($item['type']) }} • {{ ucfirst(str_replace('_', ' ', $item['billing_cycle'])) }}
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <!-- Quantity -->
                            <td class="px-6 py-4">
                                @if($editingIndex === $index)
                                    <flux:input 
                                        wire:model.live="items.{{ $index }}.quantity"
                                        wire:change="updateItem({{ $index }}, 'quantity', $event.target.value)"
                                        wire:blur="stopEditing"
                                        type="number"
                                        step="1"
                                        min="1"
                                        size="sm"
                                        class="w-20"
                                    />
                                @else
                                    <span wire:click="startEditing({{ $index }})" class="cursor-pointer">
                                        {{ $item['quantity'] }}
                                    </span>
                                @endif
                            </td>

                            <!-- Unit Price -->
                            <td class="px-6 py-4">
                                @if($editingIndex === $index)
                                    <flux:input 
                                        wire:model.live="items.{{ $index }}.unit_price"
                                        wire:change="updateItem({{ $index }}, 'unit_price', $event.target.value)"
                                        wire:blur="stopEditing"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        size="sm"
                                        class="w-24"
                                    />
                                @else
                                    <span wire:click="startEditing({{ $index }})" class="cursor-pointer">
                                        {{ $this->formatCurrency($item['unit_price']) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Discount -->
                            <td class="px-6 py-4">
                                @if($editingIndex === $index)
                                    <flux:input 
                                        wire:model.live="items.{{ $index }}.discount"
                                        wire:change="updateItem({{ $index }}, 'discount', $event.target.value)"
                                        wire:blur="stopEditing"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        size="sm"
                                        class="w-24"
                                    />
                                @else
                                    <span wire:click="startEditing({{ $index }})" class="cursor-pointer">
                                        @if($item['discount'] > 0)
                                            <span class="text-red-600 dark:text-red-400">
                                                -{{ $this->formatCurrency($item['discount']) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </span>
                                @endif
                            </td>

                            <!-- Subtotal -->
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $this->formatCurrency($item['subtotal']) }}
                                </span>
                                @if($item['tax_rate'] > 0)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        +{{ $item['tax_rate'] }}% tax
                                    </div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <!-- Move Up -->
                                    @if($index > 0)
                                        <flux:button 
                                            wire:click="moveItem({{ $index }}, {{ $index - 1 }})"
                                            variant="ghost"
                                            size="sm"
                                            class="p-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        </flux:button>
                                    @endif

                                    <!-- Move Down -->
                                    @if($index < count($items) - 1)
                                        <flux:button 
                                            wire:click="moveItem({{ $index }}, {{ $index + 1 }})"
                                            variant="ghost"
                                            size="sm"
                                            class="p-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </flux:button>
                                    @endif

                                    <!-- Duplicate -->
                                    <flux:button 
                                        wire:click="duplicateItem({{ $index }})"
                                        variant="ghost"
                                        size="sm"
                                        class="p-1"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </flux:button>

                                    <!-- Delete -->
                                    <flux:button 
                                        wire:click="removeItem({{ $index }})"
                                        variant="ghost"
                                        size="sm"
                                        class="p-1 text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Items Summary -->
        <div class="flex justify-end">
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-900 dark:text-white">Items Total:</span>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400-600 dark:text-blue-600 dark:text-blue-400-400">
                        {{ $this->formattedItemsTotal }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $this->itemsCount }} {{ Str::plural('item', $this->itemsCount) }}
                </p>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <flux:card class="p-12">
            <div class="text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No items added</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Start building your quote by adding products or services.
                </p>
                <div class="flex justify-center space-x-3">
                    <flux:button wire:click="toggleProductSelector" variant="ghost">
                        Browse Products
                    </flux:button>
                    <flux:button wire:click="addItem" variant="primary">
                        Add Custom Item
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endif
</div>
