<div class="h-full">
    <flux:card class="h-full">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.document-text class="size-5 text-blue-500" />
                Invoice Status
            </flux:heading>
        </div>
        
        <div class="p-6">
            @if($loading)
                <div class="flex items-center justify-center h-64">
                    <flux:icon.arrow-path class="size-8 animate-spin text-zinc-400" />
                </div>
            @else
                <!-- Status Summary -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @foreach($statusData as $status => $data)
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ ucfirst($status) }}
                                    </flux:text>
                                    
                                    <div class="mt-1 flex items-baseline">
                                        <flux:heading size="lg" class="font-semibold">
                                            {{ $data['count'] }}
                                        </flux:heading>
                                        <flux:text class="ml-2 text-sm text-zinc-500">
                                            ${{ number_format($data['total'], 2) }}
                                        </flux:text>
                                    </div>
                                </div>
                                
                                <div class="ml-3">
                                    @switch($status)
                                        @case('draft')
                                            <flux:icon.document class="size-5 text-zinc-400" />
                                            @break
                                        @case('sent')
                                            <flux:icon.paper-airplane class="size-5 text-blue-500" />
                                            @break
                                        @case('viewed')
                                            <flux:icon.eye class="size-5 text-indigo-500" />
                                            @break
                                        @case('partial')
                                            <flux:icon.clock class="size-5 text-yellow-500" />
                                            @break
                                        @case('paid')
                                            <flux:icon.check-circle class="size-5 text-green-500" />
                                            @break
                                        @case('overdue')
                                            <flux:icon.exclamation-triangle class="size-5 text-red-500" />
                                            @break
                                        @default
                                            <flux:icon.document-text class="size-5 text-zinc-400" />
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Aging Report -->
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6">
                    <flux:heading size="sm" class="mb-4 text-zinc-700 dark:text-zinc-300">
                        Aging Report - Outstanding Balances
                    </flux:heading>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                        @foreach($agingData as $period => $balance)
                            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @switch($period)
                                        @case('current')
                                            Current
                                            @break
                                        @case('1-30')
                                            1-30 Days
                                            @break
                                        @case('31-60')
                                            31-60 Days
                                            @break
                                        @case('61-90')
                                            61-90 Days
                                            @break
                                        @case('90+')
                                            90+ Days
                                            @break
                                    @endswitch
                                </flux:text>
                                
                                <div class="mt-1">
                                    <flux:heading size="lg" class="font-semibold 
                                        @if($period === '90+' && $balance > 0)
                                            text-red-600 dark:text-red-400
                                        @elseif($period === '61-90' && $balance > 0)
                                            text-orange-600 dark:text-orange-400
                                        @elseif($period === '31-60' && $balance > 0)
                                            text-yellow-600 dark:text-yellow-400
                                        @else
                                            text-zinc-900 dark:text-zinc-100
                                        @endif">
                                        ${{ number_format($balance, 2) }}
                                    </flux:heading>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Summary -->
                <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <flux:text class="text-xs text-zinc-500">Total Outstanding</flux:text>
                            <flux:text class="font-medium">
                                ${{ number_format(array_sum($agingData), 2) }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Total Invoices</flux:text>
                            <flux:text class="font-medium">
                                {{ array_sum(array_column($statusData, 'count')) }}
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Last Updated</flux:text>
                            <flux:text class="font-medium">{{ now()->format('g:i A') }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500">Currency</flux:text>
                            <flux:text class="font-medium">USD</flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </flux:card>
</div>
