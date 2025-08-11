@extends('layouts.app')

@section('title', 'Create Asset')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Asset</h3>
                </div>
                
                <form action="{{ route('assets.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="card-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Basic Information</h5>
                                
                                <x-forms.client-search-field 
                                    name="client_id" 
                                    :required="true"
                                    :selected="old('client_id', $selectedClientId ?? null) ? \App\Models\Client::find(old('client_id', $selectedClientId ?? null)) : null"
                                    label="Client"
                                    placeholder="Search for client..."
                                    class="mb-3" />

                                <div class="mb-3">
                                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        @foreach(App\Models\Asset::TYPES as $type)
                                            <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                                {{ $type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" placeholder="Asset name or tag" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                              rows="3" placeholder="Asset description">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                        @foreach(App\Models\Asset::STATUSES as $status)
                                            <option value="{{ $status }}" {{ old('status', 'Ready To Deploy') == $status ? 'selected' : '' }}>
                                                {{ $status }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Hardware Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Hardware Information</h5>

                                <div class="mb-3">
                                    <label for="make" class="form-label">Make <span class="text-danger">*</span></label>
                                    <input type="text" name="make" id="make" class="form-control @error('make') is-invalid @enderror" 
                                           value="{{ old('make') }}" placeholder="e.g., Dell, HP, Cisco" required>
                                    @error('make')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="model" class="form-label">Model</label>
                                    <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror" 
                                           value="{{ old('model') }}" placeholder="e.g., OptiPlex 7080">
                                    @error('model')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="serial" class="form-label">Serial Number</label>
                                    <input type="text" name="serial" id="serial" class="form-control @error('serial') is-invalid @enderror" 
                                           value="{{ old('serial') }}" placeholder="Serial number">
                                    @error('serial')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="os" class="form-label">Operating System</label>
                                    <input type="text" name="os" id="os" class="form-control @error('os') is-invalid @enderror" 
                                           value="{{ old('os') }}" placeholder="e.g., Windows 11 Pro">
                                    @error('os')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="vendor_id" class="form-label">Vendor</label>
                                    <select name="vendor_id" id="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
                                        <option value="">Select Vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('vendor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <!-- Network Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Network Information</h5>

                                <div class="mb-3">
                                    <label for="ip" class="form-label">IP Address</label>
                                    <div class="input-group">
                                        <input type="text" name="ip" id="ip" class="form-control @error('ip') is-invalid @enderror" 
                                               value="{{ old('ip') }}" placeholder="192.168.1.100">
                                        <div class="input-group-text">
                                            <input type="checkbox" name="dhcp" id="dhcp" value="1" {{ old('dhcp') ? 'checked' : '' }}>
                                            <label for="dhcp" class="ms-2 mb-0">DHCP</label>
                                        </div>
                                    </div>
                                    @error('ip')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="nat_ip" class="form-label">NAT IP</label>
                                    <input type="text" name="nat_ip" id="nat_ip" class="form-control @error('nat_ip') is-invalid @enderror" 
                                           value="{{ old('nat_ip') }}" placeholder="External IP">
                                    @error('nat_ip')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="mac" class="form-label">MAC Address</label>
                                    <input type="text" name="mac" id="mac" class="form-control @error('mac') is-invalid @enderror" 
                                           value="{{ old('mac') }}" placeholder="00:00:00:00:00:00" maxlength="17">
                                    @error('mac')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="network_id" class="form-label">Network</label>
                                    <select name="network_id" id="network_id" class="form-select @error('network_id') is-invalid @enderror">
                                        <option value="">Select Network</option>
                                        @foreach($networks as $network)
                                            <option value="{{ $network->id }}" {{ old('network_id') == $network->id ? 'selected' : '' }}>
                                                {{ $network->name }} ({{ $network->network }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('network_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="uri" class="form-label">URI/URL</label>
                                    <input type="text" name="uri" id="uri" class="form-control @error('uri') is-invalid @enderror" 
                                           value="{{ old('uri') }}" placeholder="https://device.local">
                                    @error('uri')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="uri_2" class="form-label">Secondary URI/URL</label>
                                    <input type="text" name="uri_2" id="uri_2" class="form-control @error('uri_2') is-invalid @enderror" 
                                           value="{{ old('uri_2') }}" placeholder="https://device-mgmt.local">
                                    @error('uri_2')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Assignment & Location -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Assignment & Location</h5>

                                <div class="mb-3">
                                    <label for="location_id" class="form-label">Location</label>
                                    <select name="location_id" id="location_id" class="form-select @error('location_id') is-invalid @enderror">
                                        <option value="">Select Location</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="contact_id" class="form-label">Assigned To</label>
                                    <select name="contact_id" id="contact_id" class="form-select @error('contact_id') is-invalid @enderror">
                                        <option value="">Select Contact</option>
                                        @foreach($contacts as $contact)
                                            <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                                                {{ $contact->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('contact_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <h5 class="mb-3 mt-4">Important Dates</h5>

                                <div class="mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" name="purchase_date" id="purchase_date" 
                                           class="form-control @error('purchase_date') is-invalid @enderror" 
                                           value="{{ old('purchase_date') }}">
                                    @error('purchase_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="warranty_expire" class="form-label">Warranty Expiration</label>
                                    <input type="date" name="warranty_expire" id="warranty_expire" 
                                           class="form-control @error('warranty_expire') is-invalid @enderror" 
                                           value="{{ old('warranty_expire') }}">
                                    @error('warranty_expire')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="install_date" class="form-label">Install Date</label>
                                    <input type="date" name="install_date" id="install_date" 
                                           class="form-control @error('install_date') is-invalid @enderror" 
                                           value="{{ old('install_date') }}">
                                    @error('install_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Additional Information</h5>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" 
                                              rows="4" placeholder="Additional notes about this asset">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="files" class="form-label">Attachments</label>
                                    <input type="file" name="files[]" id="files" class="form-control @error('files.*') is-invalid @enderror" 
                                           multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                    <small class="text-muted">You can attach multiple files (PDF, Word, Excel, Images)</small>
                                    @error('files.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Asset
                        </button>
                        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-disable IP field when DHCP is checked
    const dhcpCheckbox = document.getElementById('dhcp');
    const ipField = document.getElementById('ip');
    
    dhcpCheckbox.addEventListener('change', function() {
        if (this.checked) {
            ipField.value = 'DHCP';
            ipField.disabled = true;
        } else {
            ipField.value = '';
            ipField.disabled = false;
        }
    });

    // Format MAC address as user types
    const macField = document.getElementById('mac');
    macField.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9a-fA-F]/g, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length && i < 12; i++) {
            if (i > 0 && i % 2 === 0) {
                formattedValue += ':';
            }
            formattedValue += value[i];
        }
        
        e.target.value = formattedValue.toUpperCase();
    });

    // Dynamic client-based filtering
    const clientSelect = document.getElementById('client_id');
    const locationSelect = document.getElementById('location_id');
    const contactSelect = document.getElementById('contact_id');
    const networkSelect = document.getElementById('network_id');

    clientSelect.addEventListener('change', function() {
        const clientId = this.value;
        
        // Filter locations, contacts, and networks based on selected client
        if (clientId) {
            // This would typically make an AJAX call to get filtered data
            // For now, we'll just show all options
        }
    });
});
</script>
@endpush