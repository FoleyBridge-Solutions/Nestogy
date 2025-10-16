<div>
    <flux:text>{{ ucfirst(str_replace('_', ' ', $item->payment_method)) }}</flux:text>
    @if($item->gateway)
        <flux:text size="sm" variant="muted" class="block">{{ ucfirst(str_replace('_', ' ', $item->gateway)) }}</flux:text>
    @endif
</div>
