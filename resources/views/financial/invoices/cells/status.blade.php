@php
    $statusColor = match($item->status) {
        'Draft' => 'zinc',
        'Sent' => 'blue',
        'Paid' => 'green',
        'Cancelled' => 'red',
        default => 'zinc'
    };
    
    if ($item->status === 'Sent' && $item->due_date && \Carbon\Carbon::parse($item->due_date)->isPast()) {
        $statusColor = 'amber';
        $displayStatus = 'Overdue';
    } else {
        $displayStatus = $item->status;
    }
@endphp
<flux:badge size="sm" :color="$statusColor" inset="top bottom">
    {{ $displayStatus }}
</flux:badge>
