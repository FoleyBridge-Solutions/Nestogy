@php
    $statusColor = match($item->status) {
        'draft' => 'zinc',
        'sent' => 'blue',
        'accepted' => 'green',
        'rejected' => 'red',
        'expired' => 'amber',
        default => 'zinc'
    };
@endphp
<flux:badge size="sm" :color="$statusColor" inset="top bottom">
    {{ ucfirst($item->status) }}
</flux:badge>
