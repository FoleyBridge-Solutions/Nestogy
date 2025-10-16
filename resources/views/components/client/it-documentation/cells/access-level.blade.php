<div class="flex items-center gap-2">
    @php
        $colors = [
            'public' => 'green',
            'confidential' => 'amber',
            'restricted' => 'red',
            'admin_only' => 'zinc',
        ];
        $color = $colors[$item->access_level] ?? 'gray';
    @endphp
    <flux:badge :color="$color" :label="str_replace('_', ' ', ucfirst($item->access_level))" />
</div>
