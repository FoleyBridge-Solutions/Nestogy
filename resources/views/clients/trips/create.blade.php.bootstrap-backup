@extends('layouts.app')

@section('title', 'Create Trip')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create Trip</h1>
                <a href="{{ route('clients.trips.standalone.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Trips
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clients.trips.standalone.store') }}">
                                @csrf

                                <!-- Client Selection -->
                                <div class="mb-3">
                                    <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                                    <select name="client_id" 
                                            id="client_id" 
                                            class="form-select @error('client_id') is-invalid @enderror" 
                                            required>
                                        <option value="">Select a client...</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" 
                                                    {{ old('client_id', $selectedClientId) == $client->id ? 'selected' : '' }}>
                                                {{ $client->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Trip Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Trip Title <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           value="{{ old('title') }}" 
                                           required 
                                           maxlength="255"
                                           placeholder="Enter trip title">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Description and Purpose -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea name="description" 
                                                      id="description" 
                                                      class="form-control @error('description') is-invalid @enderror" 
                                                      rows="3" 
                                                      placeholder="Trip description...">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="purpose" class="form-label">Purpose</label>
                                            <input type="text" 
                                                   name="purpose" 
                                                   id="purpose" 
                                                   class="form-control @error('purpose') is-invalid @enderror" 
                                                   value="{{ old('purpose') }}" 
                                                   maxlength="255"
                                                   placeholder="Meeting, support, installation...">
                                            @error('purpose')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Trip Type and Transportation -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="trip_type" class="form-label">Trip Type <span class="text-danger">*</span></label>
                                            <select name="trip_type" 
                                                    id="trip_type" 
                                                    class="form-select @error('trip_type') is-invalid @enderror" 
                                                    required>
                                                <option value="">Select trip type...</option>
                                                @foreach($tripTypes as $key => $value)
                                                    <option value="{{ $key }}" {{ old('trip_type') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('trip_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="transportation_mode" class="form-label">Transportation <span class="text-danger">*</span></label>
                                            <select name="transportation_mode" 
                                                    id="transportation_mode" 
                                                    class="form-select @error('transportation_mode') is-invalid @enderror" 
                                                    required>
                                                <option value="">Select transportation...</option>
                                                @foreach($transportationModes as $key => $value)
                                                    <option value="{{ $key }}" {{ old('transportation_mode') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('transportation_mode')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Destination -->
                                <div class="mb-3">
                                    <label for="destination_address" class="form-label">Destination Address</label>
                                    <input type="text" 
                                           name="destination_address" 
                                           id="destination_address" 
                                           class="form-control @error('destination_address') is-invalid @enderror" 
                                           value="{{ old('destination_address') }}" 
                                           maxlength="255"
                                           placeholder="Street address">
                                    @error('destination_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="destination_city" class="form-label">City <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   name="destination_city" 
                                                   id="destination_city" 
                                                   class="form-control @error('destination_city') is-invalid @enderror" 
                                                   value="{{ old('destination_city') }}" 
                                                   required 
                                                   maxlength="100"
                                                   placeholder="City">
                                            @error('destination_city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="destination_state" class="form-label">State/Province</label>
                                            <input type="text" 
                                                   name="destination_state" 
                                                   id="destination_state" 
                                                   class="form-control @error('destination_state') is-invalid @enderror" 
                                                   value="{{ old('destination_state') }}" 
                                                   maxlength="100"
                                                   placeholder="State/Province">
                                            @error('destination_state')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="destination_country" class="form-label">Country</label>
                                            <input type="text" 
                                                   name="destination_country" 
                                                   id="destination_country" 
                                                   class="form-control @error('destination_country') is-invalid @enderror" 
                                                   value="{{ old('destination_country', 'United States') }}" 
                                                   maxlength="100"
                                                   placeholder="Country">
                                            @error('destination_country')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Dates and Times -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" 
                                                   name="start_date" 
                                                   id="start_date" 
                                                   class="form-control @error('start_date') is-invalid @enderror" 
                                                   value="{{ old('start_date') }}" 
                                                   required>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                            <input type="date" 
                                                   name="end_date" 
                                                   id="end_date" 
                                                   class="form-control @error('end_date') is-invalid @enderror" 
                                                   value="{{ old('end_date') }}" 
                                                   required>
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="departure_time" class="form-label">Departure Time</label>
                                            <input type="datetime-local" 
                                                   name="departure_time" 
                                                   id="departure_time" 
                                                   class="form-control @error('departure_time') is-invalid @enderror" 
                                                   value="{{ old('departure_time') }}">
                                            @error('departure_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="return_time" class="form-label">Return Time</label>
                                            <input type="datetime-local" 
                                                   name="return_time" 
                                                   id="return_time" 
                                                   class="form-control @error('return_time') is-invalid @enderror" 
                                                   value="{{ old('return_time') }}">
                                            @error('return_time')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="status" 
                                            id="status" 
                                            class="form-select @error('status') is-invalid @enderror" 
                                            required>
                                        @foreach($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ old('status', 'planned') == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Expenses -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="estimated_expenses" class="form-label">Estimated Expenses</label>
                                            <input type="number" 
                                                   name="estimated_expenses" 
                                                   id="estimated_expenses" 
                                                   class="form-control @error('estimated_expenses') is-invalid @enderror" 
                                                   value="{{ old('estimated_expenses') }}" 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('estimated_expenses')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                            <select name="currency" 
                                                    id="currency" 
                                                    class="form-select @error('currency') is-invalid @enderror" 
                                                    required>
                                                @foreach($currencies as $key => $value)
                                                    <option value="{{ $key }}" {{ old('currency', 'USD') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="mileage" class="form-label">Mileage</label>
                                            <input type="number" 
                                                   name="mileage" 
                                                   id="mileage" 
                                                   class="form-control @error('mileage') is-invalid @enderror" 
                                                   value="{{ old('mileage') }}" 
                                                   min="0" 
                                                   step="0.1"
                                                   placeholder="0.0">
                                            @error('mileage')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Per Diem -->
                                <div class="mb-3">
                                    <label for="per_diem_amount" class="form-label">Per Diem Amount</label>
                                    <input type="number" 
                                           name="per_diem_amount" 
                                           id="per_diem_amount" 
                                           class="form-control @error('per_diem_amount') is-invalid @enderror" 
                                           value="{{ old('per_diem_amount') }}" 
                                           min="0" 
                                           step="0.01"
                                           placeholder="0.00">
                                    <div class="form-text">Daily allowance for meals and incidental expenses</div>
                                    @error('per_diem_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Expense Breakdown -->
                                <div class="mb-3">
                                    <label for="expense_breakdown" class="form-label">Expense Breakdown</label>
                                    <textarea name="expense_breakdown" 
                                              id="expense_breakdown" 
                                              class="form-control @error('expense_breakdown') is-invalid @enderror" 
                                              rows="4" 
                                              placeholder="Enter expense details...">{{ old('expense_breakdown') }}</textarea>
                                    <div class="form-text">
                                        Format: Category | Amount | Description<br>
                                        Example: Transportation | 150.00 | Flight tickets
                                    </div>
                                    @error('expense_breakdown')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Settings -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="billable_to_client" 
                                                       id="billable_to_client" 
                                                       value="1" 
                                                       {{ old('billable_to_client') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="billable_to_client">
                                                    Billable to Client
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="reimbursable" 
                                                       id="reimbursable" 
                                                       value="1" 
                                                       {{ old('reimbursable') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="reimbursable">
                                                    Reimbursable
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="approval_required" 
                                                       id="approval_required" 
                                                       value="1" 
                                                       {{ old('approval_required') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="approval_required">
                                                    Requires Approval
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Accommodation -->
                                <div class="mb-3">
                                    <label for="accommodation_details" class="form-label">Accommodation Details</label>
                                    <textarea name="accommodation_details" 
                                              id="accommodation_details" 
                                              class="form-control @error('accommodation_details') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Hotel bookings, special requirements...">{{ old('accommodation_details') }}</textarea>
                                    @error('accommodation_details')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Attendees -->
                                <div class="mb-3">
                                    <label for="attendees" class="form-label">Attendees</label>
                                    <input type="text" 
                                           name="attendees" 
                                           id="attendees" 
                                           class="form-control @error('attendees') is-invalid @enderror" 
                                           value="{{ old('attendees') }}" 
                                           placeholder="John Doe, Jane Smith, client contacts">
                                    <div class="form-text">Separate multiple names with commas</div>
                                    @error('attendees')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Additional notes, special instructions...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Trip
                                    </button>
                                    <a href="{{ route('clients.trips.standalone.index') }}" class="btn btn-outline-secondary">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Trip Planning Guide
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <h6>Trip Types:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Client Visit:</strong> General client meetings and visits</li>
                                    <li><strong>Site Inspection:</strong> On-site assessments and evaluations</li>
                                    <li><strong>Installation:</strong> Equipment or software installation</li>
                                    <li><strong>Training:</strong> Client training sessions</li>
                                    <li><strong>Support:</strong> Technical support and troubleshooting</li>
                                    <li><strong>Maintenance:</strong> Routine maintenance activities</li>
                                </ul>

                                <h6 class="mt-3">Expense Planning:</h6>
                                <ul class="list-unstyled">
                                    <li>• Include transportation, lodging, meals</li>
                                    <li>• Consider parking, tolls, and incidentals</li>
                                    <li>• Set realistic per diem amounts</li>
                                    <li>• Track mileage for reimbursement</li>
                                </ul>

                                <h6 class="mt-3">Best Practices:</h6>
                                <ul class="list-unstyled">
                                    <li>• Plan trips well in advance</li>
                                    <li>• Coordinate with client schedules</li>
                                    <li>• Include buffer time for delays</li>
                                    <li>• Prepare all necessary materials</li>
                                    <li>• Confirm accommodation reservations</li>
                                </ul>
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
    // Auto-fill end date when start date changes
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    startDateInput.addEventListener('change', function() {
        if (this.value && !endDateInput.value) {
            endDateInput.value = this.value;
        }
    });
    
    // Update departure/return times based on dates
    const departureTimeInput = document.getElementById('departure_time');
    const returnTimeInput = document.getElementById('return_time');
    
    startDateInput.addEventListener('change', function() {
        if (this.value && !departureTimeInput.value) {
            departureTimeInput.value = this.value + 'T08:00';
        }
    });
    
    endDateInput.addEventListener('change', function() {
        if (this.value && !returnTimeInput.value) {
            returnTimeInput.value = this.value + 'T17:00';
        }
    });
});
</script>
@endpush