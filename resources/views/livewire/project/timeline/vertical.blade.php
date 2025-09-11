@php
    $timelineData = $this->timelineData;
    $events = $timelineData['events'] ?? [];
@endphp

<div class="vertical-timeline h-full overflow-auto p-6">
    @if(empty($events))
        <div class="flex items-center justify-center h-64">
            <div class="text-center">
                <flux:icon name="calendar-days" size="lg" class="text-zinc-400 mx-auto mb-4" />
                <flux:heading size="lg" class="text-zinc-600 dark:text-zinc-400">No timeline events</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-500 mt-2">
                    Add tasks and milestones to see them in the timeline.
                </flux:text>
            </div>
        </div>
    @else
        <div class="relative">
            <!-- Timeline Line -->
            <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-zinc-300 dark:bg-zinc-600"></div>
            
            <!-- Timeline Events -->
            <div class="space-y-6">
                @foreach($events as $event)
                    @php
                        $isToday = \Carbon\Carbon::parse($event['date'])->isToday();
                        $isPast = \Carbon\Carbon::parse($event['date'])->isPast();
                        $isOverdue = $isPast && in_array($event['type'], ['task_due', 'project_due']) && !str_contains($event['type'], 'completed');
                    @endphp
                    
                    <div class="timeline-event relative flex items-start gap-4 
                               {{ $selectedItemId === $event['id'] ? 'timeline-event-selected' : '' }}"
                         wire:click="selectItem('{{ $event['id'] }}')"
                         wire:mouseover="hoverItem('{{ $event['id'] }}')"
                         wire:mouseleave="hoverItem('')">
                        
                        <!-- Event Marker -->
                        <div class="timeline-marker relative z-10 flex-shrink-0">
                            <div class="w-6 h-6 rounded-full border-4 border-white dark:border-zinc-900 flex items-center justify-center
                                       {{ $event['color'] === 'green' ? 'bg-green-500' : '' }}
                                       {{ $event['color'] === 'blue' ? 'bg-blue-500' : '' }}
                                       {{ $event['color'] === 'red' ? 'bg-red-500' : '' }}
                                       {{ $event['color'] === 'yellow' ? 'bg-yellow-500' : '' }}
                                       {{ $event['color'] === 'orange' ? 'bg-orange-500' : '' }}
                                       {{ $event['color'] === 'purple' ? 'bg-purple-500' : '' }}
                                       {{ $event['color'] === 'gray' ? 'bg-gray-500' : '' }}
                                       {{ $isToday ? 'ring-4 ring-blue-200 dark:ring-blue-800' : '' }}">
                                <flux:icon name="{{ $event['icon'] }}" size="xs" class="text-white" />
                            </div>
                            
                            @if($isToday)
                                <div class="absolute -top-2 -right-2">
                                    <flux:badge size="sm" variant="primary">Today</flux:badge>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Event Content -->
                        <div class="timeline-content flex-1 min-w-0">
                            <flux:card class="hover:shadow-md transition-shadow duration-200 cursor-pointer
                                             {{ $isOverdue ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' : '' }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <!-- Event Header -->
                                        <div class="flex items-center gap-2 mb-1">
                                            <flux:text class="font-semibold">{{ $event['title'] }}</flux:text>
                                            @if($isOverdue)
                                                <flux:badge variant="danger" size="sm">Overdue</flux:badge>
                                            @endif
                                            @if(isset($event['completed']) && $event['completed'])
                                                <flux:badge variant="positive" size="sm">Completed</flux:badge>
                                            @endif
                                        </div>
                                        
                                        <!-- Event Description -->
                                        <flux:text class="text-zinc-600 dark:text-zinc-400 mb-2">
                                            {{ $event['description'] }}
                                        </flux:text>
                                        
                                        <!-- Event Meta -->
                                        <div class="flex items-center gap-4 text-sm text-zinc-500 dark:text-zinc-500">
                                            <div class="flex items-center gap-1">
                                                <flux:icon name="calendar" size="xs" />
                                                <span>{{ \Carbon\Carbon::parse($event['date'])->format('M j, Y') }}</span>
                                            </div>
                                            
                                            <div class="flex items-center gap-1">
                                                <flux:icon name="clock" size="xs" />
                                                <span>{{ \Carbon\Carbon::parse($event['date'])->format('g:i A') }}</span>
                                            </div>
                                            
                                            @if(isset($event['assignee']))
                                                <div class="flex items-center gap-1">
                                                    <flux:icon name="user" size="xs" />
                                                    <span>{{ $event['assignee'] }}</span>
                                                </div>
                                            @endif
                                            
                                            <!-- Time Ago -->
                                            <div class="ml-auto">
                                                @php
                                                    $eventDate = \Carbon\Carbon::parse($event['date']);
                                                @endphp
                                                @if($eventDate->isFuture())
                                                    <flux:text size="xs" class="text-blue-600 dark:text-blue-400">
                                                        in {{ $eventDate->diffForHumans() }}
                                                    </flux:text>
                                                @else
                                                    <flux:text size="xs" class="text-zinc-500">
                                                        {{ $eventDate->diffForHumans() }}
                                                    </flux:text>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-1 ml-4">
                                        @if(str_contains($event['id'], 'task_'))
                                            @php $taskId = (int) str_replace(['task_start_', 'task_due_', 'task_completed_'], '', $event['id']); @endphp
                                            <flux:button size="xs" variant="ghost" 
                                                        wire:click.stop="editTask({{ $taskId }})"
                                                        icon="pencil" />
                                        @elseif(str_contains($event['id'], 'milestone_'))
                                            @php $milestoneId = (int) str_replace('milestone_', '', $event['id']); @endphp
                                            <flux:button size="xs" variant="ghost" 
                                                        wire:click.stop="editMilestone({{ $milestoneId }})"
                                                        icon="pencil" />
                                        @endif
                                    </div>
                                </div>
                            </flux:card>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Timeline End Marker -->
            <div class="relative flex items-center gap-4 mt-6">
                <div class="timeline-marker relative z-10 flex-shrink-0">
                    <div class="w-6 h-6 rounded-full bg-zinc-300 dark:bg-zinc-600 border-4 border-white dark:border-zinc-900"></div>
                </div>
                <flux:text class="text-zinc-500 dark:text-zinc-500 italic">Timeline end</flux:text>
            </div>
        </div>
    @endif
</div>

<style>
.timeline-event {
    transition: all 0.2s ease;
}

.timeline-event:hover .timeline-marker > div {
    transform: scale(1.1);
}

.timeline-event-selected .timeline-marker > div {
    transform: scale(1.2);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
}

.timeline-marker > div {
    transition: all 0.2s ease;
}

.timeline-content {
    transform: translateY(0);
    transition: transform 0.2s ease;
}

.timeline-event:hover .timeline-content {
    transform: translateY(-2px);
}
</style>