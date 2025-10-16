<div class="flex items-center gap-2">
    @php
        $isOverdue = $item->next_review_at && $item->next_review_at->isPast();
    @endphp
    @if($item->next_review_at)
        <flux:badge
            :color="$isOverdue ? 'red' : 'blue'"
            :label="$item->next_review_at->format('M d, Y')"
        />
        @if($isOverdue)
            <span class="text-xs text-red-600 font-semibold">Overdue</span>
        @else
            <span class="text-xs text-blue-600">{{ $item->next_review_at->diffForHumans() }}</span>
        @endif
    @else
        <span class="italic text-gray-400">Not scheduled</span>
    @endif
</div>
