@props(['item'])

<button 
    wire:click="openCellModal('subject', {{ $item->id }})" 
    class="text-left hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer"
    type="button"
>
    <div class="text-gray-900 dark:text-white">
        {{ Str::limit($item->subject, 60) }}
    </div>
    <div class="text-xs text-gray-500 mt-1">
        Click to preview
    </div>
</button>
