@extends('layouts.app')

@section('title', 'Ticket Analytics')

@section('page-actions')
<div class="flex items-center space-x-4">
    <div class="flex items-center space-x-2">
        <label for="report-type" class="text-sm font-medium text-gray-700 dark:text-gray-300">Type:</label>
        <select id="report-type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border-gray-300 dark:border-gray-600 text-sm">
            <option value="overview" selected>Overview</option>
            <option value="sla">SLA Compliance</option>
            <option value="performance">Performance</option>
            <option value="workload">Workload Analysis</option>
            <option value="satisfaction">Customer Satisfaction</option>
            <option value="trends">Trend Analysis</option>
        </select>
    </div>
    
    <div class="flex items-center space-x-2">
        <label for="date-preset" class="text-sm font-medium text-gray-700 dark:text-gray-300">Period:</label>
        <select id="date-preset" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border-gray-300 dark:border-gray-600 text-sm">
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days" selected>Last 30 days</option>
            <option value="last_90_days">Last 90 days</option>
            <option value="this_month">This month</option>
            <option value="last_month">Last month</option>
            <option value="this_quarter">This quarter</option>
            <option value="custom">Custom range</option>
        </select>
    </div>
    
    <div id="custom-date-range" class="hidden flex items-center space-x-2">
        <input type="date" id="start-date" class="form-input rounded-md border-gray-300 dark:border-gray-600 text-sm">
        <span class="text-gray-500">to</span>
        <input type="date" id="end-date" class="form-input rounded-md border-gray-300 dark:border-gray-600 text-sm">
    </div>
    
    <button id="refresh-data" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
        <i class="fas fa-sync-alt mr-2"></i>
        Refresh
    </button>
    
    <div class="relative">
        <button id="export-dropdown-btn" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-download mr-2"></i>
            Export
            <i class="fas fa-chevron-down ml-2"></i>
        </button>
        <div id="export-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg z-10">
            <div class="py-1">
                <button class="export-btn block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 w-full text-left" data-format="pdf">
                    <i class="fas fa-file-pdf mr-2 text-red-500"></i>PDF Report
                </button>
                <button class="export-btn block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 w-full text-left" data-format="xlsx">
                    <i class="fas fa-file-excel mr-2 text-green-500"></i>Excel Report
                </button>
                <button class="export-btn block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 w-full text-left" data-format="csv">
                    <i class="fas fa-file-csv mr-2 text-blue-500"></i>CSV Data
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mr-4"></div>
            <span class="text-gray-700 dark:text-gray-300">Loading ticket analytics...</span>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Ticket Analytics</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Support ticket performance, SLA compliance, and customer satisfaction metrics</p>
    
    <div id="last-updated" class="text-sm text-gray-500 mt-1"></div>
</div>

<!-- Ticket KPIs -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Tickets</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-total-tickets" class="text-2xl font-bold text-blue-600">0</span>
                    <span id="kpi-total-tickets-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-ticket-alt text-blue-600"></i>
            </div>
        </div>
        <div id="kpi-total-tickets-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Resolution Time</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-resolution-time" class="text-2xl font-bold text-green-600">0h</span>
                    <span id="kpi-resolution-time-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-clock text-green-600"></i>
            </div>
        </div>
        <div id="kpi-resolution-time-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">SLA Compliance</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-sla-compliance" class="text-2xl font-bold text-yellow-600">0%</span>
                    <span id="kpi-sla-compliance-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-yellow-100 rounded-full">
                <i class="fas fa-target text-yellow-600"></i>
            </div>
        </div>
        <div id="kpi-sla-compliance-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Customer Satisfaction</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-satisfaction" class="text-2xl font-bold text-purple-600">0.0</span>
                    <span id="kpi-satisfaction-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-smile text-purple-600"></i>
            </div>
        </div>
        <div id="kpi-satisfaction-change" class="mt-4 text-sm"></div>
    </div>
</div>

<!-- Main Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Ticket Volume Trend -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ticket Volume Trend</h3>
            <div class="flex items-center space-x-2">
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md bg-indigo-100 text-indigo-700" data-chart="volume" data-period="7d">7D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="volume" data-period="30d">30D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="volume" data-period="90d">90D</button>
            </div>
        </div>
        <div class="h-80">
            <canvas id="ticket-volume-chart"></canvas>
        </div>
    </div>

    <!-- Resolution Time Trend -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Resolution Time Trend</h3>
            <div class="flex items-center space-x-2">
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md bg-indigo-100 text-indigo-700" data-chart="resolution" data-period="7d">7D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="resolution" data-period="30d">30D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="resolution" data-period="90d">90D</button>
            </div>
        </div>
        <div class="h-80">
            <canvas id="resolution-time-chart"></canvas>
        </div>
    </div>
</div>

<!-- Secondary Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Status Distribution -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ticket Status</h3>
        <div class="h-64">
            <canvas id="status-distribution-chart"></canvas>
        </div>
    </div>

    <!-- Priority Breakdown -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Priority Breakdown</h3>
        <div class="h-64">
            <canvas id="priority-breakdown-chart"></canvas>
        </div>
    </div>

    <!-- SLA Performance -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SLA Performance</h3>
        <div class="h-64">
            <canvas id="sla-performance-chart"></canvas>
        </div>
    </div>
</div>

<!-- Performance Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Performers -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Performers</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                    </tr>
                </thead>
                <tbody id="top-performers-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                    <!-- Data will be loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- SLA Violations -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent SLA Violations</h3>
        <div id="sla-violations" class="space-y-4 max-h-96 overflow-y-auto">
            <!-- Violations will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Alert Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-100 text-sm">Overdue Tickets</p>
                <p id="overdue-tickets" class="text-2xl font-bold">0</p>
            </div>
            <i class="fas fa-exclamation-triangle text-red-200 text-2xl"></i>
        </div>
    </div>

    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm">High Priority Open</p>
                <p id="high-priority-tickets" class="text-2xl font-bold">0</p>
            </div>
            <i class="fas fa-flag text-yellow-200 text-2xl"></i>
        </div>
    </div>

    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">Unassigned</p>
                <p id="unassigned-tickets" class="text-2xl font-bold">0</p>
            </div>
            <i class="fas fa-user-times text-blue-200 text-2xl"></i>
        </div>
    </div>

    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">Resolved Today</p>
                <p id="resolved-today" class="text-2xl font-bold">0</p>
            </div>
            <i class="fas fa-check-circle text-green-200 text-2xl"></i>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .chart-period-btn.active {
        @apply bg-indigo-100 text-indigo-700;
    }
    
    #export-dropdown {
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let charts = {};
    let currentConfig = {
        type: 'overview',
        preset: 'last_30_days',
        start: null,
        end: null
    };

    // Initialize
    initializeCharts();
    loadTicketData();
    setupEventListeners();

    function setupEventListeners() {
        // Report type changes
        document.getElementById('report-type').addEventListener('change', function() {
            currentConfig.type = this.value;
            loadTicketData();
        });

        // Date preset changes
        document.getElementById('date-preset').addEventListener('change', function() {
            currentConfig.preset = this.value;
            
            if (this.value === 'custom') {
                document.getElementById('custom-date-range').classList.remove('hidden');
            } else {
                document.getElementById('custom-date-range').classList.add('hidden');
                loadTicketData();
            }
        });

        // Custom date range
        document.getElementById('start-date').addEventListener('change', function() {
            currentConfig.start = this.value;
            if (currentConfig.end) loadTicketData();
        });

        document.getElementById('end-date').addEventListener('change', function() {
            currentConfig.end = this.value;
            if (currentConfig.start) loadTicketData();
        });

        // Refresh button
        document.getElementById('refresh-data').addEventListener('click', loadTicketData);

        // Export functionality
        setupExportHandlers();
    }

    function loadTicketData() {
        showLoading();

        const params = new URLSearchParams({
            type: currentConfig.type,
            preset: currentConfig.preset
        });

        if (currentConfig.preset === 'custom') {
            params.append('start_date', currentConfig.start);
            params.append('end_date', currentConfig.end);
        }

        fetch(`{{ route('reports.tickets') }}?${params}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateKPIs(data.summary);
            updateCharts(data.charts);
            updateTables(data.tables);
            updateAlertCards(data.summary);
            updateLastUpdated();
            hideLoading();
        })
        .catch(error => {
            console.error('Error loading ticket data:', error);
            hideLoading();
            showError('Failed to load ticket analytics data');
        });
    }

    function initializeCharts() {
        // Ticket Volume Chart
        const volumeCtx = document.getElementById('ticket-volume-chart').getContext('2d');
        charts.volume = new Chart(volumeCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Created',
                    data: [],
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }, {
                    label: 'Resolved',
                    data: [],
                    backgroundColor: 'rgba(34, 197, 94, 0.6)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Resolution Time Chart
        const resolutionCtx = document.getElementById('resolution-time-chart').getContext('2d');
        charts.resolution = new Chart(resolutionCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Avg Resolution Time (hours)',
                    data: [],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + 'h';
                            }
                        }
                    }
                }
            }
        });

        // Status Distribution Chart  
        const statusCtx = document.getElementById('status-distribution-chart').getContext('2d');
        charts.status = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Open', 'In Progress', 'Resolved', 'Closed'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#3B82F6', '#F59E0B', '#10B981', '#6B7280'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function updateKPIs(summary) {
        document.getElementById('kpi-total-tickets').textContent = (summary.total_tickets || 0).toLocaleString();
        document.getElementById('kpi-resolution-time').textContent = (summary.avg_resolution_time || 0).toFixed(1) + 'h';
        document.getElementById('kpi-sla-compliance').textContent = (summary.sla_compliance || 0).toFixed(1) + '%';
        document.getElementById('kpi-satisfaction').textContent = (summary.customer_satisfaction || 0).toFixed(1);
    }

    function updateCharts(chartData) {
        if (chartData.ticket_volume && charts.volume) {
            charts.volume.data = chartData.ticket_volume;
            charts.volume.update();
        }
        
        if (chartData.resolution_time && charts.resolution) {
            charts.resolution.data = chartData.resolution_time;
            charts.resolution.update();
        }
        
        if (chartData.status_distribution && charts.status) {
            charts.status.data = chartData.status_distribution;
            charts.status.update();
        }
    }

    function updateAlertCards(summary) {
        document.getElementById('overdue-tickets').textContent = (summary.overdue_tickets || 0).toLocaleString();
        document.getElementById('high-priority-tickets').textContent = (summary.high_priority_tickets || 0).toLocaleString();
        document.getElementById('unassigned-tickets').textContent = (summary.unassigned_tickets || 0).toLocaleString();
        document.getElementById('resolved-today').textContent = (summary.resolved_today || 0).toLocaleString();
    }

    function setupExportHandlers() {
        document.getElementById('export-dropdown-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('export-dropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', function() {
            document.getElementById('export-dropdown').classList.add('hidden');
        });

        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const format = this.dataset.format;
                exportTicketReport(format);
                document.getElementById('export-dropdown').classList.add('hidden');
            });
        });
    }

    function exportTicketReport(format) {
        // Export functionality implementation
        console.log('Exporting ticket report in', format, 'format');
    }

    function showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    }

    function showError(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            document.body.removeChild(notification);
        }, 5000);
    }

    function updateLastUpdated() {
        document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
    }
});
</script>
@endpush