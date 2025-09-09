@extends('layouts.app')

@section('title', 'Create Recurring Invoice')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create Recurring Invoice</h1>
                <a href="{{ route('clients.recurring-invoices.standalone.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Recurring Invoices
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clients.recurring-invoices.standalone.store') }}">
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

                                <!-- Template Name -->
                                <div class="mb-3">
                                    <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           name="template_name" 
                                           id="template_name" 
                                           class="form-control @error('template_name') is-invalid @enderror" 
                                           value="{{ old('template_name') }}" 
                                           required 
                                           maxlength="255"
                                           placeholder="Enter template name">
                                    @error('template_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea name="description" 
                                              id="description" 
                                              class="form-control @error('description') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Invoice description...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Amount and Currency -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                            <input type="number" 
                                                   name="amount" 
                                                   id="amount" 
                                                   class="form-control @error('amount') is-invalid @enderror" 
                                                   value="{{ old('amount') }}" 
                                                   required 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('amount')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
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
                                </div>

                                <!-- Tax Rate -->
                                <div class="mb-3">
                                    <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                    <input type="number" 
                                           name="tax_rate" 
                                           id="tax_rate" 
                                           class="form-control @error('tax_rate') is-invalid @enderror" 
                                           value="{{ old('tax_rate', 0) }}" 
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           placeholder="0.00">
                                    @error('tax_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Frequency Settings -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                                            <select name="frequency" 
                                                    id="frequency" 
                                                    class="form-select @error('frequency') is-invalid @enderror" 
                                                    required>
                                                @foreach($frequencies as $key => $value)
                                                    <option value="{{ $key }}" {{ old('frequency', 'monthly') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('frequency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="interval_count" class="form-label">Interval Count</label>
                                            <input type="number" 
                                                   name="interval_count" 
                                                   id="interval_count" 
                                                   class="form-control @error('interval_count') is-invalid @enderror" 
                                                   value="{{ old('interval_count', 1) }}" 
                                                   min="1" 
                                                   placeholder="1">
                                            <div class="form-text">Number of frequency periods between invoices</div>
                                            @error('interval_count')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Settings -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input type="date" 
                                                   name="start_date" 
                                                   id="start_date" 
                                                   class="form-control @error('start_date') is-invalid @enderror" 
                                                   value="{{ old('start_date', date('Y-m-d')) }}" 
                                                   required>
                                            @error('start_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input type="date" 
                                                   name="end_date" 
                                                   id="end_date" 
                                                   class="form-control @error('end_date') is-invalid @enderror" 
                                                   value="{{ old('end_date') }}">
                                            <div class="form-text">Leave blank for indefinite recurring</div>
                                            @error('end_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Timing Options -->
                                <div class="row" id="timing-options">
                                    <div class="col-md-6">
                                        <div class="mb-3" id="day-of-month-field">
                                            <label for="day_of_month" class="form-label">Day of Month</label>
                                            <input type="number" 
                                                   name="day_of_month" 
                                                   id="day_of_month" 
                                                   class="form-control @error('day_of_month') is-invalid @enderror" 
                                                   value="{{ old('day_of_month') }}" 
                                                   min="1" 
                                                   max="31"
                                                   placeholder="1-31">
                                            <div class="form-text">For monthly/quarterly/annual frequencies</div>
                                            @error('day_of_month')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3" id="day-of-week-field">
                                            <label for="day_of_week" class="form-label">Day of Week</label>
                                            <select name="day_of_week" id="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror">
                                                <option value="">Select day...</option>
                                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                    <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>
                                                        {{ $day }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="form-text">For weekly frequencies</div>
                                            @error('day_of_week')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Status and Settings -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select name="status" 
                                                    id="status" 
                                                    class="form-select @error('status') is-invalid @enderror" 
                                                    required>
                                                @foreach($statuses as $key => $value)
                                                    <option value="{{ $key }}" {{ old('status', 'draft') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="payment_terms_days" class="form-label">Payment Terms (Days)</label>
                                            <input type="number" 
                                                   name="payment_terms_days" 
                                                   id="payment_terms_days" 
                                                   class="form-control @error('payment_terms_days') is-invalid @enderror" 
                                                   value="{{ old('payment_terms_days', 30) }}" 
                                                   min="1" 
                                                   max="365"
                                                   placeholder="30">
                                            @error('payment_terms_days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Invoice Settings -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                            <input type="text" 
                                                   name="invoice_prefix" 
                                                   id="invoice_prefix" 
                                                   class="form-control @error('invoice_prefix') is-invalid @enderror" 
                                                   value="{{ old('invoice_prefix', 'REC') }}" 
                                                   maxlength="10"
                                                   placeholder="REC">
                                            @error('invoice_prefix')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="auto_send" 
                                                       id="auto_send" 
                                                       value="1" 
                                                       {{ old('auto_send') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="auto_send">
                                                    Auto-send invoices when generated
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Late Fees -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="late_fee_percentage" class="form-label">Late Fee Percentage (%)</label>
                                            <input type="number" 
                                                   name="late_fee_percentage" 
                                                   id="late_fee_percentage" 
                                                   class="form-control @error('late_fee_percentage') is-invalid @enderror" 
                                                   value="{{ old('late_fee_percentage', 0) }}" 
                                                   min="0" 
                                                   max="100" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('late_fee_percentage')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="late_fee_flat_amount" class="form-label">Late Fee Flat Amount</label>
                                            <input type="number" 
                                                   name="late_fee_flat_amount" 
                                                   id="late_fee_flat_amount" 
                                                   class="form-control @error('late_fee_flat_amount') is-invalid @enderror" 
                                                   value="{{ old('late_fee_flat_amount', 0) }}" 
                                                   min="0" 
                                                   step="0.01"
                                                   placeholder="0.00">
                                            @error('late_fee_flat_amount')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Line Items -->
                                <div class="mb-3">
                                    <label for="line_items" class="form-label">Line Items</label>
                                    <textarea name="line_items" 
                                              id="line_items" 
                                              class="form-control @error('line_items') is-invalid @enderror" 
                                              rows="4" 
                                              placeholder="Enter line items (one per line)...">{{ old('line_items') }}</textarea>
                                    <div class="form-text">Enter each line item on a separate line</div>
                                    @error('line_items')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="invoice_notes" class="form-label">Invoice Notes</label>
                                    <textarea name="invoice_notes" 
                                              id="invoice_notes" 
                                              class="form-control @error('invoice_notes') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Notes to include on generated invoices...">{{ old('invoice_notes') }}</textarea>
                                    @error('invoice_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Payment Instructions -->
                                <div class="mb-3">
                                    <label for="payment_instructions" class="form-label">Payment Instructions</label>
                                    <textarea name="payment_instructions" 
                                              id="payment_instructions" 
                                              class="form-control @error('payment_instructions') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Payment instructions for clients...">{{ old('payment_instructions') }}</textarea>
                                    @error('payment_instructions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Recurring Invoice
                                    </button>
                                    <a href="{{ route('clients.recurring-invoices.standalone.index') }}" class="btn btn-outline-secondary">
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
                                <i class="fas fa-info-circle me-2"></i>Recurring Invoice Setup
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <h6>Frequency Options:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Daily:</strong> Invoice generated every day(s)</li>
                                    <li><strong>Weekly:</strong> Invoice generated every week(s)</li>
                                    <li><strong>Monthly:</strong> Invoice generated every month(s)</li>
                                    <li><strong>Quarterly:</strong> Invoice generated every 3 months</li>
                                    <li><strong>Annually:</strong> Invoice generated every year</li>
                                </ul>

                                <h6 class="mt-3">Status Options:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Draft:</strong> Template is being prepared</li>
                                    <li><strong>Active:</strong> Template is generating invoices</li>
                                    <li><strong>Paused:</strong> Template is temporarily stopped</li>
                                    <li><strong>Cancelled:</strong> Template is permanently stopped</li>
                                </ul>

                                <h6 class="mt-3">Tips:</h6>
                                <ul class="list-unstyled">
                                    <li>• Set up payment terms that match your business needs</li>
                                    <li>• Use descriptive template names for easy identification</li>
                                    <li>• Include clear line items and payment instructions</li>
                                    <li>• Test with a draft status before activating</li>
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
    const frequencySelect = document.getElementById('frequency');
    const dayOfMonthField = document.getElementById('day-of-month-field');
    const dayOfWeekField = document.getElementById('day-of-week-field');

    function toggleTimingOptions() {
        const frequency = frequencySelect.value;
        
        // Show/hide day of month field
        if (['monthly', 'quarterly', 'semiannually', 'annually'].includes(frequency)) {
            dayOfMonthField.style.display = 'block';
        } else {
            dayOfMonthField.style.display = 'none';
        }
        
        // Show/hide day of week field
        if (['weekly', 'biweekly'].includes(frequency)) {
            dayOfWeekField.style.display = 'block';
        } else {
            dayOfWeekField.style.display = 'none';
        }
    }

    frequencySelect.addEventListener('change', toggleTimingOptions);
    
    // Initialize on page load
    toggleTimingOptions();
});
</script>
@endpush