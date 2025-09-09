@extends('layouts.app')

@section('content')
<div class="w-full px-4">
    <div class="flex flex-wrap -mx-4">
        <div class="col-12">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Add Warranty</h1>
                    <p class="text-gray-600 mb-0">Create a new warranty record for an asset</p>
                </div>
                <div>
                    <a href="{{ route('assets.warranties.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap -mx-4">
                <div class="md:w-2/3 px-4">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h5 class="bg-white rounded-lg shadow-md overflow-hidden-title mb-0">Warranty Information</h5>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('assets.warranties.store') }}" method="POST" id="warrantyForm">
                                @csrf

                                <!-- Asset Selection -->
                                <div class="row mb-3">
                                    <div class="md:w-1/2 px-4">
                                        <label for="asset_id" class="block text-sm font-medium text-gray-700 mb-1">Asset <span class="text-red-600">*</span></label>
                                        <select name="asset_id" id="asset_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('asset_id') is-invalid @enderror" required>
                                            <option value="">Select an asset...</option>
                                            @foreach($assets ?? [] as $asset)
                                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
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
                                            <option value="manufacturer" {{ old('warranty_type') === 'manufacturer' ? 'selected' : '' }}>Manufacturer</option>
                                            <option value="extended" {{ old('warranty_type') === 'extended' ? 'selected' : '' }}>Extended</option>
                                            <option value="service" {{ old('warranty_type') === 'service' ? 'selected' : '' }}>Service Contract</option>
                                            <option value="maintenance" {{ old('warranty_type') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                        </select>
                                        @error('warranty_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Warranty Provider -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="provider_name" class="form-label">Warranty Provider <span class="text-danger">*</span></label>
                                        <input type="text" name="provider_name" id="provider_name" 
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('provider_name') is-invalid @enderror" 
                                               value="{{ old('provider_name') }}" required 
                                               placeholder="e.g., Dell Technologies, HP Inc.">
                                        @error('provider_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="warranty_number" class="form-label">Warranty Number</label>
                                        <input type="text" name="warranty_number" id="warranty_number" 
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('warranty_number') is-invalid @enderror" 
                                               value="{{ old('warranty_number') }}" 
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
                                              placeholder="Describe what is covered under this warranty...">{{ old('coverage_details') }}</textarea>
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
                                               value="{{ old('warranty_start_date', now()->format('Y-m-d')) }}" required>
                                        @error('warranty_start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="warranty_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" name="warranty_end_date" id="warranty_end_date" 
                                               class="form-control @error('warranty_end_date') is-invalid @enderror" 
                                               value="{{ old('warranty_end_date') }}" required>
                                        @error('warranty_end_date')
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
                                                   value="{{ old('cost') }}" min="0" step="0.01" placeholder="0.00">
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
                                                   value="{{ old('deductible', '0.00') }}" min="0" step="0.01" placeholder="0.00">
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
                                               value="{{ old('contact_name') }}" 
                                               placeholder="Contact person name">
                                        @error('contact_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_phone" class="form-label">Support Phone</label>
                                        <input type="tel" name="contact_phone" id="contact_phone" 
                                               class="form-control @error('contact_phone') is-invalid @enderror" 
                                               value="{{ old('contact_phone') }}" 
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
                                               value="{{ old('contact_email') }}" 
                                               placeholder="support@provider.com">
                                        @error('contact_email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="support_url" class="form-label">Support Portal URL</label>
                                        <input type="url" name="support_url" id="support_url" 
                                               class="form-control @error('support_url') is-invalid @enderror" 
                                               value="{{ old('support_url') }}" 
                                               placeholder="https://support.provider.com">
                                        @error('support_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Renewal and Notification Settings -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="auto_renewal" id="auto_renewal" 
                                                   class="form-check-input" value="1" 
                                                   {{ old('auto_renewal') ? 'checked' : '' }}>
                                            <label for="auto_renewal" class="form-check-label">
                                                Auto-renewal enabled
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notification_days" class="form-label">Notify before expiry (days)</label>
                                        <input type="number" name="notification_days" id="notification_days" 
                                               class="form-control @error('notification_days') is-invalid @enderror" 
                                               value="{{ old('notification_days', '30') }}" min="1" max="365" placeholder="30">
                                        @error('notification_days')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Terms and Conditions -->
                                <div class="mb-3">
                                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" id="terms_conditions" 
                                              class="form-control @error('terms_conditions') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Important terms, limitations, and conditions...">{{ old('terms_conditions') }}</textarea>
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
                                              placeholder="Additional notes about this warranty...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Submit Buttons -->
                                <div class="flex gap-2">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-save"></i> Create Warranty
                                    </button>
                                    <button type="submit" name="status" value="active" class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        <i class="fas fa-check"></i> Create & Activate
                                    </button>
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
                    <div class="card">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <h6 class="card-title mb-0">Warranty Guidelines</h6>
                        </div>
                        <div class="p-6">
                            <div class="mb-3">
                                <h6 class="text-blue-600">Warranty Types</h6>
                                <small class="text-gray-600">
                                    <strong>Manufacturer:</strong> Original equipment warranty<br>
                                    <strong>Extended:</strong> Additional coverage beyond standard<br>
                                    <strong>Service:</strong> Service and support contract<br>
                                    <strong>Maintenance:</strong> Maintenance agreement
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-info">Coverage Details</h6>
                                <small class="text-muted">
                                    Include specific coverage information:<br>
                                    • Parts replacement<br>
                                    • Labor costs<br>
                                    • On-site service<br>
                                    • Response time requirements
                                </small>
                            </div>

                            <div class="px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700">
                                <h6 class="alert-heading">📋 Important</h6>
                                <small>
                                    • Keep warranty documentation safe<br>
                                    • Set notification reminders<br>
                                    • Review terms carefully<br>
                                    • Track warranty claims
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Asset Info (populated via JavaScript when asset is selected) -->
                    <div id="assetInfo" class="card mt-3" style="display: none;">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Asset Information</h6>
                        </div>
                        <div class="card-body">
                            <div id="assetDetails">
                                <!-- Populated dynamically -->
                            </div>
                        </div>
                    </div>

                    <!-- Warranty Calculator -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Warranty Calculator</h6>
                        </div>
                        <div class="card-body">
                            <div id="warrantyCalc">
                                <small class="text-muted">Select start and end dates to calculate warranty duration</small>
                                <div id="durationResult" class="mt-2 p-2 bg-gray-100 rounded" style="display: none;">
                                    <strong>Duration:</strong> <span id="durationText"></span>
                                </div>
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
    const assetSelect = document.getElementById('asset_id');
    const assetInfoCard = document.getElementById('assetInfo');
    const assetDetails = document.getElementById('assetDetails');
    const startDateInput = document.getElementById('warranty_start_date');
    const endDateInput = document.getElementById('warranty_end_date');
    const durationResult = document.getElementById('durationResult');
    const durationText = document.getElementById('durationText');
    
    // Asset selection change handler
    assetSelect.addEventListener('change', function() {
        const assetId = this.value;
        
        if (assetId) {
            // Show loading state
            assetInfoCard.style.display = 'block';
            assetDetails.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            // In a real application, you would fetch asset details via AJAX
            // For now, we'll show the selected asset info
            const selectedOption = this.options[this.selectedIndex];
            assetDetails.innerHTML = `
                <div class="mb-2">
                    <strong>Asset:</strong> ${selectedOption.text}
                </div>
                <div class="mb-2">
                    <small class="text-muted">Existing warranty information would be loaded here via API call</small>
                </div>
            `;
        } else {
            assetInfoCard.style.display = 'none';
        }
    });
    
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
    
    // Auto-set end date when start date changes (default 1 year warranty)
    startDateInput.addEventListener('change', function() {
        if (!endDateInput.value && this.value) {
            const startDate = new Date(this.value);
            const endDate = new Date(startDate);
            endDate.setFullYear(endDate.getFullYear() + 1);
            endDateInput.value = endDate.toISOString().split('T')[0];
            calculateDuration();
        }
    });
    
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
</script>
@endpush