@props(['item'])

<div>
    <a href="{{ route('leads.show', $item) }}" class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
        {{ $item->full_name }}
    </a>
    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->email }}</div>
    @if($item->phone)
        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->phone }}</div>
    @endif
</div>
