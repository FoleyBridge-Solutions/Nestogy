@php
    $supportStatusColors = [
        'supported' => 'green',
        'unsupported' => 'red',
        'pending_assignment' => 'amber',
        'excluded' => 'zinc',
    ];
    $color = $supportStatusColors[$item->support_status] ?? 'zinc';

    $supportStatuses = \App\Domains\Asset\Models\Asset::SUPPORT_STATUSES;
    $label = $supportStatuses[$item->support_status] ?? ucfirst(str_replace('_', ' ', $item->support_status));
@endphp
<flux:badge :color="$color" size="sm">
    {{ $label }}
</flux:badge>
