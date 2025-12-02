@extends('layouts.app')

@section('title', 'Bank Transaction Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-receipt-percent mr-2"></i>
                        Transaction Details
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('financial.bank-transactions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Bank Transactions
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date:</dt>
                                <dd class="col-sm-8">{{ $bankTransaction->transaction_date->format('M j, Y') }}</dd>

                                <dt class="col-sm-4">Amount:</dt>
                                <dd class="col-sm-8">
                                    <span class="{{ $bankTransaction->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format(abs($bankTransaction->amount), 2) }}
                                        <small>({{ $bankTransaction->amount >= 0 ? 'Credit' : 'Debit' }})</small>
                                    </span>
                                </dd>

                                <dt class="col-sm-4">Description:</dt>
                                <dd class="col-sm-8">{{ $bankTransaction->description }}</dd>

                                <dt class="col-sm-4">Merchant:</dt>
                                <dd class="col-sm-8">{{ $bankTransaction->merchant_name ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8">{{ $bankTransaction->category ?? 'Uncategorized' }}</dd>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Bank Account:</dt>
                                <dd class="col-sm-8">
                                    @if($bankTransaction->account)
                                        {{ $bankTransaction->account->name }}
                                        <br><small class="text-muted">{{ $bankTransaction->account->account_number }}</small>
                                    @else
                                        <span class="text-muted">Unknown Account</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Reconciliation:</dt>
                                <dd class="col-sm-8">
                                    @if($bankTransaction->reconciled_at)
                                        <span class="badge badge-success">Reconciled</span>
                                        <br><small class="text-muted">{{ $bankTransaction->reconciled_at->format('M j, Y g:i A') }}</small>
                                    @else
                                        <span class="badge badge-warning">Unreconciled</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    @if($bankTransaction->is_ignored)
                                        <span class="badge badge-secondary">Ignored</span>
                                    @else
                                        <span class="badge badge-info">Active</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Transaction ID:</dt>
                                <dd class="col-sm-8"><small class="text-muted">{{ $bankTransaction->transaction_id }}</small></dd>
                            </dl>
                        </div>
                    </div>

                    @if($bankTransaction->payment || $bankTransaction->expense)
                    <div class="mt-4">
                        <h5>Linked Records</h5>
                        <div class="row">
                            @if($bankTransaction->payment)
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-credit-card mr-1"></i>
                                            Linked Payment
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Reference:</strong> {{ $bankTransaction->payment->payment_reference }}</p>
                                        <p class="mb-1"><strong>Amount:</strong> ${{ number_format($bankTransaction->payment->amount, 2) }}</p>
                                        <p class="mb-1"><strong>Status:</strong> {{ ucfirst($bankTransaction->payment->status) }}</p>
                                        <a href="{{ route('financial.payments.show', $bankTransaction->payment) }}" class="btn btn-sm btn-outline-primary">
                                            View Payment
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($bankTransaction->expense)
                            <div class="col-md-6">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-receipt-percent mr-1"></i>
                                            Linked Expense
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Description:</strong> {{ $bankTransaction->expense->description }}</p>
                                        <p class="mb-1"><strong>Amount:</strong> ${{ number_format($bankTransaction->expense->amount, 2) }}</p>
                                        <p class="mb-1"><strong>Status:</strong> {{ ucfirst($bankTransaction->expense->status) }}</p>
                                        <a href="{{ route('financial.expenses.show', $bankTransaction->expense) }}" class="btn btn-sm btn-outline-danger">
                                            View Expense
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($bankTransaction->reconciliation_notes)
                    <div class="mt-4">
                        <h5>Reconciliation Notes</h5>
                        <div class="alert alert-info">
                            {{ $bankTransaction->reconciliation_notes }}
                        </div>
                    </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="btn-group">
                        @can('reconcile', $bankTransaction)
                        @if(!$bankTransaction->reconciled_at)
                        <button type="button" class="btn btn-success btn-sm" onclick="reconcileTransaction({{ $bankTransaction->id }})">
                            <i class="fas fa-check"></i> Mark as Reconciled
                        </button>
                        @else
                        <button type="button" class="btn btn-warning btn-sm" onclick="unreconcileTransaction({{ $bankTransaction->id }})">
                            <i class="fas fa-undo"></i> Unreconcile
                        </button>
                        @endif
                        @endcan

                        @can('ignore', $bankTransaction)
                        @if(!$bankTransaction->is_ignored)
                        <button type="button" class="btn btn-secondary btn-sm" onclick="ignoreTransaction({{ $bankTransaction->id }})">
                            <i class="fas fa-eye-slash"></i> Ignore Transaction
                        </button>
                        @else
                        <button type="button" class="btn btn-info btn-sm" onclick="unignoreTransaction({{ $bankTransaction->id }})">
                            <i class="fas fa-eye"></i> Unignore Transaction
                        </button>
                        @endif
                        @endcan

                        @can('update', $bankTransaction)
                        <a href="{{ route('financial.bank-transactions.edit', $bankTransaction) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endcan

                        @can('delete', $bankTransaction)
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $bankTransaction->id }})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reconcileTransaction(transactionId) {
    if (confirm('Are you sure you want to mark this transaction as reconciled?')) {
        fetch(`/financial/bank-transactions/${transactionId}/reconcile`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error reconciling transaction: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error reconciling transaction');
        });
    }
}

function unreconcileTransaction(transactionId) {
    if (confirm('Are you sure you want to unreconcile this transaction?')) {
        fetch(`/financial/bank-transactions/${transactionId}/unreconcile`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error unreconciling transaction: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error unreconciling transaction');
        });
    }
}

function ignoreTransaction(transactionId) {
    if (confirm('Are you sure you want to ignore this transaction?')) {
        fetch(`/financial/bank-transactions/${transactionId}/ignore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error ignoring transaction: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error ignoring transaction');
        });
    }
}

function unignoreTransaction(transactionId) {
    if (confirm('Are you sure you want to unignore this transaction?')) {
        fetch(`/financial/bank-transactions/${transactionId}/unignore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error unignoring transaction: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error unignoring transaction');
        });
    }
}

function confirmDelete(transactionId) {
    if (confirm('Are you sure you want to delete this bank transaction?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/financial/bank-transactions/${transactionId}`;

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);

        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = '_token';
        csrfField.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfField);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection