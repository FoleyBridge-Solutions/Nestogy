@props(['item'])

@php
    $colorMap = [
        'enrolled' => 'blue',
        'active' => 'green',
        'completed' => 'purple',
        'paused' => 'yellow',
        'unsubscribed' => 'red',
        'bounced' => 'red',
    ];
    $color = $colorMap[$item->status] ?? 'zinc';
@endphp

<flux:badge size="sm" :color="$color" inset="top bottom">
    {{ ucfirst($item->status) }}
</flux:badge>
