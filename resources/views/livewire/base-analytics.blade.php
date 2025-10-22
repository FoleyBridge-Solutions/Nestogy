<div class="space-y-6">
    {{-- Period Filter --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Analytics</flux:heading>
        
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="period" size="sm" class="w-48">
                @foreach($periodOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if($hasData)
        {{-- Stats Cards --}}
        @if(!empty($stats))
            <x-index-page-stats :stats="$stats" />
        @endif

        {{-- Charts Section --}}
        @if(!empty($charts))
            <div class="grid gap-6">
                @foreach($charts as $chartKey => $chart)
                    <flux:card>
                        <div class="mb-6">
                            <flux:heading size="lg">{{ $chart['title'] ?? 'Chart' }}</flux:heading>
                            @if(isset($chart['description']))
                                <flux:subheading class="mt-1">{{ $chart['description'] }}</flux:subheading>
                            @endif
                        </div>

                        @if(!empty($chart['cachedData']) && count($chart['cachedData']) > 0)
                            @php
                                $chartType = $chart['type'] ?? 'line';
                                $chartData = $chart['cachedData'];
                            @endphp

                            <flux:chart :value="$chartData" class="aspect-[2/1] min-h-[400px]">
                                    <flux:chart.svg>
                                        @foreach($chart['fields'] ?? [] as $field)
                                            @if($chartType === 'bar')
                                                <flux:chart.bar 
                                                    field="{{ $field['key'] }}" 
                                                    class="{{ $field['class'] ?? 'text-blue-500' }}" 
                                                />
                                            @elseif($chartType === 'area')
                                                <flux:chart.area 
                                                    field="{{ $field['key'] }}" 
                                                    class="{{ $field['areaClass'] ?? 'text-blue-200/50' }}" 
                                                />
                                                <flux:chart.line 
                                                    field="{{ $field['key'] }}" 
                                                    class="{{ $field['lineClass'] ?? $field['class'] ?? 'text-blue-500' }}" 
                                                />
                                            @else
                                                <flux:chart.line 
                                                    field="{{ $field['key'] }}" 
                                                    class="{{ $field['lineClass'] ?? $field['class'] ?? 'text-blue-500' }}" 
                                                />
                                            @endif
                                        @endforeach
                                        
                                        <flux:chart.axis axis="x" field="{{ $chart['xAxis'] ?? 'date' }}">
                                            <flux:chart.axis.line />
                                            <flux:chart.axis.tick />
                                        </flux:chart.axis>
                                        
                                        @if(isset($chart['yFormat']))
                                            <flux:chart.axis axis="y" :format="$chart['yFormat']">
                                                <flux:chart.axis.grid />
                                                <flux:chart.axis.tick />
                                            </flux:chart.axis>
                                        @else
                                            <flux:chart.axis axis="y">
                                                <flux:chart.axis.grid />
                                                <flux:chart.axis.tick />
                                            </flux:chart.axis>
                                        @endif
                                        
                                        <flux:chart.cursor />
                                    </flux:chart.svg>
                                
                                <flux:chart.tooltip>
                                    <flux:chart.tooltip.heading field="{{ $chart['xAxis'] ?? 'date' }}" />
                                    @foreach($chart['fields'] ?? [] as $field)
                                        @if(isset($field['format']))
                                            <flux:chart.tooltip.value 
                                                field="{{ $field['key'] }}" 
                                                label="{{ $field['label'] ?? $field['key'] }}" 
                                                :format="$field['format']"
                                            />
                                        @else
                                            <flux:chart.tooltip.value 
                                                field="{{ $field['key'] }}" 
                                                label="{{ $field['label'] ?? $field['key'] }}" 
                                            />
                                        @endif
                                    @endforeach
                                </flux:chart.tooltip>
                            </flux:chart>
                            
                            @if(!empty($chart['fields']) && count($chart['fields']) > 1)
                                <div class="flex justify-center gap-6 mt-4 pt-2">
                                    @foreach($chart['fields'] as $field)
                                        <div class="flex items-center gap-2">
                                            <div class="w-3 h-3 rounded {{ $field['indicatorClass'] ?? 'bg-blue-400' }}"></div>
                                            <span class="text-sm">{{ $field['label'] ?? $field['key'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="text-center py-12 text-gray-500">
                                {{ $chart['emptyMessage'] ?? 'No data available for this chart' }}
                            </div>
                        @endif
                    </flux:card>
                @endforeach
            </div>
        @endif
    @else
        {{-- Empty State --}}
        <flux:card class="text-center py-16">
            <div class="flex flex-col items-center">
                <div class="mb-4">
                    <flux:icon.{{ $emptyState['icon'] }} class="size-16 text-gray-400" />
                </div>
                <flux:heading size="lg" class="mb-2">{{ $emptyState['title'] }}</flux:heading>
                <flux:subheading class="mb-6 max-w-md">{{ $emptyState['message'] }}</flux:subheading>
                
                @if($emptyState['action'])
                    <flux:button :href="$emptyState['action']" variant="primary">
                        {{ $emptyState['actionLabel'] }}
                    </flux:button>
                @endif
            </div>
        </flux:card>
    @endif
</div>
