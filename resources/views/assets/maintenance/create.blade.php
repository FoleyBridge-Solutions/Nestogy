@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Schedule Maintenance</h1>
                    <p class="text-muted mb-0">Create a new maintenance task for an asset</p>
                </div>
                <div>
                    <a href="{{ route('assets.maintenance.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Maintenance Details</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('assets.maintenance.store') }}" method="POST" id="maintenanceForm">
                                @csrf

                                <!-- Asset Selection -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="asset_id" class="form-label">Asset <span class="text-danger">*</span></label>
                                        <select name="asset_id" id="asset_id" class="form-select @error('asset_id') is-invalid @enderror" required>
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
                                        <label for="maintenance_type" class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                        <select name="maintenance_type" id="maintenance_type" class="form-select @error('maintenance_type') is-invalid @enderror" required>
                                            <option value="">Select type...</option>
                                            <option value="preventive" {{ old('maintenance_type') === 'preventive' ? 'selected' : '' }}>Preventive</option>
                                            <option value="corrective" {{ old('maintenance_type') === 'corrective' ? 'selected' : '' }}>Corrective</option>
                                            <option value="emergency" {{ old('maintenance_type') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                            <option value="routine" {{ old('maintenance_type') === 'routine' ? 'selected' : '' }}>Routine</option>
                                        </select>
                                        @error('maintenance_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Title and Description -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" 
                                           value="{{ old('title') }}" required placeholder="Brief description of maintenance task">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" 
                                              rows="4" placeholder="Detailed description of maintenance task...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Scheduling -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="scheduled_date" class="form-label">Scheduled Date</label>
                                        <input type="date" name="scheduled_date" id="scheduled_date" 
                                               class="form-control @error('scheduled_date') is-invalid @enderror" 
                                               value="{{ old('scheduled_date') }}">
                                        @error('scheduled_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="estimated_duration" class="form-label">Estimated Duration (hours)</label>
                                        <input type="number" name="estimated_duration" id="estimated_duration" 
                                               class="form-control @error('estimated_duration') is-invalid @enderror" 
                                               value="{{ old('estimated_duration') }}" min="0.5" step="0.5" placeholder="2.0">
                                        @error('estimated_duration')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Priority and Assignment -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                        <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                            <option value="">Select priority...</option>
                                            <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                            <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                                        </select>
                                        @error('priority')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="assigned_to" class="form-label">Assign To</label>
                                        <select name="assigned_to" id="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror">
                                            <option value="">Unassigned</option>
                                            @foreach($technicians ?? [] as $user)
                                                <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('assigned_to')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Cost and Recurring -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="estimated_cost" class="form-label">Estimated Cost</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="estimated_cost" id="estimated_cost" 
                                                   class="form-control @error('estimated_cost') is-invalid @enderror" 
                                                   value="{{ old('estimated_cost') }}" min="0" step="0.01" placeholder="0.00">
                                        </div>
                                        @error('estimated_cost')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="recurring_interval" class="form-label">Recurring Interval</label>
                                        <select name="recurring_interval" id="recurring_interval" class="form-select @error('recurring_interval') is-invalid @enderror">
                                            <option value="">One-time maintenance</option>
                                            <option value="weekly" {{ old('recurring_interval') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                            <option value="monthly" {{ old('recurring_interval') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ old('recurring_interval') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="semi_annually" {{ old('recurring_interval') === 'semi_annually' ? 'selected' : '' }}>Semi-Annually</option>
                                            <option value="annually" {{ old('recurring_interval') === 'annually' ? 'selected' : '' }}>Annually</option>
                                        </select>
                                        @error('recurring_interval')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Instructions and Notes -->
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Maintenance Instructions</label>
                                    <textarea name="instructions" id="instructions" class="form-control @error('instructions') is-invalid @enderror" 
                                              rows="3" placeholder="Step-by-step instructions for performing this maintenance...">{{ old('instructions') }}</textarea>
                                    @error('instructions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" placeholder="Additional notes or comments...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Submit Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Schedule Maintenance
                                    </button>
                                    <button type="submit" name="status" value="in_progress" class="btn btn-success">
                                        <i class="fas fa-play"></i> Schedule & Start
                                    </button>
                                    <a href="{{ route('assets.maintenance.index') }}" class="btn btn-outline-secondary">
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
                        <div class="card-header">
                            <h6 class="card-title mb-0">Maintenance Guidelines</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-primary">Maintenance Types</h6>
                                <small class="text-muted">
                                    <strong>Preventive:</strong> Scheduled regular maintenance<br>
                                    <strong>Corrective:</strong> Fix identified issues<br>
                                    <strong>Emergency:</strong> Urgent unscheduled repairs<br>
                                    <strong>Routine:</strong> Regular operational checks
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-warning">Priority Levels</h6>
                                <small class="text-muted">
                                    <strong>Critical:</strong> Immediate attention required<br>
                                    <strong>High:</strong> Complete within 24 hours<br>
                                    <strong>Medium:</strong> Complete within a week<br>
                                    <strong>Low:</strong> Complete when convenient
                                </small>
                            </div>

                            <div class="alert alert-info">
                                <h6 class="alert-heading">💡 Pro Tips</h6>
                                <small>
                                    • Set realistic time estimates<br>
                                    • Include detailed instructions<br>
                                    • Consider parts availability<br>
                                    • Schedule during low usage periods
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
                    <small class="text-muted">Last maintenance details would be loaded here via API call</small>
                </div>
            `;
        } else {
            assetInfoCard.style.display = 'none';
        }
    });
    
    // Form validation
    const form = document.getElementById('maintenanceForm');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        const requiredFields = ['asset_id', 'maintenance_type', 'title', 'priority'];
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Auto-populate title based on maintenance type and asset
    const maintenanceTypeSelect = document.getElementById('maintenance_type');
    maintenanceTypeSelect.addEventListener('change', function() {
        const titleField = document.getElementById('title');
        const assetOption = assetSelect.options[assetSelect.selectedIndex];
        
        if (this.value && assetSelect.value && !titleField.value) {
            const assetName = assetOption.text.split(' (')[0];
            const typeText = this.options[this.selectedIndex].text;
            titleField.value = `${typeText} maintenance for ${assetName}`;
        }
    });
});
</script>
@endpush