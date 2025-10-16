@php
    $appStatus = $item->application_status ?? 'unapplied';
    $appColor = match($appStatus) {
        'fully_applied' => 'green',
        'partially_applied' => 'amber',
        'unapplied' => 'zinc',
        default => 'zinc'
    };
@endphp
<flux:badge size="sm" :color="$appColor" inset="top bottom">
    {{ ucfirst(str_replace('_', ' ', $appStatus)) }}
</flux:badge>
