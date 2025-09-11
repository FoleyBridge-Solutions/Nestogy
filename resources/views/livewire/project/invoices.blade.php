<div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Invoices</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->getFullNumber() }}</td>
                            <td>{{ $invoice->status }}</td>
                            <td>{{ $invoice->getFormattedAmount() }}</td>
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
