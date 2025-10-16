@if($item->due_date)
    <div class="flex items-center gap-2">
        {{ \Carbon\Carbon::parse($item->due_date)->format('M d, Y') }}
        @if($item->status === 'Sent' && \Carbon\Carbon::parse($item->due_date)->isPast())
            <flux:icon name="exclamation-triangle" variant="mini" class="text-amber-500" />
        @endif
    </div>
@else
    <flux:text variant="muted">-</flux:text>
@endif
