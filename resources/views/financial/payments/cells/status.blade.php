@php
    $statusColor = match($item->status) {
        'pending' => 'amber',
        'completed' => 'green',
        'failed' => 'red',
        'refunded' => 'zinc',
        default => 'zinc'
    };
@endphp
<flux:badge size="sm" :color="$statusColor" inset="top bottom">
    {{ ucfirst($item->status) }}
</flux:badge>
