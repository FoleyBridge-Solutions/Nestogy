@extends('layouts.app')

@section('title', 'Client Recurring Invoices')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Client Recurring Invoices</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.recurring-invoices.standalone.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Recurring Invoice
                    </a>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('clients.recurring-invoices.standalone.export', request()->query()) }}">
                            <i class="fas fa-file-csv me-2"></i>Export to CSV
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('clients.recurring-invoices.standalone.index') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search template name, description...">
                            </div>
                            <div class="col-md-2">
                                <label for="client_id" class="form-label">Client</label>
                                <select name="client_id" id="client_id" class="form-select">
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
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $key => $value)
                                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="frequency" class="form-label">Frequency</label>
                                <select name="frequency" id="frequency" class="form-select">
                                    <option value="">All Frequencies</option>
                                    @foreach($frequencies as $key => $value)
                                        <option value="{{ $key }}" {{ request('frequency') == $key ? 'selected' : '' }}>
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
                            <div class="col-md-1">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-2">
                                <label for="min_amount" class="form-label">Min Amount</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="min_amount" 
                                       name="min_amount" 
                                       value="{{ request('min_amount') }}" 
                                       step="0.01"
                                       placeholder="0.00">
                            </div>
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
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="due_soon" 
                                           id="due_soon" 
                                           value="1" 
                                           {{ request('due_soon') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="due_soon">
                                        Due Soon (7 days)
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
                            <div class="col-md-4">
                                <label class="form-label d-block">&nbsp;</label>
                                <a href="{{ route('clients.recurring-invoices.standalone.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recurring Invoices Table -->
            <div class="card">
                <div class="card-body">
                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Invoice Details</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Frequency</th>
                                        <th>Next Invoice</th>
                                        <th>Status</th>
                                        <th>Performance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $invoice->template_name }}</div>
                                                    @if($invoice->description)
                                                        <small class="text-muted">{{ Str::limit($invoice->description, 60) }}</small>
                                                    @endif
                                                    <div class="small text-muted mt-1">
                                                        <span class="badge bg-light text-dark">{{ $invoice->invoice_prefix }}</span>
                                                        @if($invoice->auto_send)
                                                            <span class="badge bg-info">Auto Send</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('clients.show', $invoice->client) }}" class="text-decoration-none">
                                                    {{ $invoice->client->display_name }}
                                                </a>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">{{ $invoice->formatted_total_amount }}</div>
                                                    @if($invoice->tax_rate > 0)
                                                        <small class="text-muted">
                                                            {{ $invoice->formatted_amount }} + {{ $invoice->tax_rate }}% tax
                                                        </small>
                                                    @endif
                                                    <div class="small text-muted">{{ $invoice->currency }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="fw-bold">{{ $invoice->frequency_description }}</div>
                                                    @if($invoice->payment_terms_days)
                                                        <div class="text-muted">{{ $invoice->payment_terms_days }} day terms</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    @if($invoice->next_invoice_date)
                                                        <div class="fw-bold">{{ $invoice->next_invoice_date->format('M j, Y') }}</div>
                                                        @if($invoice->days_until_next_invoice !== null)
                                                            @if($invoice->days_until_next_invoice < 0)
                                                                <span class="badge bg-danger">{{ abs($invoice->days_until_next_invoice) }} days overdue</span>
                                                            @elseif($invoice->days_until_next_invoice == 0)
                                                                <span class="badge bg-warning">Due Today</span>
                                                            @elseif($invoice->days_until_next_invoice <= 7)
                                                                <span class="badge bg-info">{{ $invoice->days_until_next_invoice }} days</span>
                                                            @else
                                                                <span class="text-muted">{{ $invoice->days_until_next_invoice }} days</span>
                                                            @endif
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Not scheduled</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @switch($invoice->status)
                                                    @case('draft')
                                                        <span class="badge bg-secondary">Draft</span>
                                                        @break
                                                    @case('active')
                                                        <span class="badge bg-success">Active</span>
                                                        @break
                                                    @case('paused')
                                                        <span class="badge bg-warning">Paused</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @case('expired')
                                                        <span class="badge bg-dark">Expired</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ ucfirst($invoice->status) }}</span>
                                                @endswitch
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <div class="d-flex justify-content-between">
                                                        <span>Invoices:</span>
                                                        <span class="fw-bold">{{ $invoice->invoice_count ?? 0 }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span>Revenue:</span>
                                                        <span class="fw-bold text-success">{{ $invoice->getCurrencySymbol() }}{{ number_format($invoice->total_revenue ?? 0, 0) }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('clients.recurring-invoices.standalone.show', $invoice) }}" 
                                                       class="btn btn-outline-primary" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($invoice->isActive())
                                                        <a href="{{ route('clients.recurring-invoices.standalone.edit', $invoice) }}" 
                                                           class="btn btn-outline-secondary" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if($invoice->isDue())
                                                            <button type="button" 
                                                                    class="btn btn-outline-success" 
                                                                    title="Generate Invoice"
                                                                    onclick="generateInvoice({{ $invoice->id }})">
                                                                <i class="fas fa-file-invoice"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    <button type="button" 
                                                            class="btn btn-outline-danger" 
                                                            title="Delete"
                                                            onclick="deleteInvoice({{ $invoice->id }})">
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
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">
                                Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} results
                            </div>
                            {{ $invoices->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                            <h5>No recurring invoices found</h5>
                            <p class="text-muted">Get started by creating your first recurring invoice.</p>
                            <a href="{{ route('clients.recurring-invoices.standalone.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Recurring Invoice
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Recurring Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this recurring invoice? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteInvoiceForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Generate Invoice Modal -->
<div class="modal fade" id="generateInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to generate an invoice from this recurring invoice template?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="generateInvoiceForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Generate Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteInvoice(invoiceId) {
    const form = document.getElementById('deleteInvoiceForm');
    form.action = '/clients/recurring-invoices/' + invoiceId;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteInvoiceModal'));
    modal.show();
}

function generateInvoice(invoiceId) {
    const form = document.getElementById('generateInvoiceForm');
    form.action = '/clients/recurring-invoices/' + invoiceId + '/generate';
    
    const modal = new bootstrap.Modal(document.getElementById('generateInvoiceModal'));
    modal.show();
}
</script>
@endpush