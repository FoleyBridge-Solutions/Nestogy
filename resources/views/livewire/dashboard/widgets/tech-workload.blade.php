<flux:card>
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Tech Workload</flux:heading>
        <flux:button wire:click="refresh" variant="ghost" size="xs" icon="arrow-path">
            Refresh
        </flux:button>
    </div>

    <div class="space-y-3">
        @forelse($technicians as $tech)
            <div class="p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <flux:text size="sm" class="font-medium">{{ $tech['name'] }}</flux:text>
                        <flux:text size="xs" class="text-zinc-500">{{ $tech['hours_this_week'] }}h this week</flux:text>
                    </div>
                    <div class="px-2 py-1 rounded text-xs font-medium
                        {{ $tech['workload_color'] === 'red' ? 'bg-red-100 text-red-700 dark:bg-red-900/20 dark:text-red-400' : '' }}
                        {{ $tech['workload_color'] === 'orange' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400' : '' }}
                        {{ $tech['workload_color'] === 'yellow' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400' : '' }}
                        {{ $tech['workload_color'] === 'green' ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400' : '' }}">
                        {{ $tech['workload_score'] }}
                    </div>
                </div>
                <div class="flex gap-4 text-xs">
                    <div>
                        <flux:text size="xs" class="text-zinc-500">Active:</flux:text>
                        <flux:text size="xs" class="font-medium">{{ $tech['active_tickets'] }}</flux:text>
                    </div>
                    @if($tech['critical'] > 0)
                        <div>
                            <flux:text size="xs" class="text-red-600 dark:text-red-400">Critical:</flux:text>
                            <flux:text size="xs" class="font-medium text-red-600 dark:text-red-400">{{ $tech['critical'] }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <flux:text size="sm" class="text-zinc-500">No technicians found</flux:text>
        @endforelse
    </div>
</flux:card>
