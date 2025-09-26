<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.users class="size-5 text-purple-500" />
                Team Performance
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                @if($view === 'top')
                    Showing top performers sorted by highest scores • Click scores for details
                @else
                    Showing team members needing support • Click scores for details
                @endif
            </flux:text>
        </div>
        
        <!-- Controls -->
        <div class="flex items-center gap-2">
            <flux:select wire:model.live="period" size="sm">
                <flux:select.option value="week">This Week</flux:select.option>
                <flux:select.option value="month">This Month</flux:select.option>
                <flux:select.option value="quarter">This Quarter</flux:select.option>
            </flux:select>
            
            <flux:tab.group>
                <flux:tabs wire:model.live="view" variant="segmented" size="sm">
                    <flux:tab name="top">Top Performers</flux:tab>
                    <flux:tab name="needs_improvement">Needing Improvement</flux:tab>
                </flux:tabs>
                
                <!-- Hidden panels - content is controlled by Livewire view property -->
                <flux:tab.panel name="top" class="hidden"></flux:tab.panel>
                <flux:tab.panel name="needs_improvement" class="hidden"></flux:tab.panel>
            </flux:tab.group>
        </div>
    </div>
    
    <!-- Team Members List -->
    @if($teamMembers->isNotEmpty())
        <div class="space-y-4">
            @foreach($teamMembers as $member)
                <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:shadow-md transition-shadow" wire:key="member-{{ $member['id'] }}">
                    <div class="flex items-start justify-between gap-4">
                        <!-- Member Info -->
                        <div class="flex items-center gap-3">
                            <!-- Avatar -->
                            <div class="flex-shrink-0">
                                @if($member['avatar'])
                                    <img src="{{ $member['avatar'] }}" alt="{{ $member['name'] }}" class="w-12 h-12 rounded-full">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-blue-600 flex items-center justify-center">
                                        <span class="text-white font-medium text-lg">
                                            {{ substr($member['name'], 0, 1) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Name and Role -->
                            <div>
                                <flux:heading size="base">{{ $member['name'] }}</flux:heading>
                                <div class="flex items-center gap-2 mt-1">
                                    <flux:badge size="sm" variant="outline">
                                        {{ ucfirst($member['role']) }}
                                    </flux:badge>
                                    
                                    <flux:badge size="sm" 
                                        color="{{ match($member['performance_level']) {
                                            'excellent' => 'green',
                                            'good' => 'blue',
                                            'average' => 'yellow',
                                            'needs_improvement' => 'red',
                                            default => 'zinc'
                                        } }}">
                                        {{ ucfirst(str_replace('_', ' ', $member['performance_level'])) }}
                                    </flux:badge>
                                    
                                    @if($member['trend'] === 'improving')
                                        <flux:icon.arrow-trending-up class="size-4 text-green-500" />
                                    @elseif($member['trend'] === 'declining')
                                        <flux:icon.arrow-trending-down class="size-4 text-red-500" />
                                    @else
                                        <flux:icon.minus class="size-4 text-gray-400" />
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Performance Score (Clickable) -->
                        <div class="text-right">
                            <div class="flex items-center gap-3 cursor-pointer transition-transform hover:scale-110 group"
                                 wire:click="showScoreDetails({{ $member['id'] }})"
                                 title="Click to see score breakdown">
                                <div class="text-center">
                                    <span class="text-2xl font-bold
                                        @if($member['performance_score'] >= 85) text-green-500
                                        @elseif($member['performance_score'] >= 70) text-blue-500
                                        @elseif($member['performance_score'] >= 55) text-yellow-500
                                        @else text-red-500
                                        @endif
                                    ">{{ $member['performance_score'] }}</span>
                                    <flux:text size="xs" class="text-zinc-500 block">Score</flux:text>
                                </div>
                                <div class="relative inline-block">
                                    <svg class="w-16 h-16 -rotate-90 group-hover:drop-shadow-lg">
                                        <circle cx="32" cy="32" r="28" stroke-width="6" 
                                            class="fill-none stroke-zinc-200 dark:stroke-zinc-700" />
                                        <circle cx="32" cy="32" r="28" stroke-width="6"
                                            stroke-dasharray="{{ 175.9 * ($member['performance_score'] / 100) }} 175.9"
                                            class="fill-none transition-all duration-500
                                                @if($member['performance_score'] >= 85) stroke-green-500
                                                @elseif($member['performance_score'] >= 70) stroke-blue-500
                                                @elseif($member['performance_score'] >= 55) stroke-yellow-500
                                                @else stroke-red-500
                                                @endif
                                            " />
                                    </svg>
                                    <!-- Info Icon Indicator -->
                                    <div class="absolute -top-1 -right-1 bg-blue-500 rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <flux:icon.information-circle class="size-3 text-white" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <!-- Tickets -->
                        <div>
                            <flux:text size="xs" class="text-zinc-500">Tickets</flux:text>
                            <flux:text class="font-medium">{{ $member['total_tickets'] }}</flux:text>
                            <flux:text size="xs" class="text-green-600">
                                {{ $member['resolved_tickets'] }} resolved
                            </flux:text>
                        </div>
                        
                        <!-- Resolution Rate -->
                        <div>
                            <flux:text size="xs" class="text-zinc-500">Resolution Rate</flux:text>
                            <flux:text class="font-medium">{{ $member['resolution_rate'] }}%</flux:text>
                        </div>
                        
                        <!-- Hours -->
                        <div>
                            <flux:text size="xs" class="text-zinc-500">Hours</flux:text>
                            <flux:text class="font-medium">{{ number_format($member['total_hours'], 1) }}</flux:text>
                            <flux:text size="xs" class="text-blue-600">
                                {{ number_format($member['billable_hours'], 1) }} billable
                            </flux:text>
                        </div>
                        
                        <!-- Utilization -->
                        <div>
                            <flux:text size="xs" class="text-zinc-500">Utilization</flux:text>
                            <flux:text class="font-medium">{{ $member['utilization_rate'] }}%</flux:text>
                        </div>
                        
                        <!-- Revenue -->
                        <div>
                            <flux:text size="xs" class="text-zinc-500">Revenue</flux:text>
                            <flux:text class="font-medium">${{ number_format($member['revenue_generated'], 0) }}</flux:text>
                        </div>
                        
                        <!-- Satisfaction -->
                        <div>
                            <flux:text size="xs" class="text-zinc-500">Satisfaction</flux:text>
                            <div class="flex items-center gap-1">
                                @if($member['customer_satisfaction'] > 0)
                                    <flux:text class="font-medium">{{ $member['customer_satisfaction'] }}</flux:text>
                                    <flux:icon.star class="size-3 text-yellow-500 fill-current" />
                                @else
                                    <flux:text class="font-medium text-zinc-400">N/A</flux:text>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alerts -->
                    @if($member['critical_tickets'] > 0 || $member['open_tickets'] > 10)
                        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            @if($member['critical_tickets'] > 0)
                                <flux:text size="sm" class="text-red-600 dark:text-red-400">
                                    <flux:icon.exclamation-triangle class="size-4 inline" />
                                    {{ $member['critical_tickets'] }} critical tickets
                                </flux:text>
                            @endif
                            
                            @if($member['open_tickets'] > 10)
                                <flux:text size="sm" class="text-orange-600 dark:text-orange-400 ml-4">
                                    <flux:icon.clock class="size-4 inline" />
                                    High workload: {{ $member['open_tickets'] }} open tickets
                                </flux:text>
                            @endif
                        </div>
                    @endif
                    
                    <!-- Last Active -->
                    <div class="mt-2">
                        <flux:text size="xs" class="text-zinc-400">
                            Last active: {{ $member['last_active']->diffForHumans() }}
                        </flux:text>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Load More -->
        @if($teamMembers->count() >= $limit && $allTeamMembers->count() > $limit)
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" class="w-full" wire:click="loadMore" wire:loading.attr="disabled">
                    <flux:icon.arrow-down class="size-4" wire:loading.class="animate-bounce" />
                    Load More Team Members
                </flux:button>
            </div>
        @endif
        
        <!-- Summary Stats -->
        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text size="sm" class="text-zinc-500">
                        @if($view === 'top')
                            High Performers
                        @else
                            Need Support
                        @endif
                    </flux:text>
                    <flux:heading size="lg">
                        @if($view === 'top')
                            {{ $allTeamMembers->filter(fn($m) => $m['performance_score'] >= 70)->count() }}/{{ $allTeamMembers->count() }}
                        @else
                            {{ $allTeamMembers->filter(fn($m) => $m['performance_score'] < 70)->count() }}/{{ $allTeamMembers->count() }}
                        @endif
                    </flux:heading>
                </div>
                
                <div class="text-center p-3 @if($view === 'top') bg-green-50 dark:bg-green-900/30 @else bg-orange-50 dark:bg-orange-900/30 @endif rounded-lg">
                    <flux:text size="sm" class="@if($view === 'top') text-green-600 dark:text-green-400 @else text-orange-600 dark:text-orange-400 @endif">
                        @if($view === 'top')
                            Best Score
                        @else
                            Lowest Score
                        @endif
                    </flux:text>
                    <flux:heading size="lg" class="@if($view === 'top') text-green-600 dark:text-green-400 @else text-orange-600 dark:text-orange-400 @endif">
                        {{ $allTeamMembers->first()['performance_score'] ?? 0 }}
                    </flux:heading>
                </div>
                
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                    <flux:text size="sm" class="text-blue-600 dark:text-blue-400">Total Hours</flux:text>
                    <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                        {{ number_format($allTeamMembers->sum('total_hours'), 0) }}
                    </flux:heading>
                </div>
                
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                    <flux:text size="sm" class="text-purple-600 dark:text-purple-400">Revenue</flux:text>
                    <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                        ${{ number_format($allTeamMembers->sum('revenue_generated'), 0) }}
                    </flux:heading>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="flex items-center justify-center h-64">
            <div class="text-center">
                @if($loading)
                    <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                    <flux:text>Loading team performance data...</flux:text>
                @else
                    <flux:icon.users class="size-12 text-zinc-300 mx-auto mb-3" />
                    <flux:heading size="lg">No Team Data</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">
                        Team performance metrics will appear here
                    </flux:text>
                @endif
            </div>
        </div>
    @endif
    
    <!-- Loading Overlay -->
    <div wire:loading wire:target="loadTeamPerformance,setPeriod,sort,loadMore" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-purple-500" />
            <flux:text>Analyzing performance...</flux:text>
        </div>
    </div>
    
    <!-- Performance Score Details Modal -->
    <flux:modal wire:model.live="showScoreModal" name="score-details-modal" class="md:w-[700px]">
        @if($selectedMemberDetails)
            <div class="space-y-6">
                <!-- Header -->
                <div>
                    <flux:heading size="lg" class="flex items-center gap-2">
                        <flux:icon.chart-bar class="size-5 text-purple-500" />
                        Performance Score Breakdown
                    </flux:heading>
                    <div class="mt-2 flex items-center gap-2">
                        <flux:text class="text-lg">
                            {{ $selectedMemberDetails['member_name'] }}
                        </flux:text>
                        <flux:badge variant="outline">{{ ucfirst($selectedMemberDetails['role']) }}</flux:badge>
                    </div>
                </div>
                
                <!-- Score Summary -->
                <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <flux:text size="sm" class="text-gray-500">Current Performance Score</flux:text>
                            <div class="flex items-center gap-3 mt-1">
                                <flux:heading size="xl" class="
                                    @if($selectedMemberDetails['total_score'] >= 85) text-green-600
                                    @elseif($selectedMemberDetails['total_score'] >= 70) text-blue-600
                                    @elseif($selectedMemberDetails['total_score'] >= 55) text-yellow-600
                                    @else text-red-600
                                    @endif
                                ">
                                    {{ $selectedMemberDetails['total_score'] }}/100
                                </flux:heading>
                                <flux:badge size="sm" color="{{ match($selectedMemberDetails['performance_level']) {
                                    'excellent' => 'green',
                                    'good' => 'blue',
                                    'average' => 'yellow',
                                    'needs_improvement' => 'red',
                                    default => 'zinc'
                                } }}">
                                    {{ ucfirst(str_replace('_', ' ', $selectedMemberDetails['performance_level'])) }}
                                </flux:badge>
                            </div>
                            <flux:text size="xs" class="text-gray-500 mt-1">
                                Period: {{ ucfirst($selectedMemberDetails['period']) }}
                            </flux:text>
                        </div>
                        
                        <!-- Visual Score -->
                        <div class="relative">
                            <svg class="w-24 h-24 -rotate-90">
                                <circle cx="48" cy="48" r="42" stroke-width="8" 
                                    class="fill-none stroke-gray-200 dark:stroke-gray-700" />
                                <circle cx="48" cy="48" r="42" stroke-width="8"
                                    stroke-dasharray="{{ 263.9 * ($selectedMemberDetails['total_score'] / 100) }} 263.9"
                                    class="fill-none transition-all duration-500
                                        @if($selectedMemberDetails['total_score'] >= 85) stroke-green-500
                                        @elseif($selectedMemberDetails['total_score'] >= 70) stroke-blue-500
                                        @elseif($selectedMemberDetails['total_score'] >= 55) stroke-yellow-500
                                        @else stroke-red-500
                                        @endif
                                    " />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-2xl font-bold">{{ $selectedMemberDetails['total_score'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Score Components -->
                <div>
                    <flux:heading size="base" class="mb-3">Score Components</flux:heading>
                    
                    @if(isset($selectedMemberDetails['scoring_mode']) && $selectedMemberDetails['scoring_mode'] === 'in_progress')
                        <flux:text size="sm" class="text-amber-600 dark:text-amber-400 mb-3">
                            <flux:icon.information-circle class="size-4 inline" />
                            Note: Score based on work in progress (no completed tickets yet)
                        </flux:text>
                    @endif
                    
                    <div class="space-y-3">
                        @foreach($selectedMemberDetails['components'] as $scoreComponent)
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            <div class="size-4
                                                @if($scoreComponent['color'] === 'green') text-green-500
                                                @elseif($scoreComponent['color'] === 'yellow') text-yellow-500
                                                @elseif($scoreComponent['color'] === 'red') text-red-500
                                                @else text-gray-500
                                                @endif">
                                                @switch($scoreComponent['icon'])
                                                    @case('check-circle')
                                                        <flux:icon.check-circle class="size-4" />
                                                        @break
                                                    @case('trending-up')
                                                        <flux:icon.arrow-trending-up class="size-4" />
                                                        @break
                                                    @case('clock')
                                                        <flux:icon.clock class="size-4" />
                                                        @break
                                                    @case('star')
                                                        <flux:icon.star class="size-4" />
                                                        @break
                                                    @case('activity')
                                                        <flux:icon.chart-bar class="size-4" />
                                                        @break
                                                    @case('lightning-bolt')
                                                        <flux:icon.bolt class="size-4" />
                                                        @break
                                                    @case('briefcase')
                                                        <flux:icon.briefcase class="size-4" />
                                                        @break
                                                    @default
                                                        <flux:icon.information-circle class="size-4" />
                                                @endswitch
                                            </div>
                                            <flux:text class="font-medium">{{ $scoreComponent['name'] }}</flux:text>
                                            <flux:badge size="xs" variant="outline">{{ $scoreComponent['weight'] }}</flux:badge>
                                        </div>
                                        <flux:text size="sm" class="text-gray-500">
                                            {{ $scoreComponent['description'] }}
                                        </flux:text>
                                        
                                        <!-- Progress Bar -->
                                        <div class="mt-2">
                                            <div class="flex items-center justify-between mb-1">
                                                <flux:text size="xs" class="text-gray-400">Raw Score</flux:text>
                                                <flux:text size="xs" class="font-medium">{{ $scoreComponent['raw_score'] }}%</flux:text>
                                            </div>
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-500
                                                    @if($scoreComponent['color'] === 'green') bg-green-500
                                                    @elseif($scoreComponent['color'] === 'yellow') bg-yellow-500
                                                    @elseif($scoreComponent['color'] === 'red') bg-red-500
                                                    @else bg-gray-500
                                                    @endif"
                                                     style="width: {{ $scoreComponent['raw_score'] }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <flux:text size="xs" class="text-gray-500">Weighted</flux:text>
                                        <flux:text class="font-bold text-lg
                                            @if($scoreComponent['color'] === 'green') text-green-600
                                            @elseif($scoreComponent['color'] === 'yellow') text-yellow-600
                                            @elseif($scoreComponent['color'] === 'red') text-red-600
                                            @else text-gray-600
                                            @endif">
                                            +{{ $scoreComponent['weighted_score'] }}
                                        </flux:text>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Total Calculation -->
                    <div class="mt-4 pt-3 border-t dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <flux:text class="font-semibold">Total Calculated Score</flux:text>
                            <flux:heading size="lg" class="
                                @if($selectedMemberDetails['total_calculated'] >= 85) text-green-600
                                @elseif($selectedMemberDetails['total_calculated'] >= 70) text-blue-600
                                @elseif($selectedMemberDetails['total_calculated'] >= 55) text-yellow-600
                                @else text-red-600
                                @endif
                            ">
                                {{ $selectedMemberDetails['total_calculated'] }}/100
                            </flux:heading>
                        </div>
                    </div>
                </div>
                
                <!-- Key Metrics -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <flux:heading size="sm" class="mb-3">Current Performance Metrics</flux:heading>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                        <div>
                            <flux:text size="xs" class="text-gray-500">Tickets (Resolved/Total)</flux:text>
                            <flux:text class="font-medium">
                                {{ $selectedMemberDetails['metrics']['resolved_tickets'] }}/{{ $selectedMemberDetails['metrics']['total_tickets'] }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-gray-500">Resolution Rate</flux:text>
                            <flux:text class="font-medium">{{ $selectedMemberDetails['metrics']['resolution_rate'] }}%</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-gray-500">Avg Resolution Time</flux:text>
                            <flux:text class="font-medium">{{ $selectedMemberDetails['metrics']['avg_resolution_time'] }}h</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-gray-500">Total Hours</flux:text>
                            <flux:text class="font-medium">{{ $selectedMemberDetails['metrics']['total_hours'] }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-gray-500">Utilization Rate</flux:text>
                            <flux:text class="font-medium">{{ $selectedMemberDetails['metrics']['utilization_rate'] }}%</flux:text>
                        </div>
                        <div>
                            <flux:text size="xs" class="text-gray-500">Customer Satisfaction</flux:text>
                            <flux:text class="font-medium">
                                @if($selectedMemberDetails['metrics']['customer_satisfaction'] > 0)
                                    {{ $selectedMemberDetails['metrics']['customer_satisfaction'] }}/5
                                @else
                                    N/A
                                @endif
                            </flux:text>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="primary">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</flux:card>
