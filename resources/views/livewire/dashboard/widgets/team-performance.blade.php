<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.users class="size-5 text-purple-500" />
                Team Performance
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Monitor team productivity and performance metrics
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
                <flux:tabs wire:model.live="metric" variant="segmented" size="sm">
                    <flux:tab name="tickets">Tickets</flux:tab>
                    <flux:tab name="hours">Hours</flux:tab>
                    <flux:tab name="revenue">Revenue</flux:tab>
                </flux:tabs>
                
                <!-- Hidden panels - content is controlled by Livewire metric property -->
                <flux:tab.panel name="tickets" class="hidden"></flux:tab.panel>
                <flux:tab.panel name="hours" class="hidden"></flux:tab.panel>
                <flux:tab.panel name="revenue" class="hidden"></flux:tab.panel>
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
                        
                        <!-- Performance Score -->
                        <div class="text-right">
                            <div class="relative inline-block">
                                <svg class="w-16 h-16 -rotate-90">
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
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-lg font-bold">{{ $member['performance_score'] }}</span>
                                </div>
                            </div>
                            <flux:text size="xs" class="text-zinc-500">Performance Score</flux:text>
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
                                <flux:text class="font-medium">{{ $member['customer_satisfaction'] }}</flux:text>
                                <flux:icon.star class="size-3 text-yellow-500 fill-current" />
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
        
        <!-- Summary Stats -->
        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text size="sm" class="text-zinc-500">Team Average</flux:text>
                    <flux:heading size="lg">{{ number_format($teamMembers->avg('performance_score'), 1) }}</flux:heading>
                </div>
                
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
                    <flux:text size="sm" class="text-green-600 dark:text-green-400">Top Performer</flux:text>
                    <flux:heading size="lg" class="text-green-600 dark:text-green-400">
                        {{ $teamMembers->first()['name'] ?? 'N/A' }}
                    </flux:heading>
                </div>
                
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                    <flux:text size="sm" class="text-blue-600 dark:text-blue-400">Total Hours</flux:text>
                    <flux:heading size="lg" class="text-blue-600 dark:text-blue-400">
                        {{ number_format($teamMembers->sum('total_hours'), 0) }}
                    </flux:heading>
                </div>
                
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                    <flux:text size="sm" class="text-purple-600 dark:text-purple-400">Revenue</flux:text>
                    <flux:heading size="lg" class="text-purple-600 dark:text-purple-400">
                        ${{ number_format($teamMembers->sum('revenue_generated'), 0) }}
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
    <div wire:loading wire:target="loadTeamPerformance,setPeriod,sort" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-purple-500" />
            <flux:text>Analyzing performance...</flux:text>
        </div>
    </div>
</flux:card>
