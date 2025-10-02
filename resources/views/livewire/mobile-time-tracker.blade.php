<div x-data="{
    isRunning: @entangle('isRunning'),
    startTime: @entangle('startTime'),
    elapsedSeconds: @entangle('elapsedSeconds'),
    display: '00:00:00',
    interval: null,
    
    init() {
        this.loadFromStorage();
        
        if (this.isRunning) {
            this.startInterval();
        }
        
        this.$watch('isRunning', (value) => {
            if (value) {
                this.startInterval();
                this.saveToStorage();
            } else {
                this.stopInterval();
                this.clearStorage();
            }
        });
        
        window.addEventListener('beforeunload', () => {
            if (this.isRunning) {
                this.saveToStorage();
            }
        });
        
        setInterval(() => {
            if (this.isRunning) {
                this.$wire.call('syncPendingEntry');
            }
        }, 30000);
    },
    
    startInterval() {
        if (this.interval) return;
        
        this.interval = setInterval(() => {
            if (!this.isRunning) return;
            
            const start = new Date(this.startTime);
            const now = new Date();
            const diff = Math.floor((now - start) / 1000);
            this.elapsedSeconds = diff;
            this.updateDisplay();
        }, 1000);
    },
    
    stopInterval() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
        this.display = '00:00:00';
    },
    
    updateDisplay() {
        const hours = Math.floor(this.elapsedSeconds / 3600);
        const minutes = Math.floor((this.elapsedSeconds % 3600) / 60);
        const seconds = this.elapsedSeconds % 60;
        
        this.display = [hours, minutes, seconds]
            .map(v => v.toString().padStart(2, '0'))
            .join(':');
    },
    
    saveToStorage() {
        localStorage.setItem('timeTracker', JSON.stringify({
            isRunning: this.isRunning,
            startTime: this.startTime,
            ticketId: @js($ticketId),
            description: @js($description),
            billable: @js($billable)
        }));
    },
    
    loadFromStorage() {
        const stored = localStorage.getItem('timeTracker');
        if (!stored) return;
        
        try {
            const data = JSON.parse(stored);
            if (data.isRunning) {
                this.isRunning = data.isRunning;
                this.startTime = data.startTime;
                this.startInterval();
            }
        } catch (e) {
            console.error('Failed to load timer from storage', e);
        }
    },
    
    clearStorage() {
        localStorage.removeItem('timeTracker');
    }
}">
    <flux:card class="sticky top-0 z-10 mb-6">
        <div class="text-center">
            @if($ticket)
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Tracking time for</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                        #{{ $ticket->number }} - {{ $ticket->subject }}
                    </p>
                </div>
            @endif

            <div class="mb-6">
                <div class="text-6xl font-mono font-bold text-gray-900 dark:text-gray-100" x-text="display">
                    00:00:00
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    {{ $isRunning ? 'Timer Running' : 'Timer Stopped' }}
                </p>
            </div>

            @if(!$isRunning && !$ticket)
                <div class="mb-4">
                    <flux:field>
                        <flux:label for="ticket-select">Select Ticket</flux:label>
                        <flux:select wire:model="ticketId" id="ticket-select">
                            <option value="">-- Select a ticket --</option>
                            @foreach(auth()->user()->company->tickets()->whereNotIn('status', ['Closed', 'Resolved'])->orderBy('created_at', 'desc')->limit(20)->get() as $t)
                                <option value="{{ $t->id }}">#{{ $t->number }} - {{ $t->subject }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            @endif

            <div class="mb-4">
                <flux:field>
                    <flux:label for="description">What are you working on?</flux:label>
                    <flux:textarea 
                        wire:model="description" 
                        id="description"
                        rows="2"
                        placeholder="Brief description of work..."></flux:textarea>
                </flux:field>
            </div>

            <div class="mb-4 flex items-center justify-center">
                <flux:checkbox wire:model="billable" label="Billable" />
            </div>

            <div class="flex gap-3">
                @if(!$isRunning)
                    <flux:button 
                        wire:click="startTimer" 
                        variant="primary" 
                        size="lg"
                        class="flex-1">
                        <i class="fas fa-play mr-2"></i>
                        Start Timer
                    </flux:button>
                @else
                    <flux:button 
                        wire:click="stopTimer" 
                        variant="danger" 
                        size="lg"
                        class="flex-1">
                        <i class="fas fa-stop mr-2"></i>
                        Stop Timer
                    </flux:button>
                @endif
            </div>
        </div>
    </flux:card>

    @if(count($recentEntries) > 0)
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fas fa-history mr-2"></i>
                Recent Entries
            </flux:heading>

            <div class="space-y-3">
                @foreach($recentEntries as $entry)
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    #{{ $entry['ticket']['number'] ?? 'N/A' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $entry['description'] ?? 'No description' }}
                                </p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ number_format($entry['hours_worked'], 2) }}h
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ \Carbon\Carbon::parse($entry['created_at'])->format('M j, g:i A') }}
                                    </span>
                                    @if($entry['billable'])
                                        <span class="text-green-600">
                                            <i class="fas fa-dollar-sign mr-1"></i>
                                            Billable
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <button 
                                wire:click="deleteEntry({{ $entry['id'] }})"
                                wire:confirm="Delete this time entry?"
                                class="ml-3 text-red-600 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endif

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('timer-started', () => {
                Flux.toast({
                    text: 'Timer started',
                    variant: 'success',
                    duration: 2000
                });
            });

            Livewire.on('timer-stopped', () => {
                Flux.toast({
                    text: 'Timer stopped and saved',
                    variant: 'success',
                    duration: 2000
                });
            });

            Livewire.on('success', (event) => {
                Flux.toast({
                    text: event.message,
                    variant: 'success',
                    duration: 3000
                });
            });

            Livewire.on('error', (event) => {
                Flux.toast({
                    text: event.message,
                    variant: 'danger',
                    duration: 3000
                });
            });
        });
    </script>
</div>
