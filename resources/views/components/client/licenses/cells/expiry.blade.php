<div class="flex items-center gap-2">
    @php
        $isExpired = $item->expiry_date && $item->expiry_date->isPast();
        $expiringWithin90 = $item->expiry_date && $item->expiry_date->isBefore(now()->addDays(90)) && $item->expiry_date->isAfter(now());
    @endphp
    @if($isExpired)
        <flux:badge color="red" :label="$item->expiry_date->format('M d, Y')" />
        <span class="text-xs text-red-600 font-semibold">Expired</span>
    @elseif($expiringWithin90)
        <flux:badge color="amber" :label="$item->expiry_date->format('M d, Y')" />
        <span class="text-xs text-amber-600">{{ $item->expiry_date->diffForHumans() }}</span>
    @elseif($item->expiry_date)
        <span class="text-sm text-gray-600">{{ $item->expiry_date->format('M d, Y') }}</span>
    @else
        <span class="text-sm text-gray-400">No expiration</span>
    @endif
</div>
