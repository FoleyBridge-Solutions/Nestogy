<flux:card>
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Team Activity</flux:heading>
        <flux:button wire:click="refresh" variant="ghost" size="xs" icon="arrow-path">
            Refresh
        </flux:button>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <flux:text size="sm" class="text-blue-600 dark:text-blue-400">Active Timers</flux:text>
            <flux:heading size="xl" class="text-blue-700 dark:text-blue-300">{{ $activeTimers }}</flux:heading>
        </div>
        <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <flux:text size="sm" class="text-orange-600 dark:text-orange-400">Unassigned</flux:text>
            <flux:heading size="xl" class="text-orange-700 dark:text-orange-300">{{ $unassignedTickets }}</flux:heading>
        </div>
    </div>

    <div class="space-y-2">
        @forelse($teamMembers as $member)
            <div class="flex items-center justify-between p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full {{ $member['status'] === 'working' ? 'bg-green-500' : 'bg-zinc-300' }}"></div>
                    <flux:text size="sm" class="font-medium">{{ $member['name'] }}</flux:text>
                </div>
                <flux:badge size="sm" variant="{{ $member['active_count'] > 5 ? 'warning' : 'info' }}">
                    {{ $member['active_count'] }} active
                </flux:badge>
            </div>
        @empty
            <flux:text size="sm" class="text-zinc-500">No team members found</flux:text>
        @endforelse
    </div>
</flux:card>
