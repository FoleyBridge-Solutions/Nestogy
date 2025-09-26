<flux:card class="h-full flex flex-col">
    <!-- Compact Header with Inline Filters -->
    <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3 mb-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <flux:icon.queue-list class="size-6 text-blue-500" />
                <flux:heading size="xl">{{ $tickets->count() }}</flux:heading>
                <flux:text class="text-zinc-500">tickets</flux:text>
            </div>
            
            <!-- Inline Filter Pills -->
            <div class="flex items-center gap-2 flex-1 overflow-x-auto">
                <div class="flex gap-1 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                    @foreach(['all', 'critical', 'high', 'medium', 'low'] as $pri)
                        <button 
                            wire:click="setPriority('{{ $pri }}')"
                            class="px-3 py-1 text-xs font-medium rounded transition-all {{ $priority === $pri ? 'bg-white dark:bg-zinc-700 shadow-sm text-blue-600' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            {{ ucfirst($pri) }}
                            @if($pri !== 'all')
                                <span class="ml-1 text-zinc-400">({{ $tickets->where('priority', ucfirst($pri))->count() }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
                
                <div class="flex gap-1 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
                    @foreach(['all', 'open', 'in_progress', 'waiting'] as $stat)
                        <button 
                            wire:click="setStatus('{{ $stat }}')"
                            class="px-3 py-1 text-xs font-medium rounded transition-all {{ $status === $stat ? 'bg-white dark:bg-zinc-700 shadow-sm text-blue-600' : 'text-zinc-600 hover:text-zinc-900' }}"
                        >
                            {{ ucfirst(str_replace('_', ' ', $stat)) }}
                        </button>
                    @endforeach
                </div>
            </div>
            
            <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="loadQueue" />
        </div>
    </div>
    
    <!-- Kanban-Style Priority Swim Lanes -->
    <div class="flex-1 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 h-full">
            @php
                $priorityGroups = [
                    'Critical' => $tickets->where('priority', 'Critical'),
                    'High' => $tickets->where('priority', 'High'),
                    'Medium' => $tickets->where('priority', 'Medium'),
                    'Low' => $tickets->where('priority', 'Low')
                ];
                $priorityColors = [
                    'Critical' => 'red',
                    'High' => 'orange', 
                    'Medium' => 'yellow',
                    'Low' => 'green'
                ];
            @endphp
            
            @foreach($priorityGroups as $priorityName => $priorityTickets)
                <div class="flex flex-col h-full">
                    <!-- Column Header -->
                    <div class="flex items-center justify-between mb-3 pb-2 border-b-2 
                        @if($priorityName === 'Critical') border-red-500
                        @elseif($priorityName === 'High') border-orange-500
                        @elseif($priorityName === 'Medium') border-yellow-500
                        @else border-green-500
                        @endif">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full 
                                @if($priorityName === 'Critical') bg-red-500
                                @elseif($priorityName === 'High') bg-orange-500
                                @elseif($priorityName === 'Medium') bg-yellow-500
                                @else bg-green-500
                                @endif"></div>
                            <flux:text class="font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ $priorityName }}
                            </flux:text>
                        </div>
                        <flux:badge size="sm" 
                            color="{{ $priorityName === 'Critical' ? 'red' : ($priorityName === 'High' ? 'orange' : ($priorityName === 'Medium' ? 'yellow' : 'green')) }}" 
                            variant="subtle">
                            {{ $priorityTickets->count() }}
                        </flux:badge>
                    </div>
                    
                    <!-- Tickets in Column -->
                    <div class="flex-1 overflow-y-auto space-y-2 pr-2">
                        @php
                            $isExpanded = in_array($priorityName, $expandedPriorities);
                            $showLimit = $isExpanded ? $priorityTickets->count() : 5;
                            $displayTickets = $priorityTickets->take($showLimit);
                            $hasMore = $priorityTickets->count() > 5;
                        @endphp
                        @forelse($displayTickets as $ticket)
                            <div 
                                wire:click="viewTicket({{ $ticket['id'] }})"
                                class="group bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 hover:shadow-md 
                                    @if($priorityName === 'Critical') hover:border-red-400
                                    @elseif($priorityName === 'High') hover:border-orange-400
                                    @elseif($priorityName === 'Medium') hover:border-yellow-400
                                    @else hover:border-green-400
                                    @endif transition-all cursor-pointer relative overflow-hidden"
                            >
                                <!-- SLA Indicator Bar -->
                                @if($ticket['sla_status'] === 'overdue')
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-red-500"></div>
                                @elseif($ticket['sla_status'] === 'warning')
                                    <div class="absolute top-0 left-0 right-0 h-1 bg-amber-500"></div>
                                @endif
                                
                                <!-- Ticket Number & Time -->
                                <div class="flex items-start justify-between mb-2">
                                    <flux:text class="text-xs font-mono text-zinc-500">#{{ $ticket['id'] }}</flux:text>
                                    <flux:text size="xs" class="text-zinc-400">
                                        {{ $ticket['time_in_queue'] }}
                                    </flux:text>
                                </div>
                                
                                <!-- Subject -->
                                <flux:text class="font-medium text-sm line-clamp-2 mb-2">
                                    {{ $ticket['subject'] }}
                                </flux:text>
                                
                                <!-- Client -->
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center
                                        @if($priorityName === 'Critical') bg-red-100 dark:bg-red-900/30
                                        @elseif($priorityName === 'High') bg-orange-100 dark:bg-orange-900/30
                                        @elseif($priorityName === 'Medium') bg-yellow-100 dark:bg-yellow-900/30
                                        @else bg-green-100 dark:bg-green-900/30
                                        @endif">
                                        <flux:icon.building-office class="size-3 
                                            @if($priorityName === 'Critical') text-red-600
                                            @elseif($priorityName === 'High') text-orange-600
                                            @elseif($priorityName === 'Medium') text-yellow-600
                                            @else text-green-600
                                            @endif" />
                                    </div>
                                    <flux:text size="xs" class="text-zinc-600 dark:text-zinc-400 truncate">
                                        {{ $ticket['client'] }}
                                    </flux:text>
                                </div>
                                
                                <!-- Bottom Row -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        @if($ticket['assigned_to'] === 'Unassigned')
                                            <flux:badge size="xs" color="blue" variant="subtle">
                                                <flux:icon.user-minus class="size-3" />
                                                Unassigned
                                            </flux:badge>
                                        @else
                                            <div class="flex items-center gap-1">
                                                <div class="w-5 h-5 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                    <flux:text size="xs" class="font-semibold">
                                                        {{ substr($ticket['assigned_to'], 0, 1) }}
                                                    </flux:text>
                                                </div>
                                                <flux:text size="xs" class="text-zinc-500 truncate max-w-[100px]">
                                                    {{ $ticket['assigned_to'] }}
                                                </flux:text>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Quick Actions -->
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                        @if($ticket['assigned_to'] === 'Unassigned')
                                            <flux:button 
                                                size="xs" 
                                                variant="ghost"
                                                icon="hand-raised"
                                                wire:click="assignTicket({{ $ticket['id'] }})"
                                                title="Take ticket"
                                            />
                                        @endif
                                        <flux:button 
                                            size="xs" 
                                            variant="ghost"
                                            icon="arrow-top-right-on-square"
                                            wire:click="viewTicket({{ $ticket['id'] }})"
                                            title="Open ticket"
                                        />
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <flux:icon.inbox class="size-8 text-zinc-300 mx-auto mb-2" />
                                <flux:text size="xs" class="text-zinc-400">
                                    No {{ strtolower($priorityName) }} priority tickets
                                </flux:text>
                            </div>
                        @endforelse
                        
                        @if($hasMore)
                            <button 
                                wire:click="loadMoreForPriority('{{ $priorityName }}')"
                                class="w-full py-2 px-3 text-xs font-medium rounded-lg border-2 border-dashed transition-all
                                    @if($priorityName === 'Critical') 
                                        border-red-300 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20
                                    @elseif($priorityName === 'High') 
                                        border-orange-300 text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20
                                    @elseif($priorityName === 'Medium') 
                                        border-yellow-300 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20
                                    @else 
                                        border-green-300 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20
                                    @endif"
                            >
                                @if($isExpanded)
                                    <flux:icon.chevron-up class="size-3 inline mr-1" />
                                    Show less
                                @else
                                    <flux:icon.plus class="size-3 inline mr-1" />
                                    {{ $priorityTickets->count() - 5 }} more
                                @endif
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <!-- Quick Stats Footer -->
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-3 mt-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                    <flux:text size="xs" class="text-zinc-600">
                        {{ $tickets->where('sla_status', 'overdue')->count() }} Overdue
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                    <flux:text size="xs" class="text-zinc-600">
                        {{ $tickets->where('sla_status', 'warning')->count() }} Warning
                    </flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <flux:text size="xs" class="text-zinc-600">
                        {{ $tickets->where('assigned_to', 'Unassigned')->count() }} Unassigned
                    </flux:text>
                </div>
            </div>
            
            <flux:text size="xs" class="text-zinc-400">
                Last updated {{ now()->diffForHumans() }}
            </flux:text>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div wire:loading.delay wire:target="loadQueue,setPriority,setStatus" class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 flex items-center justify-center rounded-lg backdrop-blur-sm">
        <div class="flex flex-col items-center gap-2">
            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <flux:text size="sm">Updating queue...</flux:text>
        </div>
    </div>
</flux:card>