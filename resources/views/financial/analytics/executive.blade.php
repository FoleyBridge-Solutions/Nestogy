@extends('layouts.app')

@section('title', 'Executive Dashboard - Financial Analytics')

@section('content')
<div class="executive-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="flex flex-wrap -mx-4 items-center mb-6">
            <div class="md:w-2/3 px-6">
                <h1 class="dashboard-title">
                    <i class="fas fa-chart-line mr-3 text-blue-600"></i>
                    Executive Dashboard
                </h1>
                <p class="dashboard-subtitle text-gray-600">
                    Real-time financial insights and key performance indicators
                </p>
            </div>
            <div class="md:w-1/3 px-6 text-end">
                <div class="dashboard-controls">
                    <div class="px-6 py-2 font-medium rounded-md transition-colors-group mr-2">
                        <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-primary" id="dateRangeBtn">
                            <i class="fas fa-calendar mr-2"></i>
                            <span id="dateRangeText">This Month</span>
                        </button>
                    </div>
                    <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" id="refreshDashboard">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="px-6 py-2 font-medium rounded-md transition-colors-group ml-2">
                        <button type="button" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dropdown-toggle" x-data="{ open: false }" @click="open = !open">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-export="pdf">
                                <i class="fas fa-file-pdf mr-2"></i>PDF Report
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-export="excel">
                                <i class="fas fa-file-excel mr-2"></i>Excel
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-export="csv">
                                <i class="fas fa-file-csv mr-2"></i>CSV Data
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="kpi-section mb-6">
        <div class="flex flex-wrap -mx-4" id="kpiCards">
            <!-- KPI cards will be dynamically loaded -->
            <div class="flex-1 px-6-xl-3 flex-1 px-6-md-6 mb-6">
                <div class="bg-white rounded-lg shadow-md overflow-hidden kpi-bg-white rounded-lg shadow-md overflow-hidden" id="totalRevenueCard">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-subtitle mb-1 text-gray-600">Total Revenue</h6>
                                <h3 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0" id="totalRevenueValue">
                                    <div class="skeleton-loader"></div>
                                </h3>
                                <small class="text-green-600" id="totalRevenueGrowth">
                                    <div class="skeleton-loader"></div>
                                </small>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-6-xl-3 flex-1 px-6-md-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden kpi-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden" id="mrrCard">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-subtitle mb-1 text-gray-600 dark:text-gray-400">Monthly Recurring Revenue</h6>
                                <h3 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0" id="mrrValue">
                                    <div class="skeleton-loader"></div>
                                </h3>
                                <small class="text-blue-600" id="mrrGrowth">
                                    <div class="skeleton-loader"></div>
                                </small>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-repeat"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-6-xl-3 flex-1 px-6-md-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden kpi-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden" id="newCustomersCard">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div class="flex justify-between items-center">
                            <div>
                                <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-subtitle mb-1 text-gray-600 dark:text-gray-400">New Customers</h6>
                                <h3 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0" id="newCustomersValue">
                                    <div class="skeleton-loader"></div>
                                </h3>
                                <small class="text-cyan-600 dark:text-cyan-400" id="newCustomersGrowth">
                                    <div class="skeleton-loader"></div>
                                </small>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 px-6-xl-3 flex-1 px-6-md-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden kpi-bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden" id="churnRateCard">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div class="flex justify-between items-center">
                            <div>
                                <h6 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-subtitle mb-1 text-gray-600 dark:text-gray-400">Churn Rate</h6>
                                <h3 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0" id="churnRateValue">
                                    <div class="skeleton-loader"></div>
                                </h3>
                                <small class="text-yellow-600 dark:text-yellow-400" id="churnRateGrowth">
                                    <div class="skeleton-loader"></div>
                                </small>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-user-minus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <div class="flex flex-wrap -mx-4">
            <!-- Revenue Trends Chart -->
            <div class="flex-1 px-6-xl-8 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-6 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-chart-area mr-2 text-blue-600 dark:text-blue-400"></i>
                            Revenue Trends
                        </h5>
                        <div class="chart-controls">
                            <div class="px-6 py-2 font-medium rounded-md transition-colors-group px-6 py-2 font-medium rounded-md transition-colors-group-sm">
                                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary active" data-period="12">12M</button>
                                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" data-period="6">6M</button>
                                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" data-period="3">3M</button>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <canvas id="revenueTrendsChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Cash Flow Summary -->
            <div class="flex-1 px-6-xl-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-6 border-b border-gray-200 bg-gray-50">
                        <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-water mr-2 text-cyan-600 dark:text-cyan-400"></i>
                            Cash Flow Summary
                        </h5>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div class="cash-flow-item mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">Opening Balance</span>
                                <span class="font-bold" id="openingBalance">$0.00</span>
                            </div>
                        </div>
                        <div class="cash-flow-item mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-green-600">
                                    <i class="fas fa-plus-circle mr-1"></i>Total Inflow
                                </span>
                                <span class="font-bold text-green-600 dark:text-green-400" id="totalInflow">$0.00</span>
                            </div>
                        </div>
                        <div class="cash-flow-item mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-red-600">
                                    <i class="fas fa-minus-circle mr-1"></i>Total Outflow
                                </span>
                                <span class="font-bold text-red-600" id="totalOutflow">$0.00</span>
                            </div>
                        </div>
                        <hr>
                        <div class="cash-flow-item">
                            <div class="flex justify-between items-center">
                                <span class="font-bold">Net Cash Flow</span>
                                <span class="font-bold fs-5" id="netCashFlow">$0.00</span>
                            </div>
                        </div>
                        <div class="mt-6">
                            <small class="text-gray-600 dark:text-gray-400">30-day projection: </small>
                            <small id="cashProjection" class="font-bold">$0.00</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap -mx-4">
            <!-- Customer Metrics -->
            <div class="flex-1 px-6-xl-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                        <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-users mr-2 text-green-600 dark:text-green-400"></i>
                            Customer Metrics
                        </h5>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div class="metric-item mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="metric-label">Total Customers</span>
                                <span class="metric-value" id="totalCustomers">0</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-blue-600" style="width: 100%"></div>
                            </div>
                        </div>

                        <div class="metric-item mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="metric-label">Customer Lifetime Value</span>
                                <span class="metric-value" id="averageClv">$0.00</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: 85%"></div>
                            </div>
                        </div>

                        <div class="metric-item mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="metric-label">Net Revenue Retention</span>
                                <span class="metric-value" id="nrr">0%</span>
                            </div>
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-info" style="width: 92%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Alerts -->
            <div class="flex-1 px-6-xl-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                        <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-exclamation-triangle mr-2 text-yellow-600 dark:text-yellow-400"></i>
                            Performance Alerts
                        </h5>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div id="performanceAlerts">
                            <div class="px-6 py-6 rounded bg-cyan-100 border border-cyan-400 text-cyan-700 px-6 py-6 rounded mb-6-sm mb-2">
                                <i class="fas fa-info-circle mr-2"></i>
                                <small>All systems operating normally</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="flex-1 px-6-xl-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                        <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                            <i class="fas fa-bolt mr-2 text-blue-600 dark:text-blue-400"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                        <div class="grid gap-2">
                            <a href="{{ route('financial.invoices.create') }}" class="px-6 py-2 font-medium rounded-md transition-colors border border-blue-600 text-blue-600 hover:bg-blue-50 px-6 py-2 font-medium rounded-md transition-colors-sm">
                                <i class="fas fa-plus mr-2"></i>Create Invoice
                            </a>
                            <a href="{{ route('financial.quotes.index') }}" class="px-6 py-2 font-medium rounded-md transition-colors border border-green-600 text-green-600 hover:bg-green-50 px-6 py-2 font-medium rounded-md transition-colors-sm">
                                <i class="fas fa-file-contract mr-2"></i>View Quotes Pipeline
                            </a>
                            <a href="{{ route('financial.reports.index') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-info px-6 py-2 font-medium rounded-md transition-colors-sm">
                                <i class="fas fa-chart-bar mr-2"></i>Generate Report
                            </a>
                            <a href="{{ route('financial.analytics.forecasting') }}" class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-warning px-6 py-2 font-medium rounded-md transition-colors-sm">
                                <i class="fas fa-crystal-ball mr-2"></i>Cash Flow Forecast
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header">
                    <h5 class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-title mb-0">
                        <i class="fas fa-clock mr-2 text-gray-600 dark:text-gray-400"></i>
                        Recent Financial Activity
                    </h5>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="min-w-full divide-y divide-gray-200-responsive">
                        <table class="min-w-full divide-y divide-gray-200 [&>tbody>tr:hover]:bg-gray-100" id="recentActivityTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Activity</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Picker Modal -->
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="dateRangeModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Select Date Range</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-body">
                <div class="flex flex-wrap -mx-4">
                    <div class="flex-1 px-6-md-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="startDate">
                    </div>
                    <div class="flex-1 px-6-md-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" class="block w-full px-6 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="endDate">
                    </div>
                </div>
                <div class="flex flex-wrap -mx-4 mt-6">
                    <div class="flex-1 px-6-12">
                        <div class="px-6 py-2 font-medium rounded-md transition-colors-group w-100">
                            <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" data-preset="thisMonth">This Month</button>
                            <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" data-preset="lastMonth">Last Month</button>
                            <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" data-preset="thisQuarter">This Quarter</button>
                            <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors px-6 py-2 font-medium rounded-md transition-colors-outline-secondary" data-preset="thisYear">This Year</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                <button type="button" class="inline-flex items-center px-6 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Cancel</button>
                <button type="button" class="inline-flex items-center px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="applyDateRange">Apply</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<style>
.executive-dashboard {
    padding: 0;
}

.dashboard-title {
    font-size: 2rem;
    font-weight: 600;
    color: #2c3e50;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    margin-bottom: 0;
}

.kpi-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease-in-out;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.kpi-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.skeleton-loader {
    height: 1rem;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.metric-item {
    position: relative;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.metric-value {
    font-weight: 600;
    color: #2c3e50;
}

.cash-flow-item {
    padding: 0.5rem 0;
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

.chart-controls . .btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}

.dashboard-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .dashboard-controls {
        margin-top: 1rem;
        justify-content: center;
    }
    
    .kpi-card .card-body {
        padding: 1rem;
    }
    
    .kpi-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
class ExecutiveDashboard {
    constructor() {
        this.currentDateRange = {
            start: moment().startOf('month').format('YYYY-MM-DD'),
            end: moment().endOf('month').format('YYYY-MM-DD')
        };
        this.charts = {};
        this.refreshInterval = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDashboardData();
        this.setupAutoRefresh();
    }

    setupEventListeners() {
        // Refresh button
        document.getElementById('refreshDashboard').addEventListener('click', () => {
            this.loadDashboardData(true);
        });

        // Date range picker
        document.getElementById('dateRangeBtn').addEventListener('click', () => {
            new bootstrap.Modal(document.getElementById('dateRangeModal')).show();
        });

        // Apply date range
        document.getElementById('applyDateRange').addEventListener('click', () => {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                this.currentDateRange = { start: startDate, end: endDate };
                this.updateDateRangeDisplay();
                this.loadDashboardData();
                bootstrap.Modal.getInstance(document.getElementById('dateRangeModal')).hide();
            }
        });

        // Export buttons
        document.querySelectorAll('[data-export]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportData(e.target.dataset.export);
            });
        });

        // Chart period controls
        document.querySelectorAll('[data-period]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('[data-period]').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.updateRevenueTrends(parseInt(e.target.dataset.period));
            });
        });
    }

    async loadDashboardData(refresh = false) {
        try {
            this.showLoadingState();

            const response = await fetch(`/api/financial/analytics/executive-dashboard?${new URLSearchParams({
                start_date: this.currentDateRange.start,
                end_date: this.currentDateRange.end,
                refresh: refresh
            })}`);

            if (!response.ok) throw new Error('Failed to load dashboard data');

            const data = await response.json();
            
            if (data.success) {
                this.renderKPIs(data.data.kpis);
                this.renderRevenueTrends(data.data.revenue_trends);
                this.renderCashFlowSummary(data.data.cash_flow_summary);
                this.renderCustomerMetrics(data.data.customer_metrics);
                this.renderPerformanceAlerts(data.data.performance_alerts);
                this.hideLoadingState();
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            this.showErrorState();
        }
    }

    renderKPIs(kpis) {
        // Total Revenue
        document.getElementById('totalRevenueValue').innerHTML = this.formatCurrency(kpis.total_revenue.value);
        document.getElementById('totalRevenueGrowth').innerHTML = 
            `<i class="fas fa-arrow-${kpis.total_revenue.growth_percentage > 0 ? 'up' : 'down'}"></i> 
             ${Math.abs(kpis.total_revenue.growth_percentage)}% vs last period`;
        document.getElementById('totalRevenueGrowth').className = 
            `text-${kpis.total_revenue.growth_percentage > 0 ? 'success' : 'danger'}`;

        // MRR
        document.getElementById('mrrValue').innerHTML = this.formatCurrency(kpis.mrr.value);
        document.getElementById('mrrGrowth').innerHTML = 
            `<i class="fas fa-arrow-${kpis.mrr.growth_percentage > 0 ? 'up' : 'down'}"></i> 
             ${Math.abs(kpis.mrr.growth_percentage)}%`;

        // New Customers
        document.getElementById('newCustomersValue').innerHTML = kpis.new_customers.value;
        document.getElementById('newCustomersGrowth').innerHTML = 
            `<i class="fas fa-arrow-${kpis.new_customers.growth_percentage > 0 ? 'up' : 'down'}"></i> 
             ${Math.abs(kpis.new_customers.growth_percentage)}%`;

        // Churn Rate
        document.getElementById('churnRateValue').innerHTML = kpis.churn_rate.value + '%';
        document.getElementById('churnRateGrowth').innerHTML = 
            `<i class="fas fa-arrow-${kpis.churn_rate.growth_percentage < 0 ? 'down' : 'up'}"></i> 
             ${Math.abs(kpis.churn_rate.growth_percentage)}%`;
        document.getElementById('churnRateGrowth').className = 
            `text-${kpis.churn_rate.growth_percentage < 0 ? 'success' : 'warning'}`;
    }

    renderRevenueTrends(trends) {
        const ctx = document.getElementById('revenueTrendsChart');
        
        if (this.charts.revenueTrends) {
            this.charts.revenueTrends.destroy();
        }

        this.charts.revenueTrends = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trends.map(t => moment(t.period).format('MMM YYYY')),
                datasets: [{
                    label: 'Revenue',
                    data: trends.map(t => t.revenue),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
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
                            callback: (value) => this.formatCurrency(value, true)
                        }
                    }
                }
            }
        });
    }

    renderCashFlowSummary(cashFlow) {
        document.getElementById('openingBalance').textContent = this.formatCurrency(cashFlow.opening_balance);
        document.getElementById('totalInflow').textContent = this.formatCurrency(cashFlow.total_inflow);
        document.getElementById('totalOutflow').textContent = this.formatCurrency(cashFlow.total_outflow);
        document.getElementById('netCashFlow').textContent = this.formatCurrency(cashFlow.net_change);
        document.getElementById('cashProjection').textContent = this.formatCurrency(cashFlow.projection_30d);
    }

    renderCustomerMetrics(metrics) {
        document.getElementById('totalCustomers').textContent = metrics.total_customers.toLocaleString();
        document.getElementById('averageClv').textContent = this.formatCurrency(metrics.customer_lifetime_value);
        document.getElementById('nrr').textContent = metrics.net_revenue_retention.toFixed(1) + '%';
    }

    renderPerformanceAlerts(alerts) {
        const container = document.getElementById('performanceAlerts');
        
        if (alerts.length === 0) {
            container.innerHTML = '<div class="px-6 py-6 rounded bg-green-100 border border-green-400 text-green-700 px-6 py-6 rounded mb-6-sm mb-2"><i class="fas fa-check-circle mr-2"></i><small>All systems operating normally</small></div>';
        } else {
            container.innerHTML = alerts.map(alert => 
                `<flux:callout  class="py-6 rounded mb-6.type} px-6 py-6 rounded mb-6-sm mb-2">
                    <i class="fas fa-${px-6 py-6 rounded mb-6.icon} mr-2"></i>
                    <small>${alert.message}</small>
                </flux:callout>`
            ).join('');
        }
    }

    showLoadingState() {
        document.querySelectorAll('.skeleton-loader').forEach(el => {
            el.style.display = 'block';
        });
    }

    hideLoadingState() {
        document.querySelectorAll('.skeleton-loader').forEach(el => {
            el.style.display = 'none';
        });
    }

    showErrorState() {
        // Implement error state UI
        console.log('Showing error state');
    }

    formatCurrency(amount, compact = false) {
        if (compact && Math.abs(amount) >= 1000000) {
            return '$' + (amount / 1000000).toFixed(1) + 'M';
        } else if (compact && Math.abs(amount) >= 1000) {
            return '$' + (amount / 1000).toFixed(1) + 'K';
        }
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    updateDateRangeDisplay() {
        const start = moment(this.currentDateRange.start);
        const end = moment(this.currentDateRange.end);
        document.getElementById('dateRangeText').textContent = 
            start.format('MMM D') + ' - ' + end.format('MMM D, YYYY');
    }

    setupAutoRefresh() {
        // Refresh every 5 minutes
        this.refreshInterval = setInterval(() => {
            this.loadDashboardData();
        }, 5 * 60 * 1000);
    }

    async exportData(format) {
        try {
            const response = await fetch('/api/financial/analytics/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    dashboard_type: 'executive',
                    format: format,
                    start_date: this.currentDateRange.start,
                    end_date: this.currentDateRange.end
                })
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.data.download_url) {
                    // Create download link
                    const link = document.createElement('a');
                    link.href = data.data.download_url;
                    link.download = `executive-dashboard-${format}-${moment().format('YYYY-MM-DD')}.${format}`;
                    link.click();
                }
            }
        } catch (error) {
            console.error('Error exporting data:', error);
            alert('Failed to export data. Please try again.');
        }
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ExecutiveDashboard();
});
</script>
@endpush
