@php
    $timelineData = $this->timelineData;
    $data = $timelineData['data'] ?? [];
    $timeAxis = $timelineData['timeAxis'] ?? [];
    $bounds = $timelineData['bounds'] ?? [];
@endphp

<div class="gantt-timeline h-full flex flex-col overflow-hidden">
    <!-- Time Axis Header -->
    <div class="gantt-header bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10">
        <div class="flex">
            <!-- Row Headers Column -->
            <div class="w-80 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 p-2">
                <flux:text size="sm" class="font-medium">Items</flux:text>
            </div>
            
            <!-- Time Axis -->
            <div class="flex-1 overflow-x-auto">
                <div class="flex min-w-max">
                    @foreach($timeAxis as $timeSlot)
                        <div class="gantt-time-slot flex-shrink-0 p-2 text-center border-r border-zinc-200 dark:border-zinc-700 
                                   {{ $timeSlot['isToday'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}
                                   {{ $timeSlot['isWeekend'] ? 'bg-zinc-100 dark:bg-zinc-700' : '' }}"
                             style="min-width: {{ $zoomLevel === 'day' ? '60px' : ($zoomLevel === 'week' ? '100px' : '120px') }}">
                            <flux:text size="xs" class="font-medium">{{ $timeSlot['label'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Gantt Body -->
    <div class="gantt-body flex-1 overflow-auto">
        <div class="flex">
            <!-- Row Headers -->
            <div class="w-80 flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700">
                @foreach($data as $item)
                    <div class="gantt-row-header p-3 border-b border-zinc-200 dark:border-zinc-700 
                               {{ $selectedItemId === $item['id'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                         wire:click="selectItem('{{ $item['id'] }}')"
                         style="padding-left: {{ ($item['level'] ?? 0) * 20 + 12 }}px">
                        
                        <div class="flex items-center gap-2">
                            <!-- Expandable Icon -->
                            @if($item['type'] === 'project' && isset($item['expandable']) && $item['expandable'])
                                <flux:button size="xs" variant="ghost" 
                                           wire:click.stop="expandItem('{{ $item['id'] }}')"
                                           icon="{{ in_array($item['id'], $expandedItems) ? 'chevron-down' : 'chevron-right' }}" />
                            @endif

                            <!-- Item Icon -->
                            <flux:icon name="{{ $item['type'] === 'project' ? 'chart-bar-square' : ($item['type'] === 'milestone' ? 'flag' : 'check-circle') }}" 
                                      size="sm" 
                                      class="{{ $item['type'] === 'milestone' && $item['critical'] ? 'text-red-500' : '' }}" />

                            <!-- Item Info -->
                            <div class="flex-1 min-w-0">
                                <flux:text class="font-medium truncate">{{ $item['name'] }}</flux:text>
                                @if($item['type'] === 'task' && isset($item['assignee']))
                                    <div class="flex items-center gap-1 mt-1">
                                        @if($item['assignee']['avatar'])
                                            <img src="{{ $item['assignee']['avatar'] }}" 
                                                 alt="{{ $item['assignee']['name'] }}" 
                                                 class="w-4 h-4 rounded-full">
                                        @else
                                            <flux:avatar size="xs">{{ substr($item['assignee']['name'], 0, 1) }}</flux:avatar>
                                        @endif
                                        <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400">
                                            {{ $item['assignee']['name'] }}
                                        </flux:text>
                                    </div>
                                @endif
                            </div>

                            <!-- Status Badge -->
                            @if($item['type'] !== 'milestone')
                                <flux:badge size="sm" 
                                           variant="{{ $item['status'] === 'completed' ? 'positive' : ($item['status'] === 'in_progress' ? 'primary' : 'neutral') }}">
                                    {{ $item['progress'] ?? 0 }}%
                                </flux:badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Gantt Chart Area -->
            <div class="flex-1 relative">
                <!-- Today Line -->
                @php
                    $todayPosition = 0;
                    foreach($timeAxis as $index => $timeSlot) {
                        if($timeSlot['isToday']) {
                            $todayPosition = $index * ($zoomLevel === 'day' ? 60 : ($zoomLevel === 'week' ? 100 : 120));
                            break;
                        }
                    }
                @endphp
                @if($todayPosition > 0)
                    <div class="absolute top-0 bottom-0 w-0.5 bg-red-500 z-20" 
                         style="left: {{ $todayPosition }}px">
                        <div class="absolute -top-2 -left-6 bg-red-500 text-white text-xs px-1 rounded">
                            Today
                        </div>
                    </div>
                @endif

                <!-- Gantt Bars -->
                @foreach($data as $index => $item)
                    <div class="gantt-row h-16 border-b border-zinc-200 dark:border-zinc-700 relative">
                        @if($item['type'] === 'milestone')
                            <!-- Milestone Marker -->
                            @if(isset($item['date']))
                                @php
                                    $milestonePosition = $this->calculateDatePosition($item['date'], $bounds, $timeAxis, $zoomLevel);
                                @endphp
                                <div class="absolute top-1/2 -translate-y-1/2 z-10" 
                                     style="left: {{ $milestonePosition }}px">
                                    <div class="milestone-marker w-4 h-4 rotate-45 border-2 
                                               {{ $item['critical'] ? 'bg-red-500 border-red-600' : 'bg-blue-500 border-blue-600' }}
                                               {{ $item['completed'] ? 'bg-green-500 border-green-600' : '' }}"
                                         wire:click="selectItem('{{ $item['id'] }}')"
                                         title="{{ $item['name'] }} - {{ $item['date'] }}">
                                    </div>
                                </div>
                            @endif
                        @else
                            <!-- Task/Project Bar -->
                            @if(isset($item['start']) && isset($item['end']))
                                @php
                                    $barPosition = $this->calculateBarPosition($item['start'], $item['end'], $bounds, $timeAxis, $zoomLevel);
                                @endphp
                                <div class="absolute top-2 bottom-2 timeline-gantt-bar cursor-pointer z-5
                                           {{ $selectedItemId === $item['id'] ? 'ring-2 ring-blue-500' : '' }}
                                           {{ $item['overdue'] ?? false ? 'ring-2 ring-red-500' : '' }}"
                                     style="left: {{ $barPosition['left'] }}px; 
                                            width: {{ $barPosition['width'] }}px; 
                                            background-color: {{ $item['color'] }};
                                            --progress: {{ $item['progress'] ?? 0 }}%"
                                     wire:click="selectItem('{{ $item['id'] }}')"
                                     title="{{ $item['name'] }} ({{ $item['progress'] ?? 0 }}%)">
                                    
                                    <!-- Bar Content -->
                                    <div class="flex items-center h-full px-2 text-white text-sm font-medium">
                                        <span class="truncate">{{ $item['name'] }}</span>
                                    </div>

                                    <!-- Progress Overlay -->
                                    <div class="absolute top-0 left-0 h-full bg-white/20 transition-all duration-300"
                                         style="width: {{ $item['progress'] ?? 0 }}%">
                                    </div>
                                </div>
                            @endif
                        @endif

                        <!-- Grid Lines -->
                        @foreach($timeAxis as $timeSlot)
                            <div class="absolute top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700"
                                 style="left: {{ $loop->index * ($zoomLevel === 'day' ? 60 : ($zoomLevel === 'week' ? 100 : 120)) }}px">
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <!-- Dependency Lines (SVG Overlay) -->
                <svg class="absolute inset-0 pointer-events-none z-30" style="width: 100%; height: 100%;">
                    @foreach($data as $index => $item)
                        @if(isset($item['dependencies']) && !empty($item['dependencies']))
                            @foreach($item['dependencies'] as $depId)
                                @php
                                    $depIndex = collect($data)->search(fn($d) => $d['id'] === $depId);
                                    if($depIndex !== false) {
                                        $depItem = $data[$depIndex];
                                        // Calculate dependency line coordinates
                                        $fromY = ($depIndex * 64) + 32;
                                        $toY = ($index * 64) + 32;
                                        $fromX = isset($depItem['end']) ? $this->calculateDatePosition($depItem['end'], $bounds, $timeAxis, $zoomLevel) : 0;
                                        $toX = isset($item['start']) ? $this->calculateDatePosition($item['start'], $bounds, $timeAxis, $zoomLevel) : 0;
                                    }
                                @endphp
                                @if(isset($fromX) && isset($toX))
                                    <path d="M {{ $fromX }} {{ $fromY }} L {{ $toX - 10 }} {{ $fromY }} L {{ $toX - 10 }} {{ $toY }} L {{ $toX }} {{ $toY }}"
                                          class="timeline-dependency-line"
                                          marker-end="url(#arrowhead)" />
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                    
                    <!-- Arrow marker definition -->
                    <defs>
                        <marker id="arrowhead" markerWidth="10" markerHeight="7" 
                                refX="9" refY="3.5" orient="auto">
                            <polygon points="0 0, 10 3.5, 0 7" fill="#6b7280" />
                        </marker>
                    </defs>
                </svg>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    @if(isset($timelineData['stats']))
        <div class="gantt-footer bg-zinc-50 dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center gap-6 text-sm">
                <div class="flex items-center gap-1">
                    <flux:icon name="check-circle" size="sm" class="text-green-500" />
                    <span>{{ $timelineData['stats']['completed_tasks'] }}/{{ $timelineData['stats']['total_tasks'] }} tasks completed</span>
                </div>
                @if($timelineData['stats']['overdue_tasks'] > 0)
                    <div class="flex items-center gap-1">
                        <flux:icon name="exclamation-triangle" size="sm" class="text-red-500" />
                        <span class="text-red-600">{{ $timelineData['stats']['overdue_tasks'] }} overdue</span>
                    </div>
                @endif
                <div class="flex items-center gap-1">
                    <flux:icon name="flag" size="sm" class="text-blue-500" />
                    <span>{{ $timelineData['stats']['completed_milestones'] }}/{{ $timelineData['stats']['total_milestones'] }} milestones</span>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.gantt-time-slot {
    min-width: 60px;
}

.gantt-row {
    height: 64px;
}

.timeline-gantt-bar {
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.timeline-gantt-bar:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.milestone-marker {
    cursor: pointer;
    transition: all 0.2s ease;
}

.milestone-marker:hover {
    transform: rotate(45deg) scale(1.2);
}
</style>