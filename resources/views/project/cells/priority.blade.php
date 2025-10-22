@php
    $priorityColors = [
        'low' => 'zinc',
        'medium' => 'blue',
        'high' => 'amber',
        'critical' => 'red',
        'urgent' => 'red',
    ];
    $color = $priorityColors[$item->priority] ?? 'zinc';
@endphp
<flux:badge :color="$color" size="sm">
    {{ ucfirst($item->priority ?? 'N/A') }}
</flux:badge>
