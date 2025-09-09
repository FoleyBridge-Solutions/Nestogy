@if($chartData && !empty($chartData))
    <!-- Pie/Donut Chart for Status, Priority, Category -->
    <div class="aspect-square max-h-96 mx-auto mt-6">
        <flux:chart class="w-full h-full">
            <flux:chart.viewport>
                <flux:chart.svg viewBox="0 0 200 200">
                    @php
                        $total = collect($chartData)->sum('value');
                        $startAngle = -90;
                        $centerX = 100;
                        $centerY = 100;
                        $outerRadius = 80;
                        $innerRadius = 50;
                    @endphp
                    
                    @foreach($chartData as $segment)
                        @php
                            $percentage = $total > 0 ? ($segment['value'] / $total) : 0;
                            $angle = $percentage * 360;
                            $endAngle = $startAngle + $angle;
                            
                            $startRad = deg2rad($startAngle);
                            $endRad = deg2rad($endAngle);
                            
                            $x1 = $centerX + $outerRadius * cos($startRad);
                            $y1 = $centerY + $outerRadius * sin($startRad);
                            $x2 = $centerX + $outerRadius * cos($endRad);
                            $y2 = $centerY + $outerRadius * sin($endRad);
                            
                            $x3 = $centerX + $innerRadius * cos($startRad);
                            $y3 = $centerY + $innerRadius * sin($startRad);
                            $x4 = $centerX + $innerRadius * cos($endRad);
                            $y4 = $centerY + $innerRadius * sin($endRad);
                            
                            $largeArc = $angle > 180 ? 1 : 0;
                            
                            $colorMap = [
                                'red' => 'fill-red-500',
                                'orange' => 'fill-orange-500',
                                'green' => 'fill-green-500',
                                'blue' => 'fill-blue-500',
                                'purple' => 'fill-purple-500',
                                'amber' => 'fill-amber-500',
                                'pink' => 'fill-pink-500',
                                'indigo' => 'fill-indigo-500',
                            ];
                            
                            $color = $colorMap[$segment['color']] ?? 'fill-zinc-400';
                            $startAngle = $endAngle; // For next iteration
                        @endphp
                        
                        @if($percentage > 0)
                            <path d="M {{ $x1 }} {{ $y1 }} A {{ $outerRadius }} {{ $outerRadius }} 0 {{ $largeArc }} 1 {{ $x2 }} {{ $y2 }} L {{ $x4 }} {{ $y4 }} A {{ $innerRadius }} {{ $innerRadius }} 0 {{ $largeArc }} 0 {{ $x3 }} {{ $y3 }} Z" 
                                  class="{{ $color }} hover:opacity-80 transition-opacity cursor-pointer" />
                        @endif
                    @endforeach
                    
                    <!-- Center text showing total -->
                    <text x="{{ $centerX }}" y="{{ $centerY - 8 }}" text-anchor="middle" class="text-2xl font-bold fill-zinc-700 dark:fill-zinc-300">
                        {{ $total }}
                    </text>
                    <text x="{{ $centerX }}" y="{{ $centerY + 12 }}" text-anchor="middle" class="text-sm fill-zinc-500">
                        Total
                    </text>
                </flux:chart.svg>
            </flux:chart.viewport>
        </flux:chart>
        
        <!-- Legend -->
        <div class="grid grid-cols-2 gap-2 mt-6">
            @foreach($chartData as $item)
                <div class="flex items-center gap-2">
                    @php
                        $colorMap = [
                            'red' => 'bg-red-500',
                            'orange' => 'bg-orange-500',
                            'green' => 'bg-green-500',
                            'blue' => 'bg-blue-500',
                            'purple' => 'bg-purple-500',
                            'amber' => 'bg-amber-500',
                            'pink' => 'bg-pink-500',
                            'indigo' => 'bg-indigo-500',
                        ];
                        $bgColor = $colorMap[$item['color']] ?? 'bg-zinc-400';
                    @endphp
                    <div class="w-3 h-3 rounded {{ $bgColor }}"></div>
                    <flux:text size="sm">{{ $item['name'] ?? $item['label'] ?? 'Unknown' }} ({{ $item['value'] }})</flux:text>
                </div>
            @endforeach
        </div>
    </div>
@else
    <!-- Empty State -->
    <div class="flex items-center justify-center h-64 mt-6">
        <div class="text-center">
            @if($loading)
                <flux:icon.arrow-path class="size-12 animate-spin text-zinc-400 mx-auto mb-3" />
                <flux:text>Loading ticket data...</flux:text>
            @else
                <flux:icon.ticket class="size-12 text-zinc-300 mx-auto mb-3" />
                <flux:heading size="lg">No Ticket Data</flux:heading>
                <flux:text class="mt-2 text-zinc-500">
                    Ticket metrics will appear here once tickets are created
                </flux:text>
            @endif
        </div>
    </div>
@endif
