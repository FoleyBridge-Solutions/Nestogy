@props(['item'])

@php
    $statusColors = [
        'new' => 'blue',
        'contacted' => 'yellow',
        'qualified' => 'green',
        'unqualified' => 'gray',
        'nurturing' => 'purple',
        'converted' => 'emerald',
        'lost' => 'red',
    ];
    
    $color = $statusColors[$item->status] ?? 'gray';
@endphp

<flux:badge :color="$color" size="sm">
    {{ ucfirst($item->status) }}
</flux:badge>
