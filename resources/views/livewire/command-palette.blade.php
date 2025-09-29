<div>
    {{-- Trigger Button --}}
    <flux:tooltip content="Search (⌘K)">
        <flux:button
            wire:click="open"
            variant="ghost"
            size="sm"
            icon="magnifying-glass"
            class="text-zinc-400 hover:text-zinc-100"
        />
    </flux:tooltip>

    {{-- Command Palette Modal --}}
    <flux:modal wire:model="isOpen" variant="bare" class="w-full max-w-[40rem] my-[12vh] max-h-screen overflow-y-hidden">
        <flux:command class="border-none shadow-2xl inline-flex flex-col max-h-[76vh] bg-white dark:bg-zinc-900">
            <flux:command.input 
                wire:model.live="search"
                placeholder="Search clients, tickets, assets, or type a command..."
                autofocus
            />
            
            {{-- Show results whenever we have them (from search or popular commands) --}}
            @if(count($results) > 0)
                <flux:command.items>
                    @foreach($results as $index => $result)
                        @php
                            $iconName = $result['icon'] ?? 'document';
                        @endphp
                        <flux:command.item 
                            wire:click.prevent="selectResult({{ $index }})"
                            icon="{{ $iconName }}"
                            @class([
                                'bg-zinc-100 dark:bg-zinc-800' => $selectedIndex === $index
                            ])
                        >
                            <div class="flex items-center justify-between w-full">
                                <div>
                                    <div class="font-medium">{{ $result['title'] }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $result['subtitle'] }}</div>
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
    </flux:modal>
</div>
