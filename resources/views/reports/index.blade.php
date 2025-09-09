@extends('layouts.app')

@section('title', 'Reports Dashboard')

@section('page-actions')
<div class="flex items-center space-x-4">
    <div class="flex items-center space-x-2">
        <label for="date-preset" class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Period:</label>
        <select id="date-preset" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 text-sm">
            <option value="last_7_days">Last 7 days</option>
            <option value="last_30_days" selected>Last 30 days</option>
            <option value="last_90_days">Last 90 days</option>
            <option value="this_month">This month</option>
            <option value="last_month">Last month</option>
            <option value="this_quarter">This quarter</option>
            <option value="this_year">This year</option>
            <option value="custom">Custom range</option>
        </select>
    </div>
    
    <div id="custom-date-range" class="hidden flex items-center space-x-2">
        <input type="date" id="start-date" class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 text-sm">
        <span class="text-gray-500">to</span>
        <input type="date" id="end-date" class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 text-sm">
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
        <div id="export-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-md shadow-lg z-10">
            <div class="py-1">
                <button class="export-btn block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800 w-full text-left" data-format="pdf">
                    <i class="fas fa-file-pdf mr-2 text-red-500"></i>PDF Report
                </button>
                <button class="export-btn block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800 w-full text-left" data-format="xlsx">
                    <i class="fas fa-file-excel mr-2 text-green-500"></i>Excel Report
                </button>
                <button class="export-btn block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700 dark:bg-gray-800 w-full text-left" data-format="csv">
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
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg p-6 shadow-xl">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mr-4"></div>
            <span class="text-gray-700 dark:text-gray-300 dark:text-gray-300">Loading reports data...</span>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">Reports Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-2">Comprehensive business intelligence and analytics across all domains</p>
    
    <div id="last-updated" class="text-sm text-gray-500 mt-1"></div>
</div>

<!-- System Alerts -->
<div id="system-alerts" class="mb-6"></div>

<!-- Key Performance Indicators -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Revenue</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-revenue" class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">$0</span>
                    <span id="kpi-revenue-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
        </div>
        <div id="kpi-revenue-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Active Tickets</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-tickets" class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">0</span>
                    <span id="kpi-tickets-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-ticket-alt text-blue-600"></i>
            </div>
        </div>
        <div id="kpi-tickets-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">SLA Compliance</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-sla" class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">0%</span>
                    <span id="kpi-sla-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-yellow-100 rounded-full">
                <i class="fas fa-clock text-yellow-600"></i>
            </div>
        </div>
        <div id="kpi-sla-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Active Projects</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-projects" class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">0</span>
                    <span id="kpi-projects-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-project-diagram text-purple-600"></i>
            </div>
        </div>
        <div id="kpi-projects-change" class="mt-4 text-sm"></div>
    </div>
</div>

<!-- Main Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Revenue Trend Chart -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white">Revenue Trend</h3>
            <div class="flex items-center space-x-2">
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md bg-indigo-100 text-indigo-700" data-chart="revenue" data-period="7d">7D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="revenue" data-period="30d">30D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="revenue" data-period="90d">90D</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="revenue-trend-chart"></canvas>
        </div>
    </div>

    <!-- Ticket Volume Chart -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white">Ticket Volume</h3>
            <div class="flex items-center space-x-2">
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md bg-indigo-100 text-indigo-700" data-chart="tickets" data-period="7d">7D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="tickets" data-period="30d">30D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="tickets" data-period="90d">90D</button>
            </div>
        </div>
        <div class="h-64">
            <canvas id="ticket-volume-chart"></canvas>
        </div>
    </div>
</div>

<!-- Secondary Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Project Status Distribution -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4">Project Status</h3>
        <div class="h-48">
            <canvas id="project-status-chart"></canvas>
        </div>
    </div>

    <!-- Expense Breakdown -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4">Expense Breakdown</h3>
        <div class="h-48">
            <canvas id="expense-breakdown-chart"></canvas>
        </div>
    </div>

    <!-- User Productivity -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4">Team Productivity</h3>
        <div class="h-48">
            <canvas id="user-productivity-chart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activities & Insights -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Recent Activities -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4">Recent Activities</h3>
        <div id="recent-activities" class="space-y-4 max-h-80 overflow-y-auto">
            <!-- Activities will be loaded dynamically -->
        </div>
    </div>

    <!-- Quick Insights -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4">Quick Insights</h3>
        <div id="quick-insights" class="space-y-4">
            <!-- Insights will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Report Links Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <a href="{{ route('reports.financial') }}" class="group block p-6 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full group-hover:bg-green-200 transition-colors duration-200">
                <i class="fas fa-chart-line text-green-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-indigo-600 transition-colors duration-200">Financial Reports</h4>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-sm">Revenue, expenses, and profit analysis</p>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.tickets') }}" class="group block p-6 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full group-hover:bg-blue-200 transition-colors duration-200">
                <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-indigo-600 transition-colors duration-200">Ticket Analytics</h4>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-sm">SLA compliance and performance metrics</p>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.assets') }}" class="group block p-6 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full group-hover:bg-yellow-200 transition-colors duration-200">
                <i class="fas fa-server text-yellow-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-indigo-600 transition-colors duration-200">Asset Reports</h4>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-sm">Maintenance and warranty tracking</p>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.clients') }}" class="group block p-6 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full group-hover:bg-purple-200 transition-colors duration-200">
                <i class="fas fa-users text-purple-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-indigo-600 transition-colors duration-200">Client Reports</h4>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-sm">Client analytics and satisfaction</p>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.projects') }}" class="group block p-6 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-md transition-shadows duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-indigo-100 rounded-full group-hover:bg-indigo-200 transition-colors duration-200">
                <i class="fas fa-project-diagram text-indigo-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-indigo-600 transition-colors duration-200">Project Reports</h4>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-sm">Progress and resource utilization</p>
            </div>
        </div>
    </a>

    <a href="{{ route('reports.users') }}" class="group block p-6 bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
        <div class="flex items-center">
            <div class="p-3 bg-red-100 rounded-full group-hover:bg-red-200 transition-colors duration-200">
                <i class="fas fa-user-chart text-red-600 text-xl"></i>
            </div>
            <div class="ml-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white group-hover:text-indigo-600 transition-colors duration-200">User Reports</h4>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 text-sm">Performance and productivity analysis</p>
            </div>
        </div>
    </a>
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
    let currentDateRange = {
        preset: 'last_30_days',
        start: null,
        end: null
    };

    // Initialize dashboard
    initializeDashboard();
    loadDashboardData();

    // Event listeners
    setupEventListeners();

    function initializeDashboard() {
        // Initialize all charts
        initializeCharts();
        
        // Set up real-time updates (every 5 minutes)
        setInterval(loadDashboardData, 300000);
    }

    function setupEventListeners() {
        // Date preset changes
        document.getElementById('date-preset').addEventListener('change', function() {
            currentDateRange.preset = this.value;
            
            if (this.value === 'custom') {
                document.getElementById('custom-date-range').classList.remove('hidden');
            } else {
                document.getElementById('custom-date-range').classList.add('hidden');
                loadDashboardData();
            }
        });

        // Custom date range changes
        document.getElementById('start-date').addEventListener('change', function() {
            currentDateRange.start = this.value;
            if (currentDateRange.end) {
                loadDashboardData();
            }
        });

        document.getElementById('end-date').addEventListener('change', function() {
            currentDateRange.end = this.value;
            if (currentDateRange.start) {
                loadDashboardData();
            }
        });

        // Refresh button
        document.getElementById('refresh-data').addEventListener('click', loadDashboardData);

        // Export dropdown
        document.getElementById('export-dropdown-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('export-dropdown').classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('export-dropdown').classList.add('hidden');
        });

        // Export buttons
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const format = this.dataset.format;
                exportDashboard(format);
                document.getElementById('export-dropdown').classList.add('hidden');
            });
        });

        // Chart period buttons
        document.querySelectorAll('.chart-period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const chart = this.dataset.chart;
                const period = this.dataset.period;
                
                // Update active state
                this.parentNode.querySelectorAll('.chart-period-btn').forEach(b => {
                    b.classList.remove('active');
                });
                this.classList.add('active');
                
                // Update chart data
                updateChartPeriod(chart, period);
            });
        });
    }

    function loadDashboardData() {
        showLoading();

        const params = new URLSearchParams({
            preset: currentDateRange.preset
        });

        if (currentDateRange.preset === 'custom') {
            params.append('start_date', currentDateRange.start);
            params.append('end_date', currentDateRange.end);
        }

        // For now, just update the last updated time
        // In production, you would implement the API endpoint or use AJAX to fetch data
        updateLastUpdated();
        hideLoading();
    }

    function initializeCharts() {
        // Revenue Trend Chart
        charts.revenueTrend = new Chart(document.getElementById('revenue-trend-chart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3
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
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Ticket Volume Chart
        charts.ticketVolume = new Chart(document.getElementById('ticket-volume-chart'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tickets',
                    data: [],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
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
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Project Status Chart
        charts.projectStatus = new Chart(document.getElementById('project-status-chart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Completed', 'On Hold', 'Planning'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
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

        // Initialize other charts...
    }

    function updateKPIs(kpis) {
        for (const [key, data] of Object.entries(kpis)) {
            const element = document.getElementById(`kpi-${key.replace('_', '-')}`);
            const trendElement = document.getElementById(`kpi-${key.replace('_', '-')}-trend`);
            const changeElement = document.getElementById(`kpi-${key.replace('_', '-')}-change`);
            
            if (element) {
                element.textContent = formatKPIValue(data.value, data.format);
            }
            
            if (trendElement && data.previous !== undefined) {
                const change = ((data.value - data.previous) / data.previous * 100).toFixed(1);
                const isPositive = change > 0;
                const icon = isPositive ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
                const color = isPositive ? 'text-green-600' : 'text-red-600';
                
                trendElement.innerHTML = `<i class="${icon} ${color}"></i>`;
            }
            
            if (changeElement && data.previous !== undefined) {
                const change = Math.abs(((data.value - data.previous) / data.previous * 100)).toFixed(1);
                const isPositive = data.value > data.previous;
                const direction = isPositive ? 'increase' : 'decrease';
                const color = isPositive ? 'text-green-600' : 'text-red-600';
                
                changeElement.innerHTML = `<span class="${color}">${change}% ${direction}</span> from previous period`;
            }
        }
    }

    function updateCharts(chartData) {
        if (chartData.revenue_trend) {
            charts.revenueTrend.data = chartData.revenue_trend;
            charts.revenueTrend.update();
        }
        
        if (chartData.ticket_volume) {
            charts.ticketVolume.data = chartData.ticket_volume;
            charts.ticketVolume.update();
        }
        
        if (chartData.project_status) {
            charts.projectStatus.data = chartData.project_status;
            charts.projectStatus.update();
        }
    }

    function updateRecentActivities(activities) {
        const container = document.getElementById('recent-activities');
        container.innerHTML = '';
        
        activities.forEach(activity => {
            const item = document.createElement('div');
            item.className = 'flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 rounded-lg';
            item.innerHTML = `
                <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-${activity.icon} text-indigo-600 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white dark:text-white">${activity.title}</p>
                    <p class="text-sm text-gray-500">${activity.description}</p>
                    <p class="text-xs text-gray-400 mt-1">${formatTimestamp(activity.timestamp)}</p>
                </div>
            `;
            container.appendChild(item);
        });
    }

    function updateSystemAlerts(alerts) {
        const container = document.getElementById('system-alerts');
        container.innerHTML = '';
        
        alerts.forEach(alert => {
            const alertClass = alert.type === 'warning' ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : 
                              alert.type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                              'bg-blue-50 border-blue-200 text-blue-800';
            
            const item = document.createElement('div');
            item.className = `border-l-4 p-4 ${alertClass} mb-2`;
            item.innerHTML = `
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium">${alert.title}</h3>
                        <div class="mt-2 text-sm">
                            <p>${alert.message}</p>
                        </div>
                        ${alert.action_url ? `<div class="mt-4"><a href="${alert.action_url}" class="text-sm font-medium underline">View Details</a></div>` : ''}
                    </div>
                </div>
            `;
            container.appendChild(item);
        });
    }

    function formatKPIValue(value, format) {
        switch(format) {
            case 'currency':
                return '$' + value.toLocaleString();
            case 'percentage':
                return value.toFixed(1) + '%';
            case 'hours':
                return value.toFixed(1) + 'h';
            case 'number':
            default:
                return value.toLocaleString();
        }
    }

    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMinutes = Math.floor((now - date) / (1000 * 60));
        
        if (diffMinutes < 1) return 'Just now';
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
        return date.toLocaleDateString();
    }

    function showLoading() {
        document.getElementById('loading-overlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-overlay').classList.add('hidden');
    }

    function showError(message) {
        // Create error notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        notification.textContent = message;
        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 5000);
    }

    function updateLastUpdated() {
        document.getElementById('last-updated').textContent = `Last updated: ${new Date().toLocaleString()}`;
    }

    function exportDashboard(format) {
        showLoading();

        const params = new URLSearchParams({
            report_type: 'dashboard',
            format: format,
            preset: currentDateRange.preset
        });

        if (currentDateRange.preset === 'custom') {
            params.append('start_date', currentDateRange.start);
            params.append('end_date', currentDateRange.end);
        }

        // Create a form to submit the export request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/reports/generate/revenue-summary`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const formatInput = document.createElement('input');
        formatInput.type = 'hidden';
        formatInput.name = 'format';
        formatInput.value = format;
        form.appendChild(formatInput);
        
        document.body.appendChild(form);
        form.submit();
        return;
        
        // Original fetch code (commented out for now)
        /*fetch(`/reports/export/${format}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                report_type: 'dashboard',
                format: format,
                date_range: {
                    start: currentDateRange.start,
                    end: currentDateRange.end
                },
                filters: {}
            })
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dashboard_report_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            hideLoading();
        })
        .catch(error => {
            console.error('Export failed:', error);
            hideLoading();
            showError('Failed to export report');
        });
    }

    function updateChartPeriod(chartType, period) {
        // This would fetch updated data for the specific chart period
        console.log(`Updating ${chartType} chart for ${period} period`);
        
        // In a real implementation, you would:
        // 1. Fetch new data based on the period
        // 2. Update the specific chart
        // For now, we'll just refresh all data
        loadDashboardData();
    }
});
</script>
@endpush
