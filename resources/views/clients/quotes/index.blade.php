@extends('layouts.app')

@section('title', 'Client Quotes')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <div class="flex justify-between items-center mb-4">
                <h1 class="h3 mb-0">Client Quotes</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.quotes.standalone.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus mr-2"></i>Add New Quote
                    </a>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" x-data="{ open: false }" @click="open = !open">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('clients.quotes.standalone.export', request()->query()) }}">
                            <i class="fas fa-file-csv mr-2"></i>Export to CSV
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('clients.quotes.standalone.index') }}">
                        <div class="flex flex-wrap -mx-4 g-3">
                            <div class="md:w-1/4 px-4">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search quote number, title...">
                            </div>
                            <div class="md:w-1/6 px-4">
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select name="client_id" id="client_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Clients</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $key => $value)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="currency" class="form-label">Currency</label>
                                <select name="currency" id="currency" class="form-select">
                                    <option value="">All Currencies</option>
                                    @foreach($currencies as $key => $value)
                                        <option value="{{ $key }}" {{ request('currency') == $key ? 'selected' : '' }}>
                                            {{ $key }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="min_amount" class="form-label">Min Amount</label>
                                <input type="number" 
                                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                       id="min_amount" 
                                       name="min_amount" 
                                       value="{{ request('min_amount') }}" 
                                       step="0.01"
                                       placeholder="0.00">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label block">&nbsp;</label>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-2">
                                <label for="max_amount" class="form-label">Max Amount</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="max_amount" 
                                       name="max_amount" 
                                       value="{{ request('max_amount') }}" 
                                       step="0.01"
                                       placeholder="0.00">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="expiring_soon" 
                                           id="expiring_soon" 
                                           value="1" 
                                           {{ request('expiring_soon') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="expiring_soon">
                                        Expiring Soon
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="overdue" 
                                           id="overdue" 
                                           value="1" 
                                           {{ request('overdue') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="overdue">
                                        Overdue
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="follow_up_due" 
                                           id="follow_up_due" 
                                           value="1" 
                                           {{ request('follow_up_due') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="follow_up_due">
                                        Follow-up Due
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">&nbsp;</label>
                                <a href="{{ route('clients.quotes.standalone.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quotes Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    @if($quotes->count() > 0)
                        <div class="min-w-full divide-y divide-gray-200-responsive">
                            <table class="table min-w-full divide-y divide-gray-200-striped [&>tbody>tr:hover]:bg-gray-100">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Quote Details</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Validity</th>
                                        <th>Conversion</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($quotes as $quote)
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $quote->quote_number }}</div>
                                                    <div class="text-blue-600">{{ $quote->title }}</div>
                                                    @if($quote->description)
                                                        <small class="text-gray-600">{{ Str::limit($quote->description, 60) }}</small>
                                                    @endif
                                                    <div class="small text-gray-600 mt-1">
                                                        Issued: {{ $quote->issued_date ? $quote->issued_date->format('M j, Y') : 'Not issued' }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('clients.show', $quote->client) }}" class="text-decoration-none">
                                                    {{ $quote->client->display_name }}
                                                </a>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $quote->formatted_total_amount }}</div>
                                                    @if($quote->discount_amount > 0)
                                                        <small class="text-muted">
                                                            <s>{{ $quote->getCurrencySymbol() }}{{ number_format($quote->amount, 2) }}</s>
                                                            <span class="text-green-600">-{{ $quote->formatted_discount_amount }}</span>
                                                        </small>
                                                    @endif
                                                    <div class="small text-muted">{{ $quote->currency }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                @switch($quote->status)
                                                    @case('draft')
                                                        <span class="badge bg-gray-600">Draft</span>
                                                        @break
                                                    @case('pending')
                                                        <span class="badge bg-warning">Pending</span>
                                                        @break
                                                    @case('sent')
                                                        <span class="badge bg-blue-600">Sent</span>
                                                        @break
                                                    @case('viewed')
                                                        <span class="badge bg-info">Viewed</span>
                                                        @break
                                                    @case('accepted')
                                                        <span class="badge bg-success">Accepted</span>
                                                        @break
                                                    @case('declined')
                                                        <span class="badge bg-danger">Declined</span>
                                                        @break
                                                    @case('expired')
                                                        <span class="badge bg-gray-900">Expired</span>
                                                        @break
                                                    @case('converted')
                                                        <span class="badge bg-success">Converted</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-gray-600">Cancelled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($quote->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="small">
                                                    @if($quote->valid_until)
                                                        <div>{{ $quote->valid_until->format('M j, Y') }}</div>
                                                        @if($quote->days_until_expiry !== null)
                                                            @if($quote->days_until_expiry < 0)
                                                                <span class="badge bg-danger">Expired {{ abs($quote->days_until_expiry) }}d ago</span>
                                                            @elseif($quote->days_until_expiry == 0)
                                                                <span class="badge bg-warning">Expires Today</span>
                                                            @elseif($quote->days_until_expiry <= 7)
                                                                <span class="badge bg-info">{{ $quote->days_until_expiry }}d left</span>
                                                            @else
                                                                <span class="text-muted">{{ $quote->days_until_expiry }}d left</span>
                                                            @endif
                                                        @endif
                                                    @else
                                                        <span class="text-muted">No expiry</span>
                                                    @endif
                                                    
                                                    @if($quote->needsFollowUp())
                                                        <div class="mt-1">
                                                            <span class="badge bg-warning">Follow-up Due</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small text-center">
                                                    @if($quote->conversion_probability > 0)
                                                        <div class="progress mb-1" style="height: 8px;">
                                                            <div class="progress-bar @if($quote->conversion_probability >= 75) bg-success @elseif($quote->conversion_probability >= 50) bg-info @elseif($quote->conversion_probability >= 25) bg-warning @else bg-danger @endif" 
                                                                 style="width: {{ $quote->conversion_probability }}%"></div>
                                                        </div>
                                                        <div class="fw-bold">{{ $quote->conversion_probability }}%</div>
                                                    @else
                                                        <span class="text-muted">Not set</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('clients.quotes.standalone.show', $quote) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if(in_array($quote->status, ['draft', 'pending']))
                                                        <a href="{{ route('clients.quotes.standalone.edit', $quote) }}" 
                                                           class="btn btn-outline-secondary" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-success" 
                                                                title="Send Quote"
                                                                onclick="sendQuote({{ $quote->id }})">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    @endif
                                                    @if($quote->isAccepted())
                                                        <button type="button" 
                                                                class="btn btn-outline-info" 
                                                                title="Convert to Invoice"
                                                                onclick="convertQuote({{ $quote->id }})">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            title="Delete"
                                                            onclick="deleteQuote({{ $quote->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="flex justify-between items-center mt-4">
                            <div class="text-muted small">
                                Showing {{ $quotes->firstItem() }} to {{ $quotes->lastItem() }} of {{ $quotes->total() }} results
                            </div>
                            {{ $quotes->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5>No quotes found</h5>
                            <p class="text-muted">Get started by creating your first quote.</p>
                            <a href="{{ route('clients.quotes.standalone.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Quote
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Quote</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this quote? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteQuoteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Quote</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Send Quote Modal -->
<div class="modal fade" id="sendQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Quote</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to send this quote to the client?
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="sendQuoteForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Send Quote</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Convert Quote Modal -->
<div class="modal fade" id="convertQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Convert to Invoice</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Convert this quote to an invoice? This will create a new invoice with the quote details.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="$dispatch('close-modal')">Cancel</button>
                <form id="convertQuoteForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Convert to Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteQuote(quoteId) {
    const form = document.getElementById('deleteQuoteForm');
    form.action = '/clients/quotes/' + quoteId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteQuoteModal'));
    modal.show();
}

function sendQuote(quoteId) {
    const form = document.getElementById('sendQuoteForm');
    form.action = '/clients/quotes/' + quoteId + '/send';
    
    const modal = new bootstrap.Modal(document.getElementById('sendQuoteModal'));
    modal.show();
}

function convertQuote(quoteId) {
    const form = document.getElementById('convertQuoteForm');
    form.action = '/clients/quotes/' + quoteId + '/convert';
    
    const modal = new bootstrap.Modal(document.getElementById('convertQuoteModal'));
    modal.show();
}
</script>
@endpush