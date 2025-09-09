@extends('layouts.app')

@section('title', 'Edit Trip')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Edit Trip</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.trips.standalone.show', $trip) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-eye me-2"></i>View Trip
                    </a>
                    <a href="{{ route('clients.trips.standalone.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Trips
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clients.trips.standalone.update', $trip) }}">
                                @csrf
                                @method('PUT')

                                <!-- Trip Number -->
                                <div class="mb-3">
                                    <label class="form-label">Trip Number</label>
                                    <input type="text" class="form-control" value="{{ $trip->trip_number }}" readonly>
                                    <div class="form-text">Trip number is automatically generated</div>
                                </div>

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
                                                    {{ old('client_id', $trip->client_id) == $client->id ? 'selected' : '' }}>
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
                                           value="{{ old('title', $trip->title) }}" 
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
                                                      placeholder="Trip description...">{{ old('description', $trip->description) }}</textarea>
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
                                                   value="{{ old('purpose', $trip->purpose) }}" 
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
                                                    <option value="{{ $key }}" {{ old('trip_type', $trip->trip_type) == $key ? 'selected' : '' }}>
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
                                                    <option value="{{ $key }}" {{ old('transportation_mode', $trip->transportation_mode) == $key ? 'selected' : '' }}>
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
                                           value="{{ old('destination_address', $trip->destination_address) }}" 
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
                                                   value="{{ old('destination_city', $trip->destination_city) }}" 
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
                                                   value="{{ old('destination_state', $trip->destination_state) }}" 
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
                                                   value="{{ old('destination_country', $trip->destination_country) }}" 
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
                                                   value="{{ old('start_date', $trip->start_date ? $trip->start_date->format('Y-m-d') : '') }}" 
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
                                                   value="{{ old('end_date', $trip->end_date ? $trip->end_date->format('Y-m-d') : '') }}" 
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
                                                   value="{{ old('departure_time', $trip->departure_time ? $trip->departure_time->format('Y-m-d\TH:i') : '') }}">
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
                                                   value="{{ old('return_time', $trip->return_time ? $trip->return_time->format('Y-m-d\TH:i') : '') }}">
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
                                            <option value="{{ $key }}" {{ old('status', $trip->status) == $key ? 'selected' : '' }}>
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
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="estimated_expenses" class="form-label">Estimated Expenses</label>
                                            <input type="number" 
                                                   name="estimated_expenses" 
                                                   id="estimated_expenses" 
                                                   class="form-control @error('estimated_expenses') is-invalid @enderror" 
                                                   value="{{ old('estimated_expenses', $trip->estimated_expenses) }}" 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('estimated_expenses')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="actual_expenses" class="form-label">Actual Expenses</label>
                                            <input type="number" 
                                                   name="actual_expenses" 
                                                   id="actual_expenses" 
                                                   class="form-control @error('actual_expenses') is-invalid @enderror" 
                                                   value="{{ old('actual_expenses', $trip->actual_expenses) }}" 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('actual_expenses')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                                            <select name="currency" 
                                                    id="currency" 
                                                    class="form-select @error('currency') is-invalid @enderror" 
                                                    required>
                                                @foreach($currencies as $key => $value)
                                                    <option value="{{ $key }}" {{ old('currency', $trip->currency) == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="mileage" class="form-label">Mileage</label>
                                            <input type="number" 
                                                   name="mileage" 
                                                   id="mileage" 
                                                   class="form-control @error('mileage') is-invalid @enderror" 
                                                   value="{{ old('mileage', $trip->mileage) }}" 
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
                                           value="{{ old('per_diem_amount', $trip->per_diem_amount) }}" 
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
                                              placeholder="Enter expense details...">{{ old('expense_breakdown', $trip->expense_breakdown ? implode("\n", array_map(function($item) {
                                                return $item['category'] . ' | ' . $item['amount'] . ' | ' . ($item['description'] ?? '');
                                            }, $trip->expense_breakdown)) : '') }}</textarea>
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
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="billable_to_client" 
                                                       id="billable_to_client" 
                                                       value="1" 
                                                       {{ old('billable_to_client', $trip->billable_to_client) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="billable_to_client">
                                                    Billable to Client
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="reimbursable" 
                                                       id="reimbursable" 
                                                       value="1" 
                                                       {{ old('reimbursable', $trip->reimbursable) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="reimbursable">
                                                    Reimbursable
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="approval_required" 
                                                       id="approval_required" 
                                                       value="1" 
                                                       {{ old('approval_required', $trip->approval_required) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="approval_required">
                                                    Requires Approval
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="follow_up_required" 
                                                       id="follow_up_required" 
                                                       value="1" 
                                                       {{ old('follow_up_required', $trip->follow_up_required) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="follow_up_required">
                                                    Follow-up Required
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
                                              placeholder="Hotel bookings, special requirements...">{{ old('accommodation_details', $trip->accommodation_details) }}</textarea>
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
                                           value="{{ old('attendees', is_array($trip->attendees) ? implode(', ', $trip->attendees) : '') }}" 
                                           placeholder="John Doe, Jane Smith, client contacts">
                                    <div class="form-text">Separate multiple names with commas</div>
                                    @error('attendees')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Feedback and Rating (for completed trips) -->
                                @if($trip->isCompleted())
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="client_feedback" class="form-label">Client Feedback</label>
                                                <textarea name="client_feedback" 
                                                          id="client_feedback" 
                                                          class="form-control @error('client_feedback') is-invalid @enderror" 
                                                          rows="3" 
                                                          placeholder="Client feedback and comments...">{{ old('client_feedback', $trip->client_feedback) }}</textarea>
                                                @error('client_feedback')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="internal_rating" class="form-label">Internal Rating</label>
                                                <select name="internal_rating" id="internal_rating" class="form-select @error('internal_rating') is-invalid @enderror">
                                                    <option value="">Select rating...</option>
                                                    <option value="5" {{ old('internal_rating', $trip->internal_rating) == 5 ? 'selected' : '' }}>5 - Excellent</option>
                                                    <option value="4" {{ old('internal_rating', $trip->internal_rating) == 4 ? 'selected' : '' }}>4 - Very Good</option>
                                                    <option value="3" {{ old('internal_rating', $trip->internal_rating) == 3 ? 'selected' : '' }}>3 - Good</option>
                                                    <option value="2" {{ old('internal_rating', $trip->internal_rating) == 2 ? 'selected' : '' }}>2 - Fair</option>
                                                    <option value="1" {{ old('internal_rating', $trip->internal_rating) == 1 ? 'selected' : '' }}>1 - Poor</option>
                                                </select>
                                                @error('internal_rating')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Additional notes, special instructions...">{{ old('notes', $trip->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Trip
                                    </button>
                                    <a href="{{ route('clients.trips.standalone.show', $trip) }}" class="btn btn-outline-secondary">
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
                                <i class="fas fa-clock me-2"></i>Trip History
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <div class="mb-2">
                                    <strong>Created:</strong> {{ $trip->created_at->format('M j, Y g:i A') }}
                                    @if($trip->creator)
                                        <br><strong>Created by:</strong> {{ $trip->creator->name }}
                                    @endif
                                </div>
                                @if($trip->updated_at != $trip->created_at)
                                    <div class="mb-2">
                                        <strong>Last updated:</strong> {{ $trip->updated_at->format('M j, Y g:i A') }}
                                    </div>
                                @endif
                                @if($trip->approved_at)
                                    <div class="mb-2">
                                        <strong>Approved:</strong> {{ $trip->approved_at->format('M j, Y g:i A') }}
                                        @if($trip->approver)
                                            <br><strong>Approved by:</strong> {{ $trip->approver->name }}
                                        @endif
                                    </div>
                                @endif
                                @if($trip->completed_at)
                                    <div>
                                        <strong>Completed:</strong> {{ $trip->completed_at->format('M j, Y g:i A') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($trip->expense_variance !== null)
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calculator me-2"></i>Expense Analysis
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <div class="p-2 bg-light rounded">
                                            <div class="fw-bold text-info">{{ $trip->formatted_estimated_expenses }}</div>
                                            <small class="text-muted">Estimated</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 bg-light rounded">
                                            <div class="fw-bold text-primary">{{ $trip->formatted_actual_expenses }}</div>
                                            <small class="text-muted">Actual</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 bg-light rounded">
                                            <div class="fw-bold {{ $trip->expense_variance > 0 ? 'text-danger' : ($trip->expense_variance < 0 ? 'text-success' : 'text-info') }}">
                                                {{ $trip->getCurrencySymbol() }}{{ number_format(abs($trip->expense_variance), 2) }}
                                            </div>
                                            <small class="text-muted">Variance</small>