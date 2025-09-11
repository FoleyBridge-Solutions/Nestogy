@php
    $timelineData = $this->timelineData;
    $columns = $timelineData['columns'] ?? [];
    $timeAxis = $timelineData['timeAxis'] ?? [];
    $bounds = $timelineData['bounds'] ?? [];
@endphp

<div class="kanban-timeline h-full flex flex-col overflow-hidden">
    <!-- Time Axis Header -->
    <div class="kanban-header bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10">
        <div class="flex p-4">
            <!-- Column Headers -->
            <div class="flex gap-4 w-1/2">
                @foreach($columns as $status => $column)
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-3 h-3 rounded-full 
                                       {{ $column['color'] === 'gray' ? 'bg-gray-500' : '' }}
                                       {{ $column['color'] === 'blue' ? 'bg-blue-500' : '' }}
                                       {{ $column['color'] === 'yellow' ? 'bg-yellow-500' : '' }}
                                       {{ $column['color'] === 'green' ? 'bg-green-500' : '' }}"></div>
                            <flux:text class="font-medium">{{ $column['name'] }}</flux:text>
                            <flux:badge size="sm">{{ count($column['tasks']) }}</flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Timeline Header -->
            <div class="w-1/2 ml-4">
                <flux:text class="font-medium">Timeline</flux:text>
                <div class="flex mt-2 min-w-max">
                    @foreach($timeAxis as $timeSlot)
                        <div class="kanban-time-slot flex-shrink-0 text-center
                                   {{ $timeSlot['isToday'] ? 'text-blue-600 font-semibold' : '' }}"
                             style="min-width: {{ $zoomLevel === 'day' ? '40px' : ($zoomLevel === 'week' ? '60px' : '80px') }}">
                            <flux:text size="xs">{{ $timeSlot['label'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban Body -->
    <div class="kanban-body flex-1 overflow-auto p-4">
        <div class="flex gap-4 h-full">
            <!-- Status Columns -->
            <div class="w-1/2 flex gap-4">
                @foreach($columns as $status => $column)
                    <div class="flex-1 bg-zinc-50 dark:bg-zinc-800 rounded-lg p-3">
                        <!-- Column Tasks -->
                        <div class="space-y-3 max-h-full overflow-y-auto">
                            @forelse($column['tasks'] as $task)
                                <flux:card class="kanban-task-card cursor-pointer hover:shadow-md transition-all duration-200
                                                 {{ $selectedItemId === 'task_'.$task['id'] ? 'ring-2 ring-blue-500' : '' }}
                                                 {{ $task['overdue'] ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : '' }}"
                                          wire:click="selectItem('task_{{ $task['id'] }}')"
                                          size="sm">
                                    <!-- Task Header -->
                                    <div class="flex items-start justify-between mb-2">
                                        <flux:text class="font-medium text-sm leading-tight">{{ $task['name'] }}</flux:text>
                                        @if($task['overdue'])
                                            <flux:icon name="exclamation-triangle" size="xs" class="text-red-500 flex-shrink-0 ml-1" />
                                        @endif
                                    </div>
                                    
                                    <!-- Task Meta -->
                                    <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                                        <div class="flex items-center gap-2">
                                            <!-- Priority -->
                                            <div class="flex items-center gap-1">
                                                <div class="w-2 h-2 rounded-full
                                                           {{ $task['priority'] === 'low' ? 'bg-green-400' : '' }}
                                                           {{ $task['priority'] === 'medium' ? 'bg-yellow-400' : '' }}
                                                           {{ $task['priority'] === 'high' ? 'bg-orange-400' : '' }}
                                                           {{ $task['priority'] === 'critical' ? 'bg-red-400' : '' }}"></div>
                                                <span>{{ ucfirst($task['priority']) }}</span>
                                            </div>
                                            
                                            <!-- Due Date -->
                                            @if($task['due_date'])
                                                <span class="{{ $task['overdue'] ? 'text-red-600' : '' }}">
                                                    {{ $task['due_date'] }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <!-- Assignee -->
                                        @if($task['assignee'])
                                            <div class="flex items-center gap-1">
                                                @if($task['avatar'])
                                                    <img src="{{ $task['avatar'] }}" alt="{{ $task['assignee'] }}" 
                                                         class="w-4 h-4 rounded-full">
                                                @else
                                                    <flux:avatar size="xs">{{ substr($task['assignee'], 0, 1) }}</flux:avatar>
                                                @endif
                                                <span>{{ $task['assignee'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    @if($task['progress'] > 0)
                                        <div class="mt-2">
                                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1">
                                                <div class="bg-blue-500 h-1 rounded-full transition-all duration-300" 
                                                     style="width: {{ $task['progress'] }}%"></div>
                                            </div>
                                            <flux:text size="xs" class="text-zinc-500 mt-1">{{ $task['progress'] }}% complete</flux:text>
                                        </div>
                                    @endif
                                </flux:card>
                            @empty
                                <div class="text-center py-8">
                                    <flux:icon name="inbox" class="text-zinc-400 mx-auto mb-2" />
                                    <flux:text size="sm" class="text-zinc-500">No tasks</flux:text>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Timeline Visualization -->
            <div class="w-1/2 ml-4 relative">
                <!-- Today Line -->
                @php
                    $todayPosition = 0;
                    foreach($timeAxis as $index => $timeSlot) {
                        if($timeSlot['isToday']) {
                            $slotWidth = $zoomLevel === 'day' ? 40 : ($zoomLevel === 'week' ? 60 : 80);
                            $todayPosition = $index * $slotWidth;
                            break;
                        }
                    }
                @endphp
                @if($todayPosition > 0)
                    <div class="absolute top-0 bottom-0 w-0.5 bg-red-500 z-20" 
                         style="left: {{ $todayPosition }}px">
                        <div class="absolute -top-6 -left-6 bg-red-500 text-white text-xs px-1 rounded">
                            Today
                        </div>
                    </div>
                @endif

                <!-- Timeline Grid -->
                <div class="relative h-full bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <!-- Grid Lines -->
                    @foreach($timeAxis as $index => $timeSlot)
                        @php $slotWidth = $zoomLevel === 'day' ? 40 : ($zoomLevel === 'week' ? 60 : 80); @endphp
                        <div class="absolute top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700"
                             style="left: {{ $index * $slotWidth }}px">
                        </div>
                    @endforeach
                    
                    <!-- Timeline Bars for Tasks -->
                    @php $rowHeight = 30; $currentRow = 0; @endphp
                    @foreach($columns as $status => $column)
                        @foreach($column['tasks'] as $task)
                            @if(isset($task['timeline_position']) && $task['timeline_position']['width'] > 0)
                                @php
                                    $totalWidth = count($timeAxis) * ($zoomLevel === 'day' ? 40 : ($zoomLevel === 'week' ? 60 : 80));
                                    $left = ($task['timeline_position']['start'] / 100) * $totalWidth;
                                    $width = max(($task['timeline_position']['width'] / 100) * $totalWidth, 20);
                                    $top = $currentRow * ($rowHeight + 4) + 10;
                                @endphp
                                <div class="absolute timeline-task-bar rounded cursor-pointer z-10
                                           {{ $selectedItemId === 'task_'.$task['id'] ? 'ring-2 ring-blue-500' : '' }}"
                                     style="left: {{ $left }}px; 
                                            width: {{ $width }}px; 
                                            top: {{ $top }}px; 
                                            height: {{ $rowHeight }}px;
                                            background-color: {{ $task['color'] ?? '#6b7280' }}"
                                     wire:click="selectItem('task_{{ $task['id'] }}')"
                                     title="{{ $task['name'] }}">
                                    <div class="flex items-center h-full px-2 text-white text-xs font-medium">
                                        <span class="truncate">{{ $task['name'] }}</span>
                                    </div>
                                    <!-- Progress overlay -->
                                    <div class="absolute top-0 left-0 h-full bg-white/20 transition-all duration-300"
                                         style="width: {{ $task['progress'] }}%"></div>
                                </div>
                                @php $currentRow++; @endphp
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban Footer -->
    <div class="kanban-footer bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 p-3">
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-4">
                @php 
                    $totalTasks = collect($columns)->sum(fn($col) => count($col['tasks']));
                    $overdueCount = collect($columns)->sum(fn($col) => collect($col['tasks'])->where('overdue', true)->count());
                @endphp
                <span class="text-zinc-600 dark:text-zinc-400">{{ $totalTasks }} total tasks</span>
                @if($overdueCount > 0)
                    <span class="text-red-600">{{ $overdueCount }} overdue</span>
                @endif
            </div>
            
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="ghost" wire:click="createTask" icon="plus">
                    Add Task
                </flux:button>
            </div>
        </div>
    </div>
</div>

<style>
.kanban-task-card {
    transition: all 0.2s ease;
}

.kanban-task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.timeline-task-bar {
    transition: all 0.2s ease;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.timeline-task-bar:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.kanban-time-slot {
    border-right: 1px solid transparent;
}
</style>