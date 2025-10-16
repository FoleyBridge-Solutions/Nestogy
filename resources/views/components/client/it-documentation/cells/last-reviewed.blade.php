<div class="flex items-center gap-2 text-sm text-gray-600">
    @if($item->last_reviewed_at)
        {{ $item->last_reviewed_at->format('M d, Y') }}
        <span class="text-xs text-gray-500">({{ $item->last_reviewed_at->diffForHumans() }})</span>
    @else
        <span class="italic text-gray-400">Never reviewed</span>
    @endif
</div>
