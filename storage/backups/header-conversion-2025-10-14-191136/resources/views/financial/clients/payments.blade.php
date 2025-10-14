@extends('layouts.app')

@section('title', $client->name . ' - Payments')

@section('content')
<div class="container mx-auto px-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="flex items-center mb-4">
            <a href="{{ route('clients.show', $client) }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to {{ $client->name }}
            </a>
        </nav>
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Payments for {{ $client->name }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">View and manage payment history</p>
            </div>
            
            <div class="flex gap-2">
                <flux:button href="{{ route('financial.payments.create', ['client_id' => $client->id]) }}" variant="primary">
                    <i class="fas fa-plus mr-2"></i>
                    Record Payment
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-green-600 dark:text-green-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Total Paid</flux:text>
                    <flux:heading size="lg">${{ number_format($stats['total_paid'] ?? 0, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">This Month</flux:text>
                    <flux:heading size="lg">${{ number_format($stats['this_month'] ?? 0, 2) }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Pending</flux:text>
                    <flux:heading size="lg">{{ $stats['pending_count'] ?? 0 }}</flux:heading>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-receipt text-purple-600 dark:text-purple-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Total Payments</flux:text>
                    <flux:heading size="lg">{{ $stats['total_count'] ?? 0 }}</flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    <!-- Filters -->
    <flux:card class="mb-6">
        <form method="GET" action="{{ route('clients.payments.index', $client) }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <flux:field>
                    <flux:label for="search">Search</flux:label>
                    <flux:input 
                        type="text" 
                        id="search" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Reference, transaction ID..." />
                </flux:field>
                
                <flux:field>
                    <flux:label for="status">Status</flux:label>
                    <flux:select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
                    </flux:select>
                </flux:field>
                
                <flux:field>
                    <flux:label for="payment_method">Payment Method</flux:label>
                    <flux:select id="payment_method" name="payment_method">
                        <option value="">All Methods</option>
                        <option value="credit_card" {{ request('payment_method') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                        <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="check" {{ request('payment_method') == 'check' ? 'selected' : '' }}>Check</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="paypal" {{ request('payment_method') == 'paypal' ? 'selected' : '' }}>PayPal</option>
                    </flux:select>
                </flux:field>
                
                <flux:field>
                    <flux:label for="date_range">Date Range</flux:label>
                    <flux:select id="date_range" name="date_range">
                        <option value="">All Time</option>
                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="week" {{ request('date_range') == 'week' ? 'selected' : '' }}>This Week</option>
                        <option value="month" {{ request('date_range') == 'month' ? 'selected' : '' }}>This Month</option>
                        <option value="quarter" {{ request('date_range') == 'quarter' ? 'selected' : '' }}>This Quarter</option>
                        <option value="year" {{ request('date_range') == 'year' ? 'selected' : '' }}>This Year</option>
                    </flux:select>
                </flux:field>
            </div>
            
            <div class="mt-4 flex gap-2">
                <flux:button type="submit" variant="primary">
                    <i class="fas fa-filter mr-2"></i>Filter
                </flux:button>
                <flux:button href="{{ route('clients.payments.index', $client) }}" variant="ghost">
                    Clear
                </flux:button>
            </div>
        </form>
    </flux:card>

    <!-- Payments Table -->
    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Payment Details</flux:table.column>
                <flux:table.column>Invoice</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Method</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($payments as $payment)
                    <flux:table.row>
                        <flux:table.cell>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $payment->payment_reference ?? 'PMT-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
                                </div>
                                @if($payment->gateway_transaction_id)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $payment->gateway_transaction_id }}
                                    </div>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($payment->invoice)
                                <a href="{{ route('financial.invoices.show', $payment->invoice) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $payment->invoice->invoice_number }}
                                </a>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">
                                ${{ number_format($payment->amount, 2) }}
                            </div>
                            @if($payment->gateway_fee)
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Fee: ${{ number_format($payment->gateway_fee, 2) }}
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div>
                                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </div>
                            @if($payment->gateway)
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $payment->gateway }}
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $statusColors = [
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'refunded' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                ];
                                $statusColor = $statusColors[$payment->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <flux:badge class="{{ $statusColor }}">{{ ucfirst($payment->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $payment->payment_date->format('M j, Y') }}
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $payment->payment_date->format('g:i A') }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button 
                                    href="{{ route('financial.payments.show', $payment) }}" 
                                    variant="ghost" 
                                    size="sm">
                                    <i class="fas fa-eye"></i>
                                </flux:button>
                                @can('update', $payment)
                                    <flux:button 
                                        href="{{ route('financial.payments.edit', $payment) }}" 
                                        variant="ghost" 
                                        size="sm">
                                        <i class="fas fa-edit"></i>
                                    </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center py-8">
                            <div class="text-gray-500 dark:text-gray-400">
                                <i class="fas fa-credit-card text-4xl mb-4"></i>
                                <p>No payments found for this client.</p>
                                <flux:button href="{{ route('financial.payments.create', ['client_id' => $client->id]) }}" variant="primary" class="mt-4">
                                    <i class="fas fa-plus mr-2"></i>
                                    Record First Payment
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        
        @if($payments->hasPages())
            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        @endif
    </flux:card>
</div>
@endsection