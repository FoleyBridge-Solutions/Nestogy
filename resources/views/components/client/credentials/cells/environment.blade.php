<div class="flex items-center gap-2">
    @php
        $colors = [
            'production' => 'red',
            'staging' => 'amber',
            'testing' => 'blue',
            'development' => 'green',
            'sandbox' => 'gray',
        ];
        $color = $colors[$item->environment] ?? 'gray';
    @endphp
    @if($item->environment)
        <flux:badge :color="$color" :label="ucfirst($item->environment)" />
    @else
        <span class="text-sm text-gray-400">—</span>
    @endif
</div>
