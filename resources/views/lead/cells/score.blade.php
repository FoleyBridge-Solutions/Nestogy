@props(['item'])

<button 
    wire:click="openCellModal('total_score', {{ $item->id }})" 
    class="flex items-center gap-3 hover:opacity-75 transition-opacity cursor-pointer w-full text-left"
    type="button"
>
    <div class="font-medium text-gray-900 dark:text-white min-w-[3rem]">
        {{ $item->total_score }}/100
    </div>
    <div class="flex-1 max-w-[100px]">
        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div 
                class="h-2 rounded-full transition-all
                    @if($item->total_score >= 80) bg-green-500
                    @elseif($item->total_score >= 60) bg-blue-500
                    @elseif($item->total_score >= 40) bg-yellow-500
                    @else bg-red-500
                    @endif"
                style="width: {{ $item->total_score }}%"
            ></div>
        </div>
    </div>
</button>
