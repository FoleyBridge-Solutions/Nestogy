<div class="space-y-6">
    <flux:card class="space-y-6">
        @if($isOnBreak)
            {{-- On Break Status --}}
            <div class="text-center space-y-6">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-orange-100 dark:bg-orange-900 rounded-full">
                    <flux:icon.pause class="size-12 text-orange-600 dark:text-orange-400" />
                </div>
                
                <div>
                    <flux:heading size="xl">On Break</flux:heading>
                    <flux:subheading class="mt-2">
                        Break ends at {{ $breakEndTime->format('g:i A') }}
                    </flux:subheading>
                </div>
                
                <flux:callout icon="clock" variant="warning">
                    You are currently clocked out for a break. Clock back in when you return.
                </flux:callout>
                
                <flux:button 
                    variant="primary" 
                    size="base"
                    icon="play-circle"
                    wire:click="clockIn"
                    wire:loading.attr="disabled"
                    :disabled="$isProcessing">
                    Clock In from Break
                </flux:button>
            </div>
        @elseif($activeEntry)
            {{-- Clocked In Status --}}
            <div class="text-center space-y-6">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 dark:bg-green-900 rounded-full">
                    <flux:icon.clock class="size-12 text-green-600 dark:text-green-400" />
                </div>
                
                <div>
                    <flux:heading size="xl">Clocked In</flux:heading>
                    <flux:subheading class="mt-2">Started at {{ $activeEntry->clock_in->timezone(auth()->user()->company->getTimezone())->format('g:i A') }}</flux:subheading>
                </div>
                
                <div class="text-5xl font-bold text-zinc-900 dark:text-white" 
                     x-data="{ elapsed: '{{ $elapsedTime }}' }"
                     x-init="setInterval(() => { 
                         @this.updateElapsedTime();
                         elapsed = @this.elapsedTime;
                     }, 60000)"
                     x-text="elapsed">
                    {{ $elapsedTime }}
                </div>
                
                <div class="flex gap-3 justify-center">
                    <flux:button 
                        variant="ghost"
                        size="base"
                        icon="pause"
                        wire:click="openBreakModal"
                        wire:loading.attr="disabled"
                        :disabled="$isProcessing">
                        Take a Break
                    </flux:button>
                    
                    <flux:button 
                        variant="danger" 
                        size="base"
                        icon="stop-circle"
                        wire:click="openClockOutModal"
                        wire:loading.attr="disabled"
                        :disabled="$isProcessing">
                        Clock Out
                    </flux:button>
                </div>
            </div>
        @else
            {{-- Ready to Clock In --}}
            <div class="text-center space-y-6">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <flux:icon.clock class="size-12 text-blue-600 dark:text-blue-400" />
                </div>
                
                <div>
                    <flux:heading size="xl">Ready to Clock In</flux:heading>
                    <flux:subheading class="mt-2">{{ now()->timezone(auth()->user()->company->getTimezone())->format('l, F j, Y - g:i A') }}</flux:subheading>
                </div>
                
                @if($requireGPS)
                    <flux:callout icon="map-pin" variant="info">
                        GPS location is required for clock in
                    </flux:callout>
                @endif
                
                @if($requireGPS && (!$latitude || !$longitude))
                    <flux:button 
                        variant="primary" 
                        size="base"
                        icon="map-pin"
                        wire:click="requestLocation"
                        wire:loading.attr="disabled"
                        :disabled="$isProcessing">
                        Enable Location & Clock In
                    </flux:button>
                @else
                    <flux:button 
                        variant="primary" 
                        size="base"
                        icon="play-circle"
                        wire:click="clockIn"
                        wire:loading.attr="disabled"
                        :disabled="$isProcessing">
                        Clock In
                    </flux:button>
                @endif
            </div>
        @endif
    </flux:card>

    @if($recentEntries->isNotEmpty())
        <flux:card class="space-y-6">
            <flux:heading size="lg">Recent Time Entries</flux:heading>
            
            <div class="space-y-3">
                @foreach($recentEntries as $entry)
                    @php
                        $tz = auth()->user()->company->getTimezone();
                        $isBreakEntry = ($entry->metadata['is_break'] ?? false);
                    @endphp
                    <div class="flex items-center justify-between p-4 rounded-lg {{ $isBreakEntry ? 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800' : 'bg-zinc-50 dark:bg-zinc-800' }}">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                @if($isBreakEntry)
                                    <flux:icon.pause class="size-4 text-orange-600 dark:text-orange-400" />
                                @endif
                                <flux:heading>{{ $entry->clock_in->timezone($tz)->format('M d, Y') }}</flux:heading>
                            </div>
                            <flux:subheading>
                                {{ $entry->clock_in->timezone($tz)->format('g:i A') }} - 
                                {{ $entry->clock_out ? $entry->clock_out->timezone($tz)->format('g:i A') : 'In Progress' }}
                            </flux:subheading>
                            @if($entry->notes)
                                <flux:text class="mt-1">
                                    {{ Str::limit($entry->notes, 50) }}
                                </flux:text>
                            @endif
                        </div>
                        
                        <div class="text-right ml-4 space-y-2">
                            <flux:heading size="lg">{{ $entry->getTotalHours() }}h</flux:heading>
                            @if($entry->overtime_minutes > 0)
                                <flux:badge color="orange" size="sm">
                                    +{{ $entry->getOvertimeHours() }}h OT
                                </flux:badge>
                            @endif
                            @if($entry->status === 'approved')
                                <flux:badge color="green" size="sm">Approved</flux:badge>
                            @elseif($entry->status === 'completed')
                                <flux:badge color="yellow" size="sm">Pending</flux:badge>
                            @elseif($entry->status === 'rejected')
                                <flux:badge color="red" size="sm">Rejected</flux:badge>
                            @elseif($entry->status === 'paid')
                                <flux:badge color="blue" size="sm">Paid</flux:badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="flex justify-center">
                <flux:button 
                    href="{{ route('hr.time-clock.history') }}" 
                    variant="ghost">
                    View All History
                </flux:button>
            </div>
        </flux:card>
    @endif

    {{-- Break Modal --}}
    <flux:modal wire:model="showBreakModal" class="md:w-96">
        <form wire:submit="takeBreak" class="space-y-6">
            <div>
                <flux:heading size="lg">Take a Break</flux:heading>
                <flux:subheading class="mt-2">How long will your break be?</flux:subheading>
            </div>

            @if(count($availableBreakDurations) > 0)
                <flux:select 
                    wire:model="selectedBreakDuration" 
                    placeholder="Select break duration..."
                    variant="listbox">
                    @foreach($availableBreakDurations as $duration)
                        <flux:select.option value="{{ $duration }}">
                            <div class="flex items-center gap-2">
                                <flux:icon.clock class="size-4 text-zinc-400" />
                                {{ $duration }} minutes
                            </div>
                        </flux:select.option>
                    @endforeach
                    
                    @if($allowCustomDuration)
                        <flux:select.option value="custom">
                            <div class="flex items-center gap-2">
                                <flux:icon.pencil class="size-4 text-zinc-400" />
                                Custom duration...
                            </div>
                        </flux:select.option>
                    @endif
                </flux:select>
                
                @if($selectedBreakDuration === 'custom')
                    <flux:input 
                        type="number" 
                        wire:model="customBreakDuration"
                        label="Custom break duration (minutes)" 
                        placeholder="Enter minutes"
                        min="1"
                        max="480" />
                @endif
            @else
                <flux:callout icon="exclamation-triangle" variant="warning">
                    No break durations configured. Please contact your administrator.
                </flux:callout>
            @endif

            <flux:callout icon="information-circle" variant="info">
                During your break, you will be clocked out. Your time will resume when you clock back in.
            </flux:callout>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    type="submit" 
                    variant="primary"
                    wire:loading.attr="disabled"
                    :disabled="$isProcessing || !$selectedBreakDuration || (count($availableBreakDurations) === 0)">
                    Start Break
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Clock Out Modal --}}
    <flux:modal wire:model="showClockOutModal" class="md:w-96">
        <form wire:submit="clockOut" class="space-y-6">
            <div>
                <flux:heading size="lg">Clock Out</flux:heading>
                <flux:subheading class="mt-2">Add any notes about your shift (optional)</flux:subheading>
            </div>

            @if($activeEntry)
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <div class="flex items-center justify-between text-sm">
                        <flux:text>Started:</flux:text>
                        <flux:heading>{{ $activeEntry->clock_in->timezone(auth()->user()->company->getTimezone())->format('g:i A') }}</flux:heading>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-2">
                        <flux:text>Ending:</flux:text>
                        <flux:heading>{{ now()->timezone(auth()->user()->company->getTimezone())->format('g:i A') }}</flux:heading>
                    </div>
                    <flux:separator class="my-3" />
                    <div class="flex items-center justify-between">
                        <flux:text>Total Time:</flux:text>
                        <flux:heading size="lg">{{ $elapsedTime }}</flux:heading>
                    </div>
                </div>
            @endif

            <flux:textarea 
                wire:model="notes" 
                label="Notes (Optional)"
                rows="3"
                placeholder="What did you work on today?" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button 
                    type="submit" 
                    variant="danger"
                    icon="stop-circle"
                    wire:loading.attr="disabled"
                    :disabled="$isProcessing">
                    Clock Out
                </flux:button>
            </div>
         </form>
    </flux:modal>
</div>

@script
<script>
    $wire.on('requestGeoLocation', () => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    $wire.updateLocation(position.coords.latitude, position.coords.longitude).then(() => {
                        $wire.clockIn();
                    });
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    let errorMsg = 'Unable to get location. ';
                    if (error.code === error.PERMISSION_DENIED) {
                        errorMsg += 'Please enable location permissions in your browser.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errorMsg += 'Location information is unavailable.';
                    } else if (error.code === error.TIMEOUT) {
                        errorMsg += 'Location request timed out.';
                    }
                    alert(errorMsg);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            alert('Geolocation is not supported by your browser.');
        }
    });
</script>
@endscript

