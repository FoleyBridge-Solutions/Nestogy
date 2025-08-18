@extends('layouts.app')

@section('title', 'Trusted Devices')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Trusted Devices</h1>
            <p class="text-muted">Manage user device trust and access permissions</p>
        </div>
        <a href="{{ route('security.dashboard.index') }}" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Devices
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['active'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
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
                                Expired Devices
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['expired'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Revoked Devices
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['revoked'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
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
                                Total Devices
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['total'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-mobile-alt fa-2x text-gray-300"></i>
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
            <form method="GET" action="{{ route('security.dashboard.trusted-devices') }}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ $filters['status'] === 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="revoked" {{ $filters['status'] === 'revoked' ? 'selected' : '' }}>Revoked</option>
                            <option value="all" {{ $filters['status'] === 'all' ? 'selected' : '' }}>All Status</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="user_id" class="form-label">User</label>
                        <input type="text" class="form-control" id="user_id" name="user_id" 
                               value="{{ $filters['user_id'] }}" placeholder="User ID or name">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="{{ route('security.dashboard.trusted-devices') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Devices Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Trusted Devices ({{ $devices->total() }} total)
            </h6>
        </div>
        <div class="card-body">
            @if($devices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Device</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Used</th>
                                <th>Expires</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($devices as $device)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $device->user->name ?? 'Unknown User' }}</h6>
                                            <small class="text-muted">{{ $device->user->email ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $device->getDeviceString() }}</strong>
                                        <br><small class="text-muted">{{ $device->getBrowserString() }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $device->getLocationString() }}</strong>
                                        @if($device->ip_address)
                                            <br><small class="text-muted"><code>{{ $device->ip_address }}</code></small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($device->is_active && !$device->isExpired())
                                        <span class="badge badge-success">Active</span>
                                    @elseif($device->isExpired())
                                        <span class="badge badge-warning">Expired</span>
                                    @else
                                        <span class="badge badge-danger">Revoked</span>
                                    @endif
                                </td>
                                <td>
                                    <div>
                                        {{ $device->created_at->format('M j, Y') }}
                                        <br><small class="text-muted">{{ $device->created_at->format('g:i A') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        {{ $device->last_used_at ? $device->last_used_at->format('M j, Y') : 'Never' }}
                                        @if($device->last_used_at)
                                            <br><small class="text-muted">{{ $device->last_used_at->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        {{ $device->expires_at ? $device->expires_at->format('M j, Y') : 'Never' }}
                                        @if($device->expires_at)
                                            <br><small class="text-muted">{{ $device->expires_at->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button class="btn btn-outline-info btn-sm" 
                                                onclick="showDeviceDetails({{ json_encode($device) }})">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                        @if($device->is_active && !$device->isExpired())
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="revokeDevice({{ $device->id }}, '{{ $device->getDeviceString() }}')">
                                                <i class="fas fa-ban"></i> Revoke
                                            </button>
                                        @endif
                                        @if($device->ip_address)
                                            <button class="btn btn-outline-warning btn-sm" 
                                                    onclick="blockIpAddress('{{ $device->ip_address }}')">
                                                <i class="fas fa-shield-alt"></i> Block IP
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
                    {{ $devices->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-mobile-alt fa-4x text-muted mb-3"></i>
                    <h4>No Trusted Devices Found</h4>
                    <p class="text-muted">No trusted devices found matching your criteria.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Device Details Modal -->
<div class="modal fade" id="deviceDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trusted Device Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="deviceDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Revoke Device Modal -->
<div class="modal fade" id="revokeDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revoke Device Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" id="revokeDeviceForm">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <p>Are you sure you want to revoke access for this device?</p>
                    <div class="alert alert-warning">
                        <strong>Device:</strong> <span id="revokeDeviceName"></span>
                    </div>
                    <p class="text-muted">The user will need to re-verify this device on their next login.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Revoke Access</button>
                </div>
            </form>
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
<script>
function showDeviceDetails(device) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Device Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>User:</strong></td><td>${device.user?.name || 'Unknown'}</td></tr>
                    <tr><td><strong>Device Name:</strong></td><td>${device.device_name || 'Unknown'}</td></tr>
                    <tr><td><strong>Device Type:</strong></td><td>${device.device_fingerprint?.device_type || 'Unknown'}</td></tr>
                    <tr><td><strong>Operating System:</strong></td><td>${device.device_fingerprint?.os || 'Unknown'}</td></tr>
                    <tr><td><strong>Browser:</strong></td><td>${device.device_fingerprint?.browser || 'Unknown'}</td></tr>
                    <tr><td><strong>Browser Version:</strong></td><td>${device.device_fingerprint?.browser_version || 'Unknown'}</td></tr>
                </table>

                <h6 class="text-primary mt-4">Location Data</h6>
                <table class="table table-sm">
                    <tr><td><strong>IP Address:</strong></td><td><code>${device.ip_address || 'N/A'}</code></td></tr>
                    <tr><td><strong>Country:</strong></td><td>${device.location_data?.country || 'Unknown'}</td></tr>
                    <tr><td><strong>Region:</strong></td><td>${device.location_data?.region || 'Unknown'}</td></tr>
                    <tr><td><strong>City:</strong></td><td>${device.location_data?.city || 'Unknown'}</td></tr>
                    <tr><td><strong>Timezone:</strong></td><td>${device.location_data?.timezone || 'Unknown'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary">Security Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Status:</strong></td><td>${getDeviceStatusBadge(device)}</td></tr>
                    <tr><td><strong>Trust Score:</strong></td><td>${device.trust_score || 'N/A'}/100</td></tr>
                    <tr><td><strong>Created:</strong></td><td>${new Date(device.created_at).toLocaleString()}</td></tr>
                    <tr><td><strong>Last Used:</strong></td><td>${device.last_used_at ? new Date(device.last_used_at).toLocaleString() : 'Never'}</td></tr>
                    <tr><td><strong>Expires:</strong></td><td>${device.expires_at ? new Date(device.expires_at).toLocaleString() : 'Never'}</td></tr>
                </table>

                <h6 class="text-primary mt-4">Device Fingerprint</h6>
                <div class="bg-light p-3 rounded">
                    <small class="font-monospace">${device.fingerprint_hash || 'Not available'}</small>
                </div>

                ${device.device_fingerprint ? `
                    <h6 class="text-primary mt-4">Additional Details</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Screen Resolution:</strong></td><td>${device.device_fingerprint.screen_resolution || 'Unknown'}</td></tr>
                        <tr><td><strong>Language:</strong></td><td>${device.device_fingerprint.language || 'Unknown'}</td></tr>
                        <tr><td><strong>User Agent:</strong></td><td><small>${device.device_fingerprint.user_agent || 'Unknown'}</small></td></tr>
                    </table>
                ` : ''}
            </div>
        </div>
    `;

    document.getElementById('deviceDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('deviceDetailsModal')).show();
}

function revokeDevice(deviceId, deviceName) {
    document.getElementById('revokeDeviceName').textContent = deviceName;
    document.getElementById('revokeDeviceForm').action = `/security/dashboard/trusted-devices/${deviceId}/revoke`;
    new bootstrap.Modal(document.getElementById('revokeDeviceModal')).show();
}

function blockIpAddress(ipAddress) {
    document.getElementById('ip_address').value = ipAddress;
    document.getElementById('reason').value = `IP address blocked due to suspicious device activity from ${ipAddress}`;
    new bootstrap.Modal(document.getElementById('blockIpModal')).show();
}

function getDeviceStatusBadge(device) {
    if (device.is_active && !isExpired(device.expires_at)) {
        return '<span class="badge badge-success">Active</span>';
    } else if (isExpired(device.expires_at)) {
        return '<span class="badge badge-warning">Expired</span>';
    } else {
        return '<span class="badge badge-danger">Revoked</span>';
    }
}

function isExpired(expiresAt) {
    if (!expiresAt) return false;
    return new Date(expiresAt) < new Date();
}
</script>
@endpush