<div class="grid grid-cols-12 gap-6">
    
    <div class="col-span-12 lg:col-span-8 space-y-6">
        
        <flux:card>
            <flux:tab.group>
                <flux:tabs wire:model="activeTab">
                    <flux:tab name="details">Payment Details</flux:tab>
                    @if($this->payment->applications->count() > 0)
                    <flux:tab name="applications">Applications ({{ $this->payment->applications->count() }})</flux:tab>
                    @endif
                    @if($this->payment->notes)
                    <flux:tab name="notes">Notes</flux:tab>
                    @endif
                </flux:tabs>

                <flux:tab.panel name="details">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <flux:text size="sm" variant="muted">Payment Date</flux:text>
                                <flux:text>{{ $this->payment->payment_date ? $this->payment->payment_date->format('M d, Y') : '-' }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="sm" variant="muted">Payment Method</flux:text>
                                <flux:text>{{ ucfirst(str_replace('_', ' ', $this->payment->payment_method)) }}</flux:text>
                            </div>
                            
                            <div>
                                <flux:text size="sm" variant="muted">Gateway</flux:text>
                                <flux:text>{{ $this->payment->gateway ? ucfirst(str_replace('_', ' ', $this->payment->gateway)) : 'Manual Entry' }}</flux:text>
                            </div>
                            
                            @if($this->payment->gateway_transaction_id)
                            <div>
                                <flux:text size="sm" variant="muted">Transaction ID</flux:text>
                                <flux:text>{{ $this->payment->gateway_transaction_id }}</flux:text>
                            </div>
                            @endif
                            
                            <div>
                                <flux:text size="sm" variant="muted">Currency</flux:text>
                                <flux:text>{{ strtoupper($this->payment->currency) }}</flux:text>
                            </div>
                            
                            @if($this->payment->gateway_fee)
                            <div>
                                <flux:text size="sm" variant="muted">Gateway Fee</flux:text>
                                <flux:text>${{ number_format($this->payment->gateway_fee, 2) }}</flux:text>
                            </div>
                            @endif
                        </div>
                        
                        @if($this->payment->metadata)
                        <flux:separator />
                        <div>
                            <flux:text size="sm" variant="muted" class="mb-2">Additional Information</flux:text>
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4">
                                <pre class="text-xs">{{ json_encode($this->payment->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>
                </flux:tab.panel>

                @if($this->payment->applications->count() > 0)
                <flux:tab.panel name="applications">
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Invoice</flux:table.column>
                            <flux:table.column>Applied Amount</flux:table.column>
                            <flux:table.column>Applied Date</flux:table.column>
                            <flux:table.column>Applied By</flux:table.column>
                            <flux:table.column>Actions</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->payment->applications as $application)
                            <flux:table.row :key="'app-' . $application->id">
                                <flux:table.cell>
                                    @if($application->applicable_type === 'App\\Models\\Invoice')
                                        <div>
                                            <flux:text variant="strong">Invoice #{{ $application->applicable->getFullNumber() }}</flux:text>
                                            <flux:text size="sm" variant="muted" class="block">
                                                Balance: ${{ number_format($application->applicable->getBalance(), 2) }}
                                            </flux:text>
                                        </div>
                                    @else
                                        <flux:text>{{ class_basename($application->applicable_type) }} #{{ $application->applicable_id }}</flux:text>
                                    @endif
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    <flux:text variant="strong">${{ number_format($application->amount, 2) }}</flux:text>
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    {{ $application->applied_at ? $application->applied_at->format('M d, Y') : '-' }}
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    {{ $application->appliedBy->name ?? 'System' }}
                                </flux:table.cell>
                                
                                <flux:table.cell>
                                    @if(!$application->unapplied_at)
                                    <form action="{{ route('financial.payment-applications.destroy', $application) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button 
                                            type="submit"
                                            size="sm"
                                            variant="ghost"
                                            icon="x-mark"
                                            onclick="return confirm('Are you sure you want to unapply this payment?')"
                                        >
                                            Unapply
                                        </flux:button>
                                    </form>
                                    @else
                                    <flux:badge size="sm" color="zinc">Unapplied</flux:badge>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:tab.panel>
                @endif

                @if($this->payment->notes)
                <flux:tab.panel name="notes">
                    <flux:text class="text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap">{{ $this->payment->notes }}</flux:text>
                </flux:tab.panel>
                @endif
            </flux:tab.group>
        </flux:card>
        
    </div>

    <div class="col-span-12 lg:col-span-4 space-y-6">
        
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Payment Status</flux:heading>
                <flux:badge 
                    color="{{ match($this->payment->status) {
                        'pending' => 'amber',
                        'completed' => 'green',
                        'failed' => 'red',
                        'refunded' => 'zinc',
                        default => 'zinc'
                    } }}"
                >
                    {{ ucfirst($this->payment->status) }}
                </flux:badge>
            </div>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Client</flux:text>
                    <flux:text size="sm" class="font-medium">{{ $this->payment->client->name }}</flux:text>
                </div>
                
                <flux:separator />
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Total Amount</flux:text>
                    <flux:text class="font-semibold text-lg">${{ number_format($this->payment->amount, 2) }}</flux:text>
                </div>
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Applied Amount</flux:text>
                    <flux:text size="sm" class="font-medium text-green-600">${{ number_format($this->payment->applied_amount, 2) }}</flux:text>
                </div>
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Available Amount</flux:text>
                    <flux:text size="sm" class="font-medium {{ $this->payment->getAvailableAmount() > 0 ? 'text-blue-600' : '' }}">
                        ${{ number_format($this->payment->getAvailableAmount(), 2) }}
                    </flux:text>
                </div>
                
                <flux:separator />
                
                <div class="flex justify-between items-center">
                    <flux:text size="sm" class="text-zinc-500">Application Status</flux:text>
                    @php
                        $appStatus = $this->payment->application_status ?? 'unapplied';
                        $appColor = match($appStatus) {
                            'fully_applied' => 'green',
                            'partially_applied' => 'amber',
                            'unapplied' => 'zinc',
                            default => 'zinc'
                        };
                    @endphp
                    <flux:badge size="sm" :color="$appColor">
                        {{ ucfirst(str_replace('_', ' ', $appStatus)) }}
                    </flux:badge>
                </div>
            </div>
        </flux:card>

        @if($this->payment->getAvailableAmount() > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">Available Actions</flux:heading>
            <div class="space-y-2">
                <flux:text size="sm" variant="muted" class="mb-3">
                    This payment has ${{ number_format($this->payment->getAvailableAmount(), 2) }} available to apply to invoices.
                </flux:text>
                
                <form action="{{ route('financial.payments.apply', $this->payment) }}" method="POST">
                    @csrf
                    <flux:button type="submit" variant="primary" class="w-full" icon="currency-dollar">
                        Apply to Invoices
                    </flux:button>
                </form>
            </div>
        </flux:card>
        @endif

        <flux:card>
            <flux:heading size="lg" class="mb-4">Audit Information</flux:heading>
            <div class="space-y-3">
                @if($this->payment->processedBy)
                <div>
                    <flux:text size="sm" variant="muted">Processed By</flux:text>
                    <flux:text size="sm">{{ $this->payment->processedBy->name }}</flux:text>
                </div>
                @endif
                
                <div>
                    <flux:text size="sm" variant="muted">Created</flux:text>
                    <flux:text size="sm">{{ $this->payment->created_at->format('M d, Y \a\t g:i A') }}</flux:text>
                </div>
                
                @if($this->payment->updated_at->ne($this->payment->created_at))
                <div>
                    <flux:text size="sm" variant="muted">Last Updated</flux:text>
                    <flux:text size="sm">{{ $this->payment->updated_at->format('M d, Y \a\t g:i A') }}</flux:text>
                </div>
                @endif
            </div>
        </flux:card>

    </div>
</div>
