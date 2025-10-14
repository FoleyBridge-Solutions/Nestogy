@extends('layouts.app')

@section('title', 'Payments')

@php
$pageTitle = 'Payments';
$pageSubtitle = 'Get started by creating a new payment.';
@endphp

@section('content')
<div class="container mx-auto mx-auto px-4 mx-auto px-4 mx-auto px-6 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-gray-600 mt-1">Manage payment records and transactions</p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('financial.payments.create') }}" 
               class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Payment
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                            <dd class="text-lg font-medium text-gray-900">${{ number_format($stats['total_amount'] ?? 0, 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">This Month</dt>
                            <dd class="text-lg font-medium text-gray-900">${{ number_format($stats['this_month_amount'] ?? 0, 2) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['pending_count'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['failed_count'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('financial.payments.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Reference, transaction ID, client..."
                           class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">All Methods</option>
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ request('payment_method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700">Client</label>
                    <select name="client_id" id="client_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                           class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700">Date To</label>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                           class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    <a href="{{ route('financial.payments.index') }}" 
                       class="inline-flex items-center px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-6 py-8 sm:p-6">
            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col-span-12" class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payment Details
                                </th>
                                <th scope="col-span-12" class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Client
                                </th>
                                <th scope="col-span-12" class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th scope="col-span-12" class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Method
                                </th>
                                <th scope="col-span-12" class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col-span-12" class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col-span-12" class="relative px-6 py-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($payments as $payment)
                                <tr>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $payment->payment_reference ?? 'No Reference' }}
                                                </div>
                                                @if($payment->invoice)
                                                    <div class="text-sm text-gray-500">
                                                        Invoice: {{ $payment->invoice->invoice_number }}
                                                    </div>
                                                @endif
                                                @if($payment->gateway_transaction_id)
                                                    <div class="text-xs text-gray-400">
                                                        {{ $payment->gateway_transaction_id }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $payment->client->name }}</div>
                                        @if($payment->client->company_name)
                                            <div class="text-sm text-gray-500">{{ $payment->client->company_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            ${{ number_format($payment->amount, 2) }}
                                        </div>
                                        @if($payment->gateway_fee)
                                            <div class="text-xs text-gray-500">
                                                Fee: ${{ number_format($payment->gateway_fee, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $paymentMethods[$payment->payment_method] ?? $payment->payment_method }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $gateways[$payment->gateway] ?? $payment->gateway }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->status_badge_class }}">
                                            {{ $statuses[$payment->status] ?? $payment->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-500">
                                        {{ $payment->payment_date->format('M j, Y') }}
                                        <div class="text-xs text-gray-400">
                                            {{ $payment->payment_date->format('g:i A') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-6 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('financial.payments.show', $payment) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">View</a>
                                            @can('update', $payment)
                                                <a href="{{ route('financial.payments.edit', $payment) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="text-center py-6">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No payments found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new payment.</p>
                    <div class="mt-6">
                        <a href="{{ route('financial.payments.create') }}" 
                           class="inline-flex items-center px-6 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Payment
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
