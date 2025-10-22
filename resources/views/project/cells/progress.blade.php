@if($item->progress !== null)
    <div class="flex items-center gap-2">
        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item->progress }}%"></div>
        </div>
        <span class="text-sm text-gray-600 dark:text-gray-400 w-8 text-right">{{ $item->progress }}%</span>
    </div>
@else
    <span class="text-gray-400">-</span>
@endif
