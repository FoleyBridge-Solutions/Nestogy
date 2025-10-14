@extends('layouts.app')

@section('title', 'Quotes')

@section('content')
<div class="w-full px-6 py-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-1">Quotes</h1>
            <p class="text-gray-600">Manage client financial.quotes and proposals</p>
        </div>
        <div>
            @can('create', App\Models\Quote::class)
            <a href="{{ route('financial.quotes.create') }}" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>New Quote
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div>
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-gray-100 text-gray-600 rounded-full p-6">
                            <i class="fas fa-file-alt text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h6 class="text-sm font-medium text-gray-500">Draft</h6>
                        <h4 class="text-2xl font-bold text-gray-900">{{ $stats['draft_financial.quotes'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-blue-100 text-blue-600 rounded-full p-6">
                            <i class="fas fa-paper-plane text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h6 class="text-sm font-medium text-gray-500">Sent</h6>
                        <h4 class="text-2xl font-bold text-gray-900">{{ $stats['sent_financial.quotes'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-green-100 text-green-600 rounded-full p-6">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h6 class="text-sm font-medium text-gray-500">Accepted</h6>
                        <h4 class="text-2xl font-bold text-gray-900">{{ $stats['accepted_financial.quotes'] ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-yellow-100 text-yellow-600 rounded-full p-6">
                            <i class="fas fa-percentage text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h6 class="text-sm font-medium text-gray-500">Conversion</h6>
                        <h4 class="text-2xl font-bold text-gray-900">{{ number_format($stats['conversion_rate'] ?? 0, 1) }}%</h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-blue-100 text-blue-600 rounded-full p-6">
                            <i class="fas fa-dollar-sign text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h6 class="text-sm font-medium text-gray-500">Total Value</h6>
                        <h4 class="text-2xl font-bold text-gray-900">${{ number_format($stats['total_value'] ?? 0, 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-6">
            <form method="GET" action="{{ route('financial.quotes.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Sent" {{ request('status') === 'Sent' ? 'selected' : '' }}>Sent</option>
                        <option value="Viewed" {{ request('status') === 'Viewed' ? 'selected' : '' }}>Viewed</option>
                        <option value="Accepted" {{ request('status') === 'Accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="Declined" {{ request('status') === 'Declined' ? 'selected' : '' }}>Declined</option>
                        <option value="Expired" {{ request('status') === 'Expired' ? 'selected' : '' }}>Expired</option>
                        <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label for="approval_status" class="block text-sm font-medium text-gray-700 mb-1">Approval Status</label>
                    <select name="approval_status" id="approval_status" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">All</option>
                        <option value="pending" {{ request('approval_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="Search financial.quotes..." class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quotes Table -->
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            @if($quotes->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quote #</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                            <th class="px-6 py-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($quotes as $quote)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-6 whitespace-nowrap">
                                    <div class="font-semibold text-gray-900">
                                        {{ $quote->getFullNumber() }}
                                        @if(isset($quote->version) && $quote->version > 1)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500 text-white ml-1">v{{ $quote->version }}</span>
                                        @endif
                                    </div>
                                    @if($quote->scope)
                                        <div class="text-sm text-gray-500">{{ Str::limit($quote->scope, 30) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap">
                                    @if($quote->client)
                                        <div class="text-sm font-medium text-gray-900">{{ $quote->client->name }}</div>
                                        @if($quote->client->company_name)
                                            <div class="text-sm text-gray-500">{{ $quote->client->company_name }}</div>
                                        @endif
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap text-sm font-semibold text-gray-900">${{ number_format($quote->amount, 2) }}</td>
                                <td class="px-6 py-6 whitespace-nowrap">
                                    @php
                                        $statusClasses = [
                                            'Draft' => 'bg-gray-500 text-white',
                                            'Sent' => 'bg-blue-100 text-blue-800',
                                            'Viewed' => 'bg-blue-600 text-white',
                                            'Accepted' => 'bg-green-100 text-green-800',
                                            'Declined' => 'bg-red-100 text-red-800',
                                            'Expired' => 'bg-yellow-100 text-yellow-800',
                                            'Converted' => 'bg-gray-900 text-white'
                                        ];
                                        $statusClass = $statusClasses[$quote->status] ?? 'bg-gray-500 text-white';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $quote->status }}</span>
                                    @if($quote->isExpired() && $quote->status !== 'Expired')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-1">Expired</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">{{ $quote->date->format('M d, Y') }}</td>
                                <td class="px-6 py-6 whitespace-nowrap text-sm text-gray-900">
                                    @if($quote->valid_until ?? $quote->expire)
                                        {{ ($quote->valid_until ?? $quote->expire)->format('M d, Y') }}
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        {{-- View: Always available if user has permission --}}
                                        @if(auth()->user()->can('financial.quotes.view'))
                                        <a href="{{ route('financial.quotes.show', $quote) }}" 
                                           class="inline-flex items-center p-2 text-blue-600 hover:text-blue-900" 
                                           title="View Quote">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endif
                                        
                                        {{-- Edit: Only for Draft quotes --}}
                                        @if(auth()->user()->can('financial.quotes.manage') && $quote->status === 'Draft')
                                        <a href="{{ route('financial.quotes.edit', $quote) }}" 
                                           class="inline-flex items-center p-2 text-gray-600 hover:text-gray-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endif
                                        
                                        {{-- Send: Only for Draft quotes --}}
                                        @if(auth()->user()->can('financial.quotes.send') && $quote->status === 'Draft')
                                        <form method="POST" action="{{ route('financial.quotes.send', $quote) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to send quote {{ $quote->getFullNumber() }}? This will change its status to Sent.')"
                                                    class="inline-flex items-center p-2 text-blue-600 hover:text-blue-900" title="Send Quote">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                        @endif
                                        
                                        {{-- Copy: Always available if user can create quotes --}}
                                        @if(auth()->user()->can('financial.quotes.create'))
                                        <a href="{{ route('financial.quotes.copy', $quote) }}" 
                                           class="inline-flex items-center p-2 text-blue-600 hover:text-blue-900" title="Copy Quote">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        @endif
                                        
                                        {{-- Convert to Invoice: Only for Accepted quotes --}}
                                        @if(auth()->user()->can('financial.quotes.convert') && $quote->status === 'Accepted')
                                        <form method="POST" action="{{ route('financial.quotes.convert-to-invoice', $quote) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('Convert quote {{ $quote->getFullNumber() }} to an invoice?')"
                                                    class="inline-flex items-center p-2 text-green-600 hover:text-green-900" title="Convert to Invoice">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                        </form>
                                        @endif
                                        
                                        {{-- Cancel: Only for Sent, Viewed, Accepted, or Declined quotes --}}
                                        @if(auth()->user()->can('financial.quotes.cancel') && in_array($quote->status, ['Sent', 'Viewed', 'Accepted', 'Declined']))
                                        <form method="POST" action="{{ route('financial.quotes.cancel', $quote) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to cancel quote {{ $quote->getFullNumber() }}? This will mark it as cancelled and it cannot be sent or converted.')"
                                                    class="inline-flex items-center p-2 text-yellow-600 hover:text-yellow-900" title="Cancel Quote">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                        @endif
                                        
                                        {{-- Delete: Only for Draft quotes --}}
                                        @if(auth()->user()->can('financial.quotes.delete') && $quote->status === 'Draft')
                                        <form method="POST" action="{{ route('financial.quotes.destroy', $quote) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to delete quote {{ $quote->getFullNumber() }}? This action cannot be undone.')"
                                                    class="inline-flex items-center p-2 text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                <!-- Pagination -->
                <div class="px-6 py-6 bg-gray-50 border-t border-gray-200">
                    {{ $quotes->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-file-alt text-6xl text-gray-400 mb-6"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No quotes found</h3>
                    <p class="text-gray-600 mb-6">Get started by creating your first quote</p>
                    @can('create', App\Models\Quote::class)
                    <a href="{{ route('financial.quotes.create') }}" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i>Create Quote
                    </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function convertQuote(quoteId) {
    if (confirm('Convert this quote to an invoice?')) {
        fetch(`/financial/quotes/${quoteId}/convert-to-invoice`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = `/financial/invoices/${data.invoice.id}`;
            } else {
                alert('Error: ' + (data.message || 'Failed to convert quote'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error converting quote');
        });
    }
}

function deleteQuote(quoteId, quoteNumber) {
    if (confirm(`Are you sure you want to delete quote ${quoteNumber}? This action cannot be undone.`)) {
        // Use fetch API for DELETE request to ensure proper handling
        fetch(`/financial/quotes/${quoteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok');
        })
        .then(data => {
            if (data.success) {
                // Reload the page to refresh the quote list
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete quote'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Fallback: try using form submission method
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ url('financial/quotes') }}/${quoteId}`;
            form.style.display = 'none';
            
            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfInput);
            
            // Add DELETE method
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            // Submit form
            document.body.appendChild(form);
            form.submit();
        });
    }
}

function cancelQuote(quoteId, quoteNumber) {
    if (confirm(`Are you sure you want to cancel quote ${quoteNumber}? This will mark it as cancelled and it cannot be sent or converted.`)) {
        fetch(`/financial/quotes/${quoteId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh page to show updated status
            } else {
                alert('Error: ' + (data.message || 'Failed to cancel quote'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cancelling quote');
        });
    }
}

function sendQuote(quoteId, quoteNumber) {
    if (confirm(`Are you sure you want to send quote ${quoteNumber}? This will change its status to 'Sent'.`)) {
        fetch(`/financial/quotes/${quoteId}/send`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh page to show updated status
            } else {
                alert('Error: ' + (data.message || 'Failed to send quote'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending quote');
        });
    }
}
</script>
@endpush
@endsection
