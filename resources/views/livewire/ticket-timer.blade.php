<div wire:poll.1s="updateElapsedTime" class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
    <div class="flex items-center justify-between mb-3">
        <flux:heading size="base" class="flex items-center gap-2">
            <flux:icon.clock class="size-5 text-blue-500" />
            Time Tracking
        </flux:heading>
        
        <div class="flex items-center gap-2 text-sm">
            <span class="text-zinc-500">Today:</span>
            <span class="font-medium">{{ $this->formatMinutes($todayMinutes) }}</span>
            <span class="text-zinc-400">|</span>
            <span class="text-zinc-500">Total:</span>
            <span class="font-medium">{{ $this->formatMinutes($totalMinutes) }}</span>
        </div>
    </div>
    
    @if($timerStarted)
        <!-- Active Timer Display -->
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 mb-3">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Timer Running</span>
                    </div>
                    <div class="text-2xl font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $elapsedTime }}
                    </div>
                    @if($timerDescription)
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                            {{ $timerDescription }}
                        </div>
                    @endif
                </div>
                
                <flux:button 
                    wire:click="stopTimer"
                    variant="danger"
                    size="sm"
                    icon="stop"
                >
                    Stop Timer
                </flux:button>
            </div>
        </div>
    @else
        <!-- Start Timer Form -->
        <div class="space-y-3">
            <flux:input 
                wire:model="timerDescription"
                placeholder="What are you working on? (optional)"
                icon="pencil"
            />
            
            <flux:button 
                wire:click="startTimer"
                variant="primary"
                size="sm"
                icon="play"
                class="w-full"
            >
                Start Timer
            </flux:button>
        </div>
    @endif
    
    {{-- Success messages will be shown via toast notifications --}}
</div>

@script
<script>
    // Listen for timer stopped event to update the comment form
    $wire.on('timer-stopped', (event) => {
        const minutes = event.detail.minutes;
        const timeInput = document.querySelector('input[name="time_minutes"]');
        if (timeInput) {
            const currentValue = parseInt(timeInput.value) || 0;
            timeInput.value = currentValue + minutes;
            
            // Highlight the field briefly
            timeInput.classList.add('ring-2', 'ring-emerald-500');
            setTimeout(() => {
                timeInput.classList.remove('ring-2', 'ring-emerald-500');
            }, 2000);
        }
    });
</script>
@endscript