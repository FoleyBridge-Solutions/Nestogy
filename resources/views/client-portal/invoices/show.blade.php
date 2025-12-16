@extends('client-portal.layouts.app')

@section('title', 'Invoice #' . $invoice->number)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Invoice #{{ $invoice->number }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $invoice->client->name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('client.invoices') }}" variant="outline" icon="arrow-left">
                Back to Invoices
            </flux:button>
            
            <flux:button href="{{ route('client.invoices.download', $invoice->id) }}" variant="outline" icon="arrow-down-tray">
                Save as PDF
            </flux:button>
            
            <flux:button href="{{ route('client.invoices.print', $invoice->id) }}" target="_blank" variant="outline" icon="printer">
                Print
            </flux:button>
            
            @if($invoice->status !== 'paid' && !in_array($invoice->status, ['cancelled', 'canceled']) && $invoice->getBalance() > 0)
                <flux:button href="{{ route('client.invoices.pay', $invoice->id) }}" variant="primary" icon="credit-card">
                    Pay Now
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Invoice Information -->
        <flux:card class="lg:col-span-2">
            <flux:heading size="lg" class="mb-4">Invoice Information</flux:heading>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Invoice Number</flux:text>
                    <flux:text class="font-semibold">{{ $invoice->number }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Status</flux:text>
                    <div class="mt-1">
                        <x-status-badge :model="$invoice" :status="$invoice->status ?? 'pending'" size="lg" />
                    </div>
                </div>
                <div>
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Invoice Date</flux:text>
                    <flux:text class="font-semibold">{{ $invoice->date ? \Carbon\Carbon::parse($invoice->date)->format('M j, Y') : 'N/A' }}</flux:text>
                </div>
                <div>
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Due Date</flux:text>
                    <flux:text class="font-semibold">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M j, Y') : 'N/A' }}</flux:text>
                </div>
            </div>

            <!-- Invoice Items -->
            <flux:heading size="lg" class="mb-4">Items</flux:heading>
            
            @if($invoice->items && $invoice->items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quantity</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rate</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->description }}</div>
                                        @if($item->details)
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->details }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm text-gray-900 dark:text-gray-100">{{ $item->quantity }}</td>
                                    <td class="px-4 py-4 text-right text-sm text-gray-900 dark:text-gray-100">${{ number_format($item->price ?? 0, 2) }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">${{ number_format($item->total ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No items found for this invoice.
                </div>
            @endif
        </flux:card>

        <!-- Summary -->
        <flux:card>
            <flux:heading size="lg" class="mb-4">Summary</flux:heading>
            
            <div class="space-y-3">
                @php
                    // Calculate subtotal from items
                    $subtotal = $invoice->items->sum('subtotal');
                    $taxAmount = $invoice->items->sum('tax');
                    $total = $invoice->amount ?? 0;
                    $discountAmount = $invoice->discount_amount ?? 0;
                @endphp
                
                <div class="flex justify-between text-sm">
                    <flux:text class="text-gray-600 dark:text-gray-400">Subtotal</flux:text>
                    <flux:text class="font-semibold">${{ number_format($subtotal, 2) }}</flux:text>
                </div>
                
                @if($discountAmount > 0)
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-gray-600 dark:text-gray-400">Discount</flux:text>
                        <flux:text class="font-semibold text-green-600">-${{ number_format($discountAmount, 2) }}</flux:text>
                    </div>
                @endif
                
                @if($taxAmount > 0)
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-gray-600 dark:text-gray-400">Tax</flux:text>
                        <flux:text class="font-semibold">${{ number_format($taxAmount, 2) }}</flux:text>
                    </div>
                @endif
                
                <div class="border-t dark:border-gray-700 pt-3 mt-3">
                    <div class="flex justify-between">
                        <flux:text class="font-bold text-lg">Total</flux:text>
                        <flux:text class="font-bold text-lg text-blue-600 dark:text-blue-400">${{ number_format($total, 2) }}</flux:text>
                    </div>
                </div>
                
                @if(isset($invoice->paid_amount) && $invoice->paid_amount > 0)
                    <div class="flex justify-between text-sm">
                        <flux:text class="text-gray-600 dark:text-gray-400">Amount Paid</flux:text>
                        <flux:text class="font-semibold text-green-600">${{ number_format($invoice->paid_amount, 2) }}</flux:text>
                    </div>
                    
                    <div class="border-t dark:border-gray-700 pt-3 mt-3">
                        <div class="flex justify-between">
                            <flux:text class="font-bold">Balance Due</flux:text>
                            <flux:text class="font-bold text-red-600 dark:text-red-400">${{ number_format($total - $invoice->paid_amount, 2) }}</flux:text>
                        </div>
                    </div>
                @endif
            </div>
            
            @if($invoice->status !== 'paid' && !in_array($invoice->status, ['cancelled', 'canceled']) && Route::has('client.invoices.payment-options'))
                <div class="mt-6">
                    <flux:button href="{{ route('client.invoices.payment-options', $invoice->id) }}" variant="primary" class="w-full" icon="credit-card">
                        Pay Invoice
                    </flux:button>
                </div>
            @endif
        </flux:card>
    </div>

    <!-- Notes -->
    @if($invoice->notes)
        <flux:card>
            <flux:heading size="lg" class="mb-3">Notes</flux:heading>
            <flux:text class="text-gray-600 dark:text-gray-400">{{ $invoice->notes }}</flux:text>
        </flux:card>
    @endif
</div>
@endsection
