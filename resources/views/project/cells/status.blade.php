@php
    $statusColors = [
        'pending' => 'zinc',
        'planning' => 'zinc',
        'active' => 'green',
        'in_progress' => 'green',
        'on_hold' => 'amber',
        'completed' => 'blue',
        'cancelled' => 'red',
    ];
    $color = $statusColors[$item->status] ?? 'zinc';
@endphp
<flux:badge :color="$color" size="sm">
    {{ ucfirst(str_replace('_', ' ', $item->status)) }}
</flux:badge>
