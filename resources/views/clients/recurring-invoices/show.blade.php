@extends('layouts.app')

@section('title', 'Recurring Invoice Details')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <div class="flex justify-between items-center mb-4">
                <h1 class="h3 mb-0">Recurring Invoice Details</h1>
                <div class="btn-group">
                    @if($recurringInvoice->isActive())
                        <a href="{{ route('clients.recurring-invoices.standalone.edit', $recurringInvoice) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-edit mr-2"></i>Edit Invoice
                        </a>
                        @if($recurringInvoice->isDue())
                            <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="generateInvoice({{ $recurringInvoice->id }})">
                                <i class="fas fa-file-invoice mr-2"></i>Generate Invoice
                            </button>
                        @endif
                    @endif
                    <a href="{{ route('clients.recurring-invoices.standalone.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Invoices
                    </a>
                    <button type="button" 
                            class="btn btn-outline-danger" 
                            onclick="deleteInvoice({{ $recurringInvoice->id }})">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="col-lg-8">
                    <!-- Main Invoice Details -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-4">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex justify-between items-center">
                                <h5 class="bg-white rounded-lg shadow-md overflow-hidden-title mb-0">
                                    <i class="fas fa-file-invoice me-2"></i>{{ $recurringInvoice->template_name }}
                                </h5>
                                <div class="d-flex gap-2">
                                    @switch($recurringInvoice->status)
                                        @case('draft')
                                            <span class="badge bg-gray-600">Draft</span>
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
                                            <span class="badge bg-gray-900">Expired</span>
                                            @break
                                    @endswitch

                                    @if($recurringInvoice->auto_send)
                                        <span class="badge bg-info">Auto Send</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="row">
                                <div class="md:w-1/2 px-4">
                                    <h6 class="text-gray-600">Client</h6>
                                    <p class="mb-3">
                                        <a href="{{ route('clients.show', $recurringInvoice->client) }}" class="text-decoration-none">
                                            {{ $recurringInvoice->client->display_name }}
                                        </a>
                                    </p>

                                    <h6 class="text-gray-600">Amount Details</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Subtotal:</span>
                                            <span class="fw-bold">{{ $recurringInvoice->formatted_amount }}</span>
                                        </div>
                                        @if($recurringInvoice->tax_rate > 0)
                                            <div class="d-flex justify-content-between">
                                                <span>Tax ({{ $recurringInvoice->tax_rate }}%):</span>
                                                <span>{{ $recurringInvoice->getCurrencySymbol() }}{{ number_format($recurringInvoice->tax_amount, 2) }}</span>
                                            </div>
                                        @endif
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">Total:</span>
                                            <span class="fw-bold text-blue-600 fs-5">{{ $recurringInvoice->formatted_total_amount }}</span>
                                        </div>
                                    </div>

                                    <h6 class="text-muted">Currency</h6>
                                    <p class="mb-3">{{ $recurringInvoice->currency }} ({{ $recurringInvoice->getCurrencySymbol() }})</p>
                                </div>

                                <div class="md:w-1/2 px-4">
                                    <h6 class="text-muted">Frequency</h6>
                                    <p class="mb-3">{{ $recurringInvoice->frequency_description }}</p>

                                    <h6 class="text-muted">Schedule</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Start Date:</span>
                                            <span>{{ $recurringInvoice->start_date->format('M j, Y') }}</span>
                                        </div>
                                        @if($recurringInvoice->end_date)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>End Date:</span>
                                                <span>{{ $recurringInvoice->end_date->format('M j, Y') }}</span>
                                            </div>
                                        @endif
                                        @if($recurringInvoice->next_invoice_date)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Next Invoice:</span>
                                                <span class="fw-bold">{{ $recurringInvoice->next_invoice_date->format('M j, Y') }}</span>
                                            </div>
                                            @if($recurringInvoice->days_until_next_invoice !== null)
                                                <div class="mt-2">
                                                    @if($recurringInvoice->days_until_next_invoice < 0)
                                                        <span class="badge bg-danger">{{ abs($recurringInvoice->days_until_next_invoice) }} days overdue</span>
                                                    @elseif($recurringInvoice->days_until_next_invoice == 0)
                                                        <span class="badge bg-warning">Due Today</span>
                                                    @elseif($recurringInvoice->days_until_next_invoice <= 7)
                                                        <span class="badge bg-info">{{ $recurringInvoice->days_until_next_invoice }} days to go</span>
                                                    @else
                                                        <span class="text-muted">{{ $recurringInvoice->days_until_next_invoice }} days to go</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                    </div>

                                    <h6 class="text-muted">Payment Terms</h6>
                                    <p class="mb-3">{{ $recurringInvoice->payment_terms_days }} days</p>
                                </div>
                            </div>

                            @if($recurringInvoice->description)
                                <hr>
                                <h6 class="text-muted">Description</h6>
                                <div class="mb-3">
                                    {!! nl2br(e($recurringInvoice->description)) !!}
                                </div>
                            @endif

                            @if($recurringInvoice->line_items && count($recurringInvoice->line_items) > 0)
                                <hr>
                                <h6 class="text-muted">Line Items</h6>
                                <div class="mb-3">
                                    <ul class="list-unstyled">
                                        @foreach($recurringInvoice->line_items as $item)
                                            <li class="mb-1">
                                                <i class="fas fa-check text-green-600 me-2"></i>
                                                {{ $item['description'] }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($recurringInvoice->invoice_notes)
                                <hr>
                                <h6 class="text-muted">Invoice Notes</h6>
                                <div class="mb-3 bg-gray-100 p-3 rounded">
                                    {!! nl2br(e($recurringInvoice->invoice_notes)) !!}
                                </div>
                            @endif

                            @if($recurringInvoice->payment_instructions)
                                <hr>
                                <h6 class="text-muted">Payment Instructions</h6>
                                <div class="bg-gray-100 p-3 rounded">
                                    {!! nl2br(e($recurringInvoice->payment_instructions)) !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Late Fee Settings -->
                    @if($recurringInvoice->late_fee_percentage > 0 || $recurringInvoice->late_fee_flat_amount > 0)
                        <div class="card mb-4">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Late Fee Settings
                                </h5>
                            </div>
                            <div class="p-6">
                                <div class="row">
                                    @if($recurringInvoice->late_fee_percentage > 0)
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Percentage Fee</h6>
                                            <p class="mb-0">{{ $recurringInvoice->late_fee_percentage }}% of total amount</p>
                                        </div>
                                    @endif
                                    @if($recurringInvoice->late_fee_flat_amount > 0)
                                        <div class="col-md-6">
                                            <h6 class="text-muted">Flat Fee</h6>
                                            <p class="mb-0">{{ $recurringInvoice->getCurrencySymbol() }}{{ number_format($recurringInvoice->late_fee_flat_amount, 2) }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <!-- Performance Metrics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar me-2"></i>Performance
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-blue-600">{{ $recurringInvoice->invoice_count ?? 0 }}</div>
                                        <small class="text-muted">Invoices</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 bg-light rounded">
                                        <div class="fw-bold text-green-600">{{ $recurringInvoice->getCurrencySymbol() }}{{ number_format($recurringInvoice->total_revenue ?? 0, 0) }}</div>
                                        <small class="text-muted">Revenue</small>
                                    </div>
                                </div>
                            </div>

                            @if($recurringInvoice->invoice_count > 0)
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="fw-bold text-info">{{ $recurringInvoice->getCurrencySymbol() }}{{ number_format(($recurringInvoice->total_revenue ?? 0) / $recurringInvoice->invoice_count, 2) }}</div>
                                    <small class="text-muted">Avg per Invoice</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Invoice Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Invoice Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Prefix:</span>
                                    <span class="fw-bold">{{ $recurringInvoice->invoice_prefix }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Auto Send:</span>
                                    <span>{{ $recurringInvoice->auto_send ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Created:</span>
                                    <span>{{ $recurringInvoice->created_at->format('M j, Y') }}</span>
                                </div>
                                @if($recurringInvoice->creator)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Created by:</span>
                                        <span>{{ $recurringInvoice->creator->name }}</span>
                                    </div>
                                @endif
                                @if($recurringInvoice->last_invoice_date)
                                    <div class="d-flex justify-content-between">
                                        <span>Last Invoice:</span>
                                        <span>{{ $recurringInvoice->last_invoice_date->format('M j, Y') }}</span>
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
                                @if($recurringInvoice->isActive())
                                    @if($recurringInvoice->isDue())
                                        <button class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 btn-sm" onclick="generateInvoice({{ $recurringInvoice->id }})">
                                            <i class="fas fa-file-invoice me-2"></i>Generate Invoice Now
                                        </button>
                                    @endif
                                    
                                    <button class="btn btn-warning btn-sm" onclick="pauseInvoice({{ $recurringInvoice->id }})">
                                        <i class="fas fa-pause me-2"></i>Pause Invoice
                                    </button>
                                    
                                    <button class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 btn-sm" onclick="cancelInvoice({{ $recurringInvoice->id }})">
                                        <i class="fas fa-times me-2"></i>Cancel Invoice
                                    </button>
                                @elseif($recurringInvoice->isPaused())
                                    <button class="btn btn-success btn-sm" onclick="resumeInvoice({{ $recurringInvoice->id }})">
                                        <i class="fas fa-play me-2"></i>Resume Invoice
                                    </button>
                                @endif

                                <hr class="my-2">
                                
                                <a href="{{ route('clients.recurring-invoices.standalone.create', ['client_id' => $recurringInvoice->client_id]) }}" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-plus me-2"></i>New Invoice for Client
                                </a>
                                
                                <a href="{{ route('clients.show', $recurringInvoice->client) }}" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-user me-2"></i>View Client
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Status History -->
                    @if($recurringInvoice->isPaused() || $recurringInvoice->isCancelled())
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Status History
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="small text-muted">
                                    @if($recurringInvoice->isPaused())
                                        <div class="mb-2">
                                            <strong>Paused:</strong> {{ $recurringInvoice->paused_at->format('M j, Y g:i A') }}
                                            @if($recurringInvoice->paused_reason)
                                                <div class="mt-1">
                                                    <strong>Reason:</strong> {{ $recurringInvoice->paused_reason }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($recurringInvoice->isCancelled())
                                        <div>
                                            <strong>Cancelled:</strong> {{ $recurringInvoice->cancelled_at->format('M j, Y g:i A') }}
                                            @if($recurringInvoice->cancelled_reason)
                                                <div class="mt-1">
                                                    <strong>Reason:</strong> {{ $recurringInvoice->cancelled_reason }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
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
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this recurring invoice? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="deleteInvoiceForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">Delete Invoice</button>
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
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Generate an invoice from this recurring invoice template?
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <form id="generateInvoiceForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Generate Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pause Invoice Modal -->
<div class="modal fade" id="pauseInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pause Recurring Invoice</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <form id="pauseInvoiceForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to pause this recurring invoice?</p>
                    <div class="mb-3">
                        <label for="pauseReason" class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                        <textarea name="reason" id="pauseReason" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" rows="3" placeholder="Reason for pausing..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$dispatch('close-modal')">Cancel</button>
                    <button type="submit" class="btn btn-warning">Pause Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Resume Invoice Modal -->
<div class="modal fade" id="resumeInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resume Recurring Invoice</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                Resume this recurring invoice? It will start generating invoices according to its schedule.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="$dispatch('close-modal')">Cancel</button>
                <form id="resumeInvoiceForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Resume Invoice</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Invoice Modal -->
<div class="modal fade" id="cancelInvoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Recurring Invoice</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <form id="cancelInvoiceForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to cancel this recurring invoice? This action cannot be undone.</p>
                    <div class="mb-3">
                        <label for="cancelReason" class="block text-sm font-medium text-gray-700 mb-1">Reason (Optional)</label>
                        <textarea name="reason" id="cancelReason" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" rows="3" placeholder="Reason for cancelling..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="$dispatch('close-modal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Cancel Invoice</button>
                </div>
            </form>
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

function pauseInvoice(invoiceId) {
    const form = document.getElementById('pauseInvoiceForm');
    form.action = '/clients/recurring-invoices/' + invoiceId + '/pause';
    
    const modal = new bootstrap.Modal(document.getElementById('pauseInvoiceModal'));
    modal.show();
}

function resumeInvoice(invoiceId) {
    const form = document.getElementById('resumeInvoiceForm');
    form.action = '/clients/recurring-invoices/' + invoiceId + '/resume';
    
    const modal = new bootstrap.Modal(document.getElementById('resumeInvoiceModal'));
    modal.show();
}

function cancelInvoice(invoiceId) {
    const form = document.getElementById('cancelInvoiceForm');
    form.action = '/clients/recurring-invoices/' + invoiceId + '/cancel';
    
    const modal = new bootstrap.Modal(document.getElementById('cancelInvoiceModal'));
    modal.show();
}
</script>
@endpush