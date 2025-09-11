<div class="timeline-container h-full flex flex-col" 
     x-data="timelineInteractions" 
     x-init="initTimeline"
     wire:key="timeline-{{ $project->id }}">

    <!-- Timeline Header -->
    <div class="timeline-header bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between">
            <!-- Project Info -->
            <div class="flex items-center gap-4">
                <flux:heading size="lg" class="font-semibold">
                    {{ $project->name }} Timeline
                </flux:heading>
                <flux:badge :variant="$project->status === 'completed' ? 'positive' : ($project->status === 'active' ? 'primary' : 'warning')">
                    {{ ucfirst($project->status) }}
                </flux:badge>
            </div>

            <!-- Timeline Controls -->
            <div class="flex items-center gap-2">
                <!-- View Type Tabs -->
                <flux:tabs variant="segmented" size="sm">
                    <flux:tab wire:click="setViewType('gantt')" 
                             :class="$viewType === 'gantt' ? 'active' : ''"
                             icon="chart-bar-square">
                        Gantt
                    </flux:tab>
                    <flux:tab wire:click="setViewType('vertical')" 
                             :class="$viewType === 'vertical' ? 'active' : ''"
                             icon="list-bullet">
                        Timeline
                    </flux:tab>
                    <flux:tab wire:click="setViewType('kanban')" 
                             :class="$viewType === 'kanban' ? 'active' : ''"
                             icon="squares-2x2">
                        Kanban
                    </flux:tab>
                    <flux:tab wire:click="setViewType('resource')" 
                             :class="$viewType === 'resource' ? 'active' : ''"
                             icon="users">
                        Resources
                    </flux:tab>
                </flux:tabs>

                <!-- Zoom Controls -->
                @if($viewType !== 'vertical')
                <div class="flex items-center gap-1 border rounded-lg p-1">
                    <flux:button size="sm" variant="ghost" wire:click="setZoomLevel('quarter')" 
                                :class="$zoomLevel === 'quarter' ? 'bg-zinc-100 dark:bg-zinc-800' : ''">
                        Q
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="setZoomLevel('month')"
                                :class="$zoomLevel === 'month' ? 'bg-zinc-100 dark:bg-zinc-800' : ''">
                        M
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="setZoomLevel('week')"
                                :class="$zoomLevel === 'week' ? 'bg-zinc-100 dark:bg-zinc-800' : ''">
                        W
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="setZoomLevel('day')"
                                :class="$zoomLevel === 'day' ? 'bg-zinc-100 dark:bg-zinc-800' : ''">
                        D
                    </flux:button>
                </div>
                @endif

                <!-- Action Buttons -->
                <flux:button size="sm" variant="ghost" icon="funnel" wire:click="toggleFilters">
                    Filters
                    @if(array_filter($filters))
                        <flux:badge size="sm" class="ml-1">{{ count(array_filter($filters)) }}</flux:badge>
                    @endif
                </flux:button>

                <flux:dropdown>
                    <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                    <flux:menu>
                        <flux:menu.item icon="plus" wire:click="createTask">Add Task</flux:menu.item>
                        <flux:menu.item icon="flag" wire:click="createMilestone">Add Milestone</flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="eye" wire:click="toggleLegend">
                            {{ $showLegend ? 'Hide' : 'Show' }} Legend
                        </flux:menu.item>
                        <flux:menu.item icon="arrow-path" wire:click="toggleAutoRefresh">
                            {{ $autoRefresh ? 'Disable' : 'Enable' }} Auto Refresh
                        </flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item icon="photo" wire:click="exportTimeline('png')">Export as PNG</flux:menu.item>
                        <flux:menu.item icon="printer" wire:click="printTimeline">Print Timeline</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>

        <!-- Filters Panel -->
        @if($showFilters)
        <div class="timeline-filters mt-4 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Assignee Filter -->
                <div>
                    <flux:field>
                        <flux:label>Assignee</flux:label>
                        <flux:select wire:model.live="filters.assignee" placeholder="All assignees">
                            <option value="">All assignees</option>
                            @foreach($this->availableAssignees as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>

                <!-- Status Filter -->
                <div>
                    <flux:label>Status</flux:label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach(['planning', 'in_progress', 'review', 'completed'] as $status)
                            <flux:button size="sm" 
                                        variant="{{ in_array($status, $filters['status']) ? 'primary' : 'ghost' }}"
                                        wire:click="toggleStatusFilter('{{ $status }}')">
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>

                <!-- Priority Filter -->
                <div>
                    <flux:label>Priority</flux:label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @foreach(['low', 'medium', 'high', 'critical'] as $priority)
                            <flux:button size="sm" 
                                        variant="{{ in_array($priority, $filters['priority']) ? 'primary' : 'ghost' }}"
                                        wire:click="togglePriorityFilter('{{ $priority }}')">
                                {{ ucfirst($priority) }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>

                <!-- Special Filters -->
                <div>
                    <flux:label>Special</flux:label>
                    <div class="flex flex-col gap-2 mt-1">
                        <flux:checkbox wire:model.live="filters.critical_only">
                            Critical milestones only
                        </flux:checkbox>
                        <flux:button size="sm" variant="ghost" wire:click="clearFilters">
                            Clear all filters
                        </flux:button>
                    </div>
                </div>
            </div>

            <!-- Filter Stats -->
            <div class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Showing {{ $this->filterStats['total_items'] ?? 0 }} items
                @if($viewType === 'gantt' && isset($this->filterStats['overdue']) && $this->filterStats['overdue'] > 0)
                    • <span class="text-red-600">{{ $this->filterStats['overdue'] }} overdue</span>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Timeline Content -->
    <div class="timeline-content flex-1 overflow-hidden">
        @if($viewType === 'gantt')
            @include('livewire.project.timeline.gantt')
        @elseif($viewType === 'vertical')
            @include('livewire.project.timeline.vertical')
        @elseif($viewType === 'kanban')
            @include('livewire.project.timeline.kanban')
        @elseif($viewType === 'resource')
            @include('livewire.project.timeline.resource')
        @endif
    </div>

    <!-- Timeline Legend -->
    @if($showLegend)
    <div class="timeline-legend bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <!-- Status Legend -->
            <div>
                <flux:text class="font-medium mb-2">Status</flux:text>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-gray-400"></div>
                        <span>Planning</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-blue-500"></div>
                        <span>In Progress</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-green-500"></div>
                        <span>Completed</span>
                    </div>
                </div>
            </div>

            <!-- Priority Legend -->
            <div>
                <flux:text class="font-medium mb-2">Priority</flux:text>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-green-500"></div>
                        <span>Low</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-yellow-500"></div>
                        <span>Medium</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-orange-500"></div>
                        <span>High</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded bg-red-500"></div>
                        <span>Critical</span>
                    </div>
                </div>
            </div>

            <!-- Item Types -->
            <div>
                <flux:text class="font-medium mb-2">Item Types</flux:text>
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <flux:icon name="chart-bar-square" size="sm" />
                        <span>Project</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" size="sm" />
                        <span>Task</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="flag" size="sm" />
                        <span>Milestone</span>
                    </div>
                </div>
            </div>

            <!-- Keyboard Shortcuts -->
            <div>
                <flux:text class="font-medium mb-2">Shortcuts</flux:text>
                <div class="space-y-1 text-xs">
                    <div>F - Toggle filters</div>
                    <div>L - Toggle legend</div>
                    <div>T - Add task</div>
                    <div>M - Add milestone</div>
                    <div>+/- - Zoom in/out</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading.flex class="absolute inset-0 bg-white/75 dark:bg-zinc-900/75 items-center justify-center z-50">
        <div class="flex items-center gap-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <flux:text>Loading timeline...</flux:text>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('timelineInteractions', () => ({
        autoRefreshTimer: null,
        
        initTimeline() {
            this.setupKeyboardShortcuts();
            this.setupAutoRefresh();
        },
        
        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                
                switch(e.key.toLowerCase()) {
                    case 'f':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'toggle-filters');
                        break;
                    case 'l':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'toggle-legend');
                        break;
                    case 't':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'create-task');
                        break;
                    case 'm':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'create-milestone');
                        break;
                    case '=':
                    case '+':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'zoom-in');
                        break;
                    case '-':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'zoom-out');
                        break;
                    case '1':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'view-gantt');
                        break;
                    case '2':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'view-vertical');
                        break;
                    case '3':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'view-kanban');
                        break;
                    case '4':
                        e.preventDefault();
                        @this.call('handleKeyboardShortcut', 'view-resource');
                        break;
                }
            });
        },
        
        setupAutoRefresh() {
            this.$wire.on('start-auto-refresh', (interval) => {
                this.clearAutoRefresh();
                this.autoRefreshTimer = setInterval(() => {
                    @this.call('autoRefreshTimeline');
                }, interval * 1000);
            });
            
            this.$wire.on('stop-auto-refresh', () => {
                this.clearAutoRefresh();
            });
        },
        
        clearAutoRefresh() {
            if (this.autoRefreshTimer) {
                clearInterval(this.autoRefreshTimer);
                this.autoRefreshTimer = null;
            }
        },
        
        destroy() {
            this.clearAutoRefresh();
        }
    }));
});
</script>

<style>
.timeline-container {
    min-height: 600px;
}

.timeline-item {
    transition: all 0.2s ease;
}

.timeline-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.timeline-item.selected {
    ring-2 ring-blue-500;
}

.timeline-gantt-bar {
    border-radius: 4px;
    position: relative;
    overflow: hidden;
}

.timeline-gantt-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(90deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    width: var(--progress, 0%);
}

.timeline-dependency-line {
    stroke: #6b7280;
    stroke-width: 1;
    stroke-dasharray: 3,3;
    fill: none;
}

@media print {
    .timeline-header .flex > div:last-child,
    .timeline-legend {
        display: none;
    }
}
</style>