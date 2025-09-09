<div class="max-w-6xl mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Invoice</h1>
        <p class="text-sm text-gray-600 mt-1">Fill in the details to create a new invoice</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form (Left Side) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Client & Basic Info --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Client Selection --}}
                    <div>
                        <label for="client" class="block text-sm font-medium text-gray-700 mb-1">
                            Client <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="client_id" id="client" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select a client...</option>
                            @foreach($this->clients as $client)
                                <option value="{{ $client->id }}">
                                    {{ $client->name }}
                                    @if($client->company_name)
                                        ({{ $client->company_name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('client_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="category_id" id="category"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select a category...</option>
                            @foreach($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Invoice Number --}}
                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-1">
                            Invoice Number
                        </label>
                        <div class="flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                {{ $prefix }}
                            </span>
                            <input type="text" wire:model="number" id="invoice_number" readonly
                                class="flex-1 rounded-none rounded-r-md border-gray-300 bg-gray-50 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    {{-- Currency --}}
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
                            Currency
                        </label>
                        <select wire:model="currency_code" id="currency"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                            <option value="CAD">CAD - Canadian Dollar</option>
                        </select>
                    </div>

                    {{-- Invoice Date --}}
                    <div>
                        <label for="invoice_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Invoice Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model.live="invoice_date" id="invoice_date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('invoice_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Payment Terms --}}
                    <div>
                        <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Terms
                        </label>
                        <select wire:model.live="payment_terms" id="payment_terms"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="0">Due on receipt</option>
                            <option value="7">Net 7 days</option>
                            <option value="14">Net 14 days</option>
                            <option value="30">Net 30 days</option>
                            <option value="45">Net 45 days</option>
                            <option value="60">Net 60 days</option>
                            <option value="90">Net 90 days</option>
                        </select>
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="due_date" id="due_date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('due_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea wire:model="description" id="description" rows="2"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Brief description of the invoice..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Line Items</h2>
                    <div class="flex gap-2">
                        @if(count($this->products) > 0)
                            <select wire:change="addProductAsItem($event.target.value)" 
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Add product...</option>
                                @foreach($this->products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} - ${{ number_format($product->price ?? 0, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <button type="button" wire:click="showAddItemForm"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Custom Item
                        </button>
                    </div>
                </div>

                @if($showItemForm)
                    {{-- Inline Item Form --}}
                    <div class="border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                                <input type="text" wire:model="itemForm.name"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter item name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                <input type="number" wire:model="itemForm.quantity" step="0.01" min="0.01"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                                <input type="number" wire:model="itemForm.unit_price" step="0.01" min="0"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                                <textarea wire:model="itemForm.description" rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Item description..."></textarea>
                            </div>
                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button type="button" wire:click="cancelItemForm"
                                    class="px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="button" wire:click="saveItem"
                                    class="px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    {{ $editingItemIndex !== null ? 'Update' : 'Add' }} Item
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                @if(count($items) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $index => $item)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $item['name'] }}</div>
                                                @if(isset($item['description']) && $item['description'])
                                                    <div class="text-sm text-gray-500">{{ Str::limit($item['description'], 50) }}</div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">
                                            {{ $item['quantity'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">
                                            ${{ number_format($item['unit_price'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                            ${{ number_format($item['quantity'] * $item['unit_price'], 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button" wire:click="editItem({{ $index }})"
                                                class="text-blue-600 hover:text-blue-900 text-sm mr-2">Edit</button>
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                onclick="confirm('Remove this item?') || event.stopImmediatePropagation()"
                                                class="text-red-600 hover:text-red-900 text-sm">Remove</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">No items added yet</p>
                        <p class="text-xs text-gray-500">Add products or custom items to your invoice</p>
                    </div>
                @endif
                @error('items')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Additional Info --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                            Internal Notes
                        </label>
                        <textarea wire:model="notes" id="notes" rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Notes for internal use (not shown to client)..."></textarea>
                    </div>

                    <div>
                        <label for="terms" class="block text-sm font-medium text-gray-700 mb-1">
                            Terms & Conditions
                        </label>
                        <textarea wire:model="terms_conditions" id="terms" rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Payment terms and conditions..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Sidebar (Right Side) --}}
        <div class="lg:col-span-1">
            <div class="sticky top-4 space-y-6">
                {{-- Client Info Card --}}
                @if($this->selectedClient)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Bill To</h3>
                        <div class="text-sm text-gray-600">
                            <p class="font-medium text-gray-900">{{ $this->selectedClient->name }}</p>
                            @if($this->selectedClient->company_name)
                                <p>{{ $this->selectedClient->company_name }}</p>
                            @endif
                            @if($this->selectedClient->email)
                                <p>{{ $this->selectedClient->email }}</p>
                            @endif
                            @if($this->selectedClient->phone)
                                <p>{{ $this->selectedClient->phone }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Pricing Summary --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Summary</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium text-gray-900">${{ number_format($this->subtotal, 2) }}</span>
                        </div>

                        {{-- Discount --}}
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center flex-1">
                                <span class="text-gray-600 mr-2">Discount</span>
                                <select wire:model.live="discount_type" 
                                    class="text-xs rounded border-gray-300 py-1 px-2">
                                    <option value="fixed">$</option>
                                    <option value="percentage">%</option>
                                </select>
                                <input type="number" wire:model.live="discount_amount" 
                                    step="0.01" min="0"
                                    class="ml-1 w-20 text-xs rounded border-gray-300 py-1 px-2"
                                    placeholder="0">
                            </div>
                            @if($this->discountAmount > 0)
                                <span class="text-green-600">-${{ number_format($this->discountAmount, 2) }}</span>
                            @else
                                <span class="text-gray-500">$0.00</span>
                            @endif
                        </div>

                        {{-- Tax --}}
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center">
                                <span class="text-gray-600 mr-2">Tax</span>
                                <input type="number" wire:model.live="tax_rate" 
                                    step="0.01" min="0" max="100"
                                    class="w-16 text-xs rounded border-gray-300 py-1 px-2"
                                    placeholder="0">
                                <span class="ml-1 text-xs text-gray-500">%</span>
                            </div>
                            @if($this->taxAmount > 0)
                                <span class="font-medium text-gray-900">${{ number_format($this->taxAmount, 2) }}</span>
                            @else
                                <span class="text-gray-500">$0.00</span>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-gray-200">
                            <div class="flex justify-between">
                                <span class="text-base font-semibold text-gray-900">Total</span>
                                <span class="text-xl font-bold text-blue-600">${{ number_format($this->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-6 space-y-2">
                        <button type="button" wire:click="createInvoice"
                            class="w-full px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Create Invoice
                        </button>
                        <button type="button" wire:click="saveAsDraft"
                            class="w-full px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Save as Draft
                        </button>
                    </div>
                </div>

                {{-- Quick Tips --}}
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-900 mb-2">Quick Tips</h4>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• Invoice number is auto-generated</li>
                        <li>• Due date updates with payment terms</li>
                        <li>• Add multiple items or products</li>
                        <li>• Save as draft to finish later</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>