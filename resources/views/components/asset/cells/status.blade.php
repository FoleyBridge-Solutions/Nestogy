@php
    $statusColors = [
        'Ready To Deploy' => 'emerald',
        'Deployed' => 'green',
        'Archived' => 'zinc',
        'Broken - Pending Repair' => 'amber',
        'Broken - Not Repairable' => 'red',
        'Out for Repair' => 'amber',
        'Lost/Stolen' => 'red',
        'Unknown' => 'zinc',
    ];
    $color = $statusColors[$item->status] ?? 'zinc';
@endphp
<flux:badge :color="$color" size="sm">
    {{ $item->status }}
</flux:badge>
