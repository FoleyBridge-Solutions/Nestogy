@php
    $timelineData = $this->timelineData;
    $resources = $timelineData['resources'] ?? [];
    $timeAxis = $timelineData['timeAxis'] ?? [];
    $bounds = $timelineData['bounds'] ?? [];
@endphp

<div class="resource-timeline h-full flex flex-col overflow-hidden">
    <!-- Time Axis Header -->
    <div class="resource-header bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10">
        <div class="flex">
            <!-- Resource Column Header -->
            <div class="w-80 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 p-4">
                <flux:text class="font-medium">Team Members</flux:text>
            </div>
            
            <!-- Time Axis -->
            <div class="flex-1 overflow-x-auto">
                <div class="flex min-w-max">
                    @foreach($timeAxis as $timeSlot)
                        <div class="resource-time-slot flex-shrink-0 p-4 text-center border-r border-zinc-200 dark:border-zinc-700
                                   {{ $timeSlot['isToday'] ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 font-semibold' : '' }}
                                   {{ $timeSlot['isWeekend'] ? 'bg-zinc-100 dark:bg-zinc-700' : '' }}"
                             style="min-width: {{ $zoomLevel === 'day' ? '80px' : ($zoomLevel === 'week' ? '120px' : '150px') }}">
                            <flux:text size="xs">{{ $timeSlot['label'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Body -->
    <div class="resource-body flex-1 overflow-auto">
        @if(empty($resources))
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <flux:icon name="users" size="lg" class="text-zinc-400 mx-auto mb-4" />
                    <flux:heading size="lg" class="text-zinc-600 dark:text-zinc-400">No team resources</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-zinc-500 mt-2">
                        Assign tasks to team members to see resource allocation.
                    </flux:text>
                </div>
            </div>
        @else
            <div class="flex">
                <!-- Resource Column -->
                <div class="w-80 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700">
                    @foreach($resources as $resource)
                        <div class="resource-row p-4 border-b border-zinc-200 dark:border-zinc-700 
                                   {{ $selectedItemId === 'resource_'.$resource['id'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                             wire:click="selectItem('resource_{{ $resource['id'] }}')"
                             style="height: 100px">
                            
                            <div class="flex items-center gap-3">
                                <!-- Avatar -->
                                @if($resource['avatar'])
                                    <img src="{{ $resource['avatar'] }}" 
                                         alt="{{ $resource['name'] }}" 
                                         class="w-10 h-10 rounded-full">
                                @else
                                    <flux:avatar size="md">{{ substr($resource['name'], 0, 1) }}</flux:avatar>
                                @endif

                                <!-- Resource Info -->
                                <div class="flex-1 min-w-0">
                                    <flux:text class="font-medium">{{ $resource['name'] }}</flux:text>
                                    <div class="flex items-center gap-2 mt-1">
                                        <flux:badge size="sm">{{ count($resource['tasks']) }} tasks</flux:badge>
                                        @if($resource['workload'] > 0)
                                            <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                                {{ $resource['workload'] }} days
                                            </flux:text>
                                        @endif
                                    </div>
                                    
                                    <!-- Workload Bar -->
                                    @if($resource['workload'] > 0)
                                        @php
                                            $maxWorkload = collect($resources)->max('workload');
                                            $workloadPercentage = $maxWorkload > 0 ? ($resource['workload'] / $maxWorkload) * 100 : 0;
                                            $overloadThreshold = 80; // Consider 80%+ as overload
                                        @endphp
                                        <div class="mt-2">
                                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-300
                                                           {{ $workloadPercentage > $overloadThreshold ? 'bg-red-500' : 'bg-blue-500' }}" 
                                                     style="width: {{ min($workloadPercentage, 100) }}%"></div>
                                            </div>
                                            <flux:text size="xs" class="text-zinc-500 mt-1">
                                                Workload: {{ round($workloadPercentage) }}%
                                            </flux:text>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Timeline Area -->
                <div class="flex-1 relative">
                    <!-- Today Line -->
                    @php
                        $todayPosition = 0;
                        foreach($timeAxis as $index => $timeSlot) {
                            if($timeSlot['isToday']) {
                                $slotWidth = $zoomLevel === 'day' ? 80 : ($zoomLevel === 'week' ? 120 : 150);
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

                    <!-- Resource Rows -->
                    @foreach($resources as $resourceIndex => $resource)
                        <div class="resource-timeline-row relative border-b border-zinc-200 dark:border-zinc-700" 
                             style="height: 100px">
                            
                            <!-- Grid Lines -->
                            @foreach($timeAxis as $timeSlot)
                                @php $slotWidth = $zoomLevel === 'day' ? 80 : ($zoomLevel === 'week' ? 120 : 150); @endphp
                                <div class="absolute top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700"
                                     style="left: {{ $loop->index * $slotWidth }}px">
                                </div>
                            @endforeach

                            <!-- Task Bars -->
                            @foreach($resource['tasks'] as $taskIndex => $task)
                                @if(isset($task['position']) && $task['position']['width'] > 0)
                                    @php
                                        $totalWidth = count($timeAxis) * ($zoomLevel === 'day' ? 80 : ($zoomLevel === 'week' ? 120 : 150));
                                        $left = ($task['position']['start'] / 100) * $totalWidth;
                                        $width = max(($task['position']['width'] / 100) * $totalWidth, 30);
                                        $taskHeight = 20;
                                        $top = 15 + ($taskIndex * ($taskHeight + 4)); // Stack tasks vertically within resource row
                                        
                                        // Ensure task fits within resource row
                                        if($top + $taskHeight > 90) {
                                            $top = 15 + (($taskIndex % 3) * ($taskHeight + 4)); // Wrap after 3 tasks
                                        }
                                    @endphp
                                    
                                    <div class="absolute resource-task-bar rounded cursor-pointer z-10 flex items-center
                                               {{ $selectedItemId === 'task_'.$task['id'] ? 'ring-2 ring-blue-500' : '' }}"
                                         style="left: {{ $left }}px; 
                                                width: {{ $width }}px; 
                                                top: {{ $top }}px; 
                                                height: {{ $taskHeight }}px;
                                                background-color: {{ $task['color'] }}"
                                         wire:click="selectItem('task_{{ $task['id'] }}')"
                                         title="{{ $task['name'] }} ({{ $task['progress'] }}%)">
                                        
                                        <!-- Task Content -->
                                        <div class="flex items-center h-full px-2 text-white text-xs font-medium w-full">
                                            <span class="truncate flex-1">{{ $task['name'] }}</span>
                                            
                                            <!-- Task Status Indicator -->
                                            @if($task['status'] === 'completed')
                                                <flux:icon name="check" size="xs" class="ml-1 flex-shrink-0" />
                                            @elseif($task['status'] === 'in_progress')
                                                <div class="w-2 h-2 bg-white rounded-full ml-1 flex-shrink-0 animate-pulse"></div>
                                            @endif
                                        </div>

                                        <!-- Progress Overlay -->
                                        <div class="absolute top-0 left-0 h-full bg-white/20 transition-all duration-300"
                                             style="width: {{ $task['progress'] }}%"></div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Resource Footer -->
    <div class="resource-footer bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-6">
                @if(!empty($resources))
                    @php
                        $totalTasks = collect($resources)->sum(fn($r) => count($r['tasks']));
                        $totalWorkload = collect($resources)->sum('workload');
                        $overloadedResources = collect($resources)->filter(function($r) {
                            $maxWorkload = collect($resources)->max('workload');
                            return $maxWorkload > 0 && ($r['workload'] / $maxWorkload) > 0.8;
                        })->count();
                    @endphp
                    
                    <div class="flex items-center gap-1">
                        <flux:icon name="users" size="sm" class="text-blue-500" />
                        <span class="text-zinc-600 dark:text-zinc-400">{{ count($resources) }} team members</span>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <flux:icon name="briefcase" size="sm" class="text-green-500" />
                        <span class="text-zinc-600 dark:text-zinc-400">{{ $totalTasks }} total tasks</span>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        <flux:icon name="clock" size="sm" class="text-orange-500" />
                        <span class="text-zinc-600 dark:text-zinc-400">{{ $totalWorkload }} total workload days</span>
                    </div>
                    
                    @if($overloadedResources > 0)
                        <div class="flex items-center gap-1">
                            <flux:icon name="exclamation-triangle" size="sm" class="text-red-500" />
                            <span class="text-red-600">{{ $overloadedResources }} overloaded</span>
                        </div>
                    @endif
                @endif
            </div>
            
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="ghost" wire:click="createTask" icon="plus">
                    Assign Task
                </flux:button>
            </div>
        </div>
    </div>
</div>

<style>
.resource-timeline-row {
    position: relative;
}

.resource-task-bar {
    transition: all 0.2s ease;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.resource-task-bar:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 30 !important;
}

.resource-row {
    transition: all 0.2s ease;
    cursor: pointer;
}

.resource-row:hover {
    background-color: rgba(59, 130, 246, 0.05);
}

.resource-time-slot {
    transition: all 0.2s ease;
}
</style>