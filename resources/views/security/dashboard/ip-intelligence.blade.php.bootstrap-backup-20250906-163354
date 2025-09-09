@extends('layouts.app')

@section('title', 'IP Intelligence Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">IP Intelligence Dashboard</h1>
            <p class="text-muted">Monitor IP addresses, threat levels, and geographic data</p>
        </div>
        <a href="{{ route('security.dashboard.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total IPs Tracked
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['total_ips']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Suspicious IPs
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['suspicious_ips']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Unique Countries
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['unique_countries']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flag fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Critical Threats
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['threat_levels']['critical'] ?? 0) }}
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

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('security.dashboard.ip-intelligence') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="threat_level" class="form-label">Threat Level</label>
                        <select class="form-select" id="threat_level" name="threat_level">
                            <option value="all" {{ $filters['threat_level'] === 'all' ? 'selected' : '' }}>All Levels</option>
                            <option value="low" {{ $filters['threat_level'] === 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $filters['threat_level'] === 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ $filters['threat_level'] === 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ $filters['threat_level'] === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" 
                               value="{{ $filters['country'] }}" placeholder="Country code (e.g., US, CN)">
                    </div>
                    <div class="col-md-3">
                        <label for="days" class="form-label">Time Range</label>
                        <select class="form-select" id="days" name="days">
                            <option value="7" {{ $filters['days'] == 7 ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ $filters['days'] == 30 ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ $filters['days'] == 90 ? 'selected' : '' }}>Last 90 days</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="{{ route('security.dashboard.ip-intelligence') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Threat Level Distribution Chart -->
    <div class="row mb-4">
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Threat Level Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="threatLevelChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Threat Level Breakdown</h6>
                </div>
                <div class="card-body">
                    @foreach(['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'] as $level => $color)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="badge badge-{{ $color }}">{{ ucfirst($level) }}</span>
                        </div>
                        <div>
                            <strong>{{ number_format($statistics['threat_levels'][$level] ?? 0) }}</strong> IPs
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- IP Lookup Results -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                IP Lookup Results ({{ $ipLogs->total() }} total)
            </h6>
        </div>
        <div class="card-body">
            @if($ipLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Location</th>
                                <th>ISP</th>
                                <th>Threat Level</th>
                                <th>Threats</th>
                                <th>Lookup Count</th>
                                <th>Last Lookup</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ipLogs as $ipLog)
                            <tr>
                                <td>
                                    <code class="fs-6">{{ $ipLog->ip_address }}</code>
                                </td>
                                <td>
                                    <div>
                                        @if($ipLog->country_code)
                                            <span class="flag-icon flag-icon-{{ strtolower($ipLog->country_code) }} me-2"></span>
                                        @endif
                                        <strong>{{ $ipLog->getLocationString() }}</strong>
                                        @if($ipLog->timezone)
                                            <br><small class="text-muted">{{ $ipLog->timezone }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <small>{{ $ipLog->isp ?? 'Unknown' }}</small>
                                </td>
                                <td>
                                    @php
                                        $threatColors = [
                                            'low' => 'success',
                                            'medium' => 'warning', 
                                            'high' => 'danger',
                                            'critical' => 'dark'
                                        ];
                                    @endphp
                                    <span class="badge badge-{{ $threatColors[$ipLog->threat_level] ?? 'secondary' }}">
                                        {{ ucfirst($ipLog->threat_level) }}
                                    </span>
                                    <br><small class="text-muted">Score: {{ $ipLog->getThreatScore() }}/100</small>
                                </td>
                                <td>
                                    <div>
                                        @if($ipLog->is_vpn)
                                            <span class="badge badge-warning me-1">VPN</span>
                                        @endif
                                        @if($ipLog->is_proxy)
                                            <span class="badge badge-warning me-1">Proxy</span>
                                        @endif
                                        @if($ipLog->is_tor)
                                            <span class="badge badge-danger me-1">Tor</span>
                                        @endif
                                        @if(!$ipLog->is_vpn && !$ipLog->is_proxy && !$ipLog->is_tor)
                                            <span class="text-muted">None detected</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <strong>{{ number_format($ipLog->lookup_count) }}</strong>
                                        <br><small class="text-muted">lookups</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        {{ $ipLog->last_lookup_at ? $ipLog->last_lookup_at->format('M j, Y') : 'Never' }}
                                        @if($ipLog->last_lookup_at)
                                            <br><small class="text-muted">{{ $ipLog->last_lookup_at->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="showIpDetails({{ json_encode($ipLog) }})">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                        @if($ipLog->threat_level !== 'critical')
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="blockIpAddress('{{ $ipLog->ip_address }}')">
                                                <i class="fas fa-ban"></i> Block
                                            </button>
                                        @else
                                            <button class="btn btn-outline-success btn-sm" 
                                                    onclick="unblockIpAddress('{{ $ipLog->ip_address }}')">
                                                <i class="fas fa-check"></i> Unblock
                                            </button>
                                        @endif
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="refreshIpLookup('{{ $ipLog->ip_address }}')">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $ipLogs->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-globe fa-4x text-muted mb-3"></i>
                    <h4>No IP Data Found</h4>
                    <p class="text-muted">No IP lookup data found matching your criteria.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- IP Details Modal -->
<div class="modal fade" id="ipDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">IP Address Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="ipDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Block/Unblock IP Modals -->
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
                        <input type="text" class="form-control" id="ip_address" name="ip_address" readonly>
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

<div class="modal fade" id="unblockIpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unblock IP Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('security.dashboard.unblock-ip') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to unblock the IP address <code id="unblock_ip_display"></code>?</p>
                    <input type="hidden" id="unblock_ip_address" name="ip_address">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Unblock IP</button>
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
const threatData = @json($statistics['threat_levels']);
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

function showIpDetails(ipLog) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Basic Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>IP Address:</strong></td><td><code>${ipLog.ip_address}</code></td></tr>
                    <tr><td><strong>Country:</strong></td><td>${ipLog.country || 'Unknown'} (${ipLog.country_code || 'N/A'})</td></tr>
                    <tr><td><strong>Region:</strong></td><td>${ipLog.region || 'Unknown'}</td></tr>
                    <tr><td><strong>City:</strong></td><td>${ipLog.city || 'Unknown'}</td></tr>
                    <tr><td><strong>ZIP Code:</strong></td><td>${ipLog.zip || 'Unknown'}</td></tr>
                    <tr><td><strong>Timezone:</strong></td><td>${ipLog.timezone || 'Unknown'}</td></tr>
                    <tr><td><strong>ISP:</strong></td><td>${ipLog.isp || 'Unknown'}</td></tr>
                </table>

                ${ipLog.latitude && ipLog.longitude ? `
                    <h6 class="text-primary mt-4">Geographic Coordinates</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Latitude:</strong></td><td>${ipLog.latitude}</td></tr>
                        <tr><td><strong>Longitude:</strong></td><td>${ipLog.longitude}</td></tr>
                    </table>
                ` : ''}
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Threat Analysis</h6>
                <table class="table table-sm">
                    <tr><td><strong>Threat Level:</strong></td><td><span class="badge badge-${getThreatColor(ipLog.threat_level)}">${ipLog.threat_level}</span></td></tr>
                    <tr><td><strong>Threat Score:</strong></td><td>${ipLog.threat_score || 'N/A'}/100</td></tr>
                    <tr><td><strong>VPN Detected:</strong></td><td>${ipLog.is_vpn ? '<span class="text-danger">Yes</span>' : '<span class="text-success">No</span>'}</td></tr>
                    <tr><td><strong>Proxy Detected:</strong></td><td>${ipLog.is_proxy ? '<span class="text-danger">Yes</span>' : '<span class="text-success">No</span>'}</td></tr>
                    <tr><td><strong>Tor Detected:</strong></td><td>${ipLog.is_tor ? '<span class="text-danger">Yes</span>' : '<span class="text-success">No</span>'}</td></tr>
                </table>

                <h6 class="text-primary mt-4">Lookup Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Lookup Source:</strong></td><td>${ipLog.lookup_source || 'Unknown'}</td></tr>
                    <tr><td><strong>Lookup Count:</strong></td><td>${ipLog.lookup_count || 0}</td></tr>
                    <tr><td><strong>First Seen:</strong></td><td>${new Date(ipLog.created_at).toLocaleString()}</td></tr>
                    <tr><td><strong>Last Lookup:</strong></td><td>${ipLog.last_lookup_at ? new Date(ipLog.last_lookup_at).toLocaleString() : 'Never'}</td></tr>
                    <tr><td><strong>Cache Expires:</strong></td><td>${ipLog.cached_until ? new Date(ipLog.cached_until).toLocaleString() : 'Not cached'}</td></tr>
                </table>
            </div>
        </div>
    `;

    document.getElementById('ipDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('ipDetailsModal')).show();
}

function blockIpAddress(ipAddress) {
    document.getElementById('ip_address').value = ipAddress;
    document.getElementById('reason').value = `IP address flagged as suspicious or malicious`;
    new bootstrap.Modal(document.getElementById('blockIpModal')).show();
}

function unblockIpAddress(ipAddress) {
    document.getElementById('unblock_ip_address').value = ipAddress;
    document.getElementById('unblock_ip_display').textContent = ipAddress;
    new bootstrap.Modal(document.getElementById('unblockIpModal')).show();
}

function refreshIpLookup(ipAddress) {
    // This would make an AJAX call to refresh the IP lookup
    fetch(`/api/security/ip-lookup/refresh`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ ip_address: ipAddress })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to refresh IP lookup data');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while refreshing IP data');
    });
}

function getThreatColor(threatLevel) {
    const colors = {
        'low': 'success',
        'medium': 'warning',
        'high': 'danger',
        'critical': 'dark'
    };
    return colors[threatLevel] || 'secondary';
}
</script>
@endpush