@extends('layouts.app')

@section('title', 'Quote Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Quote Details</h1>
                <div class="btn-group">
                    @if(in_array($quote->status, ['draft', 'pending']))
                        <a href="{{ route('clients.quotes.standalone.edit', $quote) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Quote
                        </a>
                        <button type="button" class="btn btn-success" onclick="sendQuote({{ $quote->id }})">
                            <i class="fas fa-paper-plane me-2"></i>Send Quote
                        </button>
                    @endif
                    @if($quote->isAccepted())
                        <button type="button" class="btn btn-info" onclick="convertQuote({{ $quote->id }})">
                            <i class="fas fa-file-invoice me-2"></i>Convert to Invoice
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" onclick="duplicateQuote({{ $quote->id }})">
                        <i class="fas fa-copy me-2"></i>Duplicate
                    </button>
                    <a href="{{ route('clients.quotes.standalone.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Quotes
                    </a>
                    <button type="button" 
                            class="btn btn-outline-danger" 
                            onclick="deleteQuote({{ $quote->id }})">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Main Quote Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt me-2"></i>{{ $quote->quote_number }}
                                </h5>
                                <div class="d-flex gap-2">
                                    @switch($quote->status)
                                        @case('draft')
                                            <span class="badge bg-secondary">Draft</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning">Pending Approval</span>
                                            @break
                                        @case('sent')
                                            <span class="badge bg-primary">Sent to Client</span>
                                            @break
                                        @case('viewed')
                                            <span class="badge bg-info">Viewed by Client</span>
                                            @break
                                        @case('accepted')
                                            <span class="badge bg-success">Accepted</span>
                                            @break
                                        @case('declined')
                                            <span class="badge bg-danger">Declined</span>
                                            @break
                                        @case('expired')
                                            <span class="badge bg-dark">Expired</span>
                                            @break
                                        @case('converted')
                                            <span class="badge bg-success">Converted</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary">Cancelled</span>
                                            @break
                                    @endswitch

                                    @if($quote->conversion_probability > 0)
                                        <span class="badge 
                                            @if($quote->conversion_probability >= 75) bg-success
                                            @elseif($quote->conversion_probability >= 50) bg-info
                                            @elseif($quote->conversion_probability >= 25) bg-warning
                                            @else bg-danger
                                            @endif">
                                            {{ $quote->conversion_probability }}% Conversion
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Quote Title</h6>
                                    <p class="mb-3 fw-bold">{{ $quote->title }}</p>

                                    <h6 class="text-muted">Client</h6>
                                    <p class="mb-3">
                                        <a href="{{ route('clients.show', $quote->client) }}" class="text-decoration-none">
                                            {{ $quote->client->display_name }}
                                        </a>
                                    </p>

                                    @if($quote->description)
                                        <h6 class="text-muted">Description</h6>
                                        <div class="mb-3">
                                            {!! nl2br(e($quote->description)) !!}
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-muted">Amount Details</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Subtotal:</span>
                                            <span class="fw-bold">{{ $quote->formatted_amount }}</span>
                                        </div>
                                        @if($quote->discount_amount > 0)
                                            <div class="d-flex justify-content-between text-success">
                                                <span>Discount:</span>
                                                <span>-{{ $quote->formatted_discount_amount }}</span>
                                            </div>
                                        @endif
                                        @if($quote->tax_rate > 0)
                                            <div class="d-flex justify-content-between">
                                                <span>Tax ({{ $quote->tax_rate }}%):</span>
                                                <span>{{ $quote->getCurrencySymbol() }}{{ number_format($quote->tax_amount, 2) }}</span>
                                            </div>
                                        @endif
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Total:</span>
                                            <span class="fw-bold text-primary fs-5">{{ $quote->formatted_total_amount }}</span>
                                        </div>
                                    </div>

                                    <h6 class="text-muted">Dates</h6>
                                    <div class="mb-3">
                                        @if($quote->issued_date)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Issued:</span>
                                                <span>{{ $quote->issued_date->format('M j, Y') }}</span>
                                            </div>
                                        @endif
                                        @if($quote->valid_until)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Valid Until:</span>
                                                <span>{{ $quote->valid_until->format('M j, Y') }}</span>
                                            </div>
                                            @if($quote->days_until_expiry !== null)
                                                <div class="mt-2">
                                                    @if($quote->days_until_expiry < 0)
                                                        <span class="badge bg-danger">Expired {{ abs($quote->days_until_expiry) }} days ago</span>
                                                    @elseif($quote->days_until_expiry == 0)
                                                        <span class="badge bg-warning">Expires Today</span>
                                                    @elseif($quote->days_until_expiry <= 7)
                                                        <span class="badge bg-info">{{ $quote->days_until_expiry }} days left</span>
                                                    @else
                                                        <span class="text-muted">{{ $quote->days_until_expiry }} days left</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                        @if($quote->follow_up_date)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Follow-up:</span>
                                                <span>{{ $quote->follow_up_date->format('M j, Y') }}</span>
                                            </div>
                                            @if($quote->needsFollowUp())
                                                <div class="mt-2">
                                                    <span class="badge bg-warning">Follow-up Due</span>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($quote->line_items && count($quote->line_items) > 0)
                                <hr>
                                <h6 class="text-muted">Line Items</h6>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Description</th>
                                                <th class="text-end">Quantity</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($quote->line_items as $item)
                                                <tr>
                                                    <td>{{ $item['description'] }}</td>
                                                    <td class="text-end">{{ $item['quantity'] ?? 1 }}</td>
                                                    <td class="text-end">{{ $quote->getCurrencySymbol() }}{{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                                                    <td class="text-end fw-bold">{{ $quote->getCurrencySymbol() }}{{ number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            @if($quote->project_scope)
                                <hr>
                                <h6 class="text-muted">Project Scope</h6>
                                <div class="mb-3 p-3 bg-light rounded">
                                    {!! nl2br(e($quote->project_scope)) !!}
                                </div>
                            @endif

                            @if($quote->delivery_timeframe)
                                <h6 class="text-muted">Delivery Timeframe</h6>
                                <p class="mb-3">{{ $quote->delivery_timeframe }}</p>
                            @endif

                            @if($quote->payment_terms)
                                <hr>
                                <h6 class="text-muted">Payment Terms</h6>
                                <div class="mb-3">
                                    {!! nl2br(e($quote->payment_terms)) !!}
                                </div>
                            @endif

                            @if($quote->terms_conditions)
                                <hr>
                                <h6 class="text-muted">Terms & Conditions</h6>
                                <div class="bg-light p-3 rounded">
                                    {!! nl2br(e($quote->terms_conditions)) !!}
                                </div>
                            @endif

                            @if($quote->notes)
                                <hr>
                                <h6 class="text-muted">Internal Notes</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-sticky-note me-2"></i>
                                    {!! nl2br(e($quote->notes)) !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Signature Section -->
                    @if($quote->client_signature)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-signature me-2"></i>Client Signature
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="border p-3 rounded bg-light">
                                            <img src="{{ $quote->client_signature }}" alt="Client Signature" class="img-fluid" style="max-height: 150px;">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="small text-muted">
                                            <div class="mb-2">
                                                <strong>Signed on:</strong><br>
                                                {{ $quote->signature_date->format('M j, Y g:i A') }}
                                            </div>
                                            @if($quote->signature_ip)
                                                <div>
                                                    <strong>IP Address:</strong><br>
                                                    {{ $quote->signature_ip }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <!-- Quote Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Quote Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Quote Number:</span>
                                    <span class="fw-bold">{{ $quote->quote_number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Currency:</span>
                                    <span>{{ $quote->currency }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Created:</span>
                                    <span>{{ $quote->created_at->format('M j, Y') }}</span>
                                </div>
                                @if($quote->creator)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Created by:</span>
                                        <span>{{ $quote->creator->name }}</span>
                                    </div>
                                @endif
                                @if($quote->sent_at)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Sent:</span>
                                        <span>{{ $quote->sent_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                                @if($quote->viewed_at)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Viewed:</span>
                                        <span>{{ $quote->viewed_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                                @if($quote->accepted_date)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Accepted:</span>
                                        <span>{{ $quote->accepted_date->format('M j, Y') }}</span>
                                    </div>
                                @endif
                                @if($quote->declined_date)
                                    <div class="d-flex justify-content-between">
                                        <span>Declined:</span>
                                        <span>{{ $quote->declined_date->format('M j, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if(in_array($quote->status, ['sent', 'viewed']))
                                    <button class="btn btn-success btn-sm" onclick="acceptQuote({{ $quote->id }})">
                                        <i class="fas fa-check me-2"></i>Mark as Accepted
                                    </button>
                                    
                                    <button class="btn btn-warning btn-sm" onclick="declineQuote({{ $quote->id }})">
                                        <i class="fas fa-times me-2"></i>Mark as Declined
                                    </button>
                                @endif

                                @if($quote->isAccepted() && !$quote->isConverted())
                                    <button class="btn btn-info btn-sm" onclick="convertQuote({{ $quote->id }})">
                                        <i class="fas fa-file-invoice me-2"></i>Convert to Invoice
                                    </button>
                                @endif

                                <hr class="my-2">
                                
                                <a href="{{ route('clients.quotes.standalone.create', ['client_id' => $quote->client_id]) }}" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-plus me-2"></i>New Quote for Client
                                </a>
                                
                                <button class="btn btn-outline-secondary btn-sm" onclick="duplicateQuote({{ $quote->id }})">
                                    <i class="fas fa-copy me-2"></i>Duplicate Quote
                                </button>
                                
                                <a href="{{ route('clients.show', $quote->client) }}" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-user me-2"></i>View Client
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Details -->
                    @if($quote->invoice)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-invoice me-2"></i>Converted Invoice
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    This quote was converted to invoice:
                                </p>
                                <a href="{{ route('clients.invoices.show', $quote->invoice) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-2"></i>View Invoice {{ $quote->invoice->invoice_number }}
                                </a>
                                <div class="small text-muted mt-2">
                                    Converted on: {{ $quote->converted_date->format('M j, Y') }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Win/Loss Reason -->
                    @if($quote->win_loss_reason && ($quote->isDeclined() || $quote->isConverted()))
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-comment me-2"></i>{{ $quote->isConverted() ? 'Win' : 'Loss' }} Reason
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-0">
                                    {{ $quote->win_loss_reason }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Delete Quote Modal -->
<div class="modal fade" id="deleteQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Quote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this quote? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteQuoteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Quote</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Send this quote to the client?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Convert this quote to an invoice? This will create a new invoice with the quote details.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="convertQuoteForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Convert to Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Accept Quote Modal -->
<div class="modal fade" id="acceptQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Accept Quote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Mark this quote as accepted by the client?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="acceptQuoteForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Accept Quote</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Decline Quote Modal -->
<div class="modal fade" id="declineQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Decline Quote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="declineQuoteForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Mark this quote as declined by the client?</p>
                    <div class="mb-3">
                        <label for="declineReason" class="form-label">Reason (Optional)</label>
                        <textarea name="reason" id="declineReason" class="form-control" rows="3" placeholder="Reason for declining..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Decline Quote</button>
                </div>
            </form>
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

function acceptQuote(quoteId) {
    const form = document.getElementById('acceptQuoteForm');
    form.action = '/clients/quotes/' + quoteId + '/accept';
    
    const modal = new bootstrap.Modal(document.getElementById('acceptQuoteModal'));
    modal.show();
}

function declineQuote(quoteId) {
    const form = document.getElementById('declineQuoteForm');
    form.action = '/clients/quotes/' + quoteId + '/decline';
    
    const modal = new bootstrap.Modal(document.getElementById('declineQuoteModal'));
    modal.show();
}

function duplicateQuote(quoteId) {
    if (confirm('Create a duplicate of this quote?')) {
        window.location.href = '/clients/quotes/' + quoteId + '/duplicate';
    }
}
</script>
@endpush