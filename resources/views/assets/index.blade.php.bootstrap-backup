@extends('layouts.app')

@section('title', 'Assets')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="card-title mb-0">Assets</h3>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="{{ route('assets.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> New Asset
                                </a>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('assets.export', request()->query()) }}">
                                                <i class="fas fa-download"></i> Export to Excel
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('assets.import.form') }}">
                                                <i class="fas fa-upload"></i> Import from Excel
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('assets.template.download') }}">
                                                <i class="fas fa-file-download"></i> Download Template
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('assets.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control" placeholder="Search assets..." 
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <select name="client_id" class="form-select">
                                    <option value="">All Clients</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    @foreach(App\Models\Asset::TYPES as $type)
                                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach(App\Models\Asset::STATUSES as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="location_id" class="form-select">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </div>
                    </form>

                    <!-- Bulk Actions -->
                    <form id="bulkActionForm" method="POST" action="{{ route('assets.bulk.update') }}">
                        @csrf
                        <div class="row mb-3" id="bulkActions" style="display: none;">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <select name="action" class="form-select" id="bulkActionSelect">
                                        <option value="">Select Action</option>
                                        <option value="update_location">Update Location</option>
                                        <option value="update_contact">Update Contact</option>
                                        <option value="update_status">Update Status</option>
                                        <option value="archive">Archive Selected</option>
                                    </select>
                                    <div id="bulkActionParams"></div>
                                    <button type="submit" class="btn btn-primary">Apply</button>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="text-muted"><span id="selectedCount">0</span> assets selected</span>
                            </div>
                        </div>

                        <!-- Assets Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                        </th>
                                        <th>
                                            <a href="{{ route('assets.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => request('sort') == 'name' && request('direction') == 'asc' ? 'desc' : 'asc'])) }}">
                                                Name
                                                @if(request('sort') == 'name')
                                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                                @endif
                                            </a>
                                        </th>
                                        <th>Type</th>
                                        <th>Client</th>
                                        <th>Location</th>
                                        <th>Assigned To</th>
                                        <th>Status</th>
                                        <th>Serial</th>
                                        <th>IP Address</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assets as $asset)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" 
                                                       class="form-check-input asset-checkbox">
                                            </td>
                                            <td>
                                                <a href="{{ route('assets.show', $asset) }}">
                                                    <i class="fas fa-{{ $asset->icon }} text-muted me-2"></i>
                                                    {{ $asset->name }}
                                                </a>
                                                @if($asset->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($asset->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $asset->type }}</td>
                                            <td>
                                                @if($asset->client)
                                                    <a href="{{ route('clients.show', $asset->client) }}">
                                                        {{ $asset->client->name }}
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if($asset->location)
                                                    {{ $asset->location->name }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($asset->contact)
                                                    {{ $asset->contact->name }}
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $asset->status_color }}">
                                                    {{ $asset->status }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($asset->serial)
                                                    <code>{{ $asset->serial }}</code>
                                                @endif
                                            </td>
                                            <td>
                                                @if($asset->ip)
                                                    <code>{{ $asset->ip }}</code>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                            data-bs-toggle="dropdown">
                                                        <span class="visually-hidden">Toggle Dropdown</span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('assets.show', $asset) }}">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('assets.qr-code', $asset) }}" target="_blank">
                                                                <i class="fas fa-qrcode"></i> QR Code
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('assets.label', $asset) }}" target="_blank">
                                                                <i class="fas fa-tag"></i> Print Label
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('assets.archive', $asset) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="dropdown-item text-warning">
                                                                    <i class="fas fa-archive"></i> Archive
                                                                </button>
                                                            </form>
                                                        </li>
                                                        @can('delete', $asset)
                                                        <li>
                                                            <form action="{{ route('assets.destroy', $asset) }}" method="POST" 
                                                                  onsubmit="return confirm('Are you sure you want to delete this asset?');" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </li>
                                                        @endcan
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <p class="mb-0">No assets found.</p>
                                                <a href="{{ route('assets.create') }}" class="btn btn-primary mt-2">
                                                    <i class="fas fa-plus"></i> Create First Asset
                                                </a>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $assets->firstItem() ?? 0 }} to {{ $assets->lastItem() ?? 0 }} of {{ $assets->total() }} assets
                        </div>
                        {{ $assets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    const bulkActionParams = document.getElementById('bulkActionParams');

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActions();
    });

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checkedCount = document.querySelectorAll('.asset-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        bulkActions.style.display = checkedCount > 0 ? 'block' : 'none';
    }

    // Dynamic bulk action parameters
    bulkActionSelect.addEventListener('change', function() {
        bulkActionParams.innerHTML = '';
        
        switch(this.value) {
            case 'update_location':
                bulkActionParams.innerHTML = `
                    <select name="location_id" class="form-select" required>
                        <option value="">Select Location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                `;
                break;
            case 'update_contact':
                bulkActionParams.innerHTML = `
                    <select name="contact_id" class="form-select" required>
                        <option value="">Select Contact</option>
                        @foreach($contacts as $contact)
                            <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                        @endforeach
                    </select>
                `;
                break;
            case 'update_status':
                bulkActionParams.innerHTML = `
                    <select name="status" class="form-select" required>
                        <option value="">Select Status</option>
                        @foreach(App\Models\Asset::STATUSES as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                `;
                break;
        }
    });
});
</script>
@endpush