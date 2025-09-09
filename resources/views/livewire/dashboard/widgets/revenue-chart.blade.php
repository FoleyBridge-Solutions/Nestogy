<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.currency-dollar class="size-5 text-green-500" />
                Revenue Analytics
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Track your revenue performance over time
            </flux:text>
        </div>
        
        <!-- Options Menu -->
        <div class="flex items-center gap-2">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                
                <flux:menu>
                    <flux:menu.checkbox wire:model="showComparison">
                        Show Previous Period
                    </flux:menu.checkbox>
                    
                    <flux:menu.separator />
                    
                    <flux:menu.item icon="arrow-down-tray" wire:click="exportChart">
                        Export Chart
                    </flux:menu.item>
                    
                    <flux:menu.item icon="arrow-path" wire:click="loadChartData">
                        Refresh Data
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>
    
    <!-- Chart with Tab Panels -->
    <flux:tab.group>
        <flux:tabs wire:model.live="period" variant="segmented" size="sm">
            <flux:tab name="month">30 Days</flux:tab>
            <flux:tab name="quarter">Quarter</flux:tab>
            <flux:tab name="year">Year</flux:tab>
        </flux:tabs>
        
        <!-- Month Panel -->
        <flux:tab.panel name="month">
            @include('livewire.dashboard.widgets.partials.revenue-chart-panel')
        </flux:tab.panel>
        
        <!-- Quarter Panel -->
        <flux:tab.panel name="quarter">
            @include('livewire.dashboard.widgets.partials.revenue-chart-panel')
        </flux:tab.panel>
        
        <!-- Year Panel -->
        <flux:tab.panel name="year">
            @include('livewire.dashboard.widgets.partials.revenue-chart-panel')
        </flux:tab.panel>
    </flux:tab.group>
    
    <!-- Loading Overlay -->
    <div wire:loading wire:target="loadChartData,setPeriod" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-blue-500" />
            <flux:text>Updating chart...</flux:text>
        </div>
    </div>
</flux:card>
