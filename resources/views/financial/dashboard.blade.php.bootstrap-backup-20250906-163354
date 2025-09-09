@extends('layouts.app')

@section('title', 'Financial Dashboard')

@push('styles')
<style>
    .metric-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .metric-card.success { border-left-color: #28a745; }
    .metric-card.warning { border-left-color: #ffc107; }
    .metric-card.danger { border-left-color: #dc3545; }
    .metric-card.info { border-left-color: #17a2b8; }
    
    .chart-container {
        position: relative;
        height: 300px;
    }
    
    .activity-item {
        border-left: 2px solid #e9ecef;
        padding-left: 1rem;
        position: relative;
    }
    .activity-item::before {
        content: '';
        position: absolute;
        left: -5px;
        top: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #6c757d;
    }
    .activity-item.invoice::before { background: #28a745; }
    .activity-item.quote::before { background: #17a2b8; }
    .activity-item.payment::before { background: #ffc107; }
</style>
@endpush

@section('content')
<div class="w-full px-4 px-4 py-4" x-data="financialDashboard()">
    
    <!-- Header with Actions -->
    <div class="flex justify-between items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Financial Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-0">Overview of your invoices, quotes, and payments</p>
        </div>
        <div class="btn-group">
            <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dropdown-toggle" x-data="{ open: false }" @click="open = !open">
                <i class="fas fa-plus mr-2"></i>Quick Create
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="{{ route('financial.invoices.create') }}">
                    <i class="fas fa-file-invoice mr-2"></i>New Invoice
                </a></li>
                <li><a class="dropdown-item" href="{{ route('financial.quotes.create') }}">
                    <i class="fas fa-file-alt me-2"></i>New Quote
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" @click="showRecurringModal = true">
                    <i class="fas fa-redo me-2"></i>Recurring Invoice
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-0 shadow-sm mb-4">
        <div class="p-6">
            <div class="flex flex-wrap -mx-4 g-3 align-items-end">
                <div class="md:w-1/4 px-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1">Date Range</label>
                    <select class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="dateRange" @change="updateDashboard()">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="md:w-1/4 px-4" x-show="dateRange === 'custom'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1">Start Date</label>
                    <input type="date" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="customStart">
                </div>
                <div class="col-md-3" x-show="dateRange === 'custom'">
                    <label class="form-label">End Date</label>
                    <input type="date" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" x-model="customEnd">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-primary" @click="exportDashboard()">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="flex flex-wrap -mx-4 g-3 mb-4">
        <!-- Total Revenue -->
        <div class="col-md-3">
            <div class="card metric-bg-white dark:bg-gray-800 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden success border-0 shadow-sm">
                <div class="p-6">
                    <div class="flex justify-between align-items-start">
                        <div>
                            <h6 class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-2">Total Revenue</h6>
                            <h3 class="mb-0">${{ number_format($metrics['total_revenue'] ?? 0, 2) }}</h3>
                            <small class="text-green-600">
                                <i class="fas fa-arrow-up"></i> {{ $metrics['revenue_change'] ?? 0 }}%
                            </small>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-full p-3">
                            <i class="fas fa-dollar-sign text-green-600 fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-success" style="width: {{ $metrics['revenue_progress'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Outstanding Amount -->
        <div class="col-md-3">
            <div class="card metric-card warning border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Outstanding</h6>
                            <h3 class="mb-0">${{ number_format($metrics['outstanding'] ?? 0, 2) }}</h3>
                            <small class="text-warning">
                                {{ $metrics['outstanding_count'] ?? 0 }} invoices
                            </small>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-full p-3">
                            <i class="fas fa-clock text-warning fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-warning" style="width: {{ $metrics['outstanding_progress'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Amount -->
        <div class="col-md-3">
            <div class="card metric-card danger border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Overdue</h6>
                            <h3 class="mb-0">${{ number_format($metrics['overdue'] ?? 0, 2) }}</h3>
                            <small class="text-red-600">
                                {{ $metrics['overdue_count'] ?? 0 }} invoices
                            </small>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-exclamation-triangle text-red-600 fa-lg"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-danger" @click="sendOverdueReminders()">
                            Send Reminders
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quote Conversion -->
        <div class="col-md-3">
            <div class="card metric-card info border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Quote Conversion</h6>
                            <h3 class="mb-0">{{ number_format($metrics['conversion_rate'] ?? 0, 1) }}%</h3>
                            <small class="text-info">
                                {{ $metrics['converted_quotes'] ?? 0 }}/{{ $metrics['total_quotes'] ?? 0 }}
                            </small>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-chart-line text-info fa-lg"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-info" style="width: {{ $metrics['conversion_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Revenue Chart -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 bg-white dark:bg-gray-800 dark:bg-gray-800">
                    <div class="d-flex justify-content-between items-center">
                        <h5 class="mb-0">Revenue Trend</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" @click="chartType = 'line'" :class="{'active': chartType === 'line'}">Line</button>
                            <button class="btn btn-outline-secondary" @click="chartType = 'bar'" :class="{'active': chartType === 'bar'}">Bar</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container mx-auto px-4 mx-auto px-4">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 dark:bg-gray-900 bg-white dark:bg-gray-800 dark:bg-gray-800">
                    <h5 class="mb-0">Invoice Status</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-success me-2"></i>Paid</span>
                            <strong>{{ $metrics['paid_count'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-warning me-2"></i>Pending</span>
                            <strong>{{ $metrics['pending_count'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-danger me-2"></i>Overdue</span>
                            <strong>{{ $metrics['overdue_count'] ?? 0 }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-circle text-secondary me-2"></i>Draft</span>
                            <strong>{{ $metrics['draft_count'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row g-3 mb-4">
        <!-- Recent Invoices -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white dark:bg-gray-800 dark:bg-gray-800 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Invoices</h5>
                    <a href="{{ route('financial.invoices.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="min-w-full divide-y divide-gray-200-responsive">
                        <table class="min-w-full divide-y divide-gray-200 [&>tbody>tr:hover]:bg-gray-100 dark:bg-gray-800 dark:bg-gray-800 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentInvoices as $invoice)
                                <tr>
                                    <td class="fw-semibold">{{ $invoice->number }}</td>
                                    <td>{{ $invoice->client->name ?? 'N/A' }}</td>
                                    <td>${{ number_format($invoice->amount, 2) }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($invoice->status) {
                                                'Paid' => 'success',
                                                'Sent' => 'info',
                                                'Overdue' => 'danger',
                                                'Draft' => 'secondary',
                                                default => 'warning'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ $invoice->status }}</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('financial.invoices.show', $invoice) }}" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($invoice->status === 'Draft' || $invoice->status === 'Sent')
                                            <button class="btn btn-outline-success" onclick="recordPayment({{ $invoice->id }})">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">No recent invoices</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Due -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white dark:bg-gray-800 dark:bg-gray-800 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Due</h5>
                    <span class="badge bg-warning">{{ count($upcomingDue) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($upcomingDue as $invoice)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $invoice->client->name ?? 'N/A' }}</h6>
                                    <p class="mb-0 text-muted">Invoice #{{ $invoice->number }}</p>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-1">${{ number_format($invoice->amount, 2) }}</h6>
                                    <small class="text-{{ $invoice->days_until_due <= 3 ? 'danger' : 'warning' }}">
                                        Due in {{ $invoice->days_until_due }} days
                                    </small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="sendReminder({{ $invoice->id }})">
                                    <i class="fas fa-envelope"></i> Remind
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="recordPayment({{ $invoice->id }})">
                                    <i class="fas fa-check"></i> Mark Paid
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="list-group-item text-center py-3 text-muted">
                            No upcoming due invoices
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="row g-3">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white dark:bg-gray-800 dark:bg-gray-800">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        @forelse($recentActivity as $activity)
                        <div class="activity-item {{ $activity->type }} mb-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $activity->description }}</strong>
                                    <p class="mb-0 text-muted">{{ $activity->details }}</p>
                                </div>
                                <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        @empty
                        <p class="text-center text-muted">No recent activity</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recurring Invoice Modal -->
    <div class="modal fade" id="recurringModal" tabindex="-1" x-show="showRecurringModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Recurring Invoice</h5>
                    <button type="button" class="btn-close" @click="showRecurringModal = false"></button>
                </div>
                <div class="modal-body">
                    <!-- Recurring invoice form would go here -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function financialDashboard() {
    return {
        dateRange: 'month',
        customStart: '',
        customEnd: '',
        chartType: 'line',
        showRecurringModal: false,
        revenueChart: null,
        statusChart: null,
        
        init() {
            this.initCharts();
            this.loadDashboardData();
        },
        
        initCharts() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            this.revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels ?? []),
                    datasets: [{
                        label: 'Revenue',
                        data: @json($revenueData ?? []),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1
                    }, {
                        label: 'Quotes',
                        data: @json($quotesData ?? []),
                        borderColor: 'rgb(255, 159, 64)',
                        backgroundColor: 'rgba(255, 159, 64, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
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
            
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            this.statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Pending', 'Overdue', 'Draft'],
                    datasets: [{
                        data: @json($statusData ?? []),
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(108, 117, 125, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },
        
        updateDashboard() {
            // Reload dashboard data based on date range
            window.location.href = `/financial/dashboard?range=${this.dateRange}&start=${this.customStart}&end=${this.customEnd}`;
        },
        
        async loadDashboardData() {
            // Load dashboard data via AJAX
            try {
                const response = await fetch(`/api/financial/dashboard?range=${this.dateRange}`);
                const data = await response.json();
                
                if (data.success) {
                    // Update charts
                    this.updateCharts(data);
                }
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        },
        
        updateCharts(data) {
            // Update revenue chart
            if (this.revenueChart && data.revenueData) {
                this.revenueChart.data.datasets[0].data = data.revenueData;
                this.revenueChart.update();
            }
            
            // Update status chart
            if (this.statusChart && data.statusData) {
                this.statusChart.data.datasets[0].data = data.statusData;
                this.statusChart.update();
            }
        },
        
        async sendOverdueReminders() {
            if (!confirm('Send reminders to all clients with overdue invoices?')) return;
            
            try {
                const response = await fetch('/api/financial/send-overdue-reminders', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    alert(`Reminders sent to ${data.count} clients`);
                }
            } catch (error) {
                console.error('Failed to send reminders:', error);
            }
        },
        
        exportDashboard() {
            window.location.href = `/financial/dashboard/export?range=${this.dateRange}`;
        }
    };
}

function recordPayment(invoiceId) {
    // Open payment modal or redirect to payment page
    window.location.href = `/financial/invoices/${invoiceId}/payment`;
}

function sendReminder(invoiceId) {
    if (confirm('Send payment reminder to client?')) {
        fetch(`/api/financial/invoices/${invoiceId}/remind`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully');
            }
        });
    }
}
</script>
@endpush
@endsection