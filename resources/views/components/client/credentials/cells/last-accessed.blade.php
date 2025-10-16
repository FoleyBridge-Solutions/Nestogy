<div class="flex items-center gap-2 text-sm text-gray-600">
    @if($item->last_accessed_at)
        {{ $item->last_accessed_at->format('M d, Y H:i') }}
        <span class="text-xs text-gray-500">({{ $item->last_accessed_at->diffForHumans() }})</span>
    @else
        <span class="italic text-gray-400">Never accessed</span>
    @endif
</div>
