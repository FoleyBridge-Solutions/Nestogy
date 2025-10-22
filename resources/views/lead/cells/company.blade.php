@props(['item'])

<div>
    @if($item->company_name)
        <div class="font-medium text-gray-900 dark:text-white">{{ $item->company_name }}</div>
    @else
        <span class="text-gray-400 dark:text-gray-500">-</span>
    @endif
    @if($item->title)
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->title }}</div>
    @endif
</div>
