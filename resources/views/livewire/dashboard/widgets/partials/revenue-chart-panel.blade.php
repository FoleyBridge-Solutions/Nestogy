@if($chartData && !empty($chartData))
    @php
        $currentTotal = collect($chartData)->sum('revenue');
        $previousTotal = collect($chartData)->sum('lastYear');
        $percentChange = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;
        $latestData = end($chartData);
    @endphp
    
    <div class="mt-6">
        <flux:chart class="grid gap-6" wire:model.live="chartData">
            <flux:chart.summary class="flex gap-8">
                <div>
                    <flux:text size="sm">Current Period</flux:text>
                    <flux:heading size="xl" class="mt-1 tabular-nums">
                         <flux:chart.summary.value 
                            field="revenue" 
                            :format="['style' => 'currency', 'currency' => 'USD']" 
                            fallback="{{ number_format($currentTotal, 2) }}"
                         />
                    </flux:heading>
                    <div class="mt-2 flex items-center gap-2">
                        @if($percentChange > 0)
                            <flux:badge color="green" size="sm">
                                <flux:icon.arrow-trending-up class="size-3" />
                                +{{ number_format(abs($percentChange), 1) }}%
                            </flux:badge>
                        @elseif($percentChange < 0)
                            <flux:badge color="red" size="sm">
                                <flux:icon.arrow-trending-down class="size-3" />
                                -{{ number_format(abs($percentChange), 1) }}%
                            </flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">
                                <flux:icon.minus class="size-3" />
                                No Change
                            </flux:badge>
                        @endif
                        <flux:text size="xs" class="text-zinc-500">vs previous period</flux:text>
                    </div>
                </div>
                
                @if($showComparison)
                    <div>
                        <flux:text size="sm">Previous Period</flux:text>
                        <flux:heading size="lg" class="mt-1 tabular-nums text-zinc-500">
                            ${{ number_format($previousTotal, 2) }}
                        </flux:heading>
                    </div>
                @endif
                
                <div>
                    <flux:text size="sm">Latest</flux:text>
                    <flux:heading size="lg" class="mt-1 tabular-nums">
                        ${{ number_format($latestData['revenue'] ?? 0, 2) }}
                    </flux:heading>
                    <flux:text size="xs" class="mt-1 text-zinc-500">
                        {{ \Carbon\Carbon::parse($latestData['date'] ?? now())->format('M d, Y') }}
                    </flux:text>
                </div>
            </flux:chart.summary>
            
            <!-- Chart Viewport -->
            <flux:chart.viewport class="aspect-[3/1]">
                <flux:chart.svg>
                    <!-- Comparison Line -->
                    @if($showComparison)
                        <flux:chart.line 
                            field="lastYear" 
                            class="text-zinc-300 dark:text-zinc-600" 
                            stroke-dasharray="4 4"
                        />
                    @endif
                    
                    <!-- Main Revenue Line -->
                    <flux:chart.line 
                        field="revenue" 
                        class="text-green-500 dark:text-green-400" 
                        stroke-width="3"
                    />
                    
                    <!-- Revenue Area -->
                    <flux:chart.area 
                        field="revenue" 
                        class="text-green-200/30 dark:text-green-400/20"
                    />
                    
                    <!-- Points for interactivity -->
                    <flux:chart.point 
                        field="revenue" 
                        class="text-green-500 dark:text-green-400"
                        r="4"
                        stroke-width="2"
                    />
                    
                    <!-- X Axis -->
                     <flux:chart.axis 
                        axis="x" 
                        field="date"
                        :format="$period === 'month' ? 
                            ['month' => 'short', 'day' => 'numeric'] : 
                            ['year' => 'numeric', 'month' => 'short']"
                     >
                        <flux:chart.axis.line class="text-zinc-200 dark:text-zinc-700" />
                        <flux:chart.axis.tick class="text-xs" />
                    </flux:chart.axis>
                    
                    <!-- Y Axis -->
                     <flux:chart.axis 
                        axis="y"
                        :format="[
                            'style' => 'currency',
                            'currency' => 'USD',
                            'notation' => 'compact',
                            'compactDisplay' => 'short',
                            'maximumFractionDigits' => 1
                        ]"
                     >
                        <flux:chart.axis.grid class="text-zinc-100 dark:text-zinc-800" />
                        <flux:chart.axis.tick class="text-xs" />
                    </flux:chart.axis>
                    
                    <!-- Cursor for interaction -->
                    <flux:chart.cursor class="text-zinc-400" stroke-dasharray="3,3" />
                </flux:chart.svg>
            </flux:chart.viewport>
            
            <!-- Tooltip -->
            <flux:chart.tooltip>
                 <flux:chart.tooltip.heading 
                    field="date"
                    :format="['year' => 'numeric', 'month' => 'long', 'day' => 'numeric']"
                 />
                 <flux:chart.tooltip.value 
                    field="revenue" 
                    label="Revenue"
                    :format="['style' => 'currency', 'currency' => 'USD']"
                 />
                @if($showComparison)
                     <flux:chart.tooltip.value 
                        field="lastYear" 
                        label="Previous Period"
                        :format="['style' => 'currency', 'currency' => 'USD']"
                     />
                @endif
                 <flux:chart.tooltip.value 
                    field="invoices" 
                    label="Invoiced"
                    :format="['style' => 'currency', 'currency' => 'USD']"
                 />
                <flux:chart.tooltip.value 
                    field="payments" 
                    label="Payments"
                />
            </flux:chart.tooltip>
        </flux:chart>
        
        <!-- Legend -->
        <div class="flex justify-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:chart.legend label="Current Period">
                <flux:chart.legend.indicator class="bg-green-500" />
            </flux:chart.legend>
            
            @if($showComparison)
                <flux:chart.legend label="Previous Period">
                    <flux:chart.legend.indicator class="bg-zinc-400" />
                </flux:chart.legend>
            @endif
        </div>
    </div>
@else
    <!-- Empty State -->
    <div class="flex items-center justify-center h-96 mt-6">
        <div class="text-center">
            @if($loading)
                <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                <flux:heading size="lg">Loading Revenue Data...</flux:heading>
            @else
                <flux:icon.chart-bar class="size-12 text-zinc-300 mx-auto mb-3" />
                <flux:heading size="lg">No Revenue Data</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Revenue data will appear here once transactions are recorded
                </flux:text>
                <flux:button variant="primary" size="sm" class="mt-4" wire:click="loadChartData">
                    Refresh Data
                </flux:button>
            @endif
        </div>
    </div>
@endif
