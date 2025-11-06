<div 
    class="flex-1 ml-4 mr-[32px] relative"
    x-data="{ 
        open: @entangle('isOpen'),
        hoveredIndex: null 
    }"
    x-init="
        $watch('open', value => {
            if (value) {
                $nextTick(() => {
                    $refs.searchInput?.focus();
                });
            }
        })
    "
>
    {{-- Search Bar Trigger --}}
    <button 
        type="button"
        @click="open = !open; $wire.open('{{ Route::currentRouteName() }}')"
        class="w-full"
    >
        <div class="flex items-center gap-3 px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-zinc-300 dark:hover:border-zinc-600 hover:shadow-sm transition-all">
            <flux:icon name="magnifying-glass" class="w-5 h-5 text-zinc-400" />
            <span class="text-sm text-zinc-500 dark:text-zinc-400 flex-1 text-left">Search for clients, tickets, assets, or commands...</span>
            <kbd class="hidden lg:inline-flex items-center gap-1 px-2 py-1 text-xs font-mono text-zinc-400 bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded">
                ⌘K
            </kbd>
        </div>
    </button>

    {{-- Command Palette Popover --}}
    <template x-teleport="body">
        <div 
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            @click.self="open = false"
            class="fixed inset-0 z-50 flex items-start justify-center pt-20"
            style="background: rgba(0, 0, 0, 0.1);"
        >
            <div class="mx-auto w-[48rem] max-w-[90vw]">
                <div 
                    @mouseleave="hoveredIndex = null"
                    @click.stop
                    class="flex flex-col max-h-[70vh] bg-white dark:bg-zinc-900 rounded-lg shadow-2xl border border-zinc-200 dark:border-zinc-700"
                >
                <flux:command class="border-none inline-flex flex-col flex-1 overflow-hidden">
                    <flux:command.input 
                        x-ref="searchInput"
                        wire:model.live.debounce.200ms="search"
                        placeholder="Search clients, tickets, assets, or type a command..."
                        @keydown.arrow-down.prevent="$wire.selectNext(); hoveredIndex = null;"
                        @keydown.arrow-up.prevent="$wire.selectPrevious(); hoveredIndex = null;"
                        @keydown.enter.prevent="
                            let index = hoveredIndex !== null ? hoveredIndex : $wire.selectedIndex;
                            console.log('Enter pressed, using index:', index);
                            $wire.selectResult(index);
                        "
                    />
                    
                    {{-- Show results whenever we have them (from search or popular commands) --}}
                    @if(count($this->searchResults) > 0)
                        <flux:command.items class="overflow-y-auto">
                            @foreach($this->searchResults as $index => $result)
                                @php
                                    $iconName = $result['icon'] ?? 'document';
                                    $isHovered = false;
                                @endphp
                                <flux:command.item 
                                    wire:key="result-{{ $loop->index }}-{{ md5($result['title'] ?? '') }}"
                                    wire:click.prevent="selectResult({{ $loop->index }})"
                                    @mouseenter="hoveredIndex = {{ $loop->index }}"
                                    icon="{{ $iconName }}"
                                    x-bind:class="hoveredIndex === {{ $loop->index }} || (hoveredIndex === null && $wire.selectedIndex === {{ $loop->index }}) ? 'bg-zinc-100 dark:bg-zinc-800' : ''"
                                >
                                    <div class="flex items-center justify-between w-full">
                                        <div>
                                            <div class="font-medium">{{ $result['title'] ?? '' }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $result['subtitle'] ?? '' }}</div>
                                        </div>
                                        @if(isset($result['type']) && in_array($result['type'], ['action', 'quick_action']))
                                            <flux:icon name="arrow-right" variant="mini" class="w-4 h-4 text-zinc-400" />
                                        @endif
                                    </div>
                                </flux:command.item>
                            @endforeach
                        </flux:command.items>
                    @elseif(strlen($search) >= 2)
                        <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No results found for "{{ $search }}"
                        </div>
                    @else
                        {{-- Fallback if no results loaded (shouldn't happen) --}}
                        <flux:command.items>
                            <flux:command.item wire:click="navigateToRoute('dashboard')" icon="home">Go to Dashboard</flux:command.item>
                            <flux:command.item wire:click="navigateToRoute('tickets.index')" icon="ticket">View All Tickets</flux:command.item>
                            <flux:command.item wire:click="navigateToRoute('clients.index')" icon="building-office">View All Clients</flux:command.item>
                        </flux:command.items>
                    @endif
                </flux:command>
                </div>
            </div>
        </div>
    </template>
</div>