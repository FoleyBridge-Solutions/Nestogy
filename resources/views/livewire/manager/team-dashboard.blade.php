<div wire:poll.{{ $refreshInterval }}ms="refresh">
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100">Team Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1 text-sm sm:text-base">Real-time overview of team performance and ticket status</p>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <span class="text-sm text-gray-500 hidden sm:inline">Auto-refresh: 30s</span>
            <flux:button wire:click="refresh" variant="ghost" size="sm" class="w-full sm:w-auto">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh Now
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Open Tickets</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $teamStats['total_open'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-ticket-alt text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Overdue</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $teamStats['total_overdue'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">High Priority</p>
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ $teamStats['high_priority'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-flag text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Resolved Today</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $teamStats['resolved_today'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Avg Resolution Time</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $teamStats['avg_resolution_time'] ?? 0 }}h</p>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">SLA Compliance (30d)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $slaCompliance['compliance_rate'] ?? 0 }}%</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">{{ $slaCompliance['met'] ?? 0 }} met / {{ $slaCompliance['breached'] ?? 0 }} breached</p>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $slaCompliance['compliance_rate'] ?? 0 }}%"></div>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fas fa-users mr-2"></i>
                Team Performance
            </flux:heading>

            <div class="space-y-3">
                @forelse($technicianStats as $stat)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600 dark:text-gray-300"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $stat['user']->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $stat['user']->email }}</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-{{ $stat['workload_status']['color'] }}-100 text-{{ $stat['workload_status']['color'] }}-800 dark:bg-{{ $stat['workload_status']['color'] }}-900 dark:text-{{ $stat['workload_status']['color'] }}-200">
                                {{ $stat['workload_status']['label'] }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-4 gap-3 mt-3 text-center">
                            <div>
                                <p class="text-2xl font-bold text-blue-600">{{ $stat['active_tickets'] }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Active</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-red-600">{{ $stat['overdue_tickets'] }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Overdue</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600">{{ $stat['resolved_today'] }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Today</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-purple-600">{{ $stat['resolved_this_week'] }}</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Week</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-8">No technicians found</p>
                @endforelse
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg" class="mb-4">
                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                Overdue Tickets ({{ count($overdueTickets) }})
            </flux:heading>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($overdueTickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket->id) }}" 
                       class="block p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-600 rounded hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">#{{ $ticket->number }} - {{ $ticket->subject }}</p>
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    <span><i class="fas fa-flag mr-1"></i>{{ $ticket->priority }}</span>
                                    @if($ticket->assignee)
                                        <span><i class="fas fa-user mr-1"></i>{{ $ticket->assignee->name }}</span>
                                    @else
                                        <span class="text-red-600"><i class="fas fa-user-slash mr-1"></i>Unassigned</span>
                                    @endif
                                    @if($ticket->priorityQueue)
                                        <span class="text-red-600">
                                            <i class="fas fa-clock mr-1"></i>
                                            Overdue {{ $ticket->priorityQueue->sla_deadline->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                        <p class="text-gray-500">No overdue tickets!</p>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading size="lg" class="mb-4">
            <i class="fas fa-list mr-2"></i>
            Active Tickets ({{ count($activeTickets) }})
        </flux:heading>

        <div class="overflow-x-auto -mx-6 sm:mx-0">
            <table class="w-full min-w-max">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Ticket</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Assigned</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">Age</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">SLA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($activeTickets as $ticket)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3">
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="text-blue-600 hover:underline font-medium">
                                    #{{ $ticket->number }}
                                </a>
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs">{{ $ticket->subject }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->client->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($ticket->priority === 'Critical') bg-red-100 text-red-800
                                    @elseif($ticket->priority === 'High') bg-orange-100 text-orange-800
                                    @elseif($ticket->priority === 'Medium') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ $ticket->priority }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->status }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($ticket->assignee)
                                    {{ $ticket->assignee->name }}
                                @else
                                    <span class="text-red-600">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $ticket->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                @if($ticket->priorityQueue && $ticket->priorityQueue->sla_deadline)
                                    @if(now()->gt($ticket->priorityQueue->sla_deadline))
                                        <span class="text-xs text-red-600 font-semibold">
                                            <i class="fas fa-exclamation-circle"></i> Overdue
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-600">
                                            {{ $ticket->priorityQueue->sla_deadline->diffForHumans() }}
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No active tickets
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('dashboard-refreshed', () => {
                console.log('Dashboard refreshed at ' + new Date().toLocaleTimeString());
            });
        });
    </script>
</div>
