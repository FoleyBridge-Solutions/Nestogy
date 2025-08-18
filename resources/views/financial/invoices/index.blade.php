@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="w-full px-4 px-4 py-4">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Invoices</h1>
            <p class="text-gray-600 mb-0">Manage your invoices and billing</p>
        </div>
        <a href="{{ route('financial.invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>New Invoice
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="flex flex-wrap -mx-4 g-3 mb-4">
        <div class="md:w-1/4 px-4">
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-gray-600 bg-opacity-10 text-secondary rounded-full p-3">
                                <i class="fas fa-file-invoice fa-lg"></i>
                            </div>
                        </div>
                        <div class="ml-3">
                            <h6 class="text-gray-600 mb-1">Draft</h6>
                            <h4 class="mb-0">${{ number_format($totals['draft'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="md:w-1/4 px-4">
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-0 shadow-sm">
                <div class="p-6">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 text-info rounded-full p-3">
                                <i class="fas fa-paper-plane fa-lg"></i>
                            </div>
                        </div>
                        <div class="ml-3">
                            <h6 class="text-muted mb-1">Sent</h6>
                            <h4 class="mb-0">${{ number_format($totals['sent'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 text-green-600 rounded-circle p-3">
                                <i class="fas fa-check-circle fa-lg"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-1">Paid</h6>
                            <h4 class="mb-0">${{ number_format($totals['paid'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 text-red-600 rounded-circle p-3">
                                <i class="fas fa-clock fa-lg"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h6 class="text-muted mb-1">Overdue</h6>
                            <h4 class="mb-0">${{ number_format($totals['overdue'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if($invoices->count() > 0)
                <div class="min-w-full divide-y divide-gray-200-responsive">
                    <table class="min-w-full divide-y divide-gray-200 [&>tbody>tr:hover]:bg-gray-100 align-middle">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $invoice->prefix }}{{ $invoice->number }}</div>
                                        @if($invoice->scope)
                                            <small class="text-muted">{{ Str::limit($invoice->scope, 30) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->client)
                                            <div>{{ $invoice->client->name }}</div>
                                            @if($invoice->client->company_name)
                                                <small class="text-muted">{{ $invoice->client->company_name }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">${{ number_format($invoice->amount, 2) }}</td>
                                    <td>
                                        @php
                                            $statusClasses = [
                                                'Draft' => 'bg-secondary',
                                                'Sent' => 'bg-info',
                                                'Paid' => 'bg-success',
                                                'Cancelled' => 'bg-danger',
                                                'Overdue' => 'bg-warning'
                                            ];
                                            $statusClass = $statusClasses[$invoice->status] ?? 'bg-secondary';
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $invoice->status }}</span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($invoice->date)->format('M d, Y') }}</td>
                                    <td>
                                        @if($invoice->due_date)
                                            {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}
                                            @if($invoice->status === 'Sent' && \Carbon\Carbon::parse($invoice->due_date)->isPast())
                                                <i class="fas fa-exclamation-triangle text-warning ms-1" title="Overdue"></i>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('financial.invoices.show', $invoice) }}" 
                                               class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($invoice->status === 'Draft')
                                                <a href="{{ route('financial.invoices.edit', $invoice) }}" 
                                                   class="btn btn-outline-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            @if($invoice->status === 'Sent')
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="markAsPaid({{ $invoice->id }})" title="Mark as Paid">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $invoices->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                    <h5>No invoices found</h5>
                    <p class="text-muted">Get started by creating your first invoice</p>
                    <a href="{{ route('financial.invoices.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mt-3">
                        <i class="fas fa-plus mr-2"></i>Create Invoice
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function markAsPaid(invoiceId) {
    if (confirm('Mark this invoice as paid?')) {
        fetch(`/financial/invoices/${invoiceId}/mark-paid`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to update invoice'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating invoice');
        });
    }
}
</script>
@endpush
@endsection