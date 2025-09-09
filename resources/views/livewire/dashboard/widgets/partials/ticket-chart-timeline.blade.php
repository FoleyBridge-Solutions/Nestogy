@if($chartData && !empty($chartData) && count($chartData) > 0 && !$loading)
    <!-- Timeline Line Chart -->
    <div class="mt-6">
        <flux:chart class="h-64" wire:model.live="chartData">
            <flux:chart.viewport>
                <flux:chart.svg>
                    <!-- Created Line -->
                    <flux:chart.line
                        field="created"
                        class="text-blue-500 dark:text-blue-400"
                        stroke-width="2"
                    />

                    <!-- Resolved Line -->
                    <flux:chart.line
                        field="resolved"
                        class="text-green-500 dark:text-green-400"
                        stroke-width="2"
                    />

                    <!-- Created Area -->
                    <flux:chart.area
                        field="created"
                        class="text-blue-200/20 dark:text-blue-400/10"
                    />

                    <!-- Resolved Area -->
                    <flux:chart.area
                        field="resolved"
                        class="text-green-200/20 dark:text-green-400/10"
                    />

                    <!-- Points -->
                    <flux:chart.point field="created" class="text-blue-500" r="3" />
                    <flux:chart.point field="resolved" class="text-green-500" r="3" />

                    <!-- X Axis -->
                    <flux:chart.axis
                        axis="x"
                        field="date"
                        format="{{ $period === 'week' ?
                            json_encode(['weekday' => 'short', 'month' => 'short', 'day' => 'numeric']) :
                            json_encode(['month' => 'short', 'day' => 'numeric']) }}"
                    >
                        <flux:chart.axis.line class="text-zinc-200 dark:text-zinc-700" />
                        <flux:chart.axis.tick class="text-xs" />
                    </flux:chart.axis>

                    <!-- Y Axis -->
                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid class="text-zinc-100 dark:text-zinc-800" />
                        <flux:chart.axis.tick class="text-xs" />
                    </flux:chart.axis>

                    <!-- Cursor for interaction -->
                    <flux:chart.cursor class="text-zinc-400" stroke-dasharray="3,3" />
                </flux:chart.svg>
            </flux:chart.viewport>

            <!-- Tooltip -->
            <flux:chart.tooltip>
                <flux:chart.tooltip.heading
                    field="date"
                    format="{{ json_encode(['year' => 'numeric', 'month' => 'long', 'day' => 'numeric']) }}"
                />
                <flux:chart.tooltip.value field="created" label="Created" />
                <flux:chart.tooltip.value field="resolved" label="Resolved" />
            </flux:chart.tooltip>
        </flux:chart>

        <!-- Legend -->
        <div class="flex justify-center gap-4 mt-4">
            <flux:chart.legend label="Created">
                <flux:chart.legend.indicator class="bg-blue-500" />
            </flux:chart.legend>
            <flux:chart.legend label="Resolved">
                <flux:chart.legend.indicator class="bg-green-500" />
            </flux:chart.legend>
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
                    Ticket timeline will appear here once tickets are created
                </flux:text>
            @endif
        </div>
    </div>
@endif
