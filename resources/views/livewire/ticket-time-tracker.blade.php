<div class="space-y-4" wire:poll.1s="refreshTimer">
    <!-- Active Timer Display -->
    @if($isTimerRunning)
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold">Timer Running</h3>
                <p class="text-sm opacity-90">{{ $this->ticket->number }} - {{ Str::limit($this->ticket->subject, 50) }}</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold font-mono">{{ $elapsedTime }}</div>
                <div class="text-sm opacity-90">Started {{ \Carbon\Carbon::parse($activeTimerStartedAt)->diffForHumans() }}</div>
            </div>
        </div>
        
        <!-- Work Type Display -->
        @if($this->activeTimer && $this->activeTimer->work_type)
        <div class="mb-4 p-3 bg-white/10 rounded">
            <span class="text-xs uppercase tracking-wide opacity-75">Work Type:</span>
            <span class="ml-2 font-medium">{{ ucwords(str_replace('_', ' ', $this->activeTimer->work_type)) }}</span>
        </div>
        @endif
        
        <!-- Rate Information -->
        <div class="mb-4 p-3 bg-white/10 rounded">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs uppercase tracking-wide opacity-75">Rate Type:</span>
                    <span class="ml-2 font-medium">{{ $currentRate['description'] ?? 'Standard Rate' }}</span>
                    @if($currentRate['is_premium'] ?? false)
                        <span class="ml-2 px-2 py-1 bg-yellow-400 text-yellow-900 text-xs rounded-full">
                            {{ $currentRate['multiplier'] }}x Rate
                        </span>
                    @endif
                </div>
                <div class="text-right">
                    <span class="text-xs uppercase tracking-wide opacity-75">Billable:</span>
                    <span class="ml-2 font-medium">{{ $this->activeTimer && $this->activeTimer->billable ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>
        
        <!-- Timer Controls -->
        <div class="flex gap-2">
            @if($isPaused)
                <button type="button" wire:click="resumeTimer" class="flex-1 bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded font-medium transition">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Resume
                </button>
            @else
                <button type="button" wire:click="pauseTimer" class="flex-1 bg-white/20 text-white hover:bg-white/30 px-4 py-2 rounded font-medium transition">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Pause
                </button>
            @endif
            <button type="button" wire:click="stopTimer" class="flex-1 bg-red-600 text-white hover:bg-red-700 px-4 py-2 rounded font-medium transition">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                </svg>
                Stop & Save
            </button>
        </div>
    </div>
    @else
    <!-- Start Timer Button -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <button type="button" wire:click="startTimer" class="w-full bg-blue-600 text-white hover:bg-blue-700 px-6 py-3 rounded-lg font-medium transition flex items-center justify-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Start Timer
        </button>
    </div>
    @endif
    
    <!-- Billing Dashboard -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold mb-4">Today's Time Tracking</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">Total Hours</div>
                <div class="text-2xl font-bold">{{ number_format($todayMetrics['total_hours'] ?? 0, 1) }}</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">Billable Hours</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($todayMetrics['billable_hours'] ?? 0, 1) }}</div>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm text-gray-600 mb-1">Revenue</div>
                <div class="text-2xl font-bold text-blue-600">${{ number_format($todayMetrics['revenue'] ?? 0, 2) }}</div>
            </div>
        </div>
        
        <!-- Weekly Summary -->
        <div class="border-t pt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-3">This Week</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Total Hours:</span>
                    <span class="font-medium ml-2">{{ number_format($weekMetrics['total_hours'] ?? 0, 1) }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Revenue:</span>
                    <span class="font-medium ml-2">${{ number_format($weekMetrics['revenue'] ?? 0, 2) }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Utilization:</span>
                    <span class="font-medium ml-2">{{ number_format($weekMetrics['utilization'] ?? 0, 1) }}%</span>
                </div>
                <div>
                    <span class="text-gray-600">Entries:</span>
                    <span class="font-medium ml-2">{{ $weekMetrics['entries_count'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        
        <!-- Monthly Summary -->
        <div class="border-t pt-4 mt-4">
            <h4 class="text-sm font-medium text-gray-700 mb-3">This Month</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Total Hours:</span>
                    <span class="font-medium ml-2">{{ number_format($monthMetrics['total_hours'] ?? 0, 1) }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Revenue:</span>
                    <span class="font-medium ml-2">${{ number_format($monthMetrics['revenue'] ?? 0, 2) }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Days Worked:</span>
                    <span class="font-medium ml-2">{{ $monthMetrics['days_worked'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Avg Daily:</span>
                    <span class="font-medium ml-2">{{ number_format($monthMetrics['avg_daily_hours'] ?? 0, 1) }}h</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center justify-between mt-4 pt-4 border-t">
            <button type="button" wire:click="$set('showManualEntry', true)" class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add manual entry
            </button>
        </div>
    </div>
    
    <!-- Recent Entries -->
    @if(count($recentEntries) > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold mb-4">Recent Time Entries</h3>
        
        <div class="space-y-3">
            @foreach($recentEntries as $entry)
            <div class="border-l-4 {{ $entry['billable'] ? 'border-green-500' : 'border-gray-300' }} pl-4 py-2">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="font-medium text-sm">
                            {{ ucwords(str_replace('_', ' ', $entry['work_type'] ?? 'general_support')) }}
                        </div>
                        @if($entry['description'])
                        <div class="text-sm text-gray-600 mt-1">{{ $entry['description'] }}</div>
                        @endif
                        <div class="text-xs text-gray-500 mt-1">
                            @php
                                $date = $entry['work_date'] ?? $entry['started_at'] ?? now();
                            @endphp
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                            @if(isset($entry['hours_worked']) && $entry['hours_worked'])
                            - {{ number_format($entry['hours_worked'], 1) }} hours
                            @endif
                        </div>
                    </div>
                    <div class="ml-4 text-right">
                        @if($entry['billable'] && $entry['amount'])
                        <div class="font-medium">${{ number_format($entry['amount'], 2) }}</div>
                        @endif
                        @if($entry['status'] === 'draft')
                        <button type="button"
                            wire:click="deleteEntry({{ $entry['id'] }})" 
                            wire:confirm="Are you sure you want to delete this entry?"
                            class="mt-1 text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <button type="button" wire:click="$set('showManualEntry', true)" class="mt-4 text-sm text-gray-600 hover:text-gray-900 flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Log Time Manually
        </button>
    </div>
    @endif
    
    <!-- Quick Timer Modal -->
    @if($showQuickTimer)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Start Timer</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Work Type</label>
                    <select wire:model="quickWorkType" class="w-full border-gray-300 rounded-md">
                        <option value="general_support">General Support</option>
                        <option value="troubleshooting">Troubleshooting</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="consultation">Consultation</option>
                        <option value="project_work">Project Work</option>
                        <option value="emergency_support">Emergency Support</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                    <textarea wire:model="quickDescription" rows="3" class="w-full border-gray-300 rounded-md"></textarea>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" wire:model="quickBillable" class="mr-2" checked>
                    <label class="text-sm">Mark as billable</label>
                </div>
            </div>
            
            <div class="flex gap-2 justify-end mt-6">
                <button type="button" wire:click="$set('showQuickTimer', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" wire:click="confirmStartTimer" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Start Timer
                </button>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Manual Entry Modal -->
    @if($showManualEntry)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">Add Manual Time Entry</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" wire:model="manualDate" class="w-full border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hours Worked</label>
                    <input type="number" wire:model="manualHours" step="0.25" min="0" class="w-full border-gray-300 rounded-md">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Work Type</label>
                    <select wire:model="manualWorkType" class="w-full border-gray-300 rounded-md">
                        <option value="general_support">General Support</option>
                        <option value="troubleshooting">Troubleshooting</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="consultation">Consultation</option>
                        <option value="project_work">Project Work</option>
                        <option value="emergency_support">Emergency Support</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea wire:model="manualDescription" rows="3" class="w-full border-gray-300 rounded-md"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Work Performed</label>
                    <textarea wire:model="manualWorkPerformed" rows="3" class="w-full border-gray-300 rounded-md"></textarea>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" wire:model="manualBillable" class="mr-2">
                    <label class="text-sm">Mark as billable</label>
                </div>
            </div>
            
            <div class="flex gap-2 justify-end mt-6">
                <button type="button" wire:click="$set('showManualEntry', false)" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" wire:click="addManualEntry" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Entry
                </button>
            </div>
        </div>
    </div>
    @endif
</div>