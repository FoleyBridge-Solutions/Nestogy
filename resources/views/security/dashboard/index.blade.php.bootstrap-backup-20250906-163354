@extends('layouts.app')

@section('title', 'Security Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Security Dashboard</h1>
            <p class="text-muted">Monitor security events, suspicious activities, and IP intelligence</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select" id="timeRangeSelector" onchange="updateTimeRange(this.value)">
                <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
            <button class="btn btn-outline-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Security Overview Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Logins
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['total_login_attempts']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Suspicious Attempts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['suspicious_attempts']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Blocked Attempts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['blocked_attempts']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Trusted Devices
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['trusted_devices']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Unique IPs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['unique_ips']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                High Risk IPs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['high_risk_ips']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-skull-crossbones fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="row">
        <!-- Suspicious Login Attempts -->
        <div class="col-xl-6 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Suspicious Login Attempts</h6>
                    <a href="{{ route('security.dashboard.suspicious-logins') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if($dashboardData['suspicious_logins']['recent_attempts']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Location</th>
                                        <th>Risk</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dashboardData['suspicious_logins']['recent_attempts'] as $attempt)
                                    <tr>
                                        <td>{{ $attempt->user->name ?? 'Unknown' }}</td>
                                        <td>{{ $attempt->getLocationString() }}</td>
                                        <td>
                                            <span class="badge badge-{{ $attempt->getRiskLevelColor() }}">
                                                {{ $attempt->getRiskLevelString() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $attempt->status === 'approved' ? 'success' : ($attempt->status === 'denied' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($attempt->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $attempt->created_at->diffForHumans() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                            <p class="text-muted">No suspicious login attempts detected</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Threat Level Distribution -->
        <div class="col-xl-6 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">IP Threat Level Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="threatLevelChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Countries -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Top Countries by Login Activity</h6>
                    <a href="{{ route('security.dashboard.ip-intelligence') }}" class="btn btn-sm btn-outline-primary">
                        View Details
                    </a>
                </div>
                <div class="card-body">
                    @if($dashboardData['ip_intelligence']['top_countries']->count() > 0)
                        @foreach($dashboardData['ip_intelligence']['top_countries'] as $country)
                        <div class="d-flex align-items-center mb-3">
                            <div class="mr-3">
                                <span class="flag-icon flag-icon-{{ strtolower($country->country_code) }}"></span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ $country->country ?? 'Unknown' }}</h6>
                                <small class="text-muted">{{ $country->count }} logins</small>
                            </div>
                            <div class="progress" style="width: 100px; height: 8px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ ($country->count / $dashboardData['ip_intelligence']['top_countries']->max('count')) * 100 }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No geographic data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Security Activity -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Security Events</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    @if($dashboardData['recent_activity']->count() > 0)
                        @foreach($dashboardData['recent_activity']->take(10) as $activity)
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="mr-3">
                                <i class="fas fa-{{ $activity->severity === 'critical' ? 'exclamation-circle text-danger' : ($activity->severity === 'warning' ? 'exclamation-triangle text-warning' : 'info-circle text-info') }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $activity->action }}</h6>
                                <small class="text-muted">
                                    {{ $activity->created_at->diffForHumans() }}
                                    @if($activity->user)
                                        by {{ $activity->user->name }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent security events</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('security.dashboard.suspicious-logins') }}" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-exclamation-triangle"></i>
                                Manage Suspicious Logins
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('security.dashboard.ip-intelligence') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-globe"></i>
                                IP Intelligence
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('security.dashboard.trusted-devices') }}" class="btn btn-outline-success btn-block">
                                <i class="fas fa-shield-alt"></i>
                                Trusted Devices
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-danger btn-block" onclick="showBlockIpModal()">
                                <i class="fas fa-ban"></i>
                                Block IP Address
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Block IP Modal -->
<div class="modal fade" id="blockIpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Block IP Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('security.dashboard.block-ip') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" required>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for blocking</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Block IP</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Threat Level Chart
const threatData = @json($dashboardData['ip_intelligence']['threat_levels']);
const ctx = document.getElementById('threatLevelChart').getContext('2d');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Low', 'Medium', 'High', 'Critical'],
        datasets: [{
            data: [
                threatData.low || 0,
                threatData.medium || 0,
                threatData.high || 0,
                threatData.critical || 0
            ],
            backgroundColor: [
                '#28a745',
                '#ffc107',
                '#fd7e14',
                '#dc3545'
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

function updateTimeRange(days) {
    window.location.href = `{{ route('security.dashboard.index') }}?days=${days}`;
}

function refreshDashboard() {
    window.location.reload();
}

function showBlockIpModal() {
    new bootstrap.Modal(document.getElementById('blockIpModal')).show();
}
</script>
@endpush