@php
    if ($item->status === 'Sent' && $item->due_date && \Carbon\Carbon::parse($item->due_date)->isPast()) {
        $displayStatus = 'Overdue';
        $statusColor = \App\Helpers\StatusColorHelper::conditional('overdue');
    } else {
        $displayStatus = $item->status;
        $statusColor = $item->status_color;
    }
@endphp
<flux:badge size="sm" :color="$statusColor" inset="top bottom">
    {{ $displayStatus }}
</flux:badge>
