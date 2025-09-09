<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.clock class="size-5 text-purple-500" />
                SLA Monitor
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Track service level agreement compliance
            </flux:text>
        </div>
        
        <!-- Period Selector -->
        <flux:tab.group>
            <flux:tabs wire:model.live="period" variant="pills" size="sm">
                <flux:tab name="today">Today</flux:tab>
                <flux:tab name="week">This Week</flux:tab>
                <flux:tab name="month">This Month</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire period property -->
            <flux:tab.panel name="today" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="week" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="month" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>
    
    <!-- Overall Compliance -->
    <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">Overall SLA Compliance</flux:text>
                <div class="flex items-baseline gap-2 mt-1">
                    <flux:heading size="3xl" class="font-bold">
                        {{ $slaMetrics['overall_compliance'] ?? 0 }}%
                    </flux:heading>
                    @if(($slaMetrics['overall_compliance'] ?? 0) >= 95)
                        <flux:badge color="green" size="sm">Excellent</flux:badge>
                    @elseif(($slaMetrics['overall_compliance'] ?? 0) >= 85)
                        <flux:badge color="yellow" size="sm">Good</flux:badge>
                    @else
                        <flux:badge color="red" size="sm">Needs Improvement</flux:badge>
                    @endif
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <flux:text size="xs" class="text-zinc-500">Total</flux:text>
                    <flux:text size="lg" class="font-semibold">{{ $slaMetrics['total_tickets'] ?? 0 }}</flux:text>
                </div>
                <div>
                    <flux:text size="xs" class="text-red-500">Breached</flux:text>
                    <flux:text size="lg" class="font-semibold text-red-600">{{ $slaMetrics['breached_count'] ?? 0 }}</flux:text>
                </div>
                <div>
                    <flux:text size="xs" class="text-amber-500">Warnings</flux:text>
                    <flux:text size="lg" class="font-semibold text-amber-600">{{ $slaMetrics['warning_count'] ?? 0 }}</flux:text>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SLA by Priority -->
    <div class="mb-6">
        <flux:heading size="base" class="mb-3">Performance by Priority</flux:heading>
        <div class="space-y-3">
            @foreach($slaMetrics['by_priority'] ?? [] as $priority => $data)
                @php
                    $compliance = $data['total'] > 0 ? round(($data['met'] / $data['total']) * 100, 1) : 100;
                    $avgResponse = $slaMetrics['avg_response_times'][$priority] ?? 0;
                    $color = match($priority) {
                        'critical' => 'red',
                        'high' => 'orange',
                        'medium' => 'yellow',
                        'low' => 'green',
                        default => 'zinc'
                    };
                @endphp
                
                <div class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <flux:badge color="{{ $color }}">{{ ucfirst($priority) }}</flux:badge>
                        <div>
                            <flux:text size="sm" class="font-medium">
                                {{ $data['met'] }}/{{ $data['total'] }} tickets met SLA
                            </flux:text>
                            <flux:text size="xs" class="text-zinc-500">
                                Target: {{ $data['target'] }}h | Avg: {{ round($avgResponse, 1) }}h
                            </flux:text>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <flux:text size="sm" class="font-semibold">{{ $compliance }}%</flux:text>
                        <div class="w-24 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                            <div class="h-full transition-all duration-300
                                @if($compliance >= 95) bg-green-500
                                @elseif($compliance >= 85) bg-yellow-500
                                @else bg-red-500 @endif"
                                style="width: {{ $compliance }}%">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Breached Tickets -->
    @if(count($breachedTickets) > 0)
        <div class="mb-6">
            <flux:heading size="base" class="mb-3 text-red-600">
                <flux:icon.exclamation-triangle class="size-4 inline" />
                SLA Breached Tickets
            </flux:heading>
            <div class="space-y-2">
                @foreach($breachedTickets as $ticket)
                    <div class="flex items-center justify-between p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="flex-1">
                            <flux:text size="sm" class="font-medium">
                                #{{ $ticket['id'] }} - {{ Str::limit($ticket['subject'], 40) }}
                            </flux:text>
                            <flux:text size="xs" class="text-zinc-500">
                                {{ $ticket['client'] }} • {{ round($ticket['hours_overdue']) }}h overdue
                            </flux:text>
                        </div>
                        <flux:badge color="red" size="sm">{{ ucfirst($ticket['priority']) }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Warning Tickets -->
    @if(count($warningTickets) > 0)
        <div>
            <flux:heading size="base" class="mb-3 text-amber-600">
                <flux:icon.clock class="size-4 inline" />
                SLA Warning Tickets
            </flux:heading>
            <div class="space-y-2">
                @foreach($warningTickets as $ticket)
                    <div class="flex items-center justify-between p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <div class="flex-1">
                            <flux:text size="sm" class="font-medium">
                                #{{ $ticket['id'] }} - {{ Str::limit($ticket['subject'], 40) }}
                            </flux:text>
                            <flux:text size="xs" class="text-zinc-500">
                                {{ $ticket['client'] }} • {{ round($ticket['time_remaining']) }}h remaining
                            </flux:text>
                        </div>
                        <flux:badge color="amber" size="sm">{{ ucfirst($ticket['priority']) }}</flux:badge>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    <!-- Empty State -->
    @if($loading)
        <div class="flex items-center justify-center h-32">
            <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400" />
        </div>
    @elseif(($slaMetrics['total_tickets'] ?? 0) === 0)
        <div class="text-center py-8">
            <flux:icon.check-circle class="size-12 text-green-400 mx-auto mb-3" />
            <flux:text>No tickets to monitor in this period</flux:text>
        </div>
    @endif
</flux:card>
