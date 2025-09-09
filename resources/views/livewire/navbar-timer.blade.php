<div wire:poll.1s class="relative">
    @if($timerCount > 0)
        <!-- Timer Display -->
        <flux:dropdown align="center">
            <!-- Trigger Button -->
            <flux:button variant="ghost" size="sm" class="relative">
                <div class="flex items-center gap-2">
                    <!-- Timer Icon with Status Indicator -->
                    <div class="relative">
                        @if($overtime)
                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                        @elseif($isMultipleTimers)
                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-blue-500 rounded-full"></div>
                        @elseif(!collect($activeTimers)->where('is_paused', true)->isEmpty())
                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-yellow-500 rounded-full"></div>
                        @else
                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        @endif
                        <flux:icon name="clock" class="w-5 h-5 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    
                    <!-- Timer Display Text -->
                    <div class="text-sm font-mono">
                        @if($isMultipleTimers)
                            <span class="text-zinc-700 dark:text-zinc-300">
                                <span class="hidden sm:inline">{{ $timerCount }} timers | </span>
                                {{ $totalElapsedTime }}
                            </span>
                        @else
                            @php $timer = $activeTimers[0] ?? null; @endphp
                            @if($timer)
                                <span class="text-zinc-700 dark:text-zinc-300">
                                    {{ $timer['elapsed_display'] }} 
                                    <span class="text-zinc-500 dark:text-zinc-400 ml-1 hidden sm:inline">
                                        #{{ $timer['ticket_number'] }}
                                    </span>
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </flux:button>
            
            <!-- Dropdown Content -->
            <flux:menu class="min-w-[320px] max-w-md">
                <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm">Active Timers</flux:heading>
                </div>
                
                <!-- Timer List -->
                <div class="max-h-96 overflow-y-auto">
                    @foreach($activeTimers as $timer)
                        <div class="p-3 border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                            <!-- Timer Header -->
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <a href="{{ route('tickets.show', $timer['ticket_id']) }}" 
                                       class="text-sm font-medium text-zinc-900 dark:text-zinc-100 hover:text-blue-600 dark:hover:text-blue-400">
                                        #{{ $timer['ticket_number'] }}
                                    </a>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-1">
                                        {{ Str::limit($timer['ticket_subject'], 40) }}
                                    </p>
                                </div>
                                <div class="text-right ml-2">
                                    <div class="font-mono text-sm font-medium {{ $timer['is_paused'] ? 'text-yellow-600' : 'text-zinc-900 dark:text-zinc-100' }}">
                                        {{ $timer['elapsed_display'] }}
                                    </div>
                                    @if($timer['billable'])
                                        <div class="text-xs text-green-600 dark:text-green-400">
                                            ${{ number_format($timer['live_amount'], 2) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Timer Info -->
                            @if($timer['work_type'] || $timer['description'])
                                <div class="mb-2">
                                    @if($timer['work_type'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                            {{ ucwords(str_replace('_', ' ', $timer['work_type'])) }}
                                        </span>
                                    @endif
                                    @if($timer['description'])
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">
                                            {{ $timer['description'] }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                            
                            <!-- Timer Controls -->
                            <div class="flex items-center gap-2">
                                @if($timer['is_paused'])
                                    <flux:button 
                                        wire:click="resumeTimer({{ $timer['id'] }})"
                                        size="xs"
                                        variant="primary"
                                        class="flex-1">
                                        <flux:icon name="play" variant="micro" />
                                        Resume
                                    </flux:button>
                                @else
                                    <flux:button 
                                        wire:click="pauseTimer({{ $timer['id'] }})"
                                        size="xs"
                                        variant="ghost"
                                        class="flex-1">
                                        <flux:icon name="pause" variant="micro" />
                                        Pause
                                    </flux:button>
                                @endif
                                
                                <flux:button 
                                    wire:click="stopTimer({{ $timer['id'] }})"
                                    wire:confirm="Stop this timer and save {{ $timer['elapsed_display'] }} to ticket?"
                                    size="xs"
                                    variant="danger"
                                    class="flex-1">
                                    <flux:icon name="stop" variant="micro" />
                                    Stop
                                </flux:button>
                                
                                <flux:button 
                                    onclick="window.location.href='{{ route('tickets.show', $timer['ticket_id']) }}'"
                                    size="xs"
                                    variant="ghost">
                                    <flux:icon name="arrow-top-right-on-square" variant="micro" />
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Footer Summary (for multiple timers) -->
                @if($isMultipleTimers)
                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Total Time</div>
                                <div class="font-mono text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $totalElapsedTime }}
                                </div>
                            </div>
                            <flux:button 
                                wire:click="stopAllTimers"
                                wire:confirm="Stop all {{ $timerCount }} timers?"
                                size="xs"
                                variant="danger">
                                Stop All Timers
                            </flux:button>
                        </div>
                    </div>
                @endif
            </flux:menu>
        </flux:dropdown>
    @endif
    
    <!-- Multiple Timer Confirmation Modal -->
    @if($showMultiTimerModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999]" wire:click.self="$set('showMultiTimerModal', false)">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <flux:icon name="exclamation-triangle" class="w-6 h-6 text-amber-500" />
                    </div>
                    <div class="ml-3">
                        <flux:heading size="lg">Active Timer Detected</flux:heading>
                        <flux:text class="mt-2">
                            You have {{ $timerCount }} active timer{{ $timerCount > 1 ? 's' : '' }} running. 
                            What would you like to do before starting a timer for ticket #{{ $pendingTicketNumber }}?
                        </flux:text>
                    </div>
                </div>
                
                <!-- Current Active Timers -->
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3 mb-4">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Currently Running:</div>
                    @foreach(array_slice($activeTimers, 0, 3) as $timer)
                        <div class="flex items-center justify-between py-1">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                #{{ $timer['ticket_number'] }}
                            </span>
                            <span class="font-mono text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $timer['elapsed_display'] }}
                            </span>
                        </div>
                    @endforeach
                    @if(count($activeTimers) > 3)
                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            ... and {{ count($activeTimers) - 3 }} more
                        </div>
                    @endif
                </div>
                
                <!-- Action Buttons -->
                <div class="space-y-2">
                    <flux:button 
                        wire:click="confirmMultipleTimers('switch')"
                        variant="primary"
                        class="w-full">
                        <flux:icon name="arrow-path" variant="micro" />
                        Switch to New Ticket
                        <flux:text size="xs" class="ml-auto opacity-75">Stop current, start new</flux:text>
                    </flux:button>
                    
                    <flux:button 
                        wire:click="confirmMultipleTimers('both')"
                        variant="ghost"
                        class="w-full">
                        <flux:icon name="play-pause" variant="micro" />
                        Run Multiple Timers
                        <flux:text size="xs" class="ml-auto opacity-75">Keep all running</flux:text>
                    </flux:button>
                    
                    <flux:button 
                        wire:click="confirmMultipleTimers('cancel')"
                        variant="ghost"
                        class="w-full">
                        <flux:icon name="x-mark" variant="micro" />
                        Cancel
                        <flux:text size="xs" class="ml-auto opacity-75">Don't start new timer</flux:text>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>