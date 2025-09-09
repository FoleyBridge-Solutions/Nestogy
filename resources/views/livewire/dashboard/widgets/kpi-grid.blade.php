<div>
    <!-- Time Filter -->
    <div class="flex justify-end mb-4">
        <flux:tab.group>
            <flux:tabs wire:model.live="period" variant="pills" size="sm">
                <flux:tab name="month">Month</flux:tab>
                <flux:tab name="quarter">Quarter</flux:tab>
                <flux:tab name="year">Year</flux:tab>
                <flux:tab name="all">All Time</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire period property -->
            <flux:tab.panel name="month" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="quarter" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="year" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="all" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>
    
    <!-- KPI Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" wire:poll.30s="loadKpis">
    @forelse($kpis as $kpi)
        <flux:card class="relative overflow-hidden group hover:shadow-lg transition-all duration-300">
            <!-- Background Gradient -->
            <div class="absolute inset-0 bg-gradient-to-br opacity-5 group-hover:opacity-10 transition-opacity
                @switch($kpi['color'])
                    @case('green')
                        from-green-500 to-emerald-600
                        @break
                    @case('blue')
                        from-blue-500 to-indigo-600
                        @break
                    @case('purple')
                        from-purple-500 to-pink-600
                        @break
                    @case('orange')
                        from-orange-500 to-red-600
                        @break
                    @case('red')
                        from-red-500 to-rose-600
                        @break
                    @case('indigo')
                        from-indigo-500 to-purple-600
                        @break
                    @case('yellow')
                        from-yellow-500 to-orange-600
                        @break
                    @case('teal')
                        from-teal-500 to-cyan-600
                        @break
                    @default
                        from-gray-500 to-slate-600
                @endswitch
            "></div>
            
            <!-- Content -->
            <div class="relative">
                <div class="flex items-start justify-between mb-3">
                    <div class="p-2 rounded-lg
                        @switch($kpi['color'])
                            @case('green')
                                bg-green-100 dark:bg-green-900/30
                                @break
                            @case('blue')
                                bg-blue-100 dark:bg-blue-900/30
                                @break
                            @case('purple')
                                bg-purple-100 dark:bg-purple-900/30
                                @break
                            @case('orange')
                                bg-orange-100 dark:bg-orange-900/30
                                @break
                            @case('red')
                                bg-red-100 dark:bg-red-900/30
                                @break
                            @case('indigo')
                                bg-indigo-100 dark:bg-indigo-900/30
                                @break
                            @case('yellow')
                                bg-yellow-100 dark:bg-yellow-900/30
                                @break
                            @case('teal')
                                bg-teal-100 dark:bg-teal-900/30
                                @break
                            @default
                                bg-gray-100 dark:bg-gray-900/30
                        @endswitch
                    ">
                        @php
                            $iconClass = 'size-6 ';
                            $iconClass .= match($kpi['color']) {
                                'green' => 'text-green-600 dark:text-green-400',
                                'blue' => 'text-blue-600 dark:text-blue-400',
                                'purple' => 'text-purple-600 dark:text-purple-400',
                                'orange' => 'text-orange-600 dark:text-orange-400',
                                'red' => 'text-red-600 dark:text-red-400',
                                'indigo' => 'text-indigo-600 dark:text-indigo-400',
                                'yellow' => 'text-yellow-600 dark:text-yellow-400',
                                'teal' => 'text-teal-600 dark:text-teal-400',
                                default => 'text-gray-600 dark:text-gray-400'
                            };
                        @endphp
                        
                        @switch($kpi['icon'])
                            @case('currency-dollar')
                                <flux:icon.currency-dollar class="{{ $iconClass }}" />
                                @break
                            @case('document-text')
                                <flux:icon.document-text class="{{ $iconClass }}" />
                                @break
                            @case('users')
                                <flux:icon.users class="{{ $iconClass }}" />
                                @break
                            @case('ticket')
                                <flux:icon.ticket class="{{ $iconClass }}" />
                                @break
                            @case('exclamation-triangle')
                                <flux:icon.exclamation-triangle class="{{ $iconClass }}" />
                                @break
                            @case('clock')
                                <flux:icon.clock class="{{ $iconClass }}" />
                                @break
                            @case('star')
                                <flux:icon.star class="{{ $iconClass }}" />
                                @break
                            @case('chart-bar')
                                <flux:icon.chart-bar class="{{ $iconClass }}" />
                                @break
                            @default
                                <flux:icon.information-circle class="{{ $iconClass }}" />
                        @endswitch
                    </div>
                    
                    <!-- Trend Indicator -->
                    <flux:tooltip content="{{ $kpi['trendValue'] . ' ' . $kpi['description'] }}">
                        <div class="flex items-center gap-1">
                            @if($kpi['trend'] === 'up')
                                <flux:icon.arrow-trending-up class="size-4 text-green-500" />
                                <flux:text size="xs" class="text-green-600 dark:text-green-400 font-medium">
                                    {{ $kpi['trendValue'] }}
                                </flux:text>
                            @elseif($kpi['trend'] === 'down')
                                <flux:icon.arrow-trending-down class="size-4 text-red-500" />
                                <flux:text size="xs" class="text-red-600 dark:text-red-400 font-medium">
                                    {{ $kpi['trendValue'] }}
                                </flux:text>
                            @elseif($kpi['trend'] === 'warning')
                                <flux:icon.exclamation-triangle class="size-4 text-orange-500" />
                                <flux:text size="xs" class="text-orange-600 dark:text-orange-400 font-medium">
                                    {{ $kpi['trendValue'] }}
                                </flux:text>
                            @else
                                <flux:icon.minus class="size-4 text-gray-400" />
                                <flux:text size="xs" class="text-gray-500 font-medium">
                                    {{ $kpi['trendValue'] }}
                                </flux:text>
                            @endif
                        </div>
                    </flux:tooltip>
                </div>
                
                <!-- Label -->
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                    {{ $kpi['label'] }}
                </flux:text>
                
                <!-- Value -->
                <div class="mt-2">
                    <flux:heading size="2xl" class="font-bold tabular-nums">
                        @switch($kpi['format'])
                            @case('currency')
                                ${{ number_format($kpi['value'], 2) }}
                                @break
                            @case('percentage')
                                {{ $kpi['value'] }}%
                                @break
                            @case('hours')
                                {{ $kpi['value'] }} hrs
                                @break
                            @case('rating')
                                <div class="flex items-center gap-1">
                                    {{ $kpi['value'] }}
                                    <flux:icon.star class="size-5 text-yellow-500 fill-current" />
                                </div>
                                @break
                            @default
                                {{ number_format($kpi['value']) }}
                        @endswitch
                    </flux:heading>
                </div>
                
                <!-- Description -->
                <flux:text size="xs" class="text-zinc-400 dark:text-zinc-500 mt-1">
                    {{ $kpi['description'] }}
                </flux:text>
            </div>
            
            <!-- Loading State -->
            <div wire:loading wire:target="loadKpis" class="absolute inset-0 bg-white/50 dark:bg-zinc-900/50 flex items-center justify-center">
                <flux:icon.arrow-path class="size-5 animate-spin text-zinc-500" />
            </div>
        </flux:card>
    @empty
        <div class="col-span-12-span-full">
            <flux:card class="p-8 text-center">
                @if($loading)
                    <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                    <flux:text>Loading KPIs...</flux:text>
                @else
                    <flux:icon.chart-bar class="size-12 text-zinc-300 mx-auto mb-3" />
                    <flux:heading size="lg">No Data Available</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">KPI data will appear here once available</flux:text>
                @endif
            </flux:card>
        </div>
    @endforelse
    </div>
</div>
