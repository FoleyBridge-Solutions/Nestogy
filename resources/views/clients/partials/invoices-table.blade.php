@if($invoices->count() > 0)
    <flux:table>
        <flux:table.columns>
            <flux:table.column>Invoice #</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($invoices as $invoice)
                <flux:table.row class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" onclick="window.location='{{ route('financial.invoices.show', $invoice) }}'">
                    <flux:table.cell>#{{ $invoice->number ?? $invoice->id }}</flux:table.cell>
                    <flux:table.cell>{{ $invoice->created_at->format('M d, Y') }}</flux:table.cell>
                    <flux:table.cell>${{ number_format($invoice->amount ?? 0, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $invoice->status === 'paid' ? 'green' : ($invoice->status === 'overdue' ? 'red' : 'yellow') }}" size="sm">
                            {{ ucfirst($invoice->status ?? 'draft') }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <p class="text-gray-500 dark:text-gray-400">No invoices found for this client.</p>
@endif