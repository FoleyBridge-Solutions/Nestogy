@php
    $statusColor = match($item->status) {
        'draft' => 'zinc',
        'active' => 'green',
        'expired' => 'amber',
        'terminated' => 'red',
        'pending_approval' => 'blue',
        default => 'zinc'
    };
@endphp
<flux:badge size="sm" :color="$statusColor" inset="top bottom">
    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
</flux:badge>
