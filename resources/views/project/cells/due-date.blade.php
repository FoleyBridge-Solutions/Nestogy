@if($item->due)
    <div class="space-y-1">
        <div>{{ $item->due->format('M d, Y') }}</div>
        @if($item->due->isPast() && !in_array($item->status, ['completed', 'cancelled']))
            <flux:badge variant="danger" size="xs">Overdue</flux:badge>
        @elseif($item->due->diffInDays(now()) <= 7 && $item->due->isFuture())
            <flux:badge variant="warning" size="xs">Due Soon</flux:badge>
        @endif
    </div>
@else
    <span class="text-gray-400">-</span>
@endif
