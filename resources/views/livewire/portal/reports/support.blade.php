{{-- Support Analytics --}}
<div class="space-y-6">
    {{-- Metrics Cards --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <flux:card class="border-blue-200 dark:border-blue-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Total Tickets</flux:text>
                <flux:heading size="xl">{{ $this->ticketStats['total_tickets'] ?? 0 }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">This period</flux:text>
            </div>
        </flux:card>

        <flux:card class="border-amber-200 dark:border-amber-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Open Tickets</flux:text>
                <flux:heading size="xl">{{ $this->ticketStats['open_tickets'] ?? 0 }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Currently active</flux:text>
            </div>
        </flux:card>

        <flux:card class="border-emerald-200 dark:border-emerald-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Avg Resolution Time</flux:text>
                <flux:heading size="xl">{{ $this->ticketStats['avg_resolution_time'] ?? 'N/A' }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Average</flux:text>
            </div>
        </flux:card>

        <flux:card class="border-purple-200 dark:border-purple-800">
            <div class="space-y-1">
                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Satisfaction Score</flux:text>
                <flux:heading size="xl">{{ $this->ticketStats['satisfaction_score'] ?? 'N/A' }}</flux:heading>
                <flux:text size="xs" class="text-zinc-400">Customer rating</flux:text>
            </div>
        </flux:card>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Ticket Volume Trend --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Ticket Volume Trend</flux:heading>
            <div x-data="{
                chart: null,
                initChart() {
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'line',
                        data: {
                            labels: {{ json_encode($this->ticketTrends['labels'] ?? []) }},
                            datasets: [{
                                label: 'Opened',
                                data: {{ json_encode($this->ticketTrends['opened'] ?? []) }},
                                borderColor: 'rgb(239, 68, 68)',
                                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                tension: 0.4
                            }, {
                                label: 'Closed',
                                data: {{ json_encode($this->ticketTrends['closed'] ?? []) }},
                                borderColor: 'rgb(34, 197, 94)',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: true, position: 'bottom' } },
                            scales: { y: { beginAtZero: true } }
                        }
                    });
                }
            }" x-init="setTimeout(() => initChart(), 100)">
                <canvas x-ref="canvas" class="w-full" height="250"></canvas>
            </div>
        </flux:card>

        {{-- Tickets by Status --}}
        <flux:card>
            <flux:heading size="lg" class="mb-4">Tickets by Status</flux:heading>
            <div x-data="{
                chart: null,
                initChart() {
                    this.chart = new Chart(this.$refs.canvas, {
                        type: 'doughnut',
                        data: {
                            labels: {{ json_encode($this->ticketsByStatus['labels'] ?? []) }},
                            datasets: [{
                                data: {{ json_encode($this->ticketsByStatus['data'] ?? []) }},
                                backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: true, position: 'bottom' } }
                        }
                    });
                }
            }" x-init="setTimeout(() => initChart(), 100)">
                <canvas x-ref="canvas" class="w-full" height="250"></canvas>
            </div>
        </flux:card>
    </div>

    {{-- Tickets by Priority --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Tickets by Priority</flux:heading>
        <div x-data="{
            chart: null,
            initChart() {
                this.chart = new Chart(this.$refs.canvas, {
                    type: 'bar',
                    data: {
                        labels: {{ json_encode($this->ticketsByPriority['labels'] ?? []) }},
                        datasets: [{
                            label: 'Tickets',
                            data: {{ json_encode($this->ticketsByPriority['data'] ?? []) }},
                            backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        }" x-init="setTimeout(() => initChart(), 100)">
            <canvas x-ref="canvas" class="w-full" height="200"></canvas>
        </div>
    </flux:card>

    {{-- Recent Tickets Table --}}
    <flux:card>
        <flux:heading size="lg" class="mb-4">Recent Tickets</flux:heading>
        @if($this->recentTickets->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">ID</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Subject</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Priority</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="text-left py-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->recentTickets as $ticket)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3">
                                    <flux:text size="sm" class="font-mono">#{{ $ticket->id }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:text size="sm">{{ $ticket->subject }}</flux:text>
                                </td>
                                <td class="py-3">
                                    <flux:badge size="sm" :color="$ticket->priority === 'critical' ? 'red' : ($ticket->priority === 'high' ? 'orange' : ($ticket->priority === 'medium' ? 'yellow' : 'gray'))">
                                        {{ ucfirst($ticket->priority ?? 'Normal') }}
                                    </flux:badge>
                                </td>
                                <td class="py-3">
                                    <flux:badge size="sm" :color="in_array(strtolower($ticket->status), ['closed', 'resolved']) ? 'green' : 'blue'">
                                        {{ ucfirst($ticket->status) }}
                                    </flux:badge>
                                </td>
                                <td class="py-3">
                                    <flux:text size="sm">{{ $ticket->created_at->format('M j, Y') }}</flux:text>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <flux:text class="text-center py-8 text-zinc-500">No tickets found for this period.</flux:text>
        @endif
    </flux:card>
</div>
