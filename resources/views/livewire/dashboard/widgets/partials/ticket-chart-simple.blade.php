@if($chartData && !empty($chartData))
    <div class="mt-6 space-y-4">
        @foreach($chartData as $item)
            <div>
                <div class="flex justify-between mb-1">
                    <flux:text size="sm">{{ $item['name'] }}</flux:text>
                    <flux:text size="sm" class="font-medium">{{ $item['value'] }}</flux:text>
                </div>
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
                @endphp
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="{{ $bgColor }} h-2 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Legend -->
    <div class="mt-6 flex flex-wrap gap-3 justify-center">
        @foreach($chartData as $item)
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
                <div class="w-3 h-3 rounded-full {{ $bgColor }}"></div>
                <flux:text size="sm">{{ $item['name'] }} ({{ $item['value'] }})</flux:text>
            </div>
        @endforeach
    </div>
@else
    <div class="flex items-center justify-center h-64">
        <flux:text class="text-gray-500">No data available</flux:text>
    </div>
@endif