@extends('layouts.app')

@section('title', 'Bank Connection Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-building-library mr-2"></i>
                        Bank Connection: {{ $plaidItem->institution_name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('financial.bank-connections.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Bank Connections
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Institution:</dt>
                                <dd class="col-sm-8">{{ $plaidItem->institution_name }}</dd>

                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    @if($plaidItem->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($plaidItem->status === 'error')
                                        <span class="badge badge-danger">Error</span>
                                    @else
                                        <span class="badge badge-warning">{{ ucfirst($plaidItem->status) }}</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Last Sync:</dt>
                                <dd class="col-sm-8">
                                    {{ $plaidItem->last_sync_at ? $plaidItem->last_sync_at->format('M j, Y g:i A') : 'Never' }}
                                </dd>

                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $plaidItem->created_at->format('M j, Y g:i A') }}</dd>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Accounts:</dt>
                                <dd class="col-sm-8">{{ $plaidItem->accounts->count() }} connected</dd>

                                <dt class="col-sm-4">Transactions:</dt>
                                <dd class="col-sm-8">{{ $plaidItem->bankTransactions->count() }} synced</dd>

                                <dt class="col-sm-4">Error Message:</dt>
                                <dd class="col-sm-8">
                                    @if($plaidItem->error_message)
                                        <span class="text-danger">{{ $plaidItem->error_message }}</span>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    @if($plaidItem->accounts->count() > 0)
                    <div class="mt-4">
                        <h5>Connected Accounts</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Account Name</th>
                                        <th>Type</th>
                                        <th>Subtype</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($plaidItem->accounts as $account)
                                    <tr>
                                        <td>{{ $account->name }}</td>
                                        <td>{{ ucfirst($account->type) }}</td>
                                        <td>{{ ucfirst($account->subtype ?? 'N/A') }}</td>
                                        <td>${{ number_format($account->current_balance ?? 0, 2) }}</td>
                                        <td>
                                            @if($account->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="btn-group">
                        @can('sync', $plaidItem)
                        <button type="button" class="btn btn-primary btn-sm" onclick="syncTransactions({{ $plaidItem->id }})">
                            <i class="fas fa-sync"></i> Sync Transactions
                        </button>
                        @endcan

                        @can('update', $plaidItem)
                        <a href="{{ route('financial.bank-connections.edit', $plaidItem) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endcan

                        @can('delete', $plaidItem)
                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $plaidItem->id }})">
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
function syncTransactions(plaidItemId) {
    if (confirm('Are you sure you want to sync transactions for this bank connection?')) {
        // Implement sync functionality
        fetch(`/financial/bank-connections/${plaidItemId}/sync`, {
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
                alert('Error syncing transactions: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error syncing transactions');
        });
    }
}

function confirmDelete(plaidItemId) {
    if (confirm('Are you sure you want to delete this bank connection? This will also delete all associated transactions.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/financial/bank-connections/${plaidItemId}`;

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