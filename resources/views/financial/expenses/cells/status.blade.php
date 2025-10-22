@php
    $color = match($item->status) {
        'approved' => 'green',
        'pending_approval' => 'yellow',
        'submitted' => 'blue',
        'rejected' => 'red',
        'paid' => 'purple',
        'invoiced' => 'indigo',
        'draft' => 'zinc',
        'cancelled' => 'zinc',
        default => 'zinc',
    };
@endphp

<flux:badge color="{{ $color }}" size="sm" inset="top bottom">
    {{ str($item->status)->replace('_', ' ')->title() }}
</flux:badge>
