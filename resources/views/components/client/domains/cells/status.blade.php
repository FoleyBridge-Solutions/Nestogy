<div class="flex items-center gap-2">
    @php
        $colors = [
            'active' => 'green',
            'pending' => 'amber',
            'suspended' => 'red',
            'transferred' => 'blue',
            'cancelled' => 'zinc',
        ];
        $color = $colors[$item->status] ?? 'gray';
    @endphp
    <flux:badge :color="$color" :label="ucfirst($item->status)" />
</div>
