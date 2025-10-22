@props(['item'])

<div class="flex items-center">
    <flux:icon.document-text class="size-5 mr-3 text-gray-400" />
    <div>
        <div class="font-medium text-gray-900 dark:text-white">
            {{ $item->name }}
        </div>
        @if($item->campaigns_count > 0)
            <div class="text-xs text-gray-500">
                Used in {{ $item->campaigns_count }} {{ Str::plural('campaign', $item->campaigns_count) }}
            </div>
        @endif
    </div>
</div>
