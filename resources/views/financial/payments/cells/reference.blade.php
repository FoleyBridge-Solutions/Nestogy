<div>
    <flux:text variant="strong">{{ $item->payment_reference ?? 'No Reference' }}</flux:text>
    @if($item->applications->count() > 0)
        <flux:text size="sm" variant="muted" class="block">
            {{ $item->applications->count() }} {{ Str::plural('application', $item->applications->count()) }}
        </flux:text>
    @endif
    @if($item->gateway_transaction_id)
        <flux:text size="xs" variant="muted" class="block">{{ $item->gateway_transaction_id }}</flux:text>
    @endif
</div>
