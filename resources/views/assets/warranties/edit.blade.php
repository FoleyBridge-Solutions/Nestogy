@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Warranty</h1>
                    <p class="text-gray-600 mb-0">Update warranty information and coverage details</p>
                </div>
                <div>
                    <a href="{{ route('assets.warranties.show', $warranty ?? 1) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    <a href="{{ route('assets.warranties.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="md:w-2/3 px-4">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex justify-between items-center">
                                <h5 class="bg-white rounded-lg shadow-md overflow-hidden-title mb-0">Warranty Information</h5>
                                <div class="d-flex gap-2">
                                    @php
                                        $status = $warranty->status ?? 'active';
                                        $statusColors = [
                                            'active' => 'bg-success',
                                            'expired' => 'bg-danger',
                                            'inactive' => 'bg-secondary',
                                            'renewed' => 'bg-info'
                                        ];
                                    @endphp
                                    <span class="badge {{ $statusColors[$status] ?? 'bg-gray-600' }}">
                                        {{ ucfirst($status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('assets.warranties.update', $warranty ?? 1) }}" method="POST" id="warrantyForm">
                                @csrf
                                @method('PUT')

                                <!-- Asset Selection -->
                                <div class="row mb-3">
                                    <div class="md:w-1/2 px-4">
                                        <label for="asset_id" class="block text-sm font-medium text-gray-700 mb-1">Asset <span class="text-red-600">*</span></label>
                                        <select name="asset_id" id="asset_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('asset_id') is-invalid @enderror" required>
                                            <option value="">Select an asset...</option>
                                            @foreach($assets ?? [] as $asset)
                                                <option value="{{ $asset->id }}" 
                                                    {{ (old('asset_id', $warranty->asset_id ?? '') == $asset->id) ? 'selected' : '' }}>
                                                    {{ $asset->name }} ({{ $asset->asset_tag }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('asset_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="warranty_type" class="block text-sm font-medium text-gray-700 mb-1">Warranty Type <span class="text-red-600">*</span></label>
                                        <select name="warranty_type" id="warranty_type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('warranty_type') is-invalid @enderror" required>
                                            <option value="">Select type...</option>
                                            <option value="manufacturer" {{ old('warranty_type', $warranty->warranty_type ?? 'manufacturer') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                                            <option value="extended" {{ old('warranty_type', $warranty->warranty_type ?? '') === 'extended' ? 'selected' : '' }}>Extended</option>
                                            <option value="service" {{ old('warranty_type', $warranty->warranty_type ?? '') === 'service' ? 'selected' : '' }}>Service Contract</option>
                                            <option value="maintenance" {{ old('warranty_type', $warranty->warranty_type ?? '') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        </select>
                                        @error('warranty_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Warranty Provider and Number -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="provider_name" class="form-label">Warranty Provider <span class="text-danger">*</span></label>
                                        <input type="text" name="provider_name" id="provider_name" 
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('provider_name') is-invalid @enderror" 
                                               value="{{ old('provider_name', $warranty->provider_name ?? 'Dell Technologies') }}" required 
                                               placeholder="e.g., Dell Technologies, HP Inc.">
                                        @error('provider_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="warranty_number" class="form-label">Warranty Number</label>
                                        <input type="text" name="warranty_number" id="warranty_number" 
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('warranty_number') is-invalid @enderror" 
                                               value="{{ old('warranty_number', $warranty->warranty_number ?? 'WRN-123456789') }}" 
                                               placeholder="WRN-123456789">
                                        @error('warranty_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Coverage Details -->
                                <div class="mb-3">
                                    <label for="coverage_details" class="form-label">Coverage Details <span class="text-danger">*</span></label>
                                    <textarea name="coverage_details" id="coverage_details" 
                                              class="form-control @error('coverage_details') is-invalid @enderror" 
                                              rows="3" required 
                                              placeholder="Describe what is covered under this warranty...">{{ old('coverage_details', $warranty->coverage_details ?? 'Full hardware replacement including parts and labor, 24/7 support with 4-hour response time, on-site service included for critical failures.') }}</textarea>
                                    @error('coverage_details')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Warranty Dates -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="warranty_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" name="warranty_start_date" id="warranty_start_date" 
                                               class="form-control @error('warranty_start_date') is-invalid @enderror" 
                                               value="{{ old('warranty_start_date', isset($warranty->warranty_start_date) ? $warranty->warranty_start_date->format('Y-m-d') : now()->subMonths(6)->format('Y-m-d')) }}" required>
                                        @error('warranty_start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="warranty_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" name="warranty_end_date" id="warranty_end_date" 
                                               class="form-control @error('warranty_end_date') is-invalid @enderror" 
                                               value="{{ old('warranty_end_date', isset($warranty->warranty_end_date) ? $warranty->warranty_end_date->format('Y-m-d') : now()->addMonths(18)->format('Y-m-d')) }}" required>
                                        @error('warranty_end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status</label>
                                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                                            <option value="active" {{ old('status', $warranty->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="expired" {{ old('status', $warranty->status ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                                            <option value="inactive" {{ old('status', $warranty->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            <option value="renewed" {{ old('status', $warranty->status ?? '') === 'renewed' ? 'selected' : '' }}>Renewed</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notification_days" class="form-label">Notify before expiry (days)</label>
                                        <input type="number" name="notification_days" id="notification_days" 
                                               class="form-control @error('notification_days') is-invalid @enderror" 
                                               value="{{ old('notification_days', $warranty->notification_days ?? 30) }}" 
                                               min="1" max="365" placeholder="30">
                                        @error('notification_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Cost Information -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="cost" class="form-label">Warranty Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="cost" id="cost" 
                                                   class="form-control @error('cost') is-invalid @enderror" 
                                                   value="{{ old('cost', $warranty->cost ?? 299.99) }}" 
                                                   min="0" step="0.01" placeholder="0.00">
                                        </div>
                                        @error('cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="deductible" class="form-label">Deductible</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="deductible" id="deductible" 
                                                   class="form-control @error('deductible') is-invalid @enderror" 
                                                   value="{{ old('deductible', $warranty->deductible ?? 0) }}" 
                                                   min="0" step="0.01" placeholder="0.00">
                                        </div>
                                        @error('deductible')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Contact and Support Information -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contact_name" class="form-label">Support Contact Name</label>
                                        <input type="text" name="contact_name" id="contact_name" 
                                               class="form-control @error('contact_name') is-invalid @enderror" 
                                               value="{{ old('contact_name', $warranty->contact_name ?? '') }}" 
                                               placeholder="Contact person name">
                                        @error('contact_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_phone" class="form-label">Support Phone</label>
                                        <input type="tel" name="contact_phone" id="contact_phone" 
                                               class="form-control @error('contact_phone') is-invalid @enderror" 
                                               value="{{ old('contact_phone', $warranty->contact_phone ?? '+1-800-555-0199') }}" 
                                               placeholder="(555) 123-4567">
                                        @error('contact_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="contact_email" class="form-label">Support Email</label>
                                        <input type="email" name="contact_email" id="contact_email" 
                                               class="form-control @error('contact_email') is-invalid @enderror" 
                                               value="{{ old('contact_email', $warranty->contact_email ?? 'support@dell.com') }}" 
                                               placeholder="support@provider.com">
                                        @error('contact_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="support_url" class="form-label">Support Portal URL</label>
                                        <input type="url" name="support_url" id="support_url" 
                                               class="form-control @error('support_url') is-invalid @enderror" 
                                               value="{{ old('support_url', $warranty->support_url ?? 'https://support.dell.com') }}" 
                                               placeholder="https://support.provider.com">
                                        @error('support_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Auto-renewal -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input type="checkbox" name="auto_renewal" id="auto_renewal" 
                                                   class="form-check-input" value="1" 
                                                   {{ old('auto_renewal', $warranty->auto_renewal ?? false) ? 'checked' : '' }}>
                                            <label for="auto_renewal" class="form-check-label">
                                                Auto-renewal enabled
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Terms and Conditions -->
                                <div class="mb-3">
                                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" id="terms_conditions" 
                                              class="form-control @error('terms_conditions') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Important terms, limitations, and conditions...">{{ old('terms_conditions', $warranty->terms_conditions ?? 'Standard manufacturer warranty terms apply. Coverage excludes damage due to misuse, environmental factors, or unauthorized modifications.') }}</textarea>
                                    @error('terms_conditions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-4">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea name="notes" id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Additional notes about this warranty...">{{ old('notes', $warranty->notes ?? 'Warranty registered and confirmed active. Keep proof of purchase for warranty claims.') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Submit Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save"></i> Update Warranty
                                    </button>
                                    @if($status === 'expired')
                                        <button type="submit" name="renew" value="1" class="btn btn-info">
                                            <i class="fas fa-refresh"></i> Save & Renew
                                        </button>
                                    @endif
                                    @if($status === 'active')
                                        <button type="submit" name="mark_expired" value="1" class="btn btn-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Save & Mark Expired
                                        </button>
                                    @endif
                                    <a href="{{ route('assets.warranties.show', $warranty ?? 1) }}" class="btn btn-outline-info">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <a href="{{ route('assets.warranties.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="col-md-4">
                    <!-- Warranty Status -->
                    <div class="card mb-4">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h6 class="card-title mb-0">Warranty Status</h6>
                        </div>
                        <div class="p-6">
                            <div class="mb-3">
                                <strong>Current Status:</strong>
                                <span class="badge {{ $statusColors[$status] ?? 'bg-gray-600' }} ml-2">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                            @php
                                $endDate = $warranty->warranty_end_date ?? now()->addMonths(18);
                                $daysRemaining = now()->diffInDays($endDate, false);
                                $isExpiring = $daysRemaining <= 30 && $daysRemaining > 0;
                                $isExpired = $daysRemaining < 0;
                            @endphp
                            @if($isExpired)
                                <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Expired:</strong> {{ abs($daysRemaining) }} days ago
                                </div>
                            @elseif($isExpiring)
                                <div class="px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700">
                                    <i class="fas fa-clock"></i>
                                    <strong>Expires:</strong> in {{ $daysRemaining }} days
                                </div>
                            @else
                                <div class="mb-2">
                                    <strong>Days Remaining:</strong> {{ $daysRemaining }}
                                </div>
                            @endif
                            @if(isset($warranty->created_at))
                                <div class="mb-2">
                                    <strong>Created:</strong> {{ $warranty->created_at->format('M d, Y g:i A') }}
                                </div>
                            @endif
                            @if(isset($warranty->updated_at))
                                <div class="mb-2">
                                    <strong>Last Updated:</strong> {{ $warranty->updated_at->format('M d, Y g:i A') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Asset Information -->
                    <div id="assetInfo" class="card mb-4" style="{{ old('asset_id', $warranty->asset_id ?? '') ? '' : 'display: none;' }}">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Asset Information</h6>
                        </div>
                        <div class="card-body">
                            <div id="assetDetails">
                                @if(isset($warranty->asset))
                                    <div class="mb-2">
                                        <strong>{{ $warranty->asset->name }}</strong>
                                        <br><small class="text-gray-600">{{ $warranty->asset->asset_tag }}</small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Category:</small> {{ $warranty->asset->category }}
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Location:</small> {{ $warranty->asset->location }}
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">Serial:</small> {{ $warranty->asset->serial_number }}
                                    </div>
                                    <a href="{{ route('assets.show', $warranty->asset->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> View Asset
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Warranty Calculator -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Warranty Calculator</h6>
                        </div>
                        <div class="card-body">
                            <div id="warrantyCalc">
                                <small class="text-muted">Duration will be calculated based on start and end dates</small>
                                <div id="durationResult" class="mt-2 p-2 bg-gray-100 rounded" style="display: none;">
                                    <strong>Duration:</strong> <span id="durationText"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="checkWarrantyStatus()">
                                    <i class="fas fa-search"></i> Check Status Online
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="contactSupport()">
                                    <i class="fas fa-phone"></i> Contact Support
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="downloadCertificate()">
                                    <i class="fas fa-certificate"></i> Download Certificate
                                </button>
                                @if($status === 'active')
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="renewWarranty()">
                                        <i class="fas fa-refresh"></i> Start Renewal
                                    </button>
                                @endif
                            </div>
                        </div>
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
    const startDateInput = document.getElementById('warranty_start_date');
    const endDateInput = document.getElementById('warranty_end_date');
    const durationResult = document.getElementById('durationResult');
    const durationText = document.getElementById('durationText');
    
    // Warranty duration calculator
    function calculateDuration() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end > start) {
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const months = Math.floor(diffDays / 30);
                const remainingDays = diffDays % 30;
                
                let durationString = '';
                if (months > 0) {
                    durationString += months + ' month' + (months > 1 ? 's' : '');
                    if (remainingDays > 0) {
                        durationString += ', ' + remainingDays + ' day' + (remainingDays > 1 ? 's' : '');
                    }
                } else {
                    durationString = diffDays + ' day' + (diffDays > 1 ? 's' : '');
                }
                
                durationText.textContent = durationString;
                durationResult.style.display = 'block';
            } else {
                durationResult.style.display = 'none';
            }
        } else {
            durationResult.style.display = 'none';
        }
    }
    
    startDateInput.addEventListener('change', calculateDuration);
    endDateInput.addEventListener('change', calculateDuration);
    
    // Initial calculation
    calculateDuration();
    
    // Form validation
    const form = document.getElementById('warrantyForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        const requiredFields = ['asset_id', 'warranty_type', 'provider_name', 'coverage_details', 'warranty_start_date', 'warranty_end_date'];
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Validate date range
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (endDate <= startDate) {
            endDateInput.classList.add('is-invalid');
            isValid = false;
            alert('Warranty end date must be after start date.');
        } else {
            endDateInput.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields correctly.');
        }
    });
});

// Quick action functions
function checkWarrantyStatus() {
    const supportUrl = document.getElementById('support_url').value;
    if (supportUrl) {
        window.open(supportUrl, '_blank');
    } else {
        alert('Support portal URL not available');
    }
}

function contactSupport() {
    const phone = document.getElementById('contact_phone').value;
    if (phone) {
        window.open(`tel:${phone}`);
    } else {
        alert('Support phone number not available');
    }
}

function downloadCertificate() {
    window.open('{{ route("assets.warranties.show", $warranty ?? 1) }}?format=pdf', '_blank');
}

function renewWarranty() {
    if (confirm('Start the warranty renewal process?')) {
        window.location.href = '{{ route("assets.warranties.renew", $warranty ?? 1) }}';
    }
}
</script>
@endpush