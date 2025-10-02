<div class="min-h-screen px-4 sm:px-6 lg:px-8 py-6 max-w-[1920px] mx-auto">
    
    <!-- Dashboard Header -->
    <flux:tab.group wire:model.live="view">
        <div class="flex flex-wrap items-center gap-4 justify-between mb-6">
            <flux:tabs variant="segmented" class="flex flex-nowrap shrink-0">
                <flux:tab name="executive" icon="chart-bar">Executive</flux:tab>
                <flux:tab name="operations" icon="cog-6-tooth">Operations</flux:tab>
                <flux:tab name="financial" icon="currency-dollar">Financial</flux:tab>
                <flux:tab name="support" icon="chat-bubble-oval-left">Support</flux:tab>
            </flux:tabs>

            <div class="flex-1 flex justify-center">
                <flux:heading size="2xl" class="flex items-center gap-3 text-center">
                    @switch($view)
                        @case('executive')
                            <flux:icon.chart-bar class="size-8 text-blue-500" />
                            Executive Dashboard
                            @break
                        @case('operations')
                            <flux:icon.cog-6-tooth class="size-8 text-green-500" />
                            Operations Dashboard
                            @break
                        @case('financial')
                            <flux:icon.currency-dollar class="size-8 text-purple-500" />
                            Financial Dashboard
                            @break
                        @case('support')
                            <flux:icon.chat-bubble-oval-left class="size-8 text-orange-500" />
                            Support Dashboard
                            @break
                    @endswitch
                </flux:heading>
            </div>

            <div class="flex flex-col items-end gap-2">
                <div class="flex items-center gap-3">
                    @if($view === 'executive')
                        <div class="flex items-center gap-2 text-xs shrink-0">
                            @foreach(['month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year', 'all' => 'All'] as $periodValue => $periodLabel)
                                <flux:button
                                    size="xs"
                                    variant="{{ $kpiPeriod === $periodValue ? 'primary' : 'ghost' }}"
                                    wire:click="setKpiPeriod('{{ $periodValue }}')">
                                    {{ $periodLabel }}
                                </flux:button>
                            @endforeach
                        </div>
                    @endif

                    <flux:dropdown position="bottom" align="end" class="shrink-0">
                        <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                        
                        <flux:menu>
                            <flux:menu.item icon="arrow-path" wire:click="loadDashboardData">
                                Refresh Data
                            </flux:menu.item>
                            
                            <flux:menu.separator />
                            
                            <flux:menu.item icon="cog-6-tooth" @click="$wire.dispatch('open-settings')">
                                Dashboard Settings
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                <flux:text class="text-sm text-zinc-500 whitespace-nowrap">
                    Last updated {{ now()->format('g:i:s A') }}
                </flux:text>
            </div>
        </div>

        <!-- Executive Panel -->
        <flux:tab.panel name="executive">
            <div class="grid grid-cols-12 gap-6">
                @foreach($allWidgetConfigs['executive'] ?? [] as $widget)
                    <div class="
                        @if($widget['size'] === 'full') col-span-12
                        @elseif($widget['size'] === 'half') col-span-12 lg:col-span-6
                        @elseif($widget['size'] === 'third') col-span-12 md:col-span-6 lg:col-span-4
                        @elseif($widget['size'] === 'quarter') col-span-12 md:col-span-6 lg:col-span-3
                        @else col-span-12 @endif">
                        
                        @switch($widget['type'])
                            @case('kpi-grid')
                                {{-- KPI Grid loads immediately as it's above the fold --}}
                                <livewire:dashboard.widgets.kpi-grid 
                                    :period="$kpiPeriod"
                                    wire:key="kpi-grid-exec" />
                                @break
                            @case('revenue-chart')
                                {{-- Charts load when visible in viewport --}}
                                <livewire:dashboard.widgets.revenue-chart 
                                    wire:key="revenue-chart-exec" />
                                @break
                            @case('ticket-chart')
                                <livewire:dashboard.widgets.ticket-chart 
                                    wire:key="ticket-chart-exec" />
                                @break
                            @case('client-health')
                                <livewire:dashboard.widgets.client-health 
                                    wire:key="client-health-exec" />
                                @break
                            @case('team-performance')
                                <livewire:dashboard.widgets.team-performance 
                                    wire:key="team-performance-exec" />
                                @break
                            @case('alert-panel')
                                {{-- Alert panel loads immediately for critical notifications --}}
                                <livewire:dashboard.widgets.alert-panel 
                                    :lazy="false"
                                    wire:key="alert-panel-exec" />
                                @break
                            @case('activity-feed')
                                {{-- Activity feed loads after page ready --}}
                                <livewire:dashboard.widgets.activity-feed 
                                    wire:key="activity-feed-exec" />
                                @break
                            @case('quick-actions')
                                <livewire:dashboard.widgets.quick-actions 
                                    view="executive" 
                                    lazy="on-load"
                                    wire:key="quick-actions-exec" />
                                @break
                            @default
                                <flux:card>
                                    <div class="p-4 text-center">
                                        <flux:text class="text-zinc-500">{{ $widget['type'] }}</flux:text>
                                    </div>
                                </flux:card>
                        @endswitch
                    </div>
                @endforeach
            </div>
        </flux:tab.panel>
        
        <!-- Operations Panel -->
        <flux:tab.panel name="operations">
            <div class="grid grid-cols-12 gap-6">
                @foreach($allWidgetConfigs['operations'] ?? [] as $widget)
                    <div class="
                        @if($widget['size'] === 'full') col-span-12
                        @elseif($widget['size'] === 'half') col-span-12 lg:col-span-6
                        @elseif($widget['size'] === 'third') col-span-12 md:col-span-6 lg:col-span-4
                        @elseif($widget['size'] === 'quarter') col-span-12 md:col-span-6 lg:col-span-3
                        @else col-span-12 @endif">
                        
                        @switch($widget['type'])
                            @case('ticket-queue')
                                <livewire:dashboard.widgets.ticket-queue 
                                    :lazy="false"
                                    wire:key="ticket-queue-ops" />
                                @break
                            @case('sla-monitor')
                                <livewire:dashboard.widgets.sla-monitor 
                                    lazy
                                    wire:key="sla-monitor-ops" />
                                @break
                            @case('team-activity')
                                <livewire:dashboard.widgets.team-activity 
                                    lazy
                                    wire:key="team-activity-ops" />
                                @break
                            @case('tech-workload')
                                <livewire:dashboard.widgets.tech-workload 
                                    lazy
                                    wire:key="tech-workload-ops" />
                                @break
                            @case('resource-allocation')
                                <livewire:dashboard.widgets.resource-allocation 
                                    lazy
                                    wire:key="resource-allocation-ops" />
                                @break
                            @case('ticket-chart')
                                <livewire:dashboard.widgets.ticket-chart 
                                     
                                    wire:key="ticket-chart-ops" />
                                @break
                            @case('response-times')
                                <livewire:dashboard.widgets.response-times 
                                    lazy
                                    wire:key="response-times-ops" />
                                @break
                            @case('activity-feed')
                                <livewire:dashboard.widgets.activity-feed 
                                     
                                    lazy="on-load"
                                    wire:key="activity-feed-ops" />
                                @break
                            @default
                                <flux:card>
                                    <div class="p-4 text-center">
                                        <flux:text class="text-zinc-500">{{ $widget['type'] }}</flux:text>
                                    </div>
                                </flux:card>
                        @endswitch
                    </div>
                @endforeach
            </div>
        </flux:tab.panel>
        
        <!-- Financial Panel -->
        <flux:tab.panel name="financial">
            <div class="grid grid-cols-12 gap-6">
                @foreach($allWidgetConfigs['financial'] ?? [] as $widget)
                    <div class="
                        @if($widget['size'] === 'full') col-span-12
                        @elseif($widget['size'] === 'half') col-span-12 lg:col-span-6
                        @elseif($widget['size'] === 'third') col-span-12 md:col-span-6 lg:col-span-4
                        @elseif($widget['size'] === 'quarter') col-span-12 md:col-span-6 lg:col-span-3
                        @else col-span-12 @endif">
                        
                        @switch($widget['type'])
                            @case('financial-kpis')
                                <livewire:dashboard.widgets.financial-kpis  wire:key="financial-kpis-fin" />
                                @break
                            @case('revenue-chart')
                                <livewire:dashboard.widgets.revenue-chart  wire:key="revenue-chart-fin" />
                                @break
                            @case('invoice-status')
                                <livewire:dashboard.widgets.invoice-status  wire:key="invoice-status-fin" />
                                @break
                            @case('payment-tracking')
                                <livewire:dashboard.widgets.payment-tracking  wire:key="payment-tracking-fin" />
                                @break
                            @case('collection-metrics')
                                <livewire:dashboard.widgets.collection-metrics wire:key="collection-metrics-fin" />
                                @break
                            @case('overdue-invoices')
                                <livewire:dashboard.widgets.overdue-invoices  wire:key="overdue-invoices-fin" />
                                @break
                            @default
                                <flux:card>
                                    <div class="p-4 text-center">
                                        <flux:text class="text-zinc-500">{{ $widget['type'] }}</flux:text>
                                    </div>
                                </flux:card>
                        @endswitch
                    </div>
                @endforeach
            </div>
        </flux:tab.panel>
        
        <!-- Support Panel -->
        <flux:tab.panel name="support">
            <div class="grid grid-cols-12 gap-6">
                @foreach($allWidgetConfigs['support'] ?? [] as $widget)
                    <div class="
                        @if($widget['size'] === 'full') col-span-12
                        @elseif($widget['size'] === 'half') col-span-12 lg:col-span-6
                        @elseif($widget['size'] === 'third') col-span-12 md:col-span-6 lg:col-span-4
                        @elseif($widget['size'] === 'quarter') col-span-12 md:col-span-6 lg:col-span-3
                        @else col-span-12 @endif">
                        
                        @switch($widget['type'])
                            @case('my-tickets')
                                <livewire:dashboard.widgets.my-tickets wire:key="my-tickets-sup" />
                                @break
                            @case('ticket-chart')
                                <livewire:dashboard.widgets.ticket-chart  wire:key="ticket-chart-sup" />
                                @break
                            @case('knowledge-base')
                                <livewire:dashboard.widgets.knowledge-base wire:key="knowledge-base-sup" />
                                @break
                            @case('customer-satisfaction')
                                <livewire:dashboard.widgets.customer-satisfaction wire:key="customer-satisfaction-sup" />
                                @break
                            @case('recent-solutions')
                                <livewire:dashboard.widgets.recent-solutions wire:key="recent-solutions-sup" />
                                @break
                            @default
                                <flux:card>
                                    <div class="p-4 text-center">
                                        <flux:text class="text-zinc-500">{{ $widget['type'] }}</flux:text>
                                    </div>
                                </flux:card>
                        @endswitch
                    </div>
                @endforeach
            </div>
        </flux:tab.panel>
    </flux:tab.group>
    
    <!-- Loading overlay -->
    <div wire:loading.delay.long class="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
        <flux:card class="p-6">
            <div class="flex items-center gap-3">
                <flux:icon.arrow-path class="size-6 animate-spin text-blue-500" />
                <flux:text>Loading dashboard data...</flux:text>
            </div>
        </flux:card>
    </div>
</div>
