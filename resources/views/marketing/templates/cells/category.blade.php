@props(['item'])

@php
    $colorMap = [
        'marketing' => 'purple',
        'transactional' => 'blue',
        'follow_up' => 'green',
        'onboarding' => 'yellow',
        'notification' => 'orange',
    ];
    $color = $colorMap[$item->category] ?? 'zinc';
@endphp

<flux:badge size="sm" :color="$color" inset="top bottom">
    {{ \App\Domains\Marketing\Models\EmailTemplate::getCategories()[$item->category] ?? $item->category }}
</flux:badge>
