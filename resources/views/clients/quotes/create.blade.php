@extends('layouts.app')

@section('title', 'Create Quote')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Create Quote</h1>
                <a href="{{ route('clients.quotes.standalone.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Quotes
                </a>
            </div>

            <div class="row">
                <div class="col-lg-8 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clients.quotes.standalone.store') }}">
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

                                <!-- Quote Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Quote Title <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           name="title" 
                                           id="title" 
                                           class="form-control @error('title') is-invalid @enderror" 
                                           value="{{ old('title') }}" 
                                           required 
                                           maxlength="255"
                                           placeholder="Enter quote title">
                                    @error('title')
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
                                              placeholder="Quote description...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Currency -->
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

                                <!-- Line Items -->
                                <div class="mb-3">
                                    <label for="line_items" class="form-label">Line Items</label>
                                    <textarea name="line_items" 
                                              id="line_items" 
                                              class="form-control @error('line_items') is-invalid @enderror" 
                                              rows="6" 
                                              placeholder="Enter line items...">{{ old('line_items') }}</textarea>
                                    <div class="form-text">
                                        Enter each line item on a separate line.<br>
                                        Format: Description | Quantity | Unit Price<br>
                                        Example: Website Design | 1 | 2500.00
                                    </div>
                                    @error('line_items')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Tax and Discount -->
                                <div class="row">
                                    <div class="col-md-6">
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
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="discount_type" class="form-label">Discount Type</label>
                                            <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror">
                                                @foreach($discountTypes as $key => $value)
                                                    <option value="{{ $key }}" {{ old('discount_type', 'fixed') == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('discount_type')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="discount_amount" class="form-label">Discount Amount</label>
                                    <input type="number" 
                                           name="discount_amount" 
                                           id="discount_amount" 
                                           class="form-control @error('discount_amount') is-invalid @enderror" 
                                           value="{{ old('discount_amount', 0) }}" 
                                           min="0" 
                                           step="0.01"
                                           placeholder="0.00">
                                    <div class="form-text">Enter percentage (0-100) for percentage discount or fixed amount for fixed discount</div>
                                    @error('discount_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Dates -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="issued_date" class="form-label">Issue Date</label>
                                            <input type="date" 
                                                   name="issued_date" 
                                                   id="issued_date" 
                                                   class="form-control @error('issued_date') is-invalid @enderror" 
                                                   value="{{ old('issued_date', date('Y-m-d')) }}">
                                            @error('issued_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="valid_until" class="form-label">Valid Until</label>
                                            <input type="date" 
                                                   name="valid_until" 
                                                   id="valid_until" 
                                                   class="form-control @error('valid_until') is-invalid @enderror" 
                                                   value="{{ old('valid_until', date('Y-m-d', strtotime('+30 days'))) }}">
                                            @error('valid_until')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Status and Conversion Probability -->
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
                                            <label for="conversion_probability" class="form-label">Conversion Probability (%)</label>
                                            <input type="number" 
                                                   name="conversion_probability" 
                                                   id="conversion_probability" 
                                                   class="form-control @error('conversion_probability') is-invalid @enderror" 
                                                   value="{{ old('conversion_probability', 50) }}" 
                                                   min="0" 
                                                   max="100"
                                                   placeholder="50">
                                            @error('conversion_probability')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Follow-up Date -->
                                <div class="mb-3">
                                    <label for="follow_up_date" class="form-label">Follow-up Date</label>
                                    <input type="date" 
                                           name="follow_up_date" 
                                           id="follow_up_date" 
                                           class="form-control @error('follow_up_date') is-invalid @enderror" 
                                           value="{{ old('follow_up_date') }}">
                                    <div class="form-text">Set a date when you want to follow up on this quote</div>
                                    @error('follow_up_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Terms & Conditions -->
                                <div class="mb-3">
                                    <label for="terms_conditions" class="form-label">Terms & Conditions</label>
                                    <textarea name="terms_conditions" 
                                              id="terms_conditions" 
                                              class="form-control @error('terms_conditions') is-invalid @enderror" 
                                              rows="4" 
                                              placeholder="Enter terms and conditions...">{{ old('terms_conditions') }}</textarea>
                                    @error('terms_conditions')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Payment Terms -->
                                <div class="mb-3">
                                    <label for="payment_terms" class="form-label">Payment Terms</label>
                                    <textarea name="payment_terms" 
                                              id="payment_terms" 
                                              class="form-control @error('payment_terms') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Enter payment terms...">{{ old('payment_terms') }}</textarea>
                                    @error('payment_terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Delivery Timeframe -->
                                <div class="mb-3">
                                    <label for="delivery_timeframe" class="form-label">Delivery Timeframe</label>
                                    <input type="text" 
                                           name="delivery_timeframe" 
                                           id="delivery_timeframe" 
                                           class="form-control @error('delivery_timeframe') is-invalid @enderror" 
                                           value="{{ old('delivery_timeframe') }}" 
                                           placeholder="e.g., 2-3 weeks, 30 business days">
                                    @error('delivery_timeframe')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Project Scope -->
                                <div class="mb-3">
                                    <label for="project_scope" class="form-label">Project Scope</label>
                                    <textarea name="project_scope" 
                                              id="project_scope" 
                                              class="form-control @error('project_scope') is-invalid @enderror" 
                                              rows="4" 
                                              placeholder="Describe the project scope...">{{ old('project_scope') }}</textarea>
                                    @error('project_scope')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Internal Notes</label>
                                    <textarea name="notes" 
                                              id="notes" 
                                              class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" 
                                              placeholder="Internal notes (not visible to client)...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Quote
                                    </button>
                                    <a href="{{ route('clients.quotes.standalone.index') }}" class="btn btn-outline-secondary">
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
                                <i class="fas fa-info-circle me-2"></i>Quote Setup Guide
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <h6>Line Items Format:</h6>
                                <p>Use the pipe (|) character to separate fields:</p>
                                <code>Description | Quantity | Unit Price</code>
                                
                                <h6 class="mt-3">Examples:</h6>
                                <ul class="list-unstyled">
                                    <li><code>Website Design | 1 | 2500.00</code></li>
                                    <li><code>Hosting Setup | 1 | 500.00</code></li>
                                    <li><code>Content Pages | 5 | 200.00</code></li>
                                </ul>

                                <h6 class="mt-3">Status Guide:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Draft:</strong> Quote is being prepared</li>
                                    <li><strong>Pending:</strong> Awaiting internal approval</li>
                                    <li><strong>Sent:</strong> Quote has been sent to client</li>
                                    <li><strong>Viewed:</strong> Client has viewed the quote</li>
                                </ul>

                                <h6 class="mt-3">Best Practices:</h6>
                                <ul class="list-unstyled">
                                    <li>• Include detailed line items for clarity</li>
                                    <li>• Set realistic conversion probabilities</li>
                                    <li>• Define clear terms and conditions</li>
                                    <li>• Set appropriate validity periods</li>
                                    <li>• Schedule follow-up dates</li>
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
    // Auto-calculate totals when line items change (basic implementation)
    const lineItemsTextarea = document.getElementById('line_items');
    
    lineItemsTextarea.addEventListener('blur', function() {
        // This would ideally calculate totals in real-time
        // For now, just a placeholder for future enhancement
    });
});
</script>
@endpush