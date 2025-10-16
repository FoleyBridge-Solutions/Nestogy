<div class="flex items-center gap-2">
    @php
        $colors = [
            'software' => 'blue',
            'hardware' => 'purple',
            'service' => 'green',
            'cloud' => 'sky',
            'subscription' => 'orange',
            'perpetual' => 'emerald',
            'oem' => 'cyan',
            'volume' => 'indigo',
            'concurrent' => 'amber',
            'named_user' => 'rose',
            'other' => 'gray',
        ];
        $color = $colors[$item->license_type] ?? 'gray';
    @endphp
    <flux:badge :color="$color" :label="str_replace('_', ' ', ucfirst($item->license_type))" />
</div>
