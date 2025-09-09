<!-- ANALYTICS STUDIO -->
<div class="reports-dashboard bg-slate-900 -m-6 p-6 min-h-screen">
    
    <!-- HEADER -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold flex items-center">
                    <svg class="h-8 w-8 mr-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Analytics Studio
                </h2>
                <p class="text-slate-300 mt-1">Real-time business intelligence and reporting</p>
            </div>
            <div class="flex space-x-3">
                <button class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 rounded-lg transition-colors flex items-center">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Report
                </button>
                <button class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- QUICK STATS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php
            $stats = $data['quick_stats'] ?? [];
        @endphp
        
        <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 uppercase">Total Clients</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['total_clients'] ?? 0 }}</p>
                    <p class="text-xs text-green-400 mt-1">↑ 12% from last month</p>
                </div>
                <div class="bg-blue-500/20 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 uppercase">Open Tickets</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['open_tickets'] ?? 0 }}</p>
                    <p class="text-xs text-yellow-400 mt-1">{{ $stats['tickets_this_week'] ?? 0 }} this week</p>
                </div>
                <div class="bg-orange-500/20 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 uppercase">Revenue MTD</p>
                    <p class="text-2xl font-bold text-white">${{ number_format($stats['revenue_this_month'] ?? 0, 0) }}</p>
                    <p class="text-xs text-green-400 mt-1">↑ 23% from last month</p>
                </div>
                <div class="bg-green-500/20 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl p-4 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400 uppercase">Active Assets</p>
                    <p class="text-2xl font-bold text-white">{{ $stats['total_assets'] ?? 0 }}</p>
                    <p class="text-xs text-cyan-400 mt-1">{{ $stats['assets_monitored'] ?? 0 }} monitored</p>
                </div>
                <div class="bg-purple-500/20 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- REPORT CATEGORIES -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Financial Reports -->
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                <svg class="h-5 w-5 mr-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
                Financial Reports
            </h3>
            <div class="space-y-2">
                @foreach($data['report_categories']['financial'] ?? [] as $report)
                <a href="#" class="block p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors">
                    <p class="text-sm text-white font-medium">{{ $report }}</p>
                    <p class="text-xs text-slate-400 mt-1">Last run: 2 hours ago</p>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Operational Reports -->
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                <svg class="h-5 w-5 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Operational Reports
            </h3>
            <div class="space-y-2">
                @foreach($data['report_categories']['operational'] ?? [] as $report)
                <a href="#" class="block p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors">
                    <p class="text-sm text-white font-medium">{{ $report }}</p>
                    <p class="text-xs text-slate-400 mt-1">Last run: Yesterday</p>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Client Reports -->
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                <svg class="h-5 w-5 mr-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Client Reports
            </h3>
            <div class="space-y-2">
                @foreach($data['report_categories']['client'] ?? [] as $report)
                <a href="#" class="block p-3 bg-slate-700/50 hover:bg-slate-700 rounded-lg transition-colors">
                    <p class="text-sm text-white font-medium">{{ $report }}</p>
                    <p class="text-xs text-slate-400 mt-1">Last run: 3 days ago</p>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- INTERACTIVE CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Ticket Trends -->
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4">Ticket Trends (30 Days)</h3>
            <div style="position: relative; height: 250px;">
                <canvas id="ticketTrendsChart"></canvas>
            </div>
        </div>

        <!-- Revenue by Service -->
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <h3 class="text-lg font-semibold text-white mb-4">Revenue by Service</h3>
            <div style="position: relative; height: 250px;">
                <canvas id="revenueByServiceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- RECENT REPORTS -->
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Recent Reports</h3>
            <button class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors">View All</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-700">
                        <th class="text-left py-2 text-xs font-medium text-slate-400 uppercase">Report Name</th>
                        <th class="text-left py-2 text-xs font-medium text-slate-400 uppercase">Type</th>
                        <th class="text-left py-2 text-xs font-medium text-slate-400 uppercase">Generated</th>
                        <th class="text-left py-2 text-xs font-medium text-slate-400 uppercase">Generated By</th>
                        <th class="text-right py-2 text-xs font-medium text-slate-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-slate-700/50">
                        <td class="py-3 text-sm text-white">Monthly Revenue Summary</td>
                        <td class="py-3 text-sm text-slate-400">Financial</td>
                        <td class="py-3 text-sm text-slate-400">2 hours ago</td>
                        <td class="py-3 text-sm text-slate-400">System</td>
                        <td class="py-3 text-right">
                            <button class="text-cyan-400 hover:text-cyan-300 text-sm">View</button>
                            <button class="text-slate-400 hover:text-slate-300 text-sm ml-3">Export</button>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-700/50">
                        <td class="py-3 text-sm text-white">SLA Performance Report</td>
                        <td class="py-3 text-sm text-slate-400">Operational</td>
                        <td class="py-3 text-sm text-slate-400">Yesterday</td>
                        <td class="py-3 text-sm text-slate-400">Admin</td>
                        <td class="py-3 text-right">
                            <button class="text-cyan-400 hover:text-cyan-300 text-sm">View</button>
                            <button class="text-slate-400 hover:text-slate-300 text-sm ml-3">Export</button>
                        </td>
                    </tr>
                    <tr class="border-b border-slate-700/50">
                        <td class="py-3 text-sm text-white">Client Activity Summary</td>
                        <td class="py-3 text-sm text-slate-400">Client</td>
                        <td class="py-3 text-sm text-slate-400">3 days ago</td>
                        <td class="py-3 text-sm text-slate-400">Manager</td>
                        <td class="py-3 text-right">
                            <button class="text-cyan-400 hover:text-cyan-300 text-sm">View</button>
                            <button class="text-slate-400 hover:text-slate-300 text-sm ml-3">Export</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ticket Trends Chart
    const ticketCtx = document.getElementById('ticketTrendsChart');
    if (ticketCtx) {
        new Chart(ticketCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 30}, (_, i) => `Day ${i + 1}`),
                datasets: [{
                    label: 'Opened',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 20) + 5),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Closed',
                    data: Array.from({length: 30}, () => Math.floor(Math.random() * 20) + 3),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: 'rgb(148, 163, 184)'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: 'rgb(148, 163, 184)'
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: 'rgb(148, 163, 184)'
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        }
                    }
                }
            }
        });
    }

    // Revenue by Service Chart
    const revenueCtx = document.getElementById('revenueByServiceChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'doughnut',
            data: {
                labels: ['Managed Services', 'Support', 'Projects', 'Hardware', 'Other'],
                datasets: [{
                    data: [35, 25, 20, 15, 5],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(148, 163, 184, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: 'rgb(148, 163, 184)'
                        }
                    }
                }
            }
        });
    }
});
</script>
