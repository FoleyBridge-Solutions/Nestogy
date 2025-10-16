<div class="flex items-center gap-2">
    @php
        $isExpired = $item->expires_at && $item->expires_at->isPast();
        $expiringWithin30 = $item->expires_at && $item->expires_at->isBefore(now()->addDays(30)) && $item->expires_at->isAfter(now());
    @endphp
    @if($isExpired)
        <flux:badge color="red" :label="$item->expires_at->format('M d, Y')" />
        <span class="text-xs text-red-600 font-semibold">Expired</span>
    @elseif($expiringWithin30)
        <flux:badge color="amber" :label="$item->expires_at->format('M d, Y')" />
        <span class="text-xs text-amber-600">{{ $item->expires_at->diffForHumans() }}</span>
    @elseif($item->expires_at)
        <span class="text-sm text-gray-600">{{ $item->expires_at->format('M d, Y') }}</span>
    @else
        <span class="text-sm text-gray-400">No expiration</span>
    @endif
</div>
