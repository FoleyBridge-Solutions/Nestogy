@extends('client-portal.layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="portal-container">
    <!-- Header -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                <div>
                    <h1 class="portal-text-3xl portal-mb-0 text-gray-800">Invoices</h1>
                    <p class="text-gray-600">View and manage your billing statements and payment history</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-primary portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-primary portal-uppercase portal-mb-1">
                                Total Invoices
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $stats['total_invoices'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-warning portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-warning portal-uppercase portal-mb-1">
                                Outstanding
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                ${{ number_format($stats['outstanding_amount'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-success portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-success portal-uppercase portal-mb-1">
                                Paid This Year
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                ${{ number_format($stats['paid_this_year'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="portal-col-6 portal-col-xl-3 portal-mb-4">
            <div class="portal-card portal-card-border-danger portal-shadow portal-h-100">
                <div class="portal-card-body portal-py-4">
                    <div class="portal-d-flex portal-align-items-center">
                        <div class="portal-col portal-mr-2">
                            <div class="portal-text-xs portal-font-bold portal-text-danger portal-uppercase portal-mb-1">
                                Overdue
                            </div>
                            <div class="portal-h5 portal-mb-0 portal-font-bold portal-text-gray-800">
                                {{ $stats['overdue_count'] ?? 0 }}
                            </div>
                        </div>
                        <div class="portal-col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Options -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="portal-card-body">
                    <form method="GET" action="{{ route('client.invoices') }}" class="portal-d-flex portal-align-items-center space-x-4">
                        <div class="portal-col-auto">
                            <label for="status" class="portal-text-sm portal-font-medium text-gray-700 portal-mr-2">
                                Status:
                            </label>
                            <select name="status" id="status" class="portal-form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="portal-col-auto">
                            <label for="year" class="portal-text-sm portal-font-medium text-gray-700 portal-mr-2">
                                Year:
                            </label>
                            <select name="year" id="year" class="portal-form-control" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                @for($year = now()->year; $year >= now()->year - 5; $year--)
                                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        
                        @if(request('status') || request('year'))
                        <div class="portal-col-auto">
                            <a href="{{ route('client.invoices') }}" class="portal-btn portal-btn-outline-secondary portal-btn-sm">
                                Clear Filters
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="portal-row">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-file-invoice-dollar portal-mr-2"></i>Your Invoices
                        @if(count($invoices) > 0)
                            <span class="portal-text-sm portal-font-normal text-gray-600">
                                ({{ count($invoices) }} total)
                            </span>
                        @endif
                    </h6>
                </div>
                <div class="portal-card-body">
                    @if(count($invoices) > 0)
                    <div class="portal-table-responsive">
                        <table class="portal-table portal-min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Invoice #
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Date
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Due Date
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Amount
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Status
                                    </th>
                                    <th class="px-4 py-3 text-left portal-text-xs portal-font-medium text-gray-500 portal-uppercase">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="portal-divide-y portal-divide-gray-200">
                                @foreach($invoices as $invoice)
                                <tr class="portal-table-row">
                                    <td class="px-4 py-4">
                                        <div class="portal-text-sm portal-font-medium text-gray-900">
                                            {{ $invoice->getFullNumber() ?? ($invoice->prefix ?? '') . $invoice->number }}
                                        </div>
                                        @if($invoice->note)
                                        <div class="portal-text-xs text-gray-500">
                                            {{ Str::limit($invoice->note, 50) }}
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm text-gray-500">
                                        {{ $invoice->date ? $invoice->date->format('M j, Y') : ($invoice->created_at ? $invoice->created_at->format('M j, Y') : 'N/A') }}
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm text-gray-500">
                                        @if($invoice->due_date ?? $invoice->due)
                                            @php 
                                                $dueDate = $invoice->due_date ?? $invoice->due;
                                                $isOverdue = $dueDate->isPast() && !in_array($invoice->status, ['paid', 'cancelled']);
                                            @endphp
                                            <span class="{{ $isOverdue ? 'text-red-600 portal-font-medium' : '' }}">
                                                {{ $dueDate->format('M j, Y') }}
                                            </span>
                                            @if($isOverdue)
                                                <div class="portal-text-xs text-red-600">
                                                    {{ $dueDate->diffForHumans() }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm portal-font-medium">
                                        ${{ number_format($invoice->amount ?? 0, 2) }}
                                        @if($invoice->currency_code && $invoice->currency_code !== 'USD')
                                            <span class="portal-text-xs text-gray-500">{{ $invoice->currency_code }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex px-2 py-1 portal-text-xs portal-font-medium rounded-full
                                            @if($invoice->status === 'paid') bg-green-100 text-green-800
                                            @elseif($invoice->status === 'sent') bg-blue-100 text-blue-800
                                            @elseif($invoice->status === 'draft') bg-gray-100 text-gray-800
                                            @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                            @elseif($invoice->status === 'cancelled') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($invoice->status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 portal-text-sm">
                                        <div class="portal-d-flex space-x-2">
                                            <a href="{{ route('client.invoices.show', $invoice) ?? '#' }}" 
                                               class="portal-btn portal-btn-sm portal-btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('client.invoices.download', $invoice) ?? '#' }}" 
                                               class="portal-btn portal-btn-sm portal-btn-outline-secondary">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            @if($invoice->status === 'sent' && ($invoice->due_date ?? $invoice->due))
                                            <button type="button" 
                                                    class="portal-btn portal-btn-sm portal-btn-outline-success"
                                                    title="Mark as Paid">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @else
                    <div class="text-center py-8">
                        <div class="portal-mb-4">
                            <i class="fas fa-file-invoice-dollar fa-3x text-gray-300"></i>
                        </div>
                        <h3 class="portal-text-lg portal-font-medium text-gray-900 portal-mb-2">
                            @if(request('status') || request('year'))
                                No Invoices Found
                            @else
                                No Invoices
                            @endif
                        </h3>
                        <p class="portal-text-sm text-gray-500 portal-mb-4">
                            @if(request('status') || request('year'))
                                No invoices match your current filters. Try adjusting the filters above.
                            @else
                                You don't have any invoices yet. Invoices will appear here once they are generated.
                            @endif
                        </p>
                        @if(request('status') || request('year'))
                        <a href="{{ route('client.invoices') }}" class="portal-btn portal-btn-outline-primary">
                            Clear Filters
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Summary -->
    @if(count($invoices) > 0)
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-chart-pie portal-mr-2"></i>Payment Summary
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="portal-row">
                        <div class="portal-col-6 portal-col-xl-4 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-green-600">
                                    {{ $invoices->where('status', 'paid')->count() }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Paid Invoices</div>
                            </div>
                        </div>
                        
                        <div class="portal-col-6 portal-col-xl-4 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-blue-600">
                                    ${{ number_format($invoices->where('status', 'paid')->sum('amount'), 2) }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Total Paid</div>
                            </div>
                        </div>
                        
                        <div class="portal-col-6 portal-col-xl-4 portal-mb-4">
                            <div class="text-center">
                                <div class="portal-text-2xl portal-font-bold text-orange-600">
                                    ${{ number_format($invoices->whereIn('status', ['sent', 'overdue'])->sum('amount'), 2) }}
                                </div>
                                <div class="portal-text-sm text-gray-600">Outstanding Balance</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.space-x-2 > * + * { margin-left: 0.5rem; }
.space-x-4 > * + * { margin-left: 1rem; }

.portal-form-control {
    width: auto;
    min-width: 120px;
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    background-color: white;
}

.portal-form-control:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

/* Badge colors for status indicators */
.bg-green-100 { background-color: #dcfce7; } .text-green-800 { color: #166534; }
.bg-yellow-100 { background-color: #fef3c7; } .text-yellow-800 { color: #92400e; }
.bg-red-100 { background-color: #fee2e2; } .text-red-800 { color: #991b1b; }
.bg-blue-100 { background-color: #dbeafe; } .text-blue-800 { color: #1e40af; }
.bg-orange-100 { background-color: #fed7aa; } .text-orange-800 { color: #9a3412; }
.bg-gray-100 { background-color: #f3f4f6; } .text-gray-800 { color: #1f2937; }

/* Hover effects */
.portal-table-row:hover {
    background-color: #f9fafb;
}

/* Summary card styles */
.text-green-600 { color: #059669; }
.text-blue-600 { color: #2563eb; }
.text-orange-600 { color: #ea580c; }
.text-red-600 { color: #dc2626; }
</style>
@endpush