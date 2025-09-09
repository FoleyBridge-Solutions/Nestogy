<div class="h-full">
    <flux:card class="h-full">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.book-open class="size-5 text-blue-500" />
                Knowledge Base
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
                        <flux:icon.book-open class="size-16 text-zinc-300 mb-4" />
                        <flux:heading size="lg" class="text-zinc-500 mb-2">No Articles</flux:heading>
                        <flux:text class="text-zinc-400">Knowledge base articles will appear here once added.</flux:text>
                    </div>
                @else
                    <!-- Knowledge Base Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        @foreach($data['stats'] as $stat)
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $stat['label'] }}
                                        </flux:text>
                                        
                                        <div class="mt-1">
                                            <flux:heading size="lg" class="font-semibold">
                                                {{ number_format($stat['value']) }}
                                            </flux:heading>
                                        </div>
                                        
                                        @if(isset($stat['change']))
                                            <div class="mt-1 text-xs">
                                                @if($stat['change'] > 0)
                                                    <span class="text-green-600">+{{ $stat['change'] }}</span>
                                                @elseif($stat['change'] < 0)
                                                    <span class="text-red-600">{{ $stat['change'] }}</span>
                                                @else
                                                    <span class="text-zinc-500">No change</span>
                                                @endif
                                                <span class="text-zinc-500"> this week</span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="ml-3">
                                        @switch($stat['icon'] ?? 'book-open')
                                            @case('book-open')
                                                <flux:icon.book-open class="size-5 text-blue-500" />
                                                @break
                                            @case('eye')
                                                <flux:icon.eye class="size-5 text-green-500" />
                                                @break
                                            @case('heart')
                                                <flux:icon.heart class="size-5 text-red-500" />
                                                @break
                                            @case('star')
                                                <flux:icon.star class="size-5 text-yellow-500" />
                                                @break
                                            @default
                                                <flux:icon.book-open class="size-5 text-zinc-400" />
                                        @endswitch
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Knowledge Base Articles -->
                    <div class="space-y-3">
                        @foreach($data['items'] as $item)
                            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 hover:border-blue-300 dark:hover:border-blue-600 transition-colors cursor-pointer">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <flux:text class="font-medium">{{ $item['title'] }}</flux:text>
                                            
                                            @if($item['category'])
                                                <flux:badge color="blue" size="sm">{{ $item['category'] }}</flux:badge>
                                            @endif
                                            
                                            @if($item['is_popular'])
                                                <flux:icon.fire class="size-4 text-orange-500" />
                                            @endif
                                        </div>
                                        
                                        @if($item['excerpt'])
                                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                                {{ Str::limit($item['excerpt'], 120) }}
                                            </flux:text>
                                        @endif
                                        
                                        <div class="mt-2 flex items-center gap-4">
                                            <div class="flex items-center gap-1">
                                                <flux:icon.eye class="size-4 text-zinc-400" />
                                                <flux:text class="text-sm text-zinc-500">{{ $item['views'] ?? 0 }} views</flux:text>
                                            </div>
                                            
                                            @if($item['rating'])
                                                <div class="flex items-center gap-1">
                                                    <flux:icon.star class="size-4 text-yellow-500" />
                                                    <flux:text class="text-sm text-zinc-500">{{ $item['rating'] }}/5</flux:text>
                                                </div>
                                            @endif
                                            
                                            <flux:text class="text-sm text-zinc-500">
                                                Updated {{ $item['updated_at'] }}
                                            </flux:text>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4">
                                        <flux:icon.chevron-right class="size-5 text-zinc-400" />
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
                            <flux:text class="text-xs text-zinc-500">Total Articles</flux:text>
                            <flux:text class="font-medium">0</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Categories</flux:text>
                            <flux:text class="font-medium">0</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Avg Rating</flux:text>
                            <flux:text class="font-medium">0.0/5</flux:text>
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
