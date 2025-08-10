@extends('layouts.app')

@section('title', 'Edit Quote')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Edit Quote</h1>
                <div class="btn-group">
                    <a href="{{ route('clients.quotes.standalone.show', $quote) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-eye me-2"></i>View Quote
                    </a>
                    <a href="{{ route('clients.quotes.standalone.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Quotes
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 col-xl-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('clients.quotes.standalone.update', $quote) }}">
                                @csrf
                                @method('PUT')

                                <!-- Quote Number -->
                                <div class="mb-3">
                                    <label class="form-label">Quote Number</label>
                                    <input type="text" class="form-control" value="{{ $quote->quote_number }}" readonly>
                                    <div class="form-text">Quote number is automatically generated</div>
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
                                                    {{ old('client_id', $quote->client_id) == $client->id ? 'selected' : '' }}>
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
                                           value="{{ old('title', $quote->title) }}" 
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
                                              placeholder="Quote description...">{{ old('description', $quote->description) }}</textarea>
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
                                            <option value="{{ $key }}" {{ old('currency', $quote->currency) == $key ? 'selected' : '' }}>
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
                                              placeholder="Enter line items...">{{ old('line_items', $quote->line_items ? implode("\n", array_map(function($item) {
                                                return $item['description'] . ' | ' . $item['quantity'] . ' | ' . $item['unit_price'];
                                            }, $quote->line_items)) : '') }}</textarea>
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
                                                   value="{{ old('tax_rate', $quote->tax_rate) }}" 
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
                                                    <option value="{{ $key }}" {{ old('discount_type', $quote->discount_type) == $key ? 'selected' : '' }}>
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
                                           value="{{ old('discount_amount', $quote->discount_amount) }}" 
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
                                                   value="{{ old('issued_date', $quote->issued_date ? $quote->issued_date->format('Y-m-d') : '') }}">
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
                                                   value="{{ old('valid_until', $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '') }}">
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
                                                    <option value="{{ $key }}" {{ old('status', $quote->status) == $key ? 'selected' : '' }}>
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
                                                   value="{{ old('conversion_probability', $quote->conversion_probability) }}" 
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
                                           value="{{ old('follow_up_date', $quote->follow_up_date ? $quote->follow_up_date->format('Y-m-d') : '') }}">
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
                                              placeholder="Enter terms and conditions...">{{ old('terms_conditions', $quote->terms_conditions) }}</textarea>
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
                                              placeholder="Enter payment terms...">{{ old('payment_terms', $quote->payment_terms) }}</textarea>
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
                                           value="{{ old('delivery_timeframe', $quote->delivery_timeframe) }}" 
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
                                              placeholder="Describe the project scope...">{{ old('project_scope', $quote->project_scope) }}</textarea>
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
                                              placeholder="Internal notes (not visible to client)...">{{ old('notes', $quote->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Quote
                                    </button>
                                    <a href="{{ route('clients.quotes.standalone.show', $quote) }}" class="btn btn-outline-secondary">
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
                                <i class="fas fa-clock me-2"></i>Quote History
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <div class="mb-2">
                                    <strong>Created:</strong> {{ $quote->created_at->format('M j, Y g:i A') }}
                                    @if($quote->creator)
                                        <br><strong>Created by:</strong> {{ $quote->creator->name }}
                                    @endif
                                </div>
                                @if($quote->updated_at != $quote->created_at)
                                    <div class="mb-2">
                                        <strong>Last updated:</strong> {{ $quote->updated_at->format('M j, Y g:i A') }}
                                    </div>
                                @endif
                                @if($quote->sent_at)
                                    <div class="mb-2">
                                        <strong>Sent to client:</strong> {{ $quote->sent_at->format('M j, Y g:i A') }}
                                    </div>
                                @endif
                                @if($quote->viewed_at)
                                    <div class="mb-2">
                                        <strong>Viewed by client:</strong> {{ $quote->viewed_at->format('M j, Y g:i A') }}
                                    </div>
                                @endif
                                @if($quote->accepted_date)
                                    <div class="mb-2">
                                        <strong>Accepted:</strong> {{ $quote->accepted_date->format('M j, Y') }}
                                    </div>
                                @endif
                                @if($quote->declined_date)
                                    <div>
                                        <strong>Declined:</strong> {{ $quote->declined_date->format('M j, Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>Current Totals
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 text-center">
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <div class="fw-bold text-primary">{{ $quote->formatted_amount }}</div>
                                        <small class="text-muted">Subtotal</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 bg-light rounded">
                                        <div class="fw-bold text-success">{{ $quote->formatted_total_amount }}</div>
                                        <small class="text-muted">Total</small>
                                    </div>
                                </div>
                            </div>
                            @if($quote->discount_amount > 0)
                                <div class="text-center mt-2">
                                    <div class="p-2 bg-warning bg-opacity-10 rounded">
                                        <div class="fw-bold text-warning">-{{ $quote->formatted_discount_amount }}</div>
                                        <small class="text-muted">Discount</small>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Update Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted">
                                <h6>Status Restrictions:</h6>
                                <ul class="list-unstyled">
                                    <li>• Quotes can only be edited in draft/pending status</li>
                                    <li>• Sent quotes may have limited editing options</li>
                                    <li>• Accepted/declined quotes cannot be modified</li>
                                </ul>

                                <h6 class="mt-3">Best Practices:</h6>
                                <ul class="list-unstyled">
                                    <li>• Update conversion probability as situation changes</li>
                                    <li>• Set realistic follow-up dates</li>
                                    <li>• Keep internal notes detailed</li>
                                    <li>• Review terms before sending</li>
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