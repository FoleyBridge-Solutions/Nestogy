<div class="space-y-4" wire:poll.1s="refreshTimer">
    <!-- Active Timer Display -->
    @if($isTimerRunning)
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                @if(!$isPaused)
                    <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
                @else
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                @endif
                <span class="text-sm font-medium">
                    {{ $isPaused ? 'PAUSED' : 'TIMER RUNNING' }}
                </span>
            </div>
            <span class="px-2 py-1 text-xs font-medium bg-white/20 rounded">
                {{ $rateBadge }}
                @if($rateMultiplier > 1)
                    <span class="ml-1 font-bold">{{ $rateMultiplier }}x</span>
                @endif
            </span>
        </div>
        
        <div class="text-center mb-4">
            <div class="text-5xl font-mono font-bold tracking-wider">
                {{ $elapsedTime }}
            </div>
            @if($activeTimer)
                <div class="text-sm mt-2 opacity-90">
                    Started at {{ Carbon\Carbon::parse($activeTimer->started_at)->format('g:i A') }}
                </div>
            @endif
        </div>
        
        @if($liveRevenue > 0)
        <div class="text-center text-2xl font-semibold">
            ${{ number_format($liveRevenue, 2) }}
        </div>
        @endif
        
        <div class="flex gap-2 mt-4">
            @if($isPaused)
                <button wire:click="resumeTimer" class="flex-1 px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50">
                    Resume
                </button>
            @else
                <button wire:click="pauseTimer" class="flex-1 px-4 py-2 bg-white/20 text-white rounded-lg font-medium hover:bg-white/30">
                    Pause
                </button>
            @endif
            <button wire:click="stopTimer" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600">
                Stop & Save
            </button>
        </div>
    </div>
    @else
    <!-- Start Timer Card -->
    <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-6 text-center">
        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <p class="text-sm text-zinc-500 mb-4">No active timer</p>
        <button wire:click="startTimer" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
            Start Timer
        </button>
    </div>
    @endif
    
    <!-- Today's Metrics -->
    <div class="grid grid-cols-3 gap-2">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-xs text-zinc-500 block mb-1">Total Hours</p>
            <div class="text-lg font-semibold">
                {{ $todayMetrics['total_hours'] ?? '0' }}h
            </div>
            @if(($todayMetrics['total_hours'] ?? 0) > 0)
            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1 mt-2">
                <div class="bg-blue-500 h-1 rounded-full" style="width: {{ min(($todayMetrics['total_hours'] ?? 0) / 8 * 100, 100) }}%"></div>
            </div>
            @endif
        </div>
        
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-xs text-zinc-500 block mb-1">Billable</p>
            <div class="flex items-baseline gap-1 justify-center">
                <span class="text-2xl font-semibold text-green-600">
                    {{ $todayMetrics['billable_hours'] ?? 0 }}h
                </span>
            </div>
            <p class="text-xs text-zinc-500">
                {{ $todayMetrics['entries_count'] ?? 0 }} entries
            </p>
        </div>
        
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-xs text-zinc-500 block mb-1">Revenue</p>
            <div class="flex items-baseline gap-1 justify-center">
                <span class="text-2xl font-semibold text-green-600">
                    ${{ number_format($todayMetrics['revenue'] ?? 0, 0) }}
                </span>
            </div>
            <p class="text-xs text-zinc-500">
                Today's earnings
            </p>
        </div>
    </div>
    
    <!-- Recent Entries -->
    @if(count($recentEntries) > 0)
    <div>
        <div class="flex justify-between items-center mb-3">
            <p class="text-sm text-zinc-500">Recent Entries</p>
            <button wire:click="$set('showManualEntry', true)" class="px-3 py-1 text-xs text-zinc-600 hover:bg-zinc-100 rounded">
                Add Entry
            </button>
        </div>
        
        <div class="space-y-2">
            @foreach($recentEntries as $entry)
            <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">{{ $entry['hours'] }}h</span>
                        <span class="text-xs text-zinc-500">{{ $entry['date'] }}</span>
                        @if($entry['billable'])
                            <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded">Billable</span>
                        @else
                            <span class="px-2 py-0.5 text-xs bg-zinc-100 text-zinc-700 rounded">Non-billable</span>
                        @endif
                    </div>
                    <p class="text-xs text-zinc-500 mt-1">
                        {{ $entry['description'] }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if($entry['amount'] > 0)
                        <span class="text-sm font-medium text-green-600">
                            ${{ number_format($entry['amount'], 2) }}
                        </span>
                    @endif
                    @if($entry['status'] !== 'approved')
                    <button wire:click="deleteEntry({{ $entry['id'] }})" 
                            wire:confirm="Are you sure you want to delete this entry?"
                            class="p-1 hover:bg-red-50 rounded">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="text-center py-4">
        <p class="text-sm text-zinc-500">No time entries yet</p>
        <button wire:click="$set('showManualEntry', true)" class="mt-2 px-3 py-1 text-sm text-zinc-600 hover:bg-zinc-100 rounded">
            Add First Entry
        </button>
    </div>
    @endif
</div>