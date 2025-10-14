<div>
    <div class="mb-6">
        <flux:heading size="xl">Edit Invoice #{{ $prefix }}-{{ $number }}</flux:heading>
        <flux:text variant="muted">Update invoice details and line items</flux:text>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">


            <flux:card>
                <div class="flex justify-between items-center mb-6">
                    <flux:heading size="lg">Line Items</flux:heading>
                    <div class="flex gap-2">
                        @if(count($this->products) > 0)
                            <flux:select 
                                wire:change="addProductAsItem($event.target.value)" 
                                placeholder="Add product"
                                class="w-48"
                            >
                                @foreach($this->products as $product)
                                    <flux:select.option value="{{ $product->id }}">
                                        {{ $product->name }} - ${{ number_format($product->price ?? 0, 2) }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
                        <flux:button 
                            wire:click="openItemModal" 
                            icon="plus"
                            variant="primary"
                        >
                            Add Item
                        </flux:button>
                    </div>
                </div>

                @if(count($items) > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Item</flux:table.column>
                            <flux:table.column class="text-center">Quantity</flux:table.column>
                            <flux:table.column class="text-right">Price</flux:table.column>
                            <flux:table.column class="text-right">Total</flux:table.column>
                            <flux:table.column class="text-center">Actions</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($items as $index => $item)
                                <flux:table.row :key="$index">
                                    <flux:table.cell>
                                        <div>
                                            <flux:text variant="strong">{{ $item['name'] }}</flux:text>
                                            @if(!empty($item['description']))
                                                <flux:text size="sm" variant="muted" class="block">
                                                    {{ Str::limit($item['description'], 50) }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-center">
                                        {{ $item['quantity'] }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        ${{ number_format($item['price'], 2) }}
                                    </flux:table.cell>
                                    <flux:table.cell class="text-right">
                                        <flux:text variant="strong">
                                            ${{ number_format($item['quantity'] * $item['price'], 2) }}
                                        </flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <flux:button 
                                                wire:click="editItem({{ $index }})"
                                                size="sm"
                                                variant="ghost"
                                                icon="pencil"
                                            />
                                            <flux:button 
                                                wire:click="removeItem({{ $index }})"
                                                wire:confirm="Remove this item?"
                                                size="sm"
                                                variant="ghost"
                                                icon="trash"
                                                class="text-red-600 hover:text-red-700"
                                            />
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="text-center py-12">
                        <flux:icon name="document-text" class="w-12 h-12 text-zinc-300 mx-auto mb-4" />
                        <flux:text variant="muted">No items added yet</flux:text>
                        <flux:text size="sm" variant="muted" class="block mt-1">
                            Add products or custom items to your invoice
                        </flux:text>
                    </div>
                @endif
                <flux:error name="items" />
            </flux:card>

            <flux:card>
                <flux:heading size="lg" class="mb-6">Additional Information</flux:heading>
                
                <div class="space-y-6">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea 
                            wire:model="scope" 
                            rows="2"
                            placeholder="Brief description of the invoice..."
                        />
                    </flux:field>

                    <flux:field>
                        <flux:label>Internal Notes</flux:label>
                        <flux:textarea 
                            wire:model="note" 
                            rows="3"
                            placeholder="Notes for internal use (not shown to client)..."
                        />
                    </flux:field>

                    <flux:field>
                        <flux:label>Terms & Conditions</flux:label>
                        <flux:textarea 
                            wire:model="terms_conditions" 
                            rows="3"
                            placeholder="Payment terms and conditions..."
                        />
                    </flux:field>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-1">
            <div class="sticky top-4 space-y-6">
                <flux:card>
                    <flux:heading size="base" class="mb-4">Invoice Information</flux:heading>
                    
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label badge="Required">Client</flux:label>
                            <flux:select wire:model.live="client_id" placeholder="Select a client">
                                @foreach($this->clients as $client)
                                    <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="client_id" />
                        </flux:field>

                        @if($this->selectedClient)
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg space-y-1">
                                <flux:text variant="strong">{{ $this->selectedClient->name }}</flux:text>
                                @if($this->selectedClient->company_name)
                                    <flux:text size="sm" variant="muted" class="block">
                                        {{ $this->selectedClient->company_name }}
                                    </flux:text>
                                @endif
                                @if($this->selectedClient->email)
                                    <flux:text size="sm" variant="muted" class="block">
                                        {{ $this->selectedClient->email }}
                                    </flux:text>
                                @endif
                                @if($this->selectedClient->phone)
                                    <flux:text size="sm" variant="muted" class="block">
                                        {{ $this->selectedClient->phone }}
                                    </flux:text>
                                @endif
                            </div>
                        @endif

                        <flux:separator variant="subtle" />

                        <div class="grid grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label badge="Required">Category</flux:label>
                                <flux:select wire:model="category_id" placeholder="Select">
                                    @foreach($this->categories as $category)
                                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="category_id" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Currency</flux:label>
                                <flux:select wire:model="currency_code">
                                    <flux:select.option value="USD">USD</flux:select.option>
                                    <flux:select.option value="EUR">EUR</flux:select.option>
                                    <flux:select.option value="GBP">GBP</flux:select.option>
                                    <flux:select.option value="CAD">CAD</flux:select.option>
                                </flux:select>
                            </flux:field>
                        </div>

                        <flux:separator variant="subtle" />

                        <div class="grid grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label badge="Required">Invoice Date</flux:label>
                                <flux:input 
                                    type="date" 
                                    wire:model.live="invoice_date"
                                />
                                <flux:error name="invoice_date" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Payment Terms</flux:label>
                                <flux:select wire:model.live="payment_terms">
                                    <flux:select.option value="0">Due on receipt</flux:select.option>
                                    <flux:select.option value="7">Net 7</flux:select.option>
                                    <flux:select.option value="14">Net 14</flux:select.option>
                                    <flux:select.option value="30">Net 30</flux:select.option>
                                    <flux:select.option value="45">Net 45</flux:select.option>
                                    <flux:select.option value="60">Net 60</flux:select.option>
                                    <flux:select.option value="90">Net 90</flux:select.option>
                                </flux:select>
                            </flux:field>

                            <flux:field>
                                <flux:label badge="Required">Due Date</flux:label>
                                <flux:input 
                                    type="date" 
                                    wire:model="due_date"
                                />
                                <flux:error name="due_date" />
                            </flux:field>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <flux:heading size="base" class="mb-4">Summary</flux:heading>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between pt-2">
                            <flux:heading size="base">Total</flux:heading>
                            <flux:heading size="lg" class="text-blue-600">
                                ${{ number_format($this->total, 2) }}
                            </flux:heading>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <flux:button 
                            wire:click="update"
                            variant="primary"
                            class="w-full"
                        >
                            Update Invoice
                        </flux:button>
                        <flux:button 
                            href="{{ route('financial.invoices.show', $invoice->id) }}"
                            variant="ghost"
                            class="w-full"
                        >
                            Cancel
                        </flux:button>
                    </div>
                </flux:card>

                <flux:callout variant="warning" icon="exclamation-triangle">
                    <flux:text size="sm">
                        <strong>Draft Invoice:</strong><br>
                        Only draft invoices can be edited. Once sent, invoices cannot be modified.
                    </flux:text>
                </flux:callout>
            </div>
        </div>
    </div>

    <flux:modal wire:model="showItemModal" name="item-form" class="md:w-[600px]">
        <form wire:submit="saveItem">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editingItemIndex !== null ? 'Edit Item' : 'Add Item' }}
                    </flux:heading>
                    <flux:text variant="muted" class="mt-2">
                        {{ $editingItemIndex !== null ? 'Update item details' : 'Add a new line item to the invoice' }}
                    </flux:text>
                </div>

                <flux:field>
                    <flux:label badge="Required">Item Name</flux:label>
                    <flux:input 
                        wire:model="itemForm.name"
                        placeholder="Enter item name"
                        autofocus
                    />
                    <flux:error name="itemForm.name" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label badge="Required">Quantity</flux:label>
                        <flux:input 
                            type="number" 
                            wire:model="itemForm.quantity" 
                            step="0.01" 
                            min="0.01"
                        />
                        <flux:error name="itemForm.quantity" />
                    </flux:field>

                    <flux:field>
                        <flux:label badge="Required">Unit Price</flux:label>
                        <flux:input 
                            type="number" 
                            wire:model="itemForm.price" 
                            step="0.01" 
                            min="0"
                        />
                        <flux:error name="itemForm.price" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea 
                        wire:model="itemForm.description" 
                        rows="3"
                        placeholder="Optional item description..."
                    />
                </flux:field>

                <div class="flex justify-end gap-2">
                    <flux:button 
                        type="button" 
                        wire:click="closeItemModal"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button 
                        type="submit"
                        variant="primary"
                    >
                        {{ $editingItemIndex !== null ? 'Update Item' : 'Add Item' }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
