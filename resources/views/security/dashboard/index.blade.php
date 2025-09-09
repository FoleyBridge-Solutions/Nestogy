@extends('layouts.app')

@section('title', 'Security Dashboard')

@section('content')
<div class="container mx-auto-fluid">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Security Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Monitor security events, suspicious activities, and IP intelligence</p>
        </div>
        <div class="flex gap-2">
            <select class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="timeRangeSelector" onchange="updateTimeRange(this.value)">
                <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
            <button class="btn px-6 py-2 font-medium rounded-md transition-colors-outline-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Security Overview Cards -->
    <div class="flex flex-wrap -mx-4 mb-6">
        <div class="xl:w-2/12 flex-1 px-6-md-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-l-primary shadow h-100 py-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4 no-gutters items-center">
                        <div class="flex-1 px-6 mr-2">
                            <div class="text-xs font-bold text-blue-600 dark:text-blue-400 text-uppercase mb-1">
                                Total Logins
                            </div>
                            <div class="h5 mb-0 font-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['total_login_attempts']) }}
                            </div>
                        </div>
                        <div class="flex-1 px-6-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:w-2/12 flex-1 px-6-md-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-l-warning shadow h-100 py-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4 no-gutters items-center">
                        <div class="flex-1 px-6 mr-2">
                            <div class="text-xs font-bold text-yellow-600 dark:text-yellow-400 text-uppercase mb-1">
                                Suspicious Attempts
                            </div>
                            <div class="h5 mb-0 font-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['suspicious_attempts']) }}
                            </div>
                        </div>
                        <div class="flex-1 px-6-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:w-2/12 flex-1 px-6-md-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-l-danger shadow h-100 py-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4 no-gutters items-center">
                        <div class="flex-1 px-6 mr-2">
                            <div class="text-xs font-bold text-red-600 dark:text-red-400 text-uppercase mb-1">
                                Blocked Attempts
                            </div>
                            <div class="h5 mb-0 font-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['blocked_attempts']) }}
                            </div>
                        </div>
                        <div class="flex-1 px-6-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:w-2/12 flex-1 px-6-md-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-l-success shadow h-100 py-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4 no-gutters items-center">
                        <div class="flex-1 px-6 mr-2">
                            <div class="text-xs font-bold text-green-600 dark:text-green-400 text-uppercase mb-1">
                                Trusted Devices
                            </div>
                            <div class="h5 mb-0 font-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['trusted_devices']) }}
                            </div>
                        </div>
                        <div class="flex-1 px-6-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:w-2/12 flex-1 px-6-md-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-l-info shadow h-100 py-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4 no-gutters items-center">
                        <div class="flex-1 px-6 mr-2">
                            <div class="text-xs font-bold text-cyan-600 dark:text-cyan-400 text-uppercase mb-1">
                                Unique IPs
                            </div>
                            <div class="h5 mb-0 font-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['unique_ips']) }}
                            </div>
                        </div>
                        <div class="flex-1 px-6-auto">
                            <i class="fas fa-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:w-2/12 flex-1 px-6-md-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden border-l-dark shadow h-100 py-2">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4 no-gutters items-center">
                        <div class="flex-1 px-6 mr-2">
                            <div class="text-xs font-bold text-dark text-uppercase mb-1">
                                High Risk IPs
                            </div>
                            <div class="h5 mb-0 font-bold text-gray-800">
                                {{ number_format($dashboardData['overview']['high_risk_ips']) }}
                            </div>
                        </div>
                        <div class="flex-1 px-6-auto">
                            <i class="fas fa-skull-crossbones fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="flex flex-wrap -mx-4">
        <!-- Suspicious Login Attempts -->
        <div class="xl:w-1/2 flex-1 px-6-lg-7">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden shadow mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header py-6 flex flex-flex flex-wrap -mx-4 items-center justify-between">
                    <h6 class="m-0 font-bold text-blue-600 dark:text-blue-400">Recent Suspicious Login Attempts</h6>
                    <a href="{{ route('security.dashboard.suspicious-logins') }}" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-primary">
                        View All
                    </a>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    @if($dashboardData['suspicious_logins']['recent_attempts']->count() > 0)
                        <div class="min-w-full divide-y divide-gray-200 dark:divide-gray-700-responsive">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 min-w-full divide-y divide-gray-200 dark:divide-gray-700-sm">
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
                                            <span class="badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium-{{ $attempt->getRiskLevelColor() }}">
                                                {{ $attempt->getRiskLevelString() }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium-{{ $attempt->status === 'approved' ? 'success' : ($attempt->status === 'denied' ? 'danger' : 'warning') }}">
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
                        <div class="text-center py-6">
                            <i class="fas fa-shield-alt fa-3x text-green-600 dark:text-green-400 mb-6"></i>
                            <p class="text-gray-600 dark:text-gray-400">No suspicious login attempts detected</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Threat Level Distribution -->
        <div class="xl:w-1/2 flex-1 px-6-lg-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden shadow mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header py-6">
                    <h6 class="m-0 font-bold text-blue-600 dark:text-blue-400">IP Threat Level Distribution</h6>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <canvas id="threatLevelChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap -mx-4">
        <!-- Top Countries -->
        <div class="xl:w-1/2 flex-1 px-6-lg-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden shadow mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header py-6 flex flex-flex flex-wrap -mx-4 items-center justify-between">
                    <h6 class="m-0 font-bold text-blue-600 dark:text-blue-400">Top Countries by Login Activity</h6>
                    <a href="{{ route('security.dashboard.ip-intelligence') }}" class="btn px-4 py-1 text-sm px-6 py-2 font-medium rounded-md transition-colors-outline-primary">
                        View Details
                    </a>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    @if($dashboardData['ip_intelligence']['top_countries']->count() > 0)
                        @foreach($dashboardData['ip_intelligence']['top_countries'] as $country)
                        <div class="flex items-center mb-6">
                            <div class="mr-3">
                                <span class="flag-icon flag-icon-{{ strtolower($country->country_code) }}"></span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ $country->country ?? 'Unknown' }}</h6>
                                <small class="text-gray-600 dark:text-gray-400">{{ $country->count }} logins</small>
                            </div>
                            <div class="progress" style="width: 100px; height: 8px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: {{ ($country->count / $dashboardData['ip_intelligence']['top_countries']->max('count')) * 100 }}%">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-globe fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                            <p class="text-gray-600 dark:text-gray-400">No geographic data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Security Activity -->
        <div class="xl:w-1/2 flex-1 px-6-lg-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden shadow mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header py-6">
                    <h6 class="m-0 font-bold text-blue-600 dark:text-blue-400">Recent Security Events</h6>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body" style="max-height: 400px; overflow-y: auto;">
                    @if($dashboardData['recent_activity']->count() > 0)
                        @foreach($dashboardData['recent_activity']->take(10) as $activity)
                        <div class="flex items-center mb-6 pb-3 border-b">
                            <div class="mr-3">
                                <i class="fas fa-{{ $activity->severity === 'critical' ? 'exclamation-circle text-red-600 dark:text-red-400' : ($activity->severity === 'warning' ? 'exclamation-triangle text-yellow-600 dark:text-yellow-400' : 'info-circle text-cyan-600 dark:text-cyan-400') }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $activity->action }}</h6>
                                <small class="text-gray-600 dark:text-gray-400">
                                    {{ $activity->created_at->diffForHumans() }}
                                    @if($activity->user)
                                        by {{ $activity->user->name }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-history fa-3x text-gray-600 dark:text-gray-400 mb-6"></i>
                            <p class="text-gray-600 dark:text-gray-400">No recent security events</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="flex flex-wrap -mx-4">
        <div class="flex-1 px-6-12">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden shadow mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-header py-6">
                    <h6 class="m-0 font-bold text-blue-600 dark:text-blue-400">Quick Actions</h6>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden-body">
                    <div class="flex flex-wrap -mx-4">
                        <div class="flex-1 px-6-md-3 mb-6">
                            <flux:button variant="ghost" color="amber" href="{{ route('security.dashboard.suspicious-logins') }}" class="px-6 py-2 font-medium rounded-md transition-colors-block" >
                                <i class="fas fa-exclamation-triangle"></i>
                                Manage Suspicious Logins
                            </flux:button>
                        </div>
                        <div class="flex-1 px-6-md-3 mb-6">
                            <flux:button variant="ghost" color="sky" href="{{ route('security.dashboard.ip-intelligence') }}" class="px-6 py-2 font-medium rounded-md transition-colors-block" >
                                <i class="fas fa-globe"></i>
                                IP Intelligence
                            </flux:button>
                        </div>
                        <div class="flex-1 px-6-md-3 mb-6">
                            <a href="{{ route('security.dashboard.trusted-devices') }}" class="btn border border-green-600 text-green-600 hover:bg-green-50 px-6 py-2 font-medium rounded-md transition-colors-block">
                                <i class="fas fa-shield-alt"></i>
                                Trusted Devices
                            </a>
                        </div>
                        <div class="flex-1 px-6-md-3 mb-6">
                            <button class="btn border border-red-600 text-red-600 hover:bg-red-50 px-6 py-2 font-medium rounded-md transition-colors-block" onclick="showBlockIpModal()">
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
<div class="fixed inset-0 z-50 overflow-y-auto fade" id="blockIpModal" tabindex="-1">
    <div class="fixed inset-0 z-50 overflow-y-auto-dialog">
        <div class="fixed inset-0 z-50 overflow-y-auto-content">
            <div class="fixed inset-0 z-50 overflow-y-auto-header">
                <h5 class="fixed inset-0 z-50 overflow-y-auto-title">Block IP Address</h5>
                <button type="button" class="px-6 py-2 font-medium rounded-md transition-colors-close" ></button>
            </div>
            <form action="{{ route('security.dashboard.block-ip') }}" method="POST">
                @csrf
                <div class="fixed inset-0 z-50 overflow-y-auto-body">
                    <div class="mb-6">
                        <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">IP Address</label>
                        <input type="text" class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="ip_address" name="ip_address" required>
                    </div>
                    <div class="mb-6">
                        <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for blocking</label>
                        <textarea class="block w-full px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="fixed inset-0 z-50 overflow-y-auto-footer">
                    <button type="button" class="btn px-6 py-2 font-medium rounded-md transition-colors-secondary" >Cancel</button>
                    <button type="submit" class="btn px-6 py-2 font-medium rounded-md transition-colors-danger">Block IP</button>
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
