@props(['item'])

<div>
    <div class="font-medium text-gray-900 dark:text-white">
        {{ $item->campaign->name ?? 'Unknown Campaign' }}
    </div>
    @if($item->campaign)
        <div class="text-xs text-gray-500">
            {{ ucfirst($item->campaign->status) }}
        </div>
    @endif
</div>
