@extends('client-portal.layouts.app')

@section('title', 'Invoices')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Invoices</h1>
            <p class="text-gray-600 dark:text-gray-400">View and manage your billing statements and payment history</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">
                    Total Invoices
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['total_invoices'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-file-invoice fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-yellow-600 dark:text-yellow-400 uppercase mb-1">
                    Outstanding
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    ${{ number_format($stats['outstanding_amount'] ?? 0, 2) }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-green-600 dark:text-green-400 uppercase mb-1">
                    Paid This Year
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    ${{ number_format($stats['paid_this_year'] ?? 0, 2) }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <div class="flex items-center">
            <div class="flex-1 mr-2">
                <div class="text-xs font-bold text-red-600 dark:text-red-400 uppercase mb-1">
                    Overdue
                </div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    {{ $stats['overdue_count'] ?? 0 }}
                </div>
            </div>
            <div class="flex-shrink-0">
                <i class="fas fa-calendar-times fa-2x text-gray-300 dark:text-gray-600"></i>
            </div>
        </div>
    </flux:card>
</div>

<!-- Invoices List -->
<flux:card>
    <div class="mb-4">
        <flux:heading size="lg">Your Invoices</flux:heading>
    </div>
    
    @if($invoices->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Invoice #
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Due Date
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Amount
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $invoice->invoice_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $invoice->invoice_date ? $invoice->invoice_date->format('M j, Y') : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $invoice->due_date ? $invoice->due_date->format('M j, Y') : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    ${{ number_format($invoice->total ?? 0, 2) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusVariant = match($invoice->status) {
                                        'paid' => 'success',
                                        'sent', 'pending' => 'warning',
                                        'overdue' => 'danger',
                                        'draft' => 'secondary',
                                        default => 'secondary'
                                    };
                                @endphp
                                <flux:badge variant="{{ $statusVariant }}">
                                    {{ ucfirst($invoice->status ?? 'pending') }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-2">
                                    @if(Route::has('client.invoices.show'))
                                        <flux:button href="{{ route('client.invoices.show', $invoice->id) }}" variant="ghost" size="sm" icon="eye">
                                            View
                                        </flux:button>
                                    @endif
                                    
                                    @if($invoice->status !== 'paid' && Route::has('client.invoices.pay'))
                                        <flux:button href="{{ route('client.invoices.pay', $invoice->id) }}" variant="primary" size="sm" icon="credit-card">
                                            Pay Now
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($invoices->hasPages())
            <div class="mt-6">
                {{ $invoices->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="fas fa-file-invoice fa-4x text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">No Invoices Found</h3>
            <p class="text-gray-500 dark:text-gray-400">You don't have any invoices at the moment.</p>
        </div>
    @endif
</flux:card>
@endsection
