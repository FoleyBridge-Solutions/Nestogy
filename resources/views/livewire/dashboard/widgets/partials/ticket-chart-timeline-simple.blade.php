@if($chartData && !empty($chartData) && isset($chartData[0]['date']))
    <div class="mt-6">
        <div class="space-y-2">
            @php
                $maxValue = max(
                    collect($chartData)->max('created'),
                    collect($chartData)->max('resolved')
                );
            @endphp
            
            @foreach($chartData as $item)
                <div class="flex items-end gap-2">
                    <flux:text size="xs" class="w-20 text-gray-500">
                        {{ \Carbon\Carbon::parse($item['date'])->format('M d') }}
                    </flux:text>
                    
                    <div class="flex-1 flex items-end gap-1 h-16">
                        @php
                            $createdHeight = $maxValue > 0 ? ($item['created'] / $maxValue) * 100 : 0;
                            $resolvedHeight = $maxValue > 0 ? ($item['resolved'] / $maxValue) * 100 : 0;
                        @endphp
                        
                        <div class="flex-1 bg-blue-200 rounded-t relative" style="height: {{ $createdHeight }}%">
                            @if($item['created'] > 0)
                                <span class="absolute -top-5 left-1/2 transform -translate-x-1/2 text-xs text-blue-600">{{ $item['created'] }}</span>
                            @endif
                        </div>
                        
                        <div class="flex-1 bg-green-200 rounded-t relative" style="height: {{ $resolvedHeight }}%">
                            @if($item['resolved'] > 0)
                                <span class="absolute -top-5 left-1/2 transform -translate-x-1/2 text-xs text-green-600">{{ $item['resolved'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Legend -->
        <div class="mt-6 flex gap-4 justify-center">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-blue-500 rounded"></div>
                <flux:text size="sm">Created</flux:text>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-green-500 rounded"></div>
                <flux:text size="sm">Resolved</flux:text>
            </div>
        </div>
    </div>
@else
    <div class="flex items-center justify-center h-64">
        <flux:text class="text-gray-500">No timeline data available</flux:text>
    </div>
@endif