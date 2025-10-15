<div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Invoice Allocation (2/3 width) -->
        <div class="lg:col-span-2">
            <flux:card>
                <flux:heading size="lg" class="mb-6">Outstanding Invoices</flux:heading>

                <!-- Client Selection -->
                <flux:field class="mb-6">
                    <flux:label>Client *</flux:label>
                    <flux:select wire:model.live="client_id" placeholder="Select a client">
                        @foreach($this->clients as $client)
                            <flux:select.option value="{{ $client->id }}">
                                {{ $client->name }}{{ $client->company_name ? ' (' . $client->company_name . ')' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>

                @if($client_id && count($invoices) > 0)
                    <!-- Payment Amount Input -->
                    <flux:field class="mb-6">
                        <flux:label>Payment Amount *</flux:label>
                        <flux:input 
                            wire:model.live.debounce.300ms="amount" 
                            type="number" 
                            step="0.01" 
                            min="0.01"
                            placeholder="0.00"
                            icon="currency-dollar"
                            class="text-xl font-bold"
                        />
                        <flux:error name="amount" />
                    </flux:field>

                    <!-- Quick Actions -->
                    <div class="flex gap-2 mb-6">
                        <flux:button 
                            wire:click="payOldestFirst"
                            variant="outline"
                            size="sm"
                            icon="clock"
                        >
                            Pay Oldest First
                        </flux:button>
                        <flux:button 
                            wire:click="payAllInvoices"
                            variant="outline"
                            size="sm"
                            icon="banknotes"
                        >
                            Distribute Across All
                        </flux:button>
                        <flux:button 
                            wire:click="clearAllocation"
                            variant="ghost"
                            size="sm"
                            icon="x-mark"
                        >
                            Clear
                        </flux:button>
                    </div>

                    <!-- Allocation Summary -->
                    @if($amount > 0)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <flux:text size="xs" variant="muted" class="block mb-1">Payment Amount</flux:text>
                                <flux:text size="lg" variant="strong" class="text-blue-700 dark:text-blue-300">
                                    ${{ number_format($amount, 2) }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" variant="muted" class="block mb-1">Total Allocated</flux:text>
                                <flux:text size="lg" variant="strong" class="text-green-700 dark:text-green-300">
                                    ${{ number_format($totalAllocated, 2) }}
                                </flux:text>
                            </div>
                            <div>
                                <flux:text size="xs" variant="muted" class="block mb-1">Remaining</flux:text>
                                <flux:text 
                                    size="lg" 
                                    variant="strong"
                                    class="{{ $remainingAmount < 0 ? 'text-red-700 dark:text-red-300' : ($remainingAmount > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-green-700 dark:text-green-300') }}"
                                >
                                    ${{ number_format($remainingAmount, 2) }}
                                </flux:text>
                            </div>
                        </div>
                        @if($remainingAmount > 0)
                        <flux:text size="xs" variant="muted" class="mt-2 block">
                            💡 Remaining amount will create a ${{ number_format($remainingAmount, 2) }} client credit
                        </flux:text>
                        @elseif($remainingAmount < 0)
                        <flux:text size="xs" class="mt-2 block text-red-600 dark:text-red-400">
                            ⚠️ Total allocation exceeds payment amount
                        </flux:text>
                        @else
                        <flux:text size="xs" variant="muted" class="mt-2 block text-green-600 dark:text-green-400">
                            ✓ Payment fully allocated
                        </flux:text>
                        @endif
                    </div>
                    @endif

                    <!-- Invoice Table -->
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="w-16">Apply</flux:table.column>
                            <flux:table.column>Invoice</flux:table.column>
                            <flux:table.column>Balance</flux:table.column>
                            <flux:table.column>Amount to Apply</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($invoices as $invoice)
                                @php
                                    $balance = $invoice->getBalance();
                                    $allocated = floatval($allocations[$invoice->id] ?? 0);
                                    $status = $this->getAllocationStatus($invoice->id, $balance);
                                    $isOverdue = $invoice->due_date && $invoice->due_date->isPast();
                                @endphp
                                <flux:table.row :key="'invoice-' . $invoice->id">
                                    <flux:table.cell>
                                        <flux:checkbox 
                                            wire:model.live="selectedInvoices.{{ $invoice->id }}"
                                        />
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <div>
                                            <flux:text variant="strong">{{ $invoice->getFullNumber() }}</flux:text>
                                            <flux:text size="xs" variant="muted" class="block">
                                                Due: {{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'No due date' }}
                                                @if($isOverdue)
                                                    <flux:badge size="xs" color="red" inset="top bottom" class="ml-1">Overdue</flux:badge>
                                                @endif
                                            </flux:text>
                                        </div>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <flux:text variant="strong">${{ number_format($balance, 2) }}</flux:text>
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <flux:input 
                                            wire:model.live.debounce.300ms="allocations.{{ $invoice->id }}"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $balance }}"
                                            placeholder="0.00"
                                            class="w-32"
                                        />
                                    </flux:table.cell>
                                    
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="$status['color']" inset="top bottom">
                                            {{ $status['label'] }}
                                        </flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>

                @elseif($client_id)
                    <div class="text-center py-12">
                        <flux:icon name="document-text" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                        <flux:heading size="lg" class="mb-2">No Unpaid Invoices</flux:heading>
                        <flux:text variant="muted">This client has no outstanding invoices to apply payments to.</flux:text>
                        <div class="mt-6">
                            <flux:checkbox wire:model="auto_apply" />
                            <flux:text class="ml-2">Record payment without invoice allocation (will be available for future use)</flux:text>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <flux:icon name="user-group" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" />
                        <flux:text variant="muted">Select a client to view outstanding invoices</flux:text>
                    </div>
                @endif
            </flux:card>
        </div>

        <!-- Right Column: Payment Details (1/3 width) -->
        <div class="lg:col-span-1">
            <flux:card class="sticky top-6">
                <flux:heading size="lg" class="mb-6">Payment Details</flux:heading>
                
                <div class="space-y-6">
                    <!-- Core Payment Info -->
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>How was this paid? *</flux:label>
                            <flux:select wire:model="payment_method" placeholder="Select payment method">
                                @foreach($this->paymentMethods as $key => $label)
                                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="payment_method" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Payment Date *</flux:label>
                            <flux:input 
                                wire:model="payment_date" 
                                type="date"
                            />
                            <flux:error name="payment_date" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Reference Number</flux:label>
                            <flux:input 
                                wire:model="payment_reference" 
                                type="text"
                                placeholder="Check #, Confirmation #, etc."
                            />
                            <flux:description>Optional - auto-generated if blank</flux:description>
                            <flux:error name="payment_reference" />
                        </flux:field>
                    </div>

                    <!-- Optional Details (Collapsed by default) -->
                    <details class="group">
                        <summary class="cursor-pointer text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-zinc-100 flex items-center gap-2">
                            <flux:icon name="chevron-right" class="size-4 transition-transform group-open:rotate-90" />
                            Additional Details (Optional)
                        </summary>
                        <div class="mt-4 space-y-4 pl-6">
                            <flux:field>
                                <flux:label>Payment Processor</flux:label>
                                <flux:select wire:model="gateway">
                                    @foreach($this->gateways as $key => $label)
                                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:description>For online payments only</flux:description>
                            </flux:field>

                            <flux:field>
                                <flux:label>Transaction ID</flux:label>
                                <flux:input 
                                    wire:model="gateway_transaction_id" 
                                    type="text"
                                    placeholder="From payment processor"
                                />
                            </flux:field>

                            <flux:field>
                                <flux:label>Processing Fee</flux:label>
                                <flux:input 
                                    wire:model="gateway_fee" 
                                    type="number" 
                                    step="0.01" 
                                    min="0"
                                    placeholder="0.00"
                                    icon="currency-dollar"
                                />
                                <flux:description>Fee charged by processor</flux:description>
                            </flux:field>

                            <flux:field>
                                <flux:label>Currency</flux:label>
                                <flux:select wire:model="currency">
                                    @foreach($this->currencies as $code => $name)
                                        <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>
                    </details>

                    <flux:field>
                        <flux:label>Notes</flux:label>
                        <flux:textarea 
                            wire:model="notes" 
                            rows="3"
                            placeholder="Any additional information about this payment..."
                        />
                    </flux:field>

                    <flux:separator />

                    <div class="flex justify-between items-center gap-3">
                        <flux:button 
                            variant="ghost" 
                            href="{{ route('financial.payments.index') }}"
                        >
                            Cancel
                        </flux:button>
                        
                        <flux:button 
                            type="submit" 
                            variant="primary"
                            icon="check"
                            wire:click="save"
                        >
                            Create Payment
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
