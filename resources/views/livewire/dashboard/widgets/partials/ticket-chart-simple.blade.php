@if($chartData && !empty($chartData))
    <div class="mt-6">
        <!-- Bar chart with inline values -->
        <div class="space-y-4">
            @foreach($chartData as $item)
                @php
                    $total = collect($chartData)->sum('value');
                    $percentage = $total > 0 ? ($item['value'] / $total) * 100 : 0;
                    $colorClasses = [
                        'red' => 'bg-red-500',
                        'orange' => 'bg-orange-500',
                        'yellow' => 'bg-yellow-500',
                        'green' => 'bg-green-500',
                        'blue' => 'bg-blue-500',
                        'purple' => 'bg-purple-500',
                        'pink' => 'bg-pink-500',
                        'gray' => 'bg-gray-500',
                        'indigo' => 'bg-indigo-500',
                        'teal' => 'bg-teal-500',
                    ];
                    $bgColor = $colorClasses[$item['color']] ?? 'bg-gray-500';
                    $lightColorClasses = [
                        'red' => 'bg-red-50 dark:bg-red-900/20',
                        'orange' => 'bg-orange-50 dark:bg-orange-900/20',
                        'yellow' => 'bg-yellow-50 dark:bg-yellow-900/20',
                        'green' => 'bg-green-50 dark:bg-green-900/20',
                        'blue' => 'bg-blue-50 dark:bg-blue-900/20',
                        'purple' => 'bg-purple-50 dark:bg-purple-900/20',
                        'pink' => 'bg-pink-50 dark:bg-pink-900/20',
                        'gray' => 'bg-gray-50 dark:bg-gray-900/20',
                        'indigo' => 'bg-indigo-50 dark:bg-indigo-900/20',
                        'teal' => 'bg-teal-50 dark:bg-teal-900/20',
                    ];
                    $lightBgColor = $lightColorClasses[$item['color']] ?? 'bg-gray-50 dark:bg-gray-900/20';
                @endphp
                <div class="relative">
                    <div class="flex items-center justify-between mb-1.5">
                        <flux:text size="sm" class="font-medium">{{ $item['name'] }}</flux:text>
                        <div class="flex items-center gap-3">
                            <flux:text size="sm" class="font-semibold tabular-nums">{{ $item['value'] }}</flux:text>
                            <flux:text size="xs" class="text-gray-500 dark:text-gray-400 tabular-nums min-w-[3rem] text-right">{{ number_format($percentage, 1) }}%</flux:text>
                        </div>
                    </div>
                    <div class="w-full {{ $lightBgColor }} rounded-full h-8 relative overflow-hidden">
                        <div class="{{ $bgColor }} h-8 rounded-full transition-all duration-500 opacity-80" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Summary stats -->
        <div class="mt-8 flex items-center justify-between border-t dark:border-gray-700 pt-4">
            @php
                $total = collect($chartData)->sum('value');
                $maxItem = collect($chartData)->sortByDesc('value')->first();
            @endphp
            
            <div class="flex items-center gap-6">
                <div>
                    <flux:text size="xs" class="text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Tickets</flux:text>
                    <flux:heading size="xl" class="mt-0.5 tabular-nums">{{ number_format($total) }}</flux:heading>
                </div>
                
                @if($maxItem && $total > 0)
                    <div class="border-l dark:border-gray-700 pl-6">
                        <flux:text size="xs" class="text-gray-500 dark:text-gray-400 uppercase tracking-wide">Most Common</flux:text>
                        <flux:text size="lg" class="mt-0.5 font-semibold">{{ $maxItem['name'] }} ({{ round(($maxItem['value'] / $total) * 100) }}%)</flux:text>
                    </div>
                @endif
            </div>
            
            <!-- Compact legend -->
            <div class="flex flex-wrap gap-4">
                @php
                    $topItems = collect($chartData)->take(3);
                @endphp
                @foreach($topItems as $item)
                    @php
                        $colorClasses = [
                            'red' => 'bg-red-500',
                            'orange' => 'bg-orange-500',
                            'yellow' => 'bg-yellow-500',
                            'green' => 'bg-green-500',
                            'blue' => 'bg-blue-500',
                            'purple' => 'bg-purple-500',
                            'pink' => 'bg-pink-500',
                            'gray' => 'bg-gray-500',
                            'indigo' => 'bg-indigo-500',
                            'teal' => 'bg-teal-500',
                        ];
                        $bgColor = $colorClasses[$item['color']] ?? 'bg-gray-500';
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full {{ $bgColor }}"></div>
                        <flux:text size="xs" class="text-gray-600 dark:text-gray-400">{{ $item['name'] }}</flux:text>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="flex items-center justify-center h-64">
        <flux:text class="text-gray-500">No data available</flux:text>
    </div>
@endif