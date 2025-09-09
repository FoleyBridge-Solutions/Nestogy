@extends('layouts.app')

@section('title', 'Asset Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Asset Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-{{ $asset->icon }} text-muted me-2"></i>
                                {{ $asset->name }}
                            </h3>
                            <p class="text-muted mb-0">{{ $asset->type }} - {{ $asset->make }} {{ $asset->model }}</p>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-secondary dropdown-toggle dropdown-toggle-split" 
                                        data-bs-toggle="dropdown">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assets.qr-code', $asset) }}" target="_blank">
                                            <i class="fas fa-qrcode"></i> View QR Code
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('assets.label', $asset) }}" target="_blank">
                                            <i class="fas fa-tag"></i> Print Label
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#checkInOutModal">
                                            <i class="fas fa-exchange-alt"></i> Check In/Out
                                        </button>
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
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-sm-3">
                                    <p class="mb-0"><strong>Status:</strong></p>
                                </div>
                                <div class="col-sm-9">
                                    <span class="badge bg-{{ $asset->status_color }}">{{ $asset->status }}</span>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-3">
                                    <p class="mb-0"><strong>Client:</strong></p>
                                </div>
                                <div class="col-sm-9">
                                    <a href="{{ route('clients.show', $asset->client) }}">{{ $asset->client->name }}</a>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-sm-3">
                                    <p class="mb-0"><strong>Serial Number:</strong></p>
                                </div>
                                <div class="col-sm-9">
                                    <code>{{ $asset->serial ?: 'N/A' }}</code>
                                </div>
                            </div>
                            @if($asset->description)
                            <hr>
                            <div class="row">
                                <div class="col-sm-3">
                                    <p class="mb-0"><strong>Description:</strong></p>
                                </div>
                                <div class="col-sm-9">
                                    <p class="mb-0">{{ $asset->description }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                {!! $qrCode !!}
                            </div>
                            <p class="text-muted small">Asset ID: {{ $asset->id }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <!-- Hardware Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Hardware Information</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Make</dt>
                                <dd class="col-sm-8">{{ $asset->make }}</dd>

                                <dt class="col-sm-4">Model</dt>
                                <dd class="col-sm-8">{{ $asset->model ?: 'N/A' }}</dd>

                                <dt class="col-sm-4">Operating System</dt>
                                <dd class="col-sm-8">{{ $asset->os ?: 'N/A' }}</dd>

                                @if($asset->vendor)
                                <dt class="col-sm-4">Vendor</dt>
                                <dd class="col-sm-8">{{ $asset->vendor->name }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Network Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Network Information</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">IP Address</dt>
                                <dd class="col-sm-8">
                                    @if($asset->ip)
                                        <code>{{ $asset->ip }}</code>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </dd>

                                @if($asset->nat_ip)
                                <dt class="col-sm-4">NAT IP</dt>
                                <dd class="col-sm-8"><code>{{ $asset->nat_ip }}</code></dd>
                                @endif

                                @if($asset->mac)
                                <dt class="col-sm-4">MAC Address</dt>
                                <dd class="col-sm-8"><code>{{ $asset->mac }}</code></dd>
                                @endif

                                @if($asset->network)
                                <dt class="col-sm-4">Network</dt>
                                <dd class="col-sm-8">{{ $asset->network->name }} ({{ $asset->network->network }})</dd>
                                @endif

                                @if($asset->uri)
                                <dt class="col-sm-4">URI/URL</dt>
                                <dd class="col-sm-8">
                                    <a href="{{ $asset->uri }}" target="_blank">{{ $asset->uri }}</a>
                                </dd>
                                @endif

                                @if($asset->uri_2)
                                <dt class="col-sm-4">Secondary URI</dt>
                                <dd class="col-sm-8">
                                    <a href="{{ $asset->uri_2 }}" target="_blank">{{ $asset->uri_2 }}</a>
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Important Dates -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Important Dates</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                @if($asset->purchase_date)
                                <dt class="col-sm-4">Purchase Date</dt>
                                <dd class="col-sm-8">
                                    {{ $asset->purchase_date->format('M d, Y') }}
                                    @if($asset->age_in_years !== null)
                                        <small class="text-muted">({{ $asset->age_in_years }} years old)</small>
                                    @endif
                                </dd>
                                @endif

                                @if($asset->warranty_expire)
                                <dt class="col-sm-4">Warranty Expires</dt>
                                <dd class="col-sm-8">
                                    {{ $asset->warranty_expire->format('M d, Y') }}
                                    @if($asset->is_warranty_expired)
                                        <span class="badge bg-danger">Expired</span>
                                    @elseif($asset->is_warranty_expiring_soon)
                                        <span class="badge bg-warning">Expiring Soon</span>
                                    @else
                                        <span class="badge bg-success">Active</span>
                                    @endif
                                </dd>
                                @endif

                                @if($asset->install_date)
                                <dt class="col-sm-4">Install Date</dt>
                                <dd class="col-sm-8">{{ $asset->install_date->format('M d, Y') }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-6">
                    <!-- Assignment & Location -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Assignment & Location</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                @if($asset->location)
                                <dt class="col-sm-4">Location</dt>
                                <dd class="col-sm-8">{{ $asset->location->name }}</dd>
                                @endif

                                @if($asset->contact)
                                <dt class="col-sm-4">Assigned To</dt>
                                <dd class="col-sm-8">{{ $asset->contact->name }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($asset->notes)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Notes</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{!! nl2br(e($asset->notes)) !!}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Files -->
                    @if($asset->files->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Attachments</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                @foreach($asset->files as $file)
                                    <a href="{{ route('files.download', $file) }}" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <i class="fas fa-file me-2"></i>
                                                {{ $file->name }}
                                            </h6>
                                            <small>{{ number_format($file->size / 1024, 2) }} KB</small>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Related Tickets -->
                    @if($asset->tickets->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Related Tickets</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                @foreach($asset->tickets->take(5) as $ticket)
                                    <a href="{{ route('tickets.show', $ticket) }}" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">#{{ $ticket->number }} - {{ $ticket->subject }}</h6>
                                            <small>{{ $ticket->created_at->diffForHumans() }}</small>
                                        </div>
                                        <p class="mb-1">
                                            <span class="badge bg-{{ $ticket->priority_color }}">{{ $ticket->priority }}</span>
                                            <span class="badge bg-{{ $ticket->status_color }}">{{ $ticket->status }}</span>
                                        </p>
                                    </a>
                                @endforeach
                            </div>
                            @if($asset->tickets->count() > 5)
                                <div class="text-center mt-3">
                                    <a href="{{ route('tickets.index', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-outline-primary">
                                        View All {{ $asset->tickets->count() }} Tickets
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Metadata</h5>
                </div>
                <div class="card-body">
                    <div class="row text-muted small">
                        <div class="col-md-6">
                            <p><strong>Created:</strong> {{ $asset->created_at->format('M d, Y g:i A') }}</p>
                            <p><strong>Last Updated:</strong> {{ $asset->updated_at->format('M d, Y g:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            @if($asset->accessed_at)
                                <p><strong>Last Accessed:</strong> {{ $asset->accessed_at->format('M d, Y g:i A') }}</p>
                            @endif
                            @if($asset->rmm_id)
                                <p><strong>RMM ID:</strong> {{ $asset->rmm_id }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Check In/Out Modal -->
<div class="modal fade" id="checkInOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('assets.check-in-out', $asset) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Check In/Out Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="checkOut" value="check_out" 
                                       {{ !$asset->contact_id ? 'checked' : '' }}>
                                <label class="form-check-label" for="checkOut">
                                    Check Out (Assign to someone)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="checkIn" value="check_in"
                                       {{ $asset->contact_id ? 'checked' : '' }}>
                                <label class="form-check-label" for="checkIn">
                                    Check In (Return to inventory)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="contactSelect" style="{{ $asset->contact_id ? 'display: none;' : '' }}">
                        <label for="contact_id" class="form-label">Assign To</label>
                        <select name="contact_id" id="contact_id" class="form-select">
                            <option value="">Select Contact</option>
                            @foreach($asset->client->contacts as $contact)
                                <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" 
                                  placeholder="Optional notes about this check in/out"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check In/Out radio button logic
    const checkOutRadio = document.getElementById('checkOut');
    const checkInRadio = document.getElementById('checkIn');
    const contactSelect = document.getElementById('contactSelect');

    checkOutRadio.addEventListener('change', function() {
        if (this.checked) {
            contactSelect.style.display = 'block';
        }
    });

    checkInRadio.addEventListener('change', function() {
        if (this.checked) {
            contactSelect.style.display = 'none';
        }
    });
});
</script>
@endpush