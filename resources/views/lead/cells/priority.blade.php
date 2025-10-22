@props(['item'])

@php
    $priorityColors = [
        'low' => 'gray',
        'medium' => 'blue',
        'high' => 'yellow',
        'urgent' => 'red',
    ];
    
    $color = $priorityColors[$item->priority] ?? 'gray';
@endphp

<flux:badge :color="$color" size="sm">
    {{ ucfirst($item->priority) }}
</flux:badge>
