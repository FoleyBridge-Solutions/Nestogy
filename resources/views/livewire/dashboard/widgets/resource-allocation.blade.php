<flux:card class="h-full flex flex-col" wire:poll.10s="loadAllocationData">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.users class="size-5 text-green-500" />
                Resource Allocation
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Team workload and capacity management
            </flux:text>
        </div>
        
        <!-- View Switcher -->
        <flux:tab.group>
            <flux:tabs wire:model.live="view" variant="pills" size="sm">
                <flux:tab name="workload">Workload</flux:tab>
                <flux:tab name="availability">Availability</flux:tab>
                <flux:tab name="projects">Projects</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire view property -->
            <flux:tab.panel name="workload" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="availability" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="projects" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>
    
    <!-- Summary Stats -->
    <div class="grid grid-cols-4 gap-2 mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500">Available</flux:text>
            <flux:heading size="lg" class="text-green-600">{{ $allocationSummary['available'] ?? 0 }}</flux:heading>
        </div>
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500">Moderate</flux:text>
            <flux:heading size="lg" class="text-blue-600">{{ $allocationSummary['moderate'] ?? 0 }}</flux:heading>
        </div>
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500">Busy</flux:text>
            <flux:heading size="lg" class="text-amber-600">{{ $allocationSummary['busy'] ?? 0 }}</flux:heading>
        </div>
        <div class="text-center">
            <flux:text size="xs" class="text-zinc-500">Overloaded</flux:text>
            <flux:heading size="lg" class="text-red-600">{{ $allocationSummary['overloaded'] ?? 0 }}</flux:heading>
        </div>
    </div>
    
    <!-- Team Members List -->
    <div class="flex-1 overflow-y-auto space-y-2">
        @if($teamMembers && count($teamMembers) > 0)
            @php
                $sortedMembers = collect($teamMembers)->sortByDesc('workload_score');
                $displayLimit = $showAllMembers ? $sortedMembers->count() : 5;
                $displayMembers = $sortedMembers->take($displayLimit);
                $remainingCount = $sortedMembers->count() - 5;
            @endphp
            
            @foreach($displayMembers as $member)
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-2 hover:shadow-sm transition-all">
                <div class="flex items-center gap-3">
                    <!-- Compact Avatar -->
                    @php
                        $avatarColor = match($member['utilization']) {
                            'available' => 'bg-green-500',
                            'moderate' => 'bg-blue-500',
                            'busy' => 'bg-amber-500',
                            'overloaded' => 'bg-red-500',
                            default => 'bg-zinc-500'
                        };
                    @endphp
                    <div class="w-8 h-8 rounded-full {{ $avatarColor }} flex items-center justify-center flex-shrink-0">
                        <flux:text size="xs" class="font-bold text-white">
                            {{ substr($member['name'], 0, 1) }}
                        </flux:text>
                    </div>
                    
                    <!-- Compact Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:text class="font-medium text-sm truncate">{{ $member['name'] }}</flux:text>
                            @php
                                $badgeColor = match($member['utilization']) {
                                    'available' => 'green',
                                    'moderate' => 'blue',
                                    'busy' => 'amber',
                                    'overloaded' => 'red',
                                    default => 'zinc'
                                };
                            @endphp
                            <flux:badge size="xs" color="{{ $badgeColor }}" class="flex-shrink-0">
                                {{ ucfirst($member['utilization']) }}
                            </flux:badge>
                        </div>
                        
                        <div class="flex items-center gap-3 mt-1">
                            <flux:text size="xs" class="text-zinc-500 truncate">
                                {{ $member['role'] }}
                            </flux:text>
                            
                            @if($view === 'workload')
                                <flux:text size="xs" class="text-zinc-600">
                                    <span class="font-medium">{{ $member['tickets']['total'] }}</span> tickets
                                </flux:text>
                                @if($member['tickets']['critical'] > 0)
                                    <flux:text size="xs" class="text-red-600 font-medium">
                                        {{ $member['tickets']['critical'] }} critical
                                    </flux:text>
                                @endif
                                @if($member['tickets']['high'] > 0)
                                    <flux:text size="xs" class="text-orange-600 font-medium">
                                        {{ $member['tickets']['high'] }} high
                                    </flux:text>
                                @endif
                            @elseif($view === 'availability')
                                <flux:text size="xs" class="text-zinc-600">
                                    {{ $member['today_hours'] }}h today
                                </flux:text>
                                <flux:text size="xs" class="text-zinc-500">
                                    {{ $member['available'] ? 'Available' : 'At capacity' }}
                                </flux:text>
                            @elseif($view === 'projects')
                                <flux:text size="xs" class="text-zinc-600">
                                    {{ $member['projects'] }} {{ Str::plural('project', $member['projects']) }}
                                </flux:text>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Capacity Indicator -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="text-right">
                            <flux:text size="xs" class="text-zinc-500">Capacity</flux:text>
                            <flux:text class="font-bold text-sm
                                @if($member['capacity_percentage'] > 80) text-red-600
                                @elseif($member['capacity_percentage'] > 60) text-amber-600
                                @elseif($member['capacity_percentage'] > 40) text-blue-600
                                @else text-green-600
                                @endif">
                                {{ round($member['capacity_percentage']) }}%
                            </flux:text>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            
            @if($remainingCount > 0 && !$showAllMembers)
                <button 
                    wire:click="toggleShowAllMembers"
                    class="w-full py-2 px-3 text-xs font-medium text-zinc-600 hover:text-zinc-900 bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-all border-2 border-dashed border-zinc-300 dark:border-zinc-600"
                >
                    <flux:icon.chevron-down class="size-3 inline mr-1" />
                    Show {{ $remainingCount }} more team members
                </button>
            @elseif($showAllMembers && $sortedMembers->count() > 5)
                <button 
                    wire:click="toggleShowAllMembers"
                    class="w-full py-2 px-3 text-xs font-medium text-zinc-600 hover:text-zinc-900 bg-zinc-50 hover:bg-zinc-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-lg transition-all border-2 border-dashed border-zinc-300 dark:border-zinc-600"
                >
                    <flux:icon.chevron-up class="size-3 inline mr-1" />
                    Show less
                </button>
            @endif
        @else
            <!-- Empty State -->
            <div class="flex items-center justify-center h-32">
                @if($loading)
                    <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400" />
                @else
                    <div class="text-center">
                        <flux:icon.users class="size-12 text-zinc-300 mx-auto mb-3" />
                        <flux:text>No team members found</flux:text>
                    </div>
                @endif
            </div>
        @endif
    </div>
    
    <!-- Footer Actions -->
    <div class="pt-3 mt-3 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between">
            <flux:text size="xs" class="text-zinc-500">
                {{ $allocationSummary['total_members'] ?? 0 }} team members • 
                {{ $allocationSummary['total_tickets'] ?? 0 }} tickets • 
                {{ $allocationSummary['total_projects'] ?? 0 }} projects
            </flux:text>
            <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="loadAllocationData">
                Refresh
            </flux:button>
        </div>
    </div>
</flux:card>