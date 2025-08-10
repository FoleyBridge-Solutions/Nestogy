@extends('layouts.app')

@section('title', 'Contract Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0 text-gray-800">Contract Analytics Dashboard</h1>
            <p class="text-muted">Comprehensive insights into contract performance, revenue, and business metrics</p>
        </div>
        <div class="col-md-4 text-right">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#filtersModal">
                    <i class="fas fa-filter"></i> Filters
                </button>
                <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#exportModal">
                    <i class="fas fa-download"></i> Export
                </button>
                <button type="button" class="btn btn-primary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Contract Value</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($analytics['overview_metrics']['total_contract_value'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Contracts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $analytics['overview_metrics']['active_contracts'] ?? 0 }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-contract fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Monthly Recurring Revenue</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ${{ number_format($analytics['overview_metrics']['monthly_recurring_revenue'] ?? 0, 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Contract Win Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($analytics['overview_metrics']['contract_win_rate'] ?? 0, 1) }}%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Revenue Trend Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Trends</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" onclick="downloadChart('revenueChart')">Download Chart</a>
                            <a class="dropdown-item" href="#" onclick="toggleChartType('revenueChart')">Toggle View</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Status Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Contract Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="contractPieChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-2">
                            <i class="fas fa-circle text-success"></i> Active
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-info"></i> Draft
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-warning"></i> Pending
                        </span>
                        <span class="mr-2">
                            <i class="fas fa-circle text-danger"></i> Terminated
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Performance Metrics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-success">
                                    {{ number_format($analytics['performance_metrics']['contract_completion_rate'] ?? 0, 1) }}%
                                </div>
                                <div class="text-xs text-gray-600">Completion Rate</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-info">
                                    {{ number_format($analytics['performance_metrics']['average_contract_duration'] ?? 0) }}
                                </div>
                                <div class="text-xs text-gray-600">Avg Duration (Days)</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-warning">
                                    {{ $analytics['performance_metrics']['milestone_performance']['on_time_completion_rate'] ?? 0 }}%
                                </div>
                                <div class="text-xs text-gray-600">On-Time Milestones</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-primary">
                                    {{ $analytics['performance_metrics']['renewal_rates']['annual_rate'] ?? 0 }}%
                                </div>
                                <div class="text-xs text-gray-600">Renewal Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Risk Analytics -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Risk Analytics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-danger">
                                    {{ $analytics['risk_analytics']['contracts_at_risk'] ?? 0 }}
                                </div>
                                <div class="text-xs text-gray-600">Contracts at Risk</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-success">
                                    {{ $analytics['risk_analytics']['compliance_risk_score'] ?? 0 }}
                                </div>
                                <div class="text-xs text-gray-600">Compliance Score</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-warning">
                                    ${{ number_format($analytics['risk_analytics']['overdue_payments_value'] ?? 0, 0) }}
                                </div>
                                <div class="text-xs text-gray-600">Overdue Payments</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 font-weight-bold text-info">
                                    {{ $analytics['risk_analytics']['overdue_milestones'] ?? 0 }}
                                </div>
                                <div class="text-xs text-gray-600">Overdue Milestones</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Analytics -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Clients by Contract Value</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Active Contracts</th>
                                    <th>Total Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($analytics['client_analytics']['top_clients_by_value'] ?? [] as $client)
                                <tr>
                                    <td>
                                        <div class="font-weight-bold">{{ $client['name'] }}</div>
                                        <div class="text-muted small">{{ $client['email'] }}</div>
                                    </td>
                                    <td>{{ $client['active_contracts'] }}</td>
                                    <td>${{ number_format($client['total_value'], 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $client['status'] == 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($client['status']) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No client data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Revenue Forecast</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="forecastChart" style="height: 200px;"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="small text-muted">
                            <strong>Next 12 Months Projection:</strong><br>
                            ${{ number_format($analytics['forecasting']['total_projected_annual'] ?? 0, 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Contract Lifecycle Analytics</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-primary">
                                    {{ $analytics['contract_lifecycle']['average_time_to_signature'] ?? 0 }} days
                                </div>
                                <div class="text-xs text-gray-600">Avg Time to Signature</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-success">
                                    {{ $analytics['contract_lifecycle']['average_approval_time'] ?? 0 }} days
                                </div>
                                <div class="text-xs text-gray-600">Avg Approval Time</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-info">
                                    {{ count($analytics['contract_lifecycle']['process_bottlenecks'] ?? []) }}
                                </div>
                                <div class="text-xs text-gray-600">Process Bottlenecks</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h5 font-weight-bold text-warning">
                                    {{ count($analytics['contract_lifecycle']['stages_distribution'] ?? []) }}
                                </div>
                                <div class="text-xs text-gray-600">Active Stages</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Modal -->
<div class="modal fade" id="filtersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Analytics Filters</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="filtersForm" method="GET">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" name="start_date" 
                                       value="{{ $filters['start_date'] ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" name="end_date" 
                                       value="{{ $filters['end_date'] ?? '' }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contract_type">Contract Type</label>
                                <select class="form-control" name="contract_type">
                                    <option value="">All Types</option>
                                    <option value="service_agreement" {{ ($filters['contract_type'] ?? '') == 'service_agreement' ? 'selected' : '' }}>Service Agreement</option>
                                    <option value="maintenance_contract" {{ ($filters['contract_type'] ?? '') == 'maintenance_contract' ? 'selected' : '' }}>Maintenance Contract</option>
                                    <option value="subscription" {{ ($filters['contract_type'] ?? '') == 'subscription' ? 'selected' : '' }}>Subscription</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="draft" {{ ($filters['status'] ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="pending_approval" {{ ($filters['status'] ?? '') == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="completed" {{ ($filters['status'] ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Analytics Report</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('analytics.export') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="report_type">Report Type</label>
                        <select class="form-control" name="report_type" required>
                            <option value="overview">Overview Report</option>
                            <option value="revenue">Revenue Analysis</option>
                            <option value="performance">Performance Metrics</option>
                            <option value="client">Client Analytics</option>
                            <option value="forecast">Revenue Forecast</option>
                            <option value="risk">Risk Analysis</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="format">Export Format</label>
                        <select class="form-control" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart data from backend
const analyticsData = @json($analytics);

// Revenue Trend Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: analyticsData.revenue_analytics?.monthly_breakdown?.labels || [],
        datasets: [{
            label: 'Revenue',
            data: analyticsData.revenue_analytics?.monthly_breakdown?.data || [],
            borderColor: 'rgb(78, 115, 223)',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
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

// Contract Distribution Pie Chart
const pieCtx = document.getElementById('contractPieChart').getContext('2d');
const contractPieChart = new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Draft', 'Pending', 'Terminated'],
        datasets: [{
            data: [
                analyticsData.overview_metrics?.active_contracts || 0,
                analyticsData.overview_metrics?.draft_contracts || 0,
                analyticsData.overview_metrics?.pending_contracts || 0,
                analyticsData.overview_metrics?.terminated_contracts || 0
            ],
            backgroundColor: ['#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Revenue Forecast Chart
const forecastCtx = document.getElementById('forecastChart').getContext('2d');
const forecastChart = new Chart(forecastCtx, {
    type: 'bar',
    data: {
        labels: analyticsData.forecasting?.forecast?.map(f => f.month) || [],
        datasets: [{
            label: 'Projected Revenue',
            data: analyticsData.forecasting?.forecast?.map(f => f.projected_revenue) || [],
            backgroundColor: 'rgba(28, 200, 138, 0.8)'
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
                        return '$' + (value/1000).toFixed(0) + 'K';
                    }
                }
            }
        }
    }
});

// Helper functions
function refreshDashboard() {
    location.reload();
}

function downloadChart(chartId) {
    const chart = window[chartId];
    const url = chart.toBase64Image();
    const a = document.createElement('a');
    a.href = url;
    a.download = chartId + '.png';
    a.click();
}

function toggleChartType(chartId) {
    const chart = window[chartId];
    chart.config.type = chart.config.type === 'line' ? 'bar' : 'line';
    chart.update();
}
</script>
@endpush

@push('styles')
<style>
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-2px);
}
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.chart-area {
    position: relative;
    height: 320px;
}
.chart-pie {
    position: relative;
    height: 250px;
}
</style>
@endpush