@if($item->warranty_expire)
    <div class="space-y-1">
        <div>{{ $item->warranty_expire->format('M d, Y') }}</div>
        @if($item->warranty_expire->isPast())
            <flux:badge variant="danger" size="xs">Expired</flux:badge>
        @elseif($item->warranty_expire->diffInDays(now()) <= 30)
            <flux:badge variant="warning" size="xs">Expiring Soon</flux:badge>
        @elseif($item->warranty_expire->diffInDays(now()) <= 90)
            <flux:badge variant="info" size="xs">3 Months Left</flux:badge>
        @endif
    </div>
@else
    <span class="text-gray-400">-</span>
@endif
