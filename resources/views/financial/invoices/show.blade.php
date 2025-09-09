@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->getFullNumber())

@section('content')
<div class="container mx-auto mx-auto px-4 mx-auto px-4 mx-auto px-6 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Invoice #{{ $invoice->getFullNumber() }}</h1>
                <p class="text-gray-600 mt-1">{{ $invoice->client->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('financial.invoices.index') }}" 
                   class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Invoices
                </a>
                @if($invoice->isDraft())
                <a href="{{ route('financial.invoices.edit', $invoice) }}" 
                   class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Edit Invoice
                </a>
                @endif
            </div>
        </div>

        <!-- Invoice Status -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Invoice Status</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($invoice->status === 'Draft') bg-gray-100 text-gray-800 @elseif($invoice->status === 'Sent') bg-blue-100 text-blue-800 @elseif($invoice->status === 'Paid') bg-green-100 text-green-800 @elseif($invoice->status === 'Overdue') bg-red-100 text-red-800 @else bg-gray-100 text-gray-800 @endif">
                        {{ $invoice->status }}
                    </span>
                </div>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->getFormattedAmount() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Balance</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->getFormattedBalance() }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Information -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Client Information</h3>
            </div>
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Client Name</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->client->name }}</dd>
                        @if($invoice->client->company_name)
                        <dd class="text-sm text-gray-500">{{ $invoice->client->company_name }}</dd>
                        @endif
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->category->name ?? 'N/A' }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Items -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Invoice Items</h3>
            </div>
            @if($invoice->items->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->items as $item)
                        <tr>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">{{ $item->description }}</td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">{{ $item->quantity }}</td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">${{ number_format($item->rate, 2) }}</td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">${{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-6 py-12 text-center">
                <p class="text-gray-500">No items added to this invoice yet.</p>
            </div>
            @endif
        </div>

        <!-- Invoice Totals -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Invoice Summary</h3>
            </div>
            <div class="px-6 py-6">
                <div class="max-w-md ml-auto">
                    <div class="flex justify-between py-2">
                        <span class="text-sm text-gray-600">Subtotal:</span>
                        <span class="text-sm text-gray-900">${{ number_format($totals['subtotal'], 2) }}</span>
                    </div>
                    @if($totals['discount'] > 0)
                    <div class="flex justify-between py-2">
                        <span class="text-sm text-gray-600">Discount:</span>
                        <span class="text-sm text-gray-900">-${{ number_format($totals['discount'], 2) }}</span>
                    </div>
                    @endif
                    @php
                        $calculatedTax = ($totals['total'] - $totals['subtotal'] + ($totals['discount'] ?? 0));
                        $hasTaxCalculation = $invoice->latestTaxCalculation();
                    @endphp
                    @if($totals['tax'] > 0 || $calculatedTax > 0 || $hasTaxCalculation)
                    <div class="py-2">
                        <x-tax-jurisdiction-breakdown 
                            :tax-calculation="$invoice->latestTaxCalculation()" 
                            :collapsible="true" 
                            :fallback-tax-amount="$calculatedTax"
                        />
                    </div>
                    @endif
                    <div class="flex justify-between py-2 border-t border-gray-200 font-medium">
                        <span class="text-sm text-gray-900">Total:</span>
                        <span class="text-sm text-gray-900">${{ number_format($totals['total'], 2) }}</span>
                    </div>
                    @if($totals['paid'] > 0)
                    <div class="flex justify-between py-2">
                        <span class="text-sm text-gray-600">Paid:</span>
                        <span class="text-sm text-green-600">-${{ number_format($totals['paid'], 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-2 font-medium">
                        <span class="text-sm text-gray-900">Balance:</span>
                        <span class="text-sm {{ $totals['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            ${{ number_format($totals['balance'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments -->
        @if($invoice->payments->count() > 0)
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Payments</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">{{ $payment->payment_method }}</td>
                            <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">${{ number_format($payment->amount, 2) }}</td>
                            <td class="px-6 py-6 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium @if($payment->status === 'completed') bg-green-100 text-green-800 @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800 @elseif($payment->status === 'failed') bg-red-100 text-red-800 @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($invoice->note)
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Notes</h3>
            </div>
            <div class="px-6 py-6">
                <p class="text-sm text-gray-700">{{ $invoice->note }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
