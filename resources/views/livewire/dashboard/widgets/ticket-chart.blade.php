<flux:card class="h-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg" class="flex items-center gap-2">
                <flux:icon.ticket class="size-5 text-orange-500" />
                Ticket Analytics
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500 mt-1">
                Track ticket metrics and performance
            </flux:text>
        </div>
        
        <!-- Timeline Period Selector (only shown for timeline view) -->
        <div class="flex items-center gap-2">
            @if($view === 'timeline')
                <flux:select wire:model.live="period" size="sm">
                    <flux:select.option value="week">7 Days</flux:select.option>
                    <flux:select.option value="month">30 Days</flux:select.option>
                    <flux:select.option value="quarter">90 Days</flux:select.option>
                </flux:select>
            @endif
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-4 gap-3 mb-6">
        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
            <flux:text size="xs" class="text-blue-600 dark:text-blue-400">Open</flux:text>
            <flux:heading size="lg" class="mt-1 text-blue-600 dark:text-blue-400">
                {{ $stats['open'] ?? 0 }}
            </flux:heading>
        </div>
        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg">
            <flux:text size="xs" class="text-green-600 dark:text-green-400">Resolved</flux:text>
            <flux:heading size="lg" class="mt-1 text-green-600 dark:text-green-400">
                {{ $stats['resolved'] ?? 0 }}
            </flux:heading>
        </div>
        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
            <flux:text size="xs" class="text-amber-600 dark:text-amber-400">In Progress</flux:text>
            <flux:heading size="lg" class="mt-1 text-amber-600 dark:text-amber-400">
                {{ $stats['in_progress'] ?? 0 }}
            </flux:heading>
        </div>
        <div class="p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg">
            <flux:text size="xs" class="text-orange-600 dark:text-orange-400">Today</flux:text>
            <flux:heading size="lg" class="mt-1 text-orange-600 dark:text-orange-400">
                {{ $stats['today'] ?? 0 }}
            </flux:heading>
        </div>
    </div>
    
    <!-- Chart with Tab Panels -->
    <flux:tab.group>
        <flux:tabs wire:model.live="view" variant="segmented" size="sm">
            <flux:tab name="status" icon="chart-bar">Status</flux:tab>
            <flux:tab name="priority" icon="exclamation-circle">Priority</flux:tab>
            <flux:tab name="category" icon="tag">Category</flux:tab>
            <flux:tab name="timeline" icon="chart-bar">Timeline</flux:tab>
        </flux:tabs>
        
        <!-- Status Panel -->
        <flux:tab.panel name="status">
            @if($view === 'status')
                @include('livewire.dashboard.widgets.partials.ticket-chart-simple')
            @endif
        </flux:tab.panel>
        
        <!-- Priority Panel -->
        <flux:tab.panel name="priority">
            @if($view === 'priority')
                @include('livewire.dashboard.widgets.partials.ticket-chart-simple')
            @endif
        </flux:tab.panel>
        
        <!-- Category Panel -->
        <flux:tab.panel name="category">
            @if($view === 'category')
                @include('livewire.dashboard.widgets.partials.ticket-chart-simple')
            @endif
        </flux:tab.panel>
        
        <!-- Timeline Panel -->
        <flux:tab.panel name="timeline">
            @if($view === 'timeline')
                @include('livewire.dashboard.widgets.partials.ticket-chart-timeline-simple')
            @endif
        </flux:tab.panel>
    </flux:tab.group>
    
    <!-- Loading Overlay -->
    <div wire:loading wire:target="loadChartData,setView,setPeriod" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg">
        <div class="flex items-center gap-2 bg-white dark:bg-zinc-800 px-4 py-2 rounded-lg shadow-lg">
            <flux:icon.arrow-path class="size-5 animate-spin text-orange-500" />
            <flux:text>Updating chart...</flux:text>
        </div>
    </div>
</flux:card>
