@extends('layouts.app')

@section('title', 'Suspicious Login Attempts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Suspicious Login Attempts</h1>
            <p class="text-muted">Monitor and manage potentially malicious login attempts</p>
        </div>
        <a href="{{ route('security.dashboard.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('security.dashboard.suspicious-logins') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $filters['status'] === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="denied" {{ $filters['status'] === 'denied' ? 'selected' : '' }}>Denied</option>
                            <option value="expired" {{ $filters['status'] === 'expired' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="risk_level" class="form-label">Risk Level</label>
                        <select class="form-select" id="risk_level" name="risk_level">
                            <option value="all" {{ $filters['risk_level'] === 'all' ? 'selected' : '' }}>All Risk Levels</option>
                            <option value="low" {{ $filters['risk_level'] === 'low' ? 'selected' : '' }}>Low (0-39)</option>
                            <option value="medium" {{ $filters['risk_level'] === 'medium' ? 'selected' : '' }}>Medium (40-59)</option>
                            <option value="high" {{ $filters['risk_level'] === 'high' ? 'selected' : '' }}>High (60-79)</option>
                            <option value="critical" {{ $filters['risk_level'] === 'critical' ? 'selected' : '' }}>Critical (80-100)</option>
                        </select>
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
                        <a href="{{ route('security.dashboard.suspicious-logins') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Login Attempts ({{ $attempts->total() }} total)
            </h6>
        </div>
        <div class="card-body">
            @if($attempts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>IP Address</th>
                                <th>Location</th>
                                <th>Device</th>
                                <th>Risk Score</th>
                                <th>Detection Reasons</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attempts as $attempt)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $attempt->user->name ?? 'Unknown User' }}</h6>
                                            <small class="text-muted">{{ $attempt->user->email ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code>{{ $attempt->ip_address }}</code>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $attempt->getLocationString() }}</strong>
                                        @if($attempt->location_data && isset($attempt->location_data['isp']))
                                            <br><small class="text-muted">{{ $attempt->location_data['isp'] }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <small>{{ $attempt->getDeviceString() }}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-{{ $attempt->getRiskLevelColor() }} me-2">
                                            {{ $attempt->risk_score }}
                                        </span>
                                        <span class="text-muted">{{ $attempt->getRiskLevelString() }}</span>
                                    </div>
                                </td>
                                <td>
                                    <small>{{ $attempt->getDetectionReasonsString() }}</small>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'denied' => 'danger',
                                            'expired' => 'secondary',
                                        ];
                                    @endphp
                                    <span class="badge badge-{{ $statusColors[$attempt->status] ?? 'secondary' }}">
                                        {{ ucfirst($attempt->status) }}
                                    </span>
                                    @if($attempt->status === 'pending' && $attempt->isExpired())
                                        <br><small class="text-danger">Expired</small>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        {{ $attempt->created_at->format('M j, Y') }}
                                        <br><small class="text-muted">{{ $attempt->created_at->format('g:i A') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        @if($attempt->status === 'pending' && !$attempt->isExpired())
                                            <a href="{{ $attempt->getApprovalUrl() }}" 
                                               class="btn btn-outline-success btn-sm"
                                               target="_blank">
                                                <i class="fas fa-check"></i> Approve
                                            </a>
                                            <a href="{{ $attempt->getDenialUrl() }}" 
                                               class="btn btn-outline-danger btn-sm"
                                               target="_blank">
                                                <i class="fas fa-times"></i> Deny
                                            </a>
                                        @endif
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="showAttemptDetails({{ json_encode($attempt) }})">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                        @if($attempt->status !== 'denied')
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="blockIpAddress('{{ $attempt->ip_address }}')">
                                                <i class="fas fa-ban"></i> Block IP
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $attempts->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-shield-alt fa-4x text-success mb-3"></i>
                    <h4>No Suspicious Login Attempts</h4>
                    <p class="text-muted">No suspicious login attempts found matching your criteria.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Attempt Details Modal -->
<div class="modal fade" id="attemptDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login Attempt Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attemptDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                        <input type="text" class="form-control" id="ip_address" name="ip_address" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for blocking</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                  placeholder="Describe why this IP should be blocked..." required></textarea>
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
<script>
function showAttemptDetails(attempt) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">User Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${attempt.user?.name || 'Unknown'}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${attempt.user?.email || 'N/A'}</td></tr>
                    <tr><td><strong>IP Address:</strong></td><td><code>${attempt.ip_address}</code></td></tr>
                    <tr><td><strong>User Agent:</strong></td><td><small>${attempt.user_agent || 'N/A'}</small></td></tr>
                </table>

                <h6 class="text-primary mt-4">Location Data</h6>
                <table class="table table-sm">
                    <tr><td><strong>Country:</strong></td><td>${attempt.location_data?.country || 'Unknown'}</td></tr>
                    <tr><td><strong>Region:</strong></td><td>${attempt.location_data?.region || 'Unknown'}</td></tr>
                    <tr><td><strong>City:</strong></td><td>${attempt.location_data?.city || 'Unknown'}</td></tr>
                    <tr><td><strong>ISP:</strong></td><td>${attempt.location_data?.isp || 'Unknown'}</td></tr>
                    <tr><td><strong>Timezone:</strong></td><td>${attempt.location_data?.timezone || 'Unknown'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Risk Analysis</h6>
                <table class="table table-sm">
                    <tr><td><strong>Risk Score:</strong></td><td><span class="badge badge-${getRiskColor(attempt.risk_score)}">${attempt.risk_score}/100</span></td></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="badge badge-${getStatusColor(attempt.status)}">${attempt.status}</span></td></tr>
                    <tr><td><strong>Created:</strong></td><td>${new Date(attempt.created_at).toLocaleString()}</td></tr>
                    <tr><td><strong>Expires:</strong></td><td>${new Date(attempt.expires_at).toLocaleString()}</td></tr>
                </table>

                <h6 class="text-primary mt-4">Detection Reasons</h6>
                <ul class="list-unstyled">
                    ${attempt.detection_reasons ? attempt.detection_reasons.map(reason => `
                        <li><i class="fas fa-exclamation-triangle text-warning me-2"></i>${formatReason(reason)}</li>
                    `).join('') : '<li class="text-muted">No specific reasons recorded</li>'}
                </ul>

                ${attempt.device_fingerprint ? `
                    <h6 class="text-primary mt-4">Device Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Browser:</strong></td><td>${attempt.device_fingerprint.browser || 'Unknown'}</td></tr>
                        <tr><td><strong>OS:</strong></td><td>${attempt.device_fingerprint.os || 'Unknown'}</td></tr>
                        <tr><td><strong>Device Type:</strong></td><td>${attempt.device_fingerprint.device_type || 'Unknown'}</td></tr>
                    </table>
                ` : ''}
            </div>
        </div>
    `;

    document.getElementById('attemptDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('attemptDetailsModal')).show();
}

function blockIpAddress(ipAddress) {
    document.getElementById('ip_address').value = ipAddress;
    document.getElementById('reason').value = `Blocked due to suspicious login activity from ${ipAddress}`;
    new bootstrap.Modal(document.getElementById('blockIpModal')).show();
}

function getRiskColor(score) {
    if (score >= 80) return 'danger';
    if (score >= 60) return 'warning';
    if (score >= 40) return 'info';
    return 'success';
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'approved': 'success',
        'denied': 'danger',
        'expired': 'secondary'
    };
    return colors[status] || 'secondary';
}

function formatReason(reason) {
    const reasons = {
        'new_country': 'Login from new country',
        'new_region': 'Login from new region',
        'vpn_detected': 'VPN connection detected',
        'proxy_detected': 'Proxy connection detected',
        'tor_detected': 'Tor connection detected',
        'suspicious_isp': 'Suspicious internet provider',
        'high_risk_country': 'High-risk country',
        'impossible_travel': 'Impossible travel time',
        'new_device': 'New device/browser'
    };
    return reasons[reason] || reason.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>
@endpush