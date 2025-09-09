<div class="h-full">
    <flux:card class="h-full">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.currency-dollar class="size-5 text-purple-500" />
                Financial KPIs
            </flux:heading>
        </div>
        
        <div class="p-6">
            @if($loading)
                <div class="flex items-center justify-center h-64">
                    <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400" />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($kpis as $kpi)
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $kpi['label'] }}
                                    </flux:text>
                                    
                                    <div class="mt-1 flex items-baseline">
                                        <flux:heading size="xl" class="font-semibold">
                                            @if($kpi['format'] === 'currency')
                                                ${{ number_format($kpi['value'], 2) }}
                                            @elseif($kpi['format'] === 'percentage')
                                                {{ number_format($kpi['value'], 1) }}%
                                            @else
                                                {{ number_format($kpi['value']) }}
                                            @endif
                                        </flux:heading>
                                    </div>
                                    
                                    @if(isset($kpi['trend']))
                                        <div class="mt-2 flex items-center text-xs">
                                            @if($kpi['trend'] === 'up')
                                                <flux:icon.arrow-trending-up 
                                                    class="size-4 {{ $kpi['label'] === 'Churn Rate' ? 'text-red-500' : 'text-green-500' }}" 
                                                />
                                            @elseif($kpi['trend'] === 'down')
                                                <flux:icon.arrow-trending-down 
                                                    class="size-4 {{ $kpi['label'] === 'Churn Rate' ? 'text-green-500' : 'text-red-500' }}" 
                                                />
                                            @else
                                                <flux:icon.minus class="size-4 text-zinc-400" />
                                            @endif
                                            
                                            <span class="ml-1 
                                                @if($kpi['trend'] === 'up')
                                                    {{ $kpi['label'] === 'Churn Rate' ? 'text-red-600' : 'text-green-600' }}
                                                @elseif($kpi['trend'] === 'down')
                                                    {{ $kpi['label'] === 'Churn Rate' ? 'text-green-600' : 'text-red-600' }}
                                                @else
                                                    text-zinc-500
                                                @endif">
                                                {{ $kpi['trendValue'] ?? '' }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="ml-3">
                                    @switch($kpi['icon'])
                                        @case('chart-bar')
                                            <flux:icon.chart-bar class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('trending-up')
                                            <flux:icon.arrow-trending-up class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('currency-dollar')
                                            <flux:icon.currency-dollar class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('clock')
                                            <flux:icon.clock class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('document-text')
                                            <flux:icon.document-text class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('users')
                                            <flux:icon.users class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('arrow-trending-down')
                                            <flux:icon.arrow-trending-down class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @case('check-circle')
                                            <flux:icon.check-circle class="size-5 text-{{ $kpi['color'] }}-500" />
                                            @break
                                        @default
                                            <flux:icon.chart-bar class="size-5 text-zinc-400" />
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Bottom Summary Row -->
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <flux:text class="text-xs text-zinc-500">Period</flux:text>
                            <flux:text class="font-medium">{{ now()->format('F Y') }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Last Updated</flux:text>
                            <flux:text class="font-medium">{{ now()->format('g:i A') }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Currency</flux:text>
                            <flux:text class="font-medium">USD</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Data Quality</flux:text>
                            <div class="flex items-center gap-1">
                                <flux:icon.check-circle class="size-4 text-green-500" />
                                <flux:text class="font-medium text-green-600">Live</flux:text>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:card>
</div>
