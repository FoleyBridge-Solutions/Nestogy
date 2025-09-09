<div class="h-full">
    <flux:card class="h-full">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.face-smile class="size-5 text-green-500" />
                Customer Satisfaction
            </flux:heading>
        </div>
        
        <div class="p-6">
            @if($loading)
                <div class="flex items-center justify-center h-64">
                    <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400" />
                </div>
            @else
                @if(empty($data['items']))
                    <div class="flex flex-col-span-12 items-center justify-center h-64 text-center">
                        <flux:icon.face-smile class="size-16 text-zinc-300 mb-4" />
                        <flux:heading size="lg" class="text-zinc-500 mb-2">No Survey Data</flux:heading>
                        <flux:text class="text-zinc-400">Customer satisfaction surveys will appear here once collected.</flux:text>
                    </div>
                @else
                    <!-- Satisfaction Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        @foreach($data['stats'] as $stat)
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $stat['label'] }}
                                        </flux:text>
                                        
                                        <div class="mt-1">
                                            <flux:heading size="lg" class="font-semibold">
                                                @if($stat['type'] === 'rating')
                                                    {{ number_format($stat['value'], 1) }}/5
                                                @elseif($stat['type'] === 'percentage')
                                                    {{ number_format($stat['value'], 1) }}%
                                                @else
                                                    {{ number_format($stat['value']) }}
                                                @endif
                                            </flux:heading>
                                        </div>
                                        
                                        @if(isset($stat['trend']))
                                            <div class="mt-1 text-xs">
                                                @if($stat['trend'] > 0)
                                                    <span class="text-green-600">+{{ $stat['trend'] }}%</span>
                                                @elseif($stat['trend'] < 0)
                                                    <span class="text-red-600">{{ $stat['trend'] }}%</span>
                                                @else
                                                    <span class="text-zinc-500">No change</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="ml-3">
                                        @switch($stat['icon'] ?? 'face-smile')
                                            @case('face-smile')
                                                <flux:icon.face-smile class="size-5 text-green-500" />
                                                @break
                                            @case('star')
                                                <flux:icon.star class="size-5 text-yellow-500" />
                                                @break
                                            @case('chart-bar')
                                                <flux:icon.chart-bar class="size-5 text-blue-500" />
                                                @break
                                            @case('users')
                                                <flux:icon.users class="size-5 text-purple-500" />
                                                @break
                                            @default
                                                <flux:icon.face-smile class="size-5 text-zinc-400" />
                                        @endswitch
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Recent Feedback -->
                    <div class="space-y-3">
                        @foreach($data['items'] as $item)
                            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <flux:text class="font-medium">{{ $item['client_name'] }}</flux:text>
                                            
                                            <div class="flex">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <flux:icon.star class="size-4 {{ $i <= $item['rating'] ? 'text-yellow-500' : 'text-zinc-300' }}" />
                                                @endfor
                                            </div>
                                            
                                            @if($item['rating'] >= 4)
                                                <flux:badge color="green" size="sm">Satisfied</flux:badge>
                                            @elseif($item['rating'] >= 3)
                                                <flux:badge color="yellow" size="sm">Neutral</flux:badge>
                                            @else
                                                <flux:badge color="red" size="sm">Unsatisfied</flux:badge>
                                            @endif
                                        </div>
                                        
                                        @if($item['comment'])
                                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                                                "{{ Str::limit($item['comment'], 150) }}"
                                            </flux:text>
                                        @endif
                                        
                                        <div class="mt-2 flex items-center gap-4">
                                            <flux:text class="text-sm text-zinc-500">
                                                Ticket #{{ $item['ticket_number'] }}
                                            </flux:text>
                                            <flux:text class="text-sm text-zinc-500">
                                                {{ $item['date'] }}
                                            </flux:text>
                                        </div>
                                    </div>
                                    
                                    <div class="text-right">
                                        <div class="flex items-center gap-1">
                                            @if($item['rating'] >= 4)
                                                <flux:icon.face-smile class="size-5 text-green-500" />
                                            @elseif($item['rating'] >= 3)
                                                <flux:icon.face-frown class="size-5 text-yellow-500" />
                                            @else
                                                <flux:icon.face-frown class="size-5 text-red-500" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Summary -->
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <flux:text class="text-xs text-zinc-500">Overall Rating</flux:text>
                            <flux:text class="font-medium">0.0/5</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Total Responses</flux:text>
                            <flux:text class="font-medium">0</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Response Rate</flux:text>
                            <flux:text class="font-medium">0%</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Last Updated</flux:text>
                            <flux:text class="font-medium">{{ now()->format('g:i A') }}</flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:card>
</div>
