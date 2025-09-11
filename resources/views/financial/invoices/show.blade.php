@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->getFullNumber())

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-full px-4 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <flux:heading size="2xl" class="flex items-center gap-3">
                    <flux:icon.document-text class="size-8 text-blue-500" />
                    Invoice #{{ $invoice->getFullNumber() }}
                </flux:heading>
                <flux:text class="mt-1 text-zinc-500">{{ $invoice->client->name }}</flux:text>
            </div>
            <div class="flex items-center gap-3">
                <flux:button href="{{ route('financial.invoices.index') }}" variant="outline" icon="arrow-left">
                    Back to Invoices
                </flux:button>
                
                <!-- Actions Dropdown -->
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon:trailing="chevron-down" icon="ellipsis-vertical" variant="outline" size="sm" />
                    <flux:menu>
                        @if($invoice->isDraft())
                        <flux:menu.item href="{{ route('financial.invoices.edit', $invoice) }}" icon="pencil">
                            Edit Invoice
                        </flux:menu.item>
                        <flux:menu.separator />
                        @endif
                        <flux:menu.item icon="document-arrow-down">
                            Download PDF
                        </flux:menu.item>
                        <flux:menu.item icon="envelope">
                            Send by Email
                        </flux:menu.item>
                        <flux:menu.item icon="document-duplicate">
                            Duplicate Invoice
                        </flux:menu.item>
                        @if($invoice->status !== 'Paid')
                        <flux:menu.separator />
                        <flux:menu.item icon="currency-dollar">
                            Record Payment
                        </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <!-- Main Content Grid: 2/3 + 1/3 layout -->
        <div class="grid grid-cols-12 gap-6">

            <!-- Left Column: 2/3 width - Main Content -->
            <div class="col-span-12 lg:col-span-8 space-y-6">
                
                <!-- Invoice Items -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Invoice Items</flux:heading>
                        @if($invoice->items->count() === 0)
                        <flux:badge color="amber">No Items</flux:badge>
                        @else
                        <flux:badge color="blue">{{ $invoice->items->count() }} Items</flux:badge>
                        @endif
                    </div>
                    
                    @if($invoice->items->count() > 0)
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Description</flux:table.column>
                            <flux:table.column>Quantity</flux:table.column>
                            <flux:table.column>Rate</flux:table.column>
                            <flux:table.column>Amount</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($invoice->items as $item)
                            <flux:table.row wire:key="item-{{ $item->id }}">
                                <flux:table.cell>
                                    <div>
                                        <flux:text class="font-medium">{{ $item->description }}</flux:text>
                                        @if($item->details)
                                        <flux:text size="sm" class="text-zinc-500 mt-1">{{ $item->details }}</flux:text>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>{{ $item->quantity }}</flux:table.cell>
                                <flux:table.cell>${{ number_format($item->rate, 2) }}</flux:table.cell>
                                <flux:table.cell variant="strong">${{ number_format($item->amount, 2) }}</flux:table.cell>
                            </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                    @else
                    <div class="text-center py-12">
                        <flux:icon.document-plus class="size-12 text-zinc-300 mx-auto mb-4" />
                        <flux:text class="text-zinc-500">No items added to this invoice yet.</flux:text>
                    </div>
                    @endif
                </flux:card>

                <!-- Payments -->
                @if($invoice->payments->count() > 0)
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Payment History</flux:heading>
                        <flux:badge color="green">{{ $invoice->payments->count() }} {{ Str::plural('Payment', $invoice->payments->count()) }}</flux:badge>
                    </div>
                    
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Date</flux:table.column>
                            <flux:table.column>Method</flux:table.column>
                            <flux:table.column>Amount</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($invoice->payments as $payment)
                            <flux:table.row :key="'payment-' . $payment->id">
                                <flux:table.cell>
                                    <flux:tooltip content="{{ $payment->payment_date->format('l, F j, Y \a\t g:i A') }}">
                                        <div>{{ $payment->payment_date->format('M d, Y') }}</div>
                                    </flux:tooltip>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        @php
                                        $methodIcon = match(strtolower($payment->payment_method)) {
                                            'credit card', 'card' => 'credit-card',
                                            'bank transfer', 'wire' => 'banknotes',
                                            'check' => 'document-text',
                                            'cash' => 'currency-dollar',
                                            default => 'currency-dollar'
                                        };
                                        @endphp
                                        <flux:icon name="{{ $methodIcon }}" class="size-4 text-zinc-400" />
                                        {{ $payment->payment_method }}
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell variant="strong">${{ number_format($payment->amount, 2) }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge 
                                        color="{{ match($payment->status) {
                                            'completed' => 'green',
                                            'pending' => 'yellow', 
                                            'failed' => 'red',
                                            default => 'zinc'
                                        } }}"
                                        size="sm" 
                                        inset="top bottom"
                                    >
                                        {{ ucfirst($payment->status) }}
                                    </flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
                @endif

                <!-- Notes -->
                @if($invoice->note)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Notes</flux:heading>
                    <flux:text class="text-zinc-700 leading-relaxed">{{ $invoice->note }}</flux:text>
                </flux:card>
                @endif
            </div>

            <!-- Right Column: 1/3 width - Sidebar -->
            <div class="col-span-12 lg:col-span-4 space-y-6">
                
                <!-- Invoice Status -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Invoice Status</flux:heading>
                        <flux:badge 
                            color="{{ match($invoice->status) {
                                'Draft' => 'zinc',
                                'Sent' => 'blue', 
                                'Paid' => 'green',
                                'Overdue' => 'red',
                                default => 'zinc'
                            } }}"
                        >
                            {{ $invoice->status }}
                        </flux:badge>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <flux:text size="sm" class="text-zinc-500">Invoice Date</flux:text>
                            <flux:tooltip content="{{ $invoice->date->format('l, F j, Y') }}">
                                <flux:text size="sm">{{ $invoice->date->format('M d, Y') }}</flux:text>
                            </flux:tooltip>
                        </div>
                        
                        <flux:separator />
                        
                        <div class="flex justify-between items-center">
                            <flux:text size="sm" class="text-zinc-500">Due Date</flux:text>
                            <flux:tooltip content="{{ $invoice->due_date->format('l, F j, Y') }}">
                                <flux:text size="sm" class="{{ $invoice->due_date->isPast() && $invoice->status !== 'Paid' ? 'text-red-600 font-medium' : '' }}">
                                    {{ $invoice->due_date->format('M d, Y') }}
                                    @if($invoice->due_date->isPast() && $invoice->status !== 'Paid')
                                        <flux:icon.exclamation-triangle class="size-4 inline ml-1 text-red-500" />
                                    @endif
                                </flux:text>
                            </flux:tooltip>
                        </div>
                        
                        <flux:separator />
                        
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500">Amount</flux:text>
                            <flux:text size="sm" class="font-medium">{{ $invoice->getFormattedAmount() }}</flux:text>
                        </div>
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500">Balance</flux:text>
                            <flux:text size="sm" class="font-medium {{ $totals['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $invoice->getFormattedBalance() }}
                                @if($totals['balance'] == 0)
                                    <flux:icon.check-circle class="size-4 inline ml-1 text-green-500" />
                                @endif
                            </flux:text>
                        </div>
                    </div>
                </flux:card>

                <!-- Client Information -->
                <flux:card>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Client Information</flux:heading>
                        <flux:tooltip content="View client details">
                            <flux:button variant="ghost" size="sm" icon="eye" href="{{ route('clients.index') }}" />
                        </flux:tooltip>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Client Name</flux:text>
                            <flux:text class="font-medium">{{ $invoice->client->name }}</flux:text>
                            @if($invoice->client->company_name)
                            <flux:text size="sm" class="text-zinc-500">{{ $invoice->client->company_name }}</flux:text>
                            @endif
                        </div>
                        
                        <flux:separator />
                        
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Category</flux:text>
                            @if($invoice->category)
                            <flux:badge variant="outline" size="sm">{{ $invoice->category->name }}</flux:badge>
                            @else
                            <flux:text size="sm" class="text-zinc-400 italic">No category assigned</flux:text>
                            @endif
                        </div>
                        
                        @if($invoice->client->email)
                        <flux:separator />
                        <div>
                            <flux:text size="sm" class="text-zinc-500">Email</flux:text>
                            <flux:text size="sm">{{ $invoice->client->email }}</flux:text>
                        </div>
                        @endif
                    </div>
                </flux:card>

                <!-- Invoice Summary -->
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Invoice Summary</flux:heading>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500">Subtotal:</flux:text>
                            <flux:text size="sm">${{ number_format($totals['subtotal'], 2) }}</flux:text>
                        </div>
                        
                        @if(isset($totals['discount']) && $totals['discount'] > 0)
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500">Discount:</flux:text>
                            <flux:text size="sm" class="text-orange-600">-${{ number_format($totals['discount'], 2) }}</flux:text>
                        </div>
                        @endif
                        
                        @php
                            $calculatedTax = ($totals['total'] - $totals['subtotal'] + ($totals['discount'] ?? 0));
                            $hasTaxCalculation = method_exists($invoice, 'latestTaxCalculation') ? $invoice->latestTaxCalculation() : null;
                        @endphp
                        
                        @if((isset($totals['tax']) && $totals['tax'] > 0) || $calculatedTax > 0 || $hasTaxCalculation)
                        <div>
                            @if(View::exists('components.tax-jurisdiction-breakdown'))
                            <x-tax-jurisdiction-breakdown 
                                :tax-calculation="$hasTaxCalculation" 
                                :collapsible="true" 
                                :fallback-tax-amount="$calculatedTax"
                            />
                            @else
                            <div class="flex justify-between">
                                <flux:text size="sm" class="text-zinc-500">Tax:</flux:text>
                                <flux:text size="sm">${{ number_format($calculatedTax, 2) }}</flux:text>
                            </div>
                            @endif
                        </div>
                        @endif
                        
                        <flux:separator />
                        
                        <div class="flex justify-between">
                            <flux:text class="font-medium">Total:</flux:text>
                            <flux:text class="font-medium">${{ number_format($totals['total'], 2) }}</flux:text>
                        </div>
                        
                        @if(isset($totals['paid']) && $totals['paid'] > 0)
                        <div class="flex justify-between">
                            <flux:text size="sm" class="text-zinc-500">Paid:</flux:text>
                            <flux:text size="sm" class="text-green-600">-${{ number_format($totals['paid'], 2) }}</flux:text>
                        </div>
                        @endif
                        
                        <flux:separator />
                        
                        <div class="flex justify-between items-center">
                            <flux:text class="font-semibold">Balance:</flux:text>
                            <div class="flex items-center gap-2">
                                <flux:text class="font-semibold {{ $totals['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    ${{ number_format($totals['balance'], 2) }}
                                </flux:text>
                                @if($totals['balance'] == 0)
                                <flux:badge color="green" size="sm">Paid</flux:badge>
                                @elseif($totals['balance'] > 0 && $invoice->due_date->isPast())
                                <flux:badge color="red" size="sm">Overdue</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                </flux:card>

            </div>
        </div>
    </div>
</div>
@endsection
