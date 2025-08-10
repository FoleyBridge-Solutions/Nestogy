@extends('layouts.app')

@section('title', 'Trip Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Trip Details</h1>
                <div class="btn-group">
                    @if(!in_array($trip->status, ['completed', 'cancelled']))
                        <a href="{{ route('clients.trips.standalone.edit', $trip) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Trip
                        </a>
                        @if($trip->requiresApproval())
                            <button type="button" class="btn btn-success" onclick="approveTrip({{ $trip->id }})">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                        @endif
                        @if($trip->status === 'approved')
                            <button type="button" class="btn btn-info" onclick="startTrip({{ $trip->id }})">
                                <i class="fas fa-play me-2"></i>Start Trip
                            </button>
                        @endif
                        @if($trip->status === 'in_progress')
                            <button type="button" class="btn btn-success" onclick="completeTrip({{ $trip->id }})">
                                <i class="fas fa-flag-checkered me-2"></i>Complete Trip
                            </button>
                        @endif
                        <button type="button" class="btn btn-outline-warning" onclick="cancelTrip({{ $trip->id }})">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    @endif
                    @if($trip->reimbursable && $trip->isCompleted() && !$trip->submitted_for_reimbursement)
                        <button type="button" class="btn btn-outline-info" onclick="submitReimbursement({{ $trip->id }})">
                            <i class="fas fa-receipt me-2"></i>Submit Reimbursement
                        </button>
                    @endif
                    <a href="{{ route('clients.trips.standalone.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Trips
                    </a>
                    <button type="button" 
                            class="btn btn-outline-danger" 
                            onclick="deleteTrip({{ $trip->id }})">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Main Trip Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-route me-2"></i>{{ $trip->trip_number }}
                                </h5>
                                <div class="d-flex gap-2">
                                    @switch($trip->status)
                                        @case('planned')
                                            <span class="badge bg-secondary">Planned</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-success">Approved</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-primary">In Progress</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                            @break
                                        @case('postponed')
                                            <span class="badge bg-warning">Postponed</span>
                                            @break
                                    @endswitch

                                    @if($trip->billable_to_client)
                                        <span class="badge bg-info">Billable</span>
                                    @endif

                                    @if($trip->reimbursable)
                                        <span class="badge bg-warning">Reimbursable</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Trip Title</h6>
                                    <p class="mb-3 fw-bold">{{ $trip->title }}</p>

                                    <h6 class="text-muted">Client</h6>
                                    <p class="mb-3">
                                        <a href="{{ route('clients.show', $trip->client) }}" class="text-decoration-none">
                                            {{ $trip->client->display_name }}
                                        </a>
                                    </p>

                                    <h6 class="text-muted">Trip Type</h6>
                                    <p class="mb-3">{{ ucfirst(str_replace('_', ' ', $trip->trip_type)) }}</p>

                                    @if($trip->purpose)
                                        <h6 class="text-muted">Purpose</h6>
                                        <p class="mb-3">{{ $trip->purpose }}</p>
                                    @endif

                                    @if($trip->description)
                                        <h6 class="text-muted">Description</h6>
                                        <div class="mb-3">
                                            {!! nl2br(e($trip->description)) !!}
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-muted">Destination</h6>
                                    <div class="mb-3">
                                        <div class="fw-bold">{{ $trip->full_destination }}</div>
                                        @if($trip->mileage > 0)
                                            <small class="text-muted">
                                                <i class="fas fa-road me-1"></i>{{ number_format($trip->mileage, 1) }} miles
                                            </small>
                                        @endif
                                    </div>

                                    <h6 class="text-muted">Transportation</h6>
                                    <p class="mb-3">{{ ucfirst(str_replace('_', ' ', $trip->transportation_mode)) }}</p>

                                    <h6 class="text-muted">Schedule</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Start Date:</span>
                                            <span class="fw-bold">{{ $trip->start_date->format('M j, Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>End Date:</span>
                                            <span class="fw-bold">{{ $trip->end_date->format('M j, Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Duration:</span>
                                            <span>{{ $trip->duration_in_days }} day(s)</span>
                                        </div>
                                        @if($trip->departure_time)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Departure:</span>
                                                <span>{{ $trip->departure_time->format('M j, Y g:i A') }}</span>
                                            </div>
                                        @endif
                                        @if($trip->return_time)
                                            <div class="d-flex justify-content-between">
                                                <span>Return:</span>
                                                <span>{{ $trip->return_time->format('M j, Y g:i A') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($trip->days_until_trip !== null)
                                        <div class="alert alert-info py-2">
                                            <i class="fas fa-clock me-2"></i>
                                            <strong>{{ $trip->time_until_trip }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($trip->accommodation_details)
                                <hr>
                                <h6 class="text-muted">Accommodation</h6>
                                <div class="mb-3 p-3 bg-light rounded">
                                    {!! nl2br(e($trip->accommodation_details)) !!}
                                </div>
                            @endif

                            @if($trip->attendees && count($trip->attendees) > 0)
                                <hr>
                                <h6 class="text-muted">Attendees</h6>
                                <div class="mb-3">
                                    @foreach($trip->attendees as $attendee)
                                        <span class="badge bg-light text-dark me-2 mb-1">
                                            <i class="fas fa-user me-1"></i>{{ $attendee }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            @if($trip->notes)
                                <hr>
                                <h6 class="text-muted">Notes</h6>
                                <div class="bg-light p-3 rounded">
                                    {!! nl2br(e($trip->notes)) !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Expense Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>Expense Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="fw-bold text-info fs-5">{{ $trip->formatted_estimated_expenses }}</div>
                                        <small class="text-muted">Estimated Expenses</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="fw-bold text-primary fs-5">{{ $trip->formatted_actual_expenses }}</div>
                                        <small class="text-muted">Actual Expenses</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        @if($trip->expense_variance !== null)
                                            <div class="fw-bold fs-5 {{ $trip->expense_variance > 0 ? 'text-danger' : ($trip->expense_variance < 0 ? 'text-success' : 'text-info') }}">
                                                {{ $trip->formatted_expense_variance }}
                                            </div>
                                        @else
                                            <div class="fw-bold text-muted fs-5">N/A</div>
                                        @endif
                                        <small class="text-muted">Variance</small>
                                    </div>
                                </div>
                            </div>

                            @if($trip->per_diem_amount > 0)
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between">
                                            <span>Per Diem Amount:</span>
                                            <span class="fw-bold">{{ $trip->getCurrencySymbol() }}{{ number_format($trip->per_diem_amount, 2) }} / day</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between">
                                            <span>Total Per Diem:</span>
                                            <span class="fw-bold">{{ $trip->getCurrencySymbol() }}{{ number_format($trip->per_diem_amount * $trip->duration_in_days, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if($trip->expense_breakdown && count($trip->expense_breakdown) > 0)
                                <hr>
                                <h6 class="text-muted">Expense Breakdown</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Category</th>
                                                <th class="text-end">Amount</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($trip->expense_breakdown as $expense)
                                                <tr>
                                                    <td class="fw-bold">{{ $expense['category'] }}</td>
                                                    <td class="text-end">{{ $trip->getCurrencySymbol() }}{{ number_format($expense['amount'], 2) }}</td>
                                                    <td class="text-muted">{{ $expense['description'] ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>Total</th>
                                                <th class="text-end">{{ $trip->getCurrencySymbol() }}{{ number_format($trip->calculateTotalExpenses(), 2) }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Feedback Section (for completed trips) -->
                    @if($trip->isCompleted() && ($trip->client_feedback || $trip->internal_rating))
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-comments me-2"></i>Trip Feedback
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($trip->client_feedback)
                                    <h6 class="text-muted">Client Feedback</h6>
                                    <div class="mb-3 p-3 bg-light rounded">
                                        {!! nl2br(e($trip->client_feedback)) !!}
                                    </div>
                                @endif

                                @if($trip->internal_rating)
                                    <h6 class="text-muted">Internal Rating</h6>
                                    <div class="mb-3">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $trip->internal_rating ? 'text-warning' : 'text-muted' }}"></i>
                                        @endfor
                                        <span class="ms-2">{{ $trip->internal_rating }}/5</span>
                                        <span class="text-muted ms-2">
                                            @switch($trip->internal_rating)
                                                @case(5) (Excellent) @break
                                                @case(4) (Very Good) @break
                                                @case(3) (Good) @break
                                                @case(2) (Fair) @break
                                                @case(1) (Poor) @break
                                            @endswitch
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <!-- Trip Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Trip Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Trip Number:</span>
                                    <span class="fw-bold">{{ $trip->trip_number }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Currency:</span>
                                    <span>{{ $trip->currency }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Created:</span>
                                    <span>{{ $trip->created_at->format('M j, Y') }}</span>
                                </div>
                                @if($trip->creator)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Created by:</span>
                                        <span>{{ $trip->creator->name }}</span>
                                    </div>
                                @endif
                                @if($trip->approved_at)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Approved:</span>
                                        <span>{{ $trip->approved_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                    @if($trip->approver)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Approved by:</span>
                                            <span>{{ $trip->approver->name }}</span>
                                        </div>
                                    @endif
                                @endif
                                @if($trip->completed_at)
                                    <div class="d-flex justify-content-between">
                                        <span>Completed:</span>
                                        <span>{{ $trip->completed_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($trip->requiresApproval())
                                    <button class="btn btn-success btn-sm" onclick="approveTrip({{ $trip->id }})">
                                        <i class="fas fa-check me-2"></i>Approve Trip
                                    </button>
                                @endif

                                @if($trip->status === 'approved')
                                    <button class="btn btn-info btn-sm" onclick="startTrip({{ $trip->id }})">
                                        <i class="fas fa-play me-2"></i>Start Trip
                                    </button>
                                @endif

                                @if($trip->status === 'in_progress')
                                    <button class="btn btn-success btn-sm" onclick="completeTrip({{ $trip->id }})">
                                        <i class="fas fa-flag-checkered me-2"></i>Complete Trip
                                    </button>
                                @endif

                                @if($trip->reimbursable && $trip->isCompleted() && !$trip->submitted_for_reimbursement)
                                    <button class="btn btn-warning btn-sm" onclick="submitReimbursement({{ $trip->id }})">
                                        <i class="fas fa-receipt me-2"></i>Submit for Reimbursement
                                    </button>
                                @endif

                                @if(!in_array($trip->status, ['completed', 'cancelled']))
                                    <button class="btn btn-outline-warning btn-sm" onclick="cancelTrip({{ $trip->id }})">
                                        <i class="fas fa-times me-2"></i>Cancel Trip
                                    </button>
                                @endif

                                <hr class="my-2">
                                
                                <a href="{{ route('clients.trips.standalone.create', ['client_id' => $trip->client_id]) }}" 
                                   class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-plus me-2"></i>New Trip for Client
                                </a>
                                
                                <a href="{{ route('clients.show', $trip->client) }}" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-user me-2"></i>View Client
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Reimbursement Status -->
                    @if($trip->reimbursable)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-receipt me-2"></i>Reimbursement Status
                                </h5>
                            </div>
                            <div class="card-body">
                                @if($trip->submitted_for_reimbursement)
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Submitted for reimbursement
                                        @if($trip->reimbursement_amount)
                                            <br>Amount: {{ $trip->getCurrencySymbol() }}{{ number_format($trip->reimbursement_amount, 2) }}
                                        @endif
                                    </div>
                                    @if($trip->reimbursement_date)
                                        <div class="small text-muted">
                                            Reimbursed on: {{ $trip->reimbursement_date->format('M j, Y') }}
                                        </div>
                                    @endif
                                @elseif($trip->isCompleted())
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Ready for reimbursement submission
                                    </div>
                                @else
                                    <div class="text-muted">
                                        <i class="fas fa-clock me-2"></i>
                                        Reimbursement available after trip completion
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<!-- Delete Trip Modal -->
<div class="modal fade" id="deleteTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this trip? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteTripForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Approve Trip Modal -->
<div class="modal fade" id="approveTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Approve this trip? This will change the status to approved.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="approveTripForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Start Trip Modal -->
<div class="modal fade" id="startTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Start this trip? This will change the status to in progress.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="startTripForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Start Trip</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Complete Trip Modal -->
<div class="modal fade" id="completeTripModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="completeTripForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Complete this trip by providing final details:</p>
                    
                    <div class="mb-3">
                        <label for="actual_expenses" class="form-label">Actual Expenses</label>
                        <input type="number" class="form-control" name="actual_expenses" id="actual_expenses" step="0.01" min="0" value="{{ $trip->actual_expenses }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="client_feedback" class="form-label">Client Feedback</label>
                        <textarea class="form-control" name="client_feedback" id="client_feedback" rows="3">{{ $trip->client_feedback }}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="internal_rating" class="form-label">Internal Rating (1-5)</label>
                        <select class="form-select" name="internal_rating" id="internal_rating">
                            <option value="">Select rating...</option>
                            <option value="5" {{ $trip->internal_rating == 5 ? 'selected' : '' }}>5 - Excellent</option>
                            <option value="4" {{ $trip->internal_rating == 4 ? 'selected' : '' }}>4 - Very Good</option>
                            <option value="3" {{ $trip->internal_rating == 3 ? 'selected' : '' }}>3 - Good</option>
                            <option value="2" {{ $trip->internal_rating == 2 ? 'selected' : '' }}>2 - Fair</option>
                            <option value="1" {{ $trip->internal_rating == 1 ? 'selected' : '' }}>1 - Poor</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="follow_up_required" id="follow_up_required" {{ $trip->follow_up_required ? 'checked' : '' }}>
                        <label class="form-check-label" for="follow_up_required">
                            Follow-up required
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Trip Modal -->
<div class="modal fade" id="cancelTripModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Trip</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelTripForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to cancel this trip?</p>
                    <div class="mb-3">
                        <label for="cancelReason" class="form-label">Reason (Optional)</label>
                        <textarea name="reason" id="cancelReason" class="form-control" rows="3" placeholder="Reason for cancelling..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Cancel Trip</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Submit Reimbursement Modal -->
<div class="modal fade" id="submitReimbursementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit for Reimbursement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="submitReimbursementForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Submit this trip for expense reimbursement?</p>
                    <div class="mb-3">
                        <label for="reimbursementAmount" class="form-label">Reimbursement Amount <span class="text-danger