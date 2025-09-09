<!-- CRISIS COMMAND CENTER WITH COMPREHENSIVE DATA -->
<div x-data="urgentDashboard()" x-init="init()" class="crisis-command-center">
    
    <!-- CRITICAL ALERT HEADER -->
    <div class="alert-header-bar mb-6 relative overflow-hidden rounded-xl">
        <div class="absolute inset-0 bg-gradient-to-r from-red-600 via-red-500 to-orange-500 animate-gradient"></div>
        <div class="relative px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="pulse-alert">
                        <svg class="h-8 w-8 text-white animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">CRISIS COMMAND CENTER</h2>
                        <p class="text-red-100 text-sm">
                            {{ $data['counts']['urgent_tickets'] ?? 0 }} Critical Items • 
                            ${{ $data['counts']['revenue_at_risk'] ?? '0' }} at Risk
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-white font-mono text-lg" x-text="currentTime"></div>
                    <button @click="toggleFullScreen()" class="text-white hover:text-red-100 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CRISIS METRICS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <!-- SLA Breaches -->
        <div class="crisis-metric-card bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-xl">
            <p class="text-red-100 text-xs uppercase tracking-wide">SLA Breaches</p>
            <p class="text-3xl font-bold mt-1">{{ $data['counts']['sla_breaches'] ?? 0 }}</p>
            <p class="text-red-100 text-xs mt-1">24hr+ open</p>
        </div>

        <!-- Critical Tickets -->
        <div class="crisis-metric-card bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-xl">
            <p class="text-orange-100 text-xs uppercase tracking-wide">Critical</p>
            <p class="text-3xl font-bold mt-1">{{ $data['counts']['urgent_tickets'] ?? 0 }}</p>
            <p class="text-orange-100 text-xs mt-1">High priority</p>
        </div>

        <!-- Escalations -->
        <div class="crisis-metric-card bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 text-white shadow-xl">
            <p class="text-yellow-100 text-xs uppercase tracking-wide">Escalating</p>
            <p class="text-3xl font-bold mt-1">{{ $data['counts']['escalations'] ?? 0 }}</p>
            <p class="text-yellow-100 text-xs mt-1">20-23hr old</p>
        </div>

        <!-- Overdue Invoices -->
        <div class="crisis-metric-card bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-xl">
            <p class="text-purple-100 text-xs uppercase tracking-wide">Overdue</p>
            <p class="text-3xl font-bold mt-1">{{ $data['counts']['overdue_invoices'] ?? 0 }}</p>
            <p class="text-purple-100 text-xs mt-1">Invoices</p>
        </div>

        <!-- Revenue at Risk -->
        <div class="crisis-metric-card bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl p-4 text-white shadow-xl">
            <p class="text-pink-100 text-xs uppercase tracking-wide">At Risk</p>
            <p class="text-2xl font-bold mt-1">${{ $data['counts']['revenue_at_risk'] ?? '0' }}</p>
            <p class="text-pink-100 text-xs mt-1">30+ days</p>
        </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- CRITICAL TICKETS LIST (2 columns wide) -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-slate-800 dark:text-white">Critical & High Priority Tickets</h3>
                    <span class="text-xs text-slate-500">{{ count($data['urgent_tickets'] ?? []) }} total</span>
                </div>
                
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($data['urgent_tickets'] ?? [] as $ticket)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-mono text-slate-500">#{{ $ticket->id }}</span>
                                <span class="font-medium text-sm text-slate-800 dark:text-white">
                                    {{ Str::limit($ticket->subject ?? 'No Subject', 50) }}
                                </span>
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $ticket->priority === 'Critical' ? 'bg-red-100 text-red-800' : 'bg-orange-100 text-orange-800' }}">
                                    {{ $ticket->priority }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-3 mt-1 text-xs text-slate-500">
                                <span>{{ $ticket->client->name ?? 'No Client' }}</span>
                                <span>•</span>
                                <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                @if($ticket->assignee)
                                <span>•</span>
                                <span>{{ $ticket->assignee->name }}</span>
                                @else
                                <span>•</span>
                                <span class="text-red-500">Unassigned</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('tickets.show', $ticket->id) }}" class="text-blue-600 hover:text-blue-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                    @empty
                    <p class="text-center text-slate-500 py-8">No critical tickets at this time</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- TEAM WORKLOAD -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
                <h3 class="font-semibold text-slate-800 dark:text-white mb-3">Team Workload</h3>
                <div class="space-y-2">
                    @forelse($data['team_workload'] ?? [] as $member)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $member->name }}</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium {{ $member->active_tickets > 5 ? 'text-red-600' : ($member->active_tickets > 3 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ $member->active_tickets }}
                            </span>
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $member->active_tickets > 5 ? 'bg-red-500' : ($member->active_tickets > 3 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                     style="width: {{ min($member->active_tickets * 10, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-slate-500">No team data available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- SECOND ROW -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- SLA BREACHES -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-slate-800 dark:text-white mb-3">SLA Breaches (24hr+)</h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($data['sla_breaches'] ?? [] as $ticket)
                <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded border-l-4 border-red-500">
                    <p class="text-sm font-medium text-slate-800 dark:text-white">
                        #{{ $ticket->id }} - {{ Str::limit($ticket->subject, 30) }}
                    </p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        {{ $ticket->client->name ?? 'Unknown' }} • Open {{ $ticket->created_at->diffForHumans(null, true) }}
                    </p>
                </div>
                @empty
                <p class="text-sm text-slate-500">No SLA breaches</p>
                @endforelse
            </div>
        </div>

        <!-- CLIENT IMPACT -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-slate-800 dark:text-white mb-3">Client Impact</h3>
            <div class="space-y-2">
                @forelse($data['client_impact'] ?? [] as $client)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ Str::limit($client->name, 20) }}</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                        {{ $client->critical_tickets }} critical
                    </span>
                </div>
                @empty
                <p class="text-sm text-slate-500">No client impact data</p>
                @endforelse
            </div>
        </div>

        <!-- OVERDUE INVOICES -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
            <h3 class="font-semibold text-slate-800 dark:text-white mb-3">Overdue Invoices</h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @forelse($data['overdue_invoices'] ?? [] as $invoice)
                <div class="flex items-center justify-between p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                    <div>
                        <p class="text-sm font-medium text-slate-800 dark:text-white">
                            #{{ $invoice->number ?? $invoice->id }}
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            {{ $invoice->client->name ?? 'Unknown' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-red-600">${{ number_format($invoice->amount, 2) }}</p>
                        <p class="text-xs text-slate-500">{{ $invoice->due_date->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-slate-500">No overdue invoices</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 7-DAY TREND CHART -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4">
        <h3 class="font-semibold text-slate-800 dark:text-white mb-3">7-Day Critical Ticket Trend</h3>
        <div style="position: relative; height: 200px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- RECENT ACTIVITY -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-4 mt-6">
        <h3 class="font-semibold text-slate-800 dark:text-white mb-3">Recent Critical Activity (Last Hour)</h3>
        <div class="space-y-2">
            @forelse($data['recent_activity'] ?? [] as $activity)
            <div class="flex items-center space-x-3 text-sm">
                <span class="text-xs text-slate-500">{{ $activity->updated_at->format('H:i') }}</span>
                <span class="text-slate-700 dark:text-slate-300">
                    Ticket #{{ $activity->id }} - {{ $activity->subject }} - {{ $activity->status }}
                </span>
            </div>
            @empty
            <p class="text-sm text-slate-500">No recent activity</p>
            @endforelse
        </div>
    </div>
</div>

<style>
    @keyframes gradient {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .animate-gradient {
        background-size: 200% 200%;
        animation: gradient 3s ease infinite;
    }
    
    .crisis-metric-card {
        position: relative;
        overflow: hidden;
        transition: transform 0.2s;
    }
    
    .crisis-metric-card:hover {
        transform: translateY(-2px);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function urgentDashboard() {
    return {
        currentTime: '',
        
        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.initChart();
        },
        
        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('en-US', { 
                hour12: false, 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
        },
        
        initChart() {
            const ctx = document.getElementById('trendChart');
            if (ctx) {
                const trendData = @json($data['seven_day_trend'] ?? []);
                const labels = trendData.map(d => d.date);
                const data = trendData.map(d => d.count);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Critical Tickets',
                            data: data,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }
        },
        
        toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    }
}
</script>