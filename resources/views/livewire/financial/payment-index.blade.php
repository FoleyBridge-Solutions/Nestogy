<div>
    @if($this->payments->total() > 0)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @if($this->totals['total_revenue'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <flux:icon name="currency-dollar" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Total Revenue</flux:text>
                    <flux:heading size="lg" class="text-green-600">${{ number_format($this->totals['total_revenue'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['this_month'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <flux:icon name="chart-bar" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">This Month</flux:text>
                    <flux:heading size="lg">${{ number_format($this->totals['this_month'], 2) }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['pending_count'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-amber-500 rounded-full flex items-center justify-center">
                            <flux:icon name="clock" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Pending</flux:text>
                    <flux:heading size="lg">{{ $this->totals['pending_count'] }}</flux:heading>
                </div>
            </flux:card>
            @endif

            @if($this->totals['failed_count'] > 0)
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center mb-2">
                        <div class="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
                            <flux:icon name="x-circle" variant="solid" class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <flux:text variant="muted" size="sm">Failed</flux:text>
                    <flux:heading size="lg">{{ $this->totals['failed_count'] }}</flux:heading>
                </div>
            </flux:card>
            @endif
        </div>
    @endif

    <div class="flex flex-wrap gap-4 mb-6">
        <flux:input 
            wire:model.live.debounce.300ms="search"
            placeholder="Search payments..."
            class="flex-1 max-w-md"
        />
        
        <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="completed">Completed</flux:select.option>
            <flux:select.option value="failed">Failed</flux:select.option>
            <flux:select.option value="refunded">Refunded</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="paymentMethodFilter" placeholder="All Methods">
            <flux:select.option value="">All Methods</flux:select.option>
            <flux:select.option value="credit_card">Credit Card</flux:select.option>
            <flux:select.option value="bank_transfer">Bank Transfer</flux:select.option>
            <flux:select.option value="check">Check</flux:select.option>
            <flux:select.option value="cash">Cash</flux:select.option>
            <flux:select.option value="paypal">PayPal</flux:select.option>
            <flux:select.option value="stripe">Stripe</flux:select.option>
            <flux:select.option value="other">Other</flux:select.option>
        </flux:select>
    </div>

    @if(count($selected) > 0)
    <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-center justify-between">
            <flux:text>
                <span class="font-semibold">{{ count($selected) }}</span> payment(s) selected
            </flux:text>
            
            <div class="flex items-center gap-2">
                <flux:button wire:click="bulkDelete" size="sm" variant="danger">
                    Delete
                </flux:button>
            </div>
        </div>
    </div>
    @endif

    <flux:card>
        @if($this->payments->count() > 0)
            <flux:table :paginate="$this->payments">
                <flux:table.columns>
                    <flux:table.column class="w-12">
                        <flux:checkbox wire:model.live="selectAll" />
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'payment_reference'" 
                        :direction="$sortDirection" 
                        wire:click="sort('payment_reference')"
                    >
                        Reference
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'client'" 
                        :direction="$sortDirection" 
                        wire:click="sort('client')"
                    >
                        Client
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'amount'" 
                        :direction="$sortDirection" 
                        wire:click="sort('amount')"
                    >
                        Amount
                    </flux:table.column>
                    
                    <flux:table.column>
                        Application Status
                    </flux:table.column>
                    
                    <flux:table.column>
                        Payment Method
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'status'" 
                        :direction="$sortDirection" 
                        wire:click="sort('status')"
                    >
                        Status
                    </flux:table.column>
                    
                    <flux:table.column 
                        sortable 
                        :sorted="$sortBy === 'payment_date'" 
                        :direction="$sortDirection" 
                        wire:click="sort('payment_date')"
                    >
                        Date
                    </flux:table.column>
                    
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->payments as $payment)
                        <flux:table.row :key="$payment->id">
                            <flux:table.cell>
                                <flux:checkbox wire:model.live="selected" :value="$payment->id" />
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div>
                                    <flux:text variant="strong">{{ $payment->payment_reference ?? 'No Reference' }}</flux:text>
                                    @if($payment->applications->count() > 0)
                                        <flux:text size="sm" variant="muted" class="block">
                                            {{ $payment->applications->count() }} {{ Str::plural('application', $payment->applications->count()) }}
                                        </flux:text>
                                    @endif
                                    @if($payment->gateway_transaction_id)
                                        <flux:text size="xs" variant="muted" class="block">{{ $payment->gateway_transaction_id }}</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($payment->client)
                                    <div>
                                        <flux:text>{{ $payment->client->name }}</flux:text>
                                        @if($payment->client->company_name)
                                            <flux:text size="sm" variant="muted" class="block">{{ $payment->client->company_name }}</flux:text>
                                        @endif
                                    </div>
                                @else
                                    <flux:text variant="muted">-</flux:text>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div>
                                    <flux:text variant="strong">${{ number_format($payment->amount, 2) }}</flux:text>
                                    @php
                                        $availableAmount = $payment->getAvailableAmount();
                                    @endphp
                                    @if($availableAmount > 0)
                                        <flux:text size="xs" variant="muted" class="block text-green-600">
                                            Available: ${{ number_format($availableAmount, 2) }}
                                        </flux:text>
                                    @endif
                                    @if($payment->gateway_fee)
                                        <flux:text size="xs" variant="muted" class="block">Fee: ${{ number_format($payment->gateway_fee, 2) }}</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @php
                                    $appStatus = $payment->application_status ?? 'unapplied';
                                    $appColor = match($appStatus) {
                                        'fully_applied' => 'green',
                                        'partially_applied' => 'amber',
                                        'unapplied' => 'zinc',
                                        default => 'zinc'
                                    };
                                @endphp
                                <flux:badge size="sm" :color="$appColor" inset="top bottom">
                                    {{ ucfirst(str_replace('_', ' ', $appStatus)) }}
                                </flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div>
                                    <flux:text>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</flux:text>
                                    @if($payment->gateway)
                                        <flux:text size="sm" variant="muted" class="block">{{ ucfirst(str_replace('_', ' ', $payment->gateway)) }}</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @php
                                    $statusColor = $this->statusColor[$payment->status] ?? 'zinc';
                                @endphp
                                <flux:badge size="sm" :color="$statusColor" inset="top bottom">
                                    {{ ucfirst($payment->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                @if($payment->payment_date)
                                    {{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') }}
                                @else
                                    <flux:text variant="muted">-</flux:text>
                                @endif
                            </flux:table.cell>
                            
                            <flux:table.cell>
                                <div class="flex items-center gap-1">
                                    <flux:button 
                                        href="{{ route('financial.payments.show', $payment) }}"
                                        size="sm"
                                        variant="ghost"
                                        icon="eye"
                                        title="View"
                                    />
                                    
                                    @if(in_array($payment->status, ['pending', 'failed']))
                                        <flux:button 
                                            href="{{ route('financial.payments.edit', $payment) }}"
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil"
                                            title="Edit"
                                        />
                                        
                                        <flux:button 
                                            wire:click="deletePayment({{ $payment->id }})"
                                            wire:confirm="Are you sure you want to delete this payment? This action cannot be undone."
                                            size="sm"
                                            variant="ghost"
                                            icon="trash"
                                            class="text-red-600 hover:text-red-700"
                                            title="Delete"
                                        />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <div class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <flux:icon name="currency-dollar" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-6" />
                    @if($search || $statusFilter || $paymentMethodFilter || $dateFrom || $dateTo)
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No payments found</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Try adjusting your filters or search terms</p>
                        <flux:button variant="ghost" wire:click="$set('search', ''); $set('statusFilter', ''); $set('paymentMethodFilter', ''); $set('dateFrom', ''); $set('dateTo', '');">
                            Clear all filters
                        </flux:button>
                    @else
                        <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No payments found</h3>
                        <p class="text-zinc-600 dark:text-zinc-400 mb-6">Get started by creating a new payment.</p>
                        <flux:button variant="primary" href="{{ route('financial.payments.create') }}">
                            Add Payment
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:card>
</div>
