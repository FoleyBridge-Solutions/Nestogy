<div class="relative">
    {{-- Search Input --}}
    <div class="relative">
        <flux:input 
            wire:model.live.debounce.300ms="query" 
            type="search" 
            placeholder="Search documentation..."
            icon="magnifying-glass"
            class="w-full"
        />
        
        @if($query)
            <button wire:click="clear" class="absolute right-3 top-1/2 -translate-y-1/2">
                <flux:icon name="x-mark" class="size-5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" />
            </button>
        @endif
    </div>

    {{-- Search Results Dropdown --}}
    @if($showResults)
        <div class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-900 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-800 max-h-96 overflow-y-auto z-50">
            @if(count($results) > 0)
                <div class="p-2">
                    @foreach($results as $result)
                        <a href="{{ route('docs.show', $result['slug']) }}" 
                           wire:navigate
                           wire:click="closeSearch"
                           class="block p-3 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                            <div class="flex items-start gap-3">
                                <flux:icon :name="$result['icon']" class="size-5 text-zinc-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ $result['title'] }}
                                    </div>
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                        {{ $result['description'] }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-zinc-600 dark:text-zinc-400">
                    <flux:icon name="magnifying-glass" class="size-8 mx-auto mb-2 text-zinc-300 dark:text-zinc-700" />
                    <p class="text-sm">No results found for "{{ $query }}"</p>
                </div>
            @endif
        </div>
    @endif
</div>
