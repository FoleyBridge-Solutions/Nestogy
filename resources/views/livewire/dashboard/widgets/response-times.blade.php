<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.clock class="size-5 text-indigo-500" />
                Response Times
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Average response and resolution metrics
            </flux:text>
        </div>
        
        <!-- Period Selector -->
        <flux:tab.group>
            <flux:tabs wire:model.live="period" variant="pills" size="sm">
                <flux:tab name="day">24h</flux:tab>
                <flux:tab name="week">7d</flux:tab>
                <flux:tab name="month">30d</flux:tab>
            </flux:tabs>
            
            <!-- Hidden panels - content is controlled by Livewire period property -->
            <flux:tab.panel name="day" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="week" class="hidden"></flux:tab.panel>
            <flux:tab.panel name="month" class="hidden"></flux:tab.panel>
        </flux:tab.group>
    </div>
    
    <!-- Key Metrics -->
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg">
            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">Avg First Response</flux:text>
            <div class="flex items-baseline gap-2 mt-1">
                <flux:heading size="2xl" class="font-bold">
                    {{ $responseData['avg_first_response'] ?? 0 }}h
                </flux:heading>
                @if(($responseData['improvement'] ?? 0) > 0)
                    <flux:badge color="green" size="sm">
                        <flux:icon.arrow-down class="size-3" />
                        {{ abs($responseData['improvement']) }}%
                    </flux:badge>
                @elseif(($responseData['improvement'] ?? 0) < 0)
                    <flux:badge color="red" size="sm">
                        <flux:icon.arrow-up class="size-3" />
                        {{ abs($responseData['improvement']) }}%
                    </flux:badge>
                @endif
            </div>
        </div>
        
        <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg">
            <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">Avg Resolution</flux:text>
            <div class="flex items-baseline gap-2 mt-1">
                <flux:heading size="2xl" class="font-bold">
                    {{ $responseData['avg_resolution'] ?? 0 }}h
                </flux:heading>
            </div>
        </div>
    </div>
    
    <!-- Response by Priority -->
    <div class="mb-6">
        <flux:heading size="base" class="mb-3">Response Time by Priority</flux:heading>
        <div class="space-y-2">
            @foreach(['critical', 'high', 'medium', 'low'] as $priority)
                @php
                    $data = $responseData['by_priority'][$priority] ?? null;
                    $color = match($priority) {
                        'critical' => 'red',
                        'high' => 'orange',
                        'medium' => 'yellow',
                        'low' => 'green',
                        default => 'zinc'
                    };
                @endphp
                
                @if($data)
                    <div class="flex items-center justify-between p-2 border border-zinc-200 dark:border-zinc-700 rounded">
                        <div class="flex items-center gap-3">
                            <flux:badge color="{{ $color }}" size="sm">{{ ucfirst($priority) }}</flux:badge>
                            <flux:text size="sm">{{ $data['count'] }} tickets</flux:text>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-zinc-500">Min: {{ $data['min'] }}h</span>
                            <span class="font-medium">Avg: {{ $data['avg'] }}h</span>
                            <span class="text-zinc-500">Max: {{ $data['max'] }}h</span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    
    <!-- Trend Chart -->
    @if($chartData && count($chartData) > 0)
        <div>
            <flux:heading size="base" class="mb-3">Response Time Trend</flux:heading>
            <flux:chart :value="$chartData" class="aspect-[3/1]">
                <flux:chart.svg>
                    <flux:chart.line field="response" class="text-indigo-500 dark:text-indigo-400" />
                    <flux:chart.axis axis="x" field="date">
                        <flux:chart.axis.line />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y" tick-suffix="h">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                    <flux:chart.cursor />
                </flux:chart.svg>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" />
                    <flux:chart.tooltip.value field="response" label="Avg Response" suffix=" hours" />
                </flux:chart.tooltip>
            </flux:chart>
        </div>
    @endif
    
    <!-- Loading State -->
    @if($loading)
        <div class="flex items-center justify-center h-32">
            <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400" />
        </div>
    @endif
</flux:card>
