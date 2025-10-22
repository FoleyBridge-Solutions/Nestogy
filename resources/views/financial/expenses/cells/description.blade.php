<div>
    <a href="{{ route('financial.expenses.show', $item) }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
        {{ $item->description }}
    </a>
    @if($item->title)
        <div class="text-xs text-gray-500">{{ $item->title }}</div>
    @endif
</div>
