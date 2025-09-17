<div>
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl" class="text-zinc-900 dark:text-white">
                        Invoice #{{ $invoice->invoice_number ?? $invoice->number }}
                    </flux:heading>
                    <flux:badge size="sm" :color="$this->statusColor" inset="top bottom">
                        {{ ucfirst($invoice->status) }}
                    </flux:badge>
                    @if($this->daysOverdue > 0)
                        <flux:badge size="sm" color="red" inset="top bottom">
                            {{ $this->daysOverdue }} days overdue
                        </flux:badge>
                    @endif
                </div>
                <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                    Created {{ $invoice->created_at->format('F j, Y') }} • 
                    Due {{ $invoice->due_date?->format('F j, Y') ?? 'N/A' }}
                </flux:text>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex items-center gap-2">
                @can('update', $invoice)
                    @if($invoice->status !== 'Paid')
                        <flux:button size="sm" variant="primary" icon="credit-card" wire:click="$set('showPaymentModal', true)">
                            Record Payment
                        </flux:button>
                    @endif
                @endcan
                
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" variant="ghost" icon="ellipsis-vertical" />
                    
                    <flux:menu>
                        <flux:menu.item icon="document-arrow-down" wire:click="downloadPdf">
                            Download PDF
                        </flux:menu.item>
                        
                        <button
                            type="button"
                            wire:click="$set('showEmailModal', true)"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                        >
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Send by Email
                        </button>
                        
                        <button
                            type="button"
                            wire:click="sendPhysicalMail"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                        >
                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                            </svg>
                            Send by Physical Mail
                        </button>
                        
                        @can('update', $invoice)
                            @if($invoice->status === 'Draft')
                                <flux:menu.item icon="paper-airplane" wire:click="markAsSent">
                                    Mark as Sent
                                </flux:menu.item>
                            @endif
                        @endcan
                        
                        <flux:menu.separator />
                        
                        @can('create', App\Models\Invoice::class)
                            <flux:menu.item icon="document-duplicate" wire:click="duplicateInvoice">
                                Duplicate Invoice
                            </flux:menu.item>
                        @endcan
                        
                        @can('update', $invoice)
                            <flux:menu.item icon="pencil" href="{{ route('financial.invoices.edit', $invoice) }}">
                                Edit Invoice
                            </flux:menu.item>
                        @endcan
                        
                        <flux:menu.item icon="printer" wire:click="printInvoice">
                            Print Invoice
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (Left 2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Client Information Card -->
            <flux:card class="overflow-hidden">
                <div class="p-6 bg-gradient-to-br from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50">
                    <div class="flex items-center justify-between mb-6">
                        <flux:heading size="lg" class="flex items-center gap-2 text-zinc-900 dark:text-white">
                            <flux:icon.user-circle class="size-5 text-zinc-400 dark:text-zinc-500" />
                            Client Information
                        </flux:heading>
                        <flux:button size="xs" variant="ghost" href="{{ route('clients.show', $invoice->client_id) }}" target="_blank">
                            View Client
                        </flux:button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <flux:text size="xs" class="uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                Company
                            </flux:text>
                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                {{ $invoice->client->name ?? 'N/A' }}
                            </flux:text>
                        </div>
                        
                        <div>
                            <flux:text size="xs" class="uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                Primary Contact
                            </flux:text>
                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                {{ $invoice->client->primary_contact ?? 'N/A' }}
                            </flux:text>
                        </div>
                        
                        <div>
                            <flux:text size="xs" class="uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                Email
                            </flux:text>
                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                {{ $invoice->client->email ?? 'N/A' }}
                            </flux:text>
                        </div>
                        
                        <div>
                            <flux:text size="xs" class="uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                Phone
                            </flux:text>
                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                {{ $invoice->client->phone ?? 'N/A' }}
                            </flux:text>
                        </div>
                        
                        @if($invoice->client->address)
                            <div class="md:col-span-2">
                                <flux:text size="xs" class="uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                    Billing Address
                                </flux:text>
                                <flux:text class="font-medium text-zinc-900 dark:text-white">
                                    {{ $invoice->client->address }}<br>
                                    {{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->zip }}
                                </flux:text>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>

            <!-- Invoice Items Card -->
            <flux:card class="overflow-hidden">
                <div class="p-6 bg-gradient-to-br from-zinc-50 to-white dark:from-zinc-800 dark:to-zinc-800/50">
                    <div class="flex items-center justify-between mb-6">
                        <flux:heading size="lg" class="flex items-center gap-2 text-zinc-900 dark:text-white">
                            <flux:icon.shopping-cart class="size-5 text-zinc-400 dark:text-zinc-500" />
                            Invoice Items
                        </flux:heading>
                        <flux:badge size="sm" :color="count($invoice->items) > 0 ? 'blue' : 'zinc'" inset="top bottom">
                            {{ count($invoice->items) }} {{ Str::plural('item', count($invoice->items)) }}
                        </flux:badge>
                    </div>
                    
                    @if($invoice->items->count() > 0)
                        <div class="overflow-x-auto -mx-6">
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Description</flux:table.column>
                                    <flux:table.column class="text-center">Qty</flux:table.column>
                                    <flux:table.column class="text-right">Rate</flux:table.column>
                                    <flux:table.column class="text-right">Tax</flux:table.column>
                                    <flux:table.column class="text-right">Amount</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach($invoice->items as $item)
                                        <flux:table.row wire:key="item-{{ $item->id }}">
                                            <flux:table.cell>
                                                <div>
                                                    <flux:text class="font-medium text-zinc-900 dark:text-white">
                                                        {{ $item->description }}
                                                    </flux:text>
                                                    @if($item->details || $item->notes)
                                                        <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400 mt-1">
                                                            {{ $item->details ?: $item->notes }}
                                                        </flux:text>
                                                    @endif
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell class="text-center text-zinc-700 dark:text-zinc-300">
                                                {{ $item->quantity }}
                                            </flux:table.cell>
                                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-300">
                                                ${{ number_format($item->price ?? 0, 2) }}
                                            </flux:table.cell>
                                            <flux:table.cell class="text-right text-zinc-700 dark:text-zinc-300">
                                                @if(($item->tax_rate ?? 0) > 0)
                                                    {{ number_format($item->tax_rate, 1) }}%
                                                @elseif($item->tax > 0)
                                                    ${{ number_format($item->tax, 2) }}
                                                @else
                                                    <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell class="text-right font-medium text-zinc-900 dark:text-white">
                                                ${{ number_format($item->total ?? $item->subtotal ?? 0, 2) }}
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                        
                        <!-- Totals Row -->
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700">
                            <div class="flex justify-end">
                                <div class="w-full max-w-xs space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <flux:text class="text-zinc-600 dark:text-zinc-400">Subtotal</flux:text>
                                        <flux:text class="font-medium text-zinc-900 dark:text-white">
                                            ${{ number_format($totals['subtotal'] ?? 0, 2) }}
                                        </flux:text>
                                    </div>
                                    @if(($totals['tax'] ?? 0) > 0)
                                        <div class="flex justify-between text-sm">
                                            <flux:text class="text-zinc-600 dark:text-zinc-400">Tax</flux:text>
                                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                                ${{ number_format($totals['tax'] ?? 0, 2) }}
                                            </flux:text>
                                        </div>
                                    @endif
                                    @if(($invoice->discount_amount ?? 0) > 0)
                                        <div class="flex justify-between text-sm">
                                            <flux:text class="text-zinc-600 dark:text-zinc-400">Discount</flux:text>
                                            <flux:text class="font-medium text-green-600 dark:text-green-400">
                                                -${{ number_format($invoice->discount_amount, 2) }}
                                            </flux:text>
                                        </div>
                                    @endif
                                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                        <div class="flex justify-between">
                                            <flux:text class="font-medium text-zinc-900 dark:text-white">Total</flux:text>
                                            <flux:text size="lg" class="font-bold text-zinc-900 dark:text-white">
                                                ${{ number_format($totals['total'] ?? $invoice->amount, 2) }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <flux:icon.shopping-cart class="size-12 text-zinc-300 dark:text-zinc-700 mx-auto mb-4" />
                            <flux:text class="text-zinc-500 dark:text-zinc-400">
                                No items have been added to this invoice yet.
                            </flux:text>
                            @can('update', $invoice)
                                <flux:button size="sm" variant="primary" href="{{ route('financial.invoices.edit', $invoice) }}" class="mt-4">
                                    Add Items
                                </flux:button>
                            @endcan
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Payment History -->
            @if($invoice->payments->count() > 0)
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Payment History</flux:heading>
                        <flux:badge color="green">{{ $invoice->payments->count() }} {{ Str::plural('Payment', $invoice->payments->count()) }}</flux:badge>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Date</flux:table.column>
                                <flux:table.column>Method</flux:table.column>
                                <flux:table.column>Reference</flux:table.column>
                                <flux:table.column class="text-right">Amount</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($invoice->payments as $payment)
                                    <flux:table.row wire:key="payment-{{ $payment->id }}">
                                        <flux:table.cell>
                                            <flux:text class="text-zinc-900 dark:text-white">
                                                {{ $payment->payment_date->format('M d, Y') }}
                                            </flux:text>
                                            <flux:text size="xs" class="text-zinc-500 dark:text-zinc-400">
                                                {{ $payment->payment_date->format('g:i A') }}
                                            </flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center gap-2">
                                                @switch($payment->payment_method)
                                                    @case('credit_card')
                                                        <flux:icon.credit-card class="w-4 h-4 text-zinc-400" />
                                                        @break
                                                    @case('bank_transfer')
                                                        <flux:icon.building-library class="w-4 h-4 text-zinc-400" />
                                                        @break
                                                    @case('check')
                                                        <flux:icon.document-check class="w-4 h-4 text-zinc-400" />
                                                        @break
                                                    @case('cash')
                                                        <flux:icon.banknotes class="w-4 h-4 text-zinc-400" />
                                                        @break
                                                    @default
                                                        <flux:icon.currency-dollar class="w-4 h-4 text-zinc-400" />
                                                @endswitch
                                                <flux:text class="text-zinc-700 dark:text-zinc-300">
                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                                </flux:text>
                                            </div>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                                {{ $payment->reference_number ?? '—' }}
                                            </flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell class="text-right">
                                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                                ${{ number_format($payment->amount, 2) }}
                                            </flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge size="sm" :color="$payment->status === 'completed' ? 'green' : 'yellow'" inset="top bottom">
                                                {{ ucfirst($payment->status) }}
                                            </flux:badge>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </flux:card>
            @endif

            <!-- Activity Timeline (Placeholder) -->
            @if(false) {{-- Enable when activity tracking is implemented --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Activity Timeline</flux:heading>
                    <div class="text-center py-8">
                        <flux:text class="text-zinc-500">Activity tracking coming soon</flux:text>
                    </div>
                </flux:card>
            @endif
        </div>

        <!-- Sidebar (Right 1/3) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Payment Summary Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Invoice Summary</flux:heading>
                
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-baseline">
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                Invoice Total
                            </flux:text>
                            <flux:text size="lg" class="font-semibold text-zinc-900 dark:text-white">
                                ${{ number_format($totals['total'] ?? $invoice->amount, 2) }}
                            </flux:text>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between items-baseline">
                            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">
                                Amount Paid
                            </flux:text>
                            <flux:text size="lg" class="font-semibold text-green-600 dark:text-green-400">
                                ${{ number_format($totals['paid'] ?? 0, 2) }}
                            </flux:text>
                        </div>
                    </div>
                    
                    <flux:separator />
                    
                    <div>
                        <div class="flex justify-between items-baseline">
                            <flux:text class="font-medium text-zinc-900 dark:text-white">
                                Balance Due
                            </flux:text>
                            <flux:text size="xl" class="font-bold {{ ($totals['balance'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                ${{ number_format($totals['balance'] ?? 0, 2) }}
                            </flux:text>
                        </div>
                    </div>
                    
                    @if(($totals['balance'] ?? 0) > 0 && $invoice->due_date)
                        @php
                            $daysUntilDue = now()->startOfDay()->diffInDays($invoice->due_date, false);
                            $isOverdue = $daysUntilDue < 0;
                            $dayCount = abs($daysUntilDue);
                        @endphp
                        <div class="mt-4 p-3 rounded-lg {{ $isOverdue ? 'bg-red-100 dark:bg-red-950/30' : 'bg-blue-100 dark:bg-blue-950/30' }}">
                            <flux:text size="xs" class="{{ $isOverdue ? 'text-red-700 dark:text-red-300' : 'text-blue-700 dark:text-blue-300' }}">
                                @if($isOverdue)
                                    <strong>{{ $dayCount }} {{ $dayCount == 1 ? 'day' : 'days' }} overdue</strong>
                                @else
                                    Due in {{ $dayCount }} {{ $dayCount == 1 ? 'day' : 'days' }}
                                @endif
                            </flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Invoice Details Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Invoice Details</flux:heading>
                
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                            Invoice Number
                        </flux:text>
                        <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">
                            #{{ $invoice->invoice_number ?? $invoice->number }}
                        </flux:text>
                    </div>
                    
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                            Invoice Date
                        </flux:text>
                        <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">
                            {{ $invoice->date?->format('M d, Y') ?? 'N/A' }}
                        </flux:text>
                    </div>
                    
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                            Due Date
                        </flux:text>
                        <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">
                            {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}
                        </flux:text>
                    </div>
                    
                    <div class="flex justify-between">
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                            Payment Terms
                        </flux:text>
                        <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">
                            {{ $invoice->payment_terms ?? 'Net 30' }}
                        </flux:text>
                    </div>
                    
                    @if($invoice->po_number)
                        <flux:separator />
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                PO Number
                            </flux:text>
                            <flux:text size="sm" class="font-medium text-zinc-900 dark:text-white">
                                {{ $invoice->po_number }}
                            </flux:text>
                        </div>
                    @endif
                    
                    @if($invoice->contract_id)
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                Contract
                            </flux:text>
                            <flux:link size="sm" href="{{ route('contracts.show', $invoice->contract_id) }}">
                                View Contract
                            </flux:link>
                        </div>
                    @endif
                    
                    @if($invoice->category)
                        <flux:separator />
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                                Category
                            </flux:text>
                            <flux:badge size="sm" color="zinc" inset="top bottom">
                                {{ $invoice->category->name }}
                            </flux:badge>
                        </div>
                    @endif
                </dl>
            </flux:card>

            <!-- Quick Actions Card -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
                
                <div class="space-y-2">
                    @can('update', $invoice)
                        @if($invoice->status !== 'Paid' && ($totals['balance'] ?? 0) > 0)
                            <flux:button size="sm" variant="primary" class="w-full" wire:click="$set('showPaymentModal', true)" icon="credit-card">
                                Record Payment
                            </flux:button>
                        @endif
                    @endcan
                    
                    <flux:button size="sm" variant="ghost" class="w-full" wire:click="downloadPdf" icon="document-arrow-down">
                        Download PDF
                    </flux:button>
                    
                    <flux:button size="sm" variant="ghost" class="w-full" wire:click="$set('showEmailModal', true)" icon="envelope">
                        Send by Email
                    </flux:button>
                    
                    <flux:button size="sm" variant="ghost" class="w-full" wire:click="sendPhysicalMail" icon="envelope-open">
                        Send by Physical Mail
                    </flux:button>
                    
                    <flux:button size="sm" variant="ghost" class="w-full" wire:click="printInvoice" icon="printer">
                        Print Invoice
                    </flux:button>
                    
                    @can('update', $invoice)
                        <flux:separator />
                        
                        <flux:button size="sm" variant="ghost" class="w-full" href="{{ route('financial.invoices.edit', $invoice) }}" icon="pencil">
                            Edit Invoice
                        </flux:button>
                    @endcan
                    
                    @can('create', App\Models\Invoice::class)
                        <flux:button size="sm" variant="ghost" class="w-full" wire:click="duplicateInvoice" icon="document-duplicate">
                            Duplicate Invoice
                        </flux:button>
                    @endcan
                </div>
            </flux:card>

            <!-- Notes -->
            @if($invoice->note)
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Notes</flux:heading>
                    <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400 whitespace-pre-wrap">
                        {{ $invoice->note }}
                    </flux:text>
                </flux:card>
            @endif

            <!-- Documents (Placeholder) -->
            @if(false) {{-- Enable when documents are implemented --}}
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Documents</flux:heading>
                    <div class="text-center py-4">
                        <flux:text size="sm" class="text-zinc-500">No documents attached</flux:text>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>

    <!-- Add Activity Function -->
    @push('scripts')
    <script>
        function addActivity(type, description) {
            // Placeholder for activity tracking
            console.log('Activity:', type, description);
        }
    </script>
    @endpush

    <!-- Payment Modal -->
    <flux:modal name="payment-modal" wire:model="showPaymentModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-zinc-900 dark:text-white">Record Payment</flux:heading>
                <flux:text class="mt-2">Record a payment for this invoice.</flux:text>
            </div>
            
            <form wire:submit.prevent="recordPayment" class="space-y-4">
                <flux:input 
                    label="Payment Amount" 
                    type="number" 
                    step="0.01" 
                    wire:model="paymentAmount" 
                    max="{{ $totals['balance'] ?? 0 }}" 
                    placeholder="${{ number_format($totals['balance'] ?? 0, 2) }}"
                />
                @error('paymentAmount') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:select wire:model="paymentMethod" label="Payment Method" placeholder="Choose payment method...">
                    <flux:select.option value="credit_card">Credit Card</flux:select.option>
                    <flux:select.option value="bank_transfer">Bank Transfer</flux:select.option>
                    <flux:select.option value="check">Check</flux:select.option>
                    <flux:select.option value="cash">Cash</flux:select.option>
                    <flux:select.option value="other">Other</flux:select.option>
                </flux:select>
                @error('paymentMethod') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:input 
                    label="Payment Date" 
                    type="date" 
                    wire:model="paymentDate" 
                />
                @error('paymentDate') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:input 
                    label="Reference Number (Optional)" 
                    wire:model="paymentReference" 
                    placeholder="Check #, transaction ID, etc."
                />
                
                <flux:textarea 
                    label="Notes (Optional)" 
                    wire:model="paymentNotes" 
                    rows="3" 
                    placeholder="Add any additional payment notes..."
                />
                @error('paymentNotes') <flux:error>{{ $message }}</flux:error> @enderror
                
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">Record Payment</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Email Modal -->
    <flux:modal name="email-modal" wire:model="showEmailModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-zinc-900 dark:text-white">Send Invoice by Email</flux:heading>
                <flux:text class="mt-2">Send this invoice to the client via email.</flux:text>
            </div>
            
            <form wire:submit.prevent="sendInvoiceEmail" class="space-y-4">
                <flux:input 
                    label="To" 
                    type="email" 
                    wire:model="emailTo" 
                    placeholder="Enter recipient email address"
                    required
                />
                @error('emailTo') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:input 
                    label="Subject" 
                    type="text" 
                    wire:model="emailSubject" 
                    placeholder="Enter email subject"
                    required
                />
                @error('emailSubject') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:textarea 
                    label="Message" 
                    wire:model="emailMessage" 
                    rows="5" 
                    placeholder="Enter email message"
                    required
                />
                @error('emailMessage') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:checkbox 
                    wire:model="attachPdf" 
                    label="Attach invoice PDF"
                />
                @error('attachPdf') <flux:error>{{ $message }}</flux:error> @enderror
                
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="sendInvoiceEmail" icon="envelope">Send Email</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
    
    <!-- Physical Mail Modal -->
    @livewire('physical-mail.send-mail-modal')
</div>