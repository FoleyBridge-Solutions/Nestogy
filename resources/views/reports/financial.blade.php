@extends('layouts.app')

@section('title', 'Financial Reports')

@section('page-actions')
<div class="flex items-center space-x-4">
    <div class="flex items-center space-x-2">
        <label for="report-type" class="text-sm font-medium text-gray-700 dark:text-gray-300">Type:</label>
        <select id="report-type" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md border-gray-300 dark:border-gray-600 text-sm">
            <option value="overview" selected>Overview</option>
            <option value="revenue">Revenue Analysis</option>
            <option value="expenses">Expense Analysis</option>
            <option value="cash_flow">Cash Flow</option>
            <option value="profit_loss">Profit & Loss</option>
            <option value="invoices">Invoice Reports</option>
            <option value="payments">Payment Analysis</option>
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
            <option value="this_year">This year</option>
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
            <span class="text-gray-700 dark:text-gray-300">Loading financial data...</span>
        </div>
    </div>
</div>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Financial Reports</h1>
    <p class="text-gray-600 dark:text-gray-400 mt-2">Revenue, expenses, profit analysis and financial performance metrics</p>
    
    <div id="last-updated" class="text-sm text-gray-500 mt-1"></div>
</div>

<!-- Financial KPIs -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-revenue" class="text-2xl font-bold text-green-600">$0</span>
                    <span id="kpi-revenue-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-arrow-trend-up text-green-600"></i>
            </div>
        </div>
        <div id="kpi-revenue-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Expenses</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-expenses" class="text-2xl font-bold text-red-600">$0</span>
                    <span id="kpi-expenses-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-red-100 rounded-full">
                <i class="fas fa-arrow-trend-down text-red-600"></i>
            </div>
        </div>
        <div id="kpi-expenses-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Profit</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-profit" class="text-2xl font-bold text-blue-600">$0</span>
                    <span id="kpi-profit-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-chart-line text-blue-600"></i>
            </div>
        </div>
        <div id="kpi-profit-change" class="mt-4 text-sm"></div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Profit Margin</p>
                <div class="flex items-center mt-2">
                    <span id="kpi-margin" class="text-2xl font-bold text-purple-600">0%</span>
                    <span id="kpi-margin-trend" class="ml-2 text-sm"></span>
                </div>
            </div>
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-percentage text-purple-600"></i>
            </div>
        </div>
        <div id="kpi-margin-change" class="mt-4 text-sm"></div>
    </div>
</div>

<!-- Main Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Revenue vs Expenses Trend -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue vs Expenses</h3>
            <div class="flex items-center space-x-2">
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md bg-indigo-100 text-indigo-700" data-chart="revenue-expenses" data-period="30d">30D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="revenue-expenses" data-period="90d">90D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="revenue-expenses" data-period="1y">1Y</button>
            </div>
        </div>
        <div class="h-80">
            <canvas id="revenue-expenses-chart"></canvas>
        </div>
    </div>

    <!-- Profit Margin Trend -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profit Margin Trend</h3>
            <div class="flex items-center space-x-2">
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md bg-indigo-100 text-indigo-700" data-chart="profit-margin" data-period="30d">30D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="profit-margin" data-period="90d">90D</button>
                <button class="chart-period-btn text-sm px-3 py-1 rounded-md text-gray-500" data-chart="profit-margin" data-period="1y">1Y</button>
            </div>
        </div>
        <div class="h-80">
            <canvas id="profit-margin-chart"></canvas>
        </div>
    </div>
</div>

<!-- Secondary Charts -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Expense Categories -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Expense Breakdown</h3>
        <div class="h-64">
            <canvas id="expense-categories-chart"></canvas>
        </div>
    </div>

    <!-- Payment Methods -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Methods</h3>
        <div class="h-64">
            <canvas id="payment-methods-chart"></canvas>
        </div>
    </div>

    <!-- Monthly Comparison -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Performance</h3>
        <div class="h-64">
            <canvas id="monthly-comparison-chart"></canvas>
        </div>
    </div>
</div>

<!-- Financial Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Expenses -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Expenses</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% of Total</th>
                    </tr>
                </thead>
                <tbody id="top-expenses-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200">
                    <!-- Data will be loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Transactions</h3>
        <div id="recent-transactions" class="space-y-4 max-h-96 overflow-y-auto">
            <!-- Transactions will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">Outstanding Invoices</p>
                <p id="outstanding-invoices" class="text-2xl font-bold">$0</p>
            </div>
            <i class="fas fa-file-invoice text-green-200 text-2xl"></i>
        </div>
    </div>

    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">Pending Payments</p>
                <p id="pending-payments" class="text-2xl font-bold">$0</p>
            </div>
            <i class="fas fa-clock text-blue-200 text-2xl"></i>
        </div>
    </div>

    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm">Pending Expenses</p>
                <p id="pending-expenses" class="text-2xl font-bold">0</p>
            </div>
            <i class="fas fa-receipt text-yellow-200 text-2xl"></i>
        </div>
    </div>

    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm">Average Order Value</p>
                <p id="average-order-value" class="text-2xl font-bold">$0</p>
            </div>
            <i class="fas fa-calculator text-purple-200 text-2xl"></i>
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
    loadFinancialData();
    setupEventListeners();

    function setupEventListeners() {
        // Report type changes
        document.getElementById('report-type').addEventListener('change', function() {
            currentConfig.type = this.value;
            loadFinancialData();
        });

        // Date preset changes
        document.getElementById('date-preset').addEventListener('change', function() {
            currentConfig.preset = this.value;
            
            if (this.value === 'custom') {
                document.getElementById('custom-date-range').classList.remove('hidden');
            } else {
                document.getElementById('custom-date-range').classList.add('hidden');
                loadFinancialData();
            }
        });

        // Custom date range
        document.getElementById('start-date').addEventListener('change', function() {
            currentConfig.start = this.value;
            if (currentConfig.end) loadFinancialData();
        });

        document.getElementById('end-date').addEventListener('change', function() {
            currentConfig.end = this.value;
            if (currentConfig.start) loadFinancialData();
        });

        // Refresh button
        document.getElementById('refresh-data').addEventListener('click', loadFinancialData);

        // Export functionality
        setupExportHandlers();
    }

    function loadFinancialData() {
        showLoading();

        const params = new URLSearchParams({
            type: currentConfig.type,
            preset: currentConfig.preset
        });

        if (currentConfig.preset === 'custom') {
            params.append('start_date', currentConfig.start);
            params.append('end_date', currentConfig.end);
        }

        fetch(`{{ route('reports.financial') }}?${params}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            updateKPIs(data.summary);
            updateCharts(data.charts);
            updateTables(data.tables);
            updateSummaryCards(data.summary);
            updateLastUpdated();
            hideLoading();
        })
        .catch(error => {
            console.error('Error loading financial data:', error);
            hideLoading();
            showError('Failed to load financial data');
        });
    }

    function initializeCharts() {
        // Revenue vs Expenses Chart
        const revenueExpensesCtx = document.getElementById('revenue-expenses-chart').getContext('2d');
        charts.revenueExpenses = new Chart(revenueExpensesCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.3
                }, {
                    label: 'Expenses',
                    data: [],
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.3
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
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Profit Margin Chart
        const profitMarginCtx = document.getElementById('profit-margin-chart').getContext('2d');
        charts.profitMargin = new Chart(profitMarginCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Profit Margin %',
                    data: [],
                    borderColor: 'rgb(147, 51, 234)',
                    backgroundColor: 'rgba(147, 51, 234, 0.1)',
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
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        // Expense Categories Chart
        const expenseCategoriesCtx = document.getElementById('expense-categories-chart').getContext('2d');
        charts.expenseCategories = new Chart(expenseCategoriesCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                        '#EC4899', '#14B8A6', '#F97316', '#84CC16', '#6366F1'
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

    function updateKPIs(summary) {
        document.getElementById('kpi-revenue').textContent = '$' + (summary.total_revenue || 0).toLocaleString();
        document.getElementById('kpi-expenses').textContent = '$' + (summary.total_expenses || 0).toLocaleString();
        document.getElementById('kpi-profit').textContent = '$' + (summary.net_profit || 0).toLocaleString();
        document.getElementById('kpi-margin').textContent = (summary.profit_margin || 0).toFixed(1) + '%';
    }

    function updateCharts(chartData) {
        if (chartData.revenue_expenses && charts.revenueExpenses) {
            charts.revenueExpenses.data = chartData.revenue_expenses;
            charts.revenueExpenses.update();
        }
        
        if (chartData.profit_margin && charts.profitMargin) {
            charts.profitMargin.data = chartData.profit_margin;
            charts.profitMargin.update();
        }
        
        if (chartData.expense_categories && charts.expenseCategories) {
            charts.expenseCategories.data = chartData.expense_categories;
            charts.expenseCategories.update();
        }
    }

    function updateTables(tables) {
        // Update top expenses table
        if (tables.top_expenses) {
            const tbody = document.getElementById('top-expenses-table');
            tbody.innerHTML = '';
            
            tables.top_expenses.forEach(expense => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${expense.category}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">$${expense.amount.toLocaleString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${expense.count}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">${expense.percentage.toFixed(1)}%</td>
                `;
                tbody.appendChild(row);
            });
        }

        // Update recent transactions
        if (tables.recent_transactions) {
            const container = document.getElementById('recent-transactions');
            container.innerHTML = '';
            
            tables.recent_transactions.forEach(transaction => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg';
                item.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 w-10 h-10 bg-${transaction.type === 'revenue' ? 'green' : 'red'}-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-${transaction.type === 'revenue' ? 'plus' : 'minus'} text-${transaction.type === 'revenue' ? 'green' : 'red'}-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${transaction.description}</p>
                            <p class="text-xs text-gray-500">${transaction.date}</p>
                        </div>
                    </div>
                    <span class="text-sm font-medium ${transaction.type === 'revenue' ? 'text-green-600' : 'text-red-600'}">
                        ${transaction.type === 'revenue' ? '+' : '-'}$${transaction.amount.toLocaleString()}
                    </span>
                `;
                container.appendChild(item);
            });
        }
    }

    function updateSummaryCards(summary) {
        document.getElementById('outstanding-invoices').textContent = '$' + (summary.outstanding_invoices || 0).toLocaleString();
        document.getElementById('pending-payments').textContent = '$' + (summary.pending_payments || 0).toLocaleString();
        document.getElementById('pending-expenses').textContent = (summary.pending_expenses || 0).toLocaleString();
        document.getElementById('average-order-value').textContent = '$' + (summary.average_order_value || 0).toLocaleString();
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
                exportFinancialReport(format);
                document.getElementById('export-dropdown').classList.add('hidden');
            });
        });
    }

    function exportFinancialReport(format) {
        showLoading();

        const params = new URLSearchParams({
            report_type: 'financial',
            format: format,
            type: currentConfig.type,
            preset: currentConfig.preset
        });

        if (currentConfig.preset === 'custom') {
            params.append('start_date', currentConfig.start);
            params.append('end_date', currentConfig.end);
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
        
        const startDate = document.createElement('input');
        startDate.type = 'hidden';
        startDate.name = 'start_date';
        startDate.value = currentConfig.start;
        form.appendChild(startDate);
        
        const endDate = document.createElement('input');
        endDate.type = 'hidden';
        endDate.name = 'end_date';
        endDate.value = currentConfig.end;
        form.appendChild(endDate);
        
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
                report_type: 'financial',
                format: format,
                date_range: {
                    start: currentConfig.start,
                    end: currentConfig.end
                },
                filters: {
                    type: currentConfig.type
                }
            })
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `financial_report_${currentConfig.type}_${new Date().toISOString().split('T')[0]}.${format}`;
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