@if($item->client)
    <div>
        <flux:text>{{ $item->client->name }}</flux:text>
        @if($item->client->company_name)
            <flux:text size="sm" variant="muted" class="block">{{ $item->client->company_name }}</flux:text>
        @endif
    </div>
@else
    <flux:text variant="muted">-</flux:text>
@endif
