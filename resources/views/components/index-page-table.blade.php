@props([
    'items',
    'emptyIcon' => 'document-text',
    'emptyTitle' => 'No items found',
    'emptyMessage' => 'No items to display',
    'emptyAction' => null,
    'emptyActionLabel' => 'Create New',
    'hasFilters' => false,
    'filterClearAction' => null,
])

<flux:card>
    @if($items->count() > 0)
        <flux:table :paginate="$items">
            {{ $slot }}
        </flux:table>
    @else
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <flux:icon name="{{ $emptyIcon }}" class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-6" />
                
                @if($hasFilters)
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">No results found</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">Try adjusting your filters or search terms</p>
                    @if($filterClearAction)
                        <flux:button variant="ghost" wire:click="{{ $filterClearAction }}">
                            Clear all filters
                        </flux:button>
                    @endif
                @else
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100 mb-2">{{ $emptyTitle }}</h3>
                    <p class="text-zinc-600 dark:text-zinc-400 mb-6">{{ $emptyMessage }}</p>
                    @if($emptyAction)
                        <flux:button variant="primary" href="{{ $emptyAction }}">
                            {{ $emptyActionLabel }}
                        </flux:button>
                    @endif
                @endif
            </div>
        </div>
    @endif
</flux:card>
