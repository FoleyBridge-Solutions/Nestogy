<div class="flex items-center gap-2">
    @php
        $colors = [
            'draft' => 'zinc',
            'review' => 'amber',
            'approved' => 'green',
            'published' => 'blue',
        ];
        $color = $colors[$item->status] ?? 'gray';
    @endphp
    <flux:badge :color="$color" :label="ucfirst($item->status)" />
</div>
