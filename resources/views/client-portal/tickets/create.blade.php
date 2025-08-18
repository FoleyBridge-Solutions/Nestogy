@extends('client-portal.layouts.app')

@section('title', 'Create Support Ticket')

@section('content')
<div class="portal-container">
    <!-- Header -->
    <div class="portal-row portal-mb-4">
        <div class="portal-col-12">
            <div class="portal-d-flex portal-align-items-center">
                <a href="{{ route('client.tickets') }}" class="portal-btn portal-btn-outline-primary portal-mr-4">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="portal-text-3xl portal-mb-0 text-gray-800">Create Support Ticket</h1>
                    <p class="text-gray-600">Submit a new support request and we'll get back to you soon</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Ticket Form -->
    <div class="portal-row">
        <div class="portal-col-12 portal-col-lg-8">
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-ticket-alt portal-mr-2"></i>Ticket Information
                    </h6>
                </div>
                <div class="portal-card-body">
                    <form action="{{ route('client.tickets.store') ?? '#' }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Subject -->
                        <div class="portal-mb-4">
                            <label for="subject" class="portal-text-sm portal-font-medium text-gray-700 portal-mb-2" style="display: block;">
                                Subject *
                            </label>
                            <input type="text" 
                                   id="subject" 
                                   name="subject" 
                                   class="portal-form-control @error('subject') is-invalid @enderror" 
                                   value="{{ old('subject') }}"
                                   placeholder="Brief description of your issue"
                                   required>
                            @error('subject')
                                <div class="portal-text-xs text-red-600 portal-mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Priority -->
                        <div class="portal-mb-4">
                            <label for="priority" class="portal-text-sm portal-font-medium text-gray-700 portal-mb-2" style="display: block;">
                                Priority *
                            </label>
                            <select id="priority" 
                                    name="priority" 
                                    class="portal-form-control @error('priority') is-invalid @enderror" 
                                    required>
                                <option value="">Select Priority</option>
                                <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low - General inquiry</option>
                                <option value="Medium" {{ old('priority') === 'Medium' ? 'selected' : '' }}>Medium - Standard issue</option>
                                <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High - Affecting productivity</option>
                                <option value="Critical" {{ old('priority') === 'Critical' ? 'selected' : '' }}>Critical - System down/urgent</option>
                            </select>
                            @error('priority')
                                <div class="portal-text-xs text-red-600 portal-mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div class="portal-mb-4">
                            <label for="category" class="portal-text-sm portal-font-medium text-gray-700 portal-mb-2" style="display: block;">
                                Category
                            </label>
                            <select id="category" 
                                    name="category" 
                                    class="portal-form-control @error('category') is-invalid @enderror">
                                <option value="">Select Category</option>
                                <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical Issue</option>
                                <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Billing Question</option>
                                <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Account Access</option>
                                <option value="feature_request" {{ old('category') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                                <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General Inquiry</option>
                                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')
                                <div class="portal-text-xs text-red-600 portal-mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Details -->
                        <div class="portal-mb-4">
                            <label for="details" class="portal-text-sm portal-font-medium text-gray-700 portal-mb-2" style="display: block;">
                                Details *
                            </label>
                            <textarea id="details" 
                                      name="details" 
                                      rows="6" 
                                      class="portal-form-control @error('details') is-invalid @enderror" 
                                      placeholder="Please provide detailed information about your issue, including steps to reproduce if applicable..."
                                      required>{{ old('details') }}</textarea>
                            @error('details')
                                <div class="portal-text-xs text-red-600 portal-mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Attachments -->
                        <div class="portal-mb-4">
                            <label for="attachments" class="portal-text-sm portal-font-medium text-gray-700 portal-mb-2" style="display: block;">
                                Attachments
                            </label>
                            <input type="file" 
                                   id="attachments" 
                                   name="attachments[]" 
                                   multiple
                                   class="portal-form-control @error('attachments.*') is-invalid @enderror"
                                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.zip">
                            <div class="portal-text-xs text-gray-500 portal-mt-1">
                                Maximum 5 files, 10MB each. Supported formats: JPG, PNG, PDF, DOC, DOCX, TXT, ZIP
                            </div>
                            @error('attachments.*')
                                <div class="portal-text-xs text-red-600 portal-mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="portal-d-flex portal-justify-content-between portal-align-items-center">
                            <a href="{{ route('client.tickets') }}" class="portal-btn portal-btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="portal-btn portal-btn-primary">
                                <i class="fas fa-paper-plane portal-mr-2"></i>Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="portal-col-12 portal-col-lg-4">
            <!-- Support Information -->
            <div class="portal-card portal-shadow portal-mb-4">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-info-circle portal-mr-2"></i>Support Information
                    </h6>
                </div>
                <div class="portal-card-body">
                    <div class="space-y-3">
                        <div>
                            <h6 class="portal-text-sm portal-font-medium text-gray-900 portal-mb-1">Response Times</h6>
                            <ul class="portal-text-xs text-gray-600 space-y-1">
                                <li>• Critical: Within 1 hour</li>
                                <li>• High: Within 4 hours</li>
                                <li>• Normal: Within 24 hours</li>
                                <li>• Low: Within 48 hours</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h6 class="portal-text-sm portal-font-medium text-gray-900 portal-mb-1">Business Hours</h6>
                            <p class="portal-text-xs text-gray-600">
                                Monday - Friday<br>
                                8:00 AM - 6:00 PM EST
                            </p>
                        </div>
                        
                        <div>
                            <h6 class="portal-text-sm portal-font-medium text-gray-900 portal-mb-1">Emergency Contact</h6>
                            <p class="portal-text-xs text-gray-600">
                                For critical issues outside business hours, call:<br>
                                <strong>(555) 123-4567</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips -->
            <div class="portal-card portal-shadow">
                <div class="px-6 py-4 portal-border-b portal-bg-gray-50 py-3">
                    <h6 class="portal-mb-0 portal-font-bold portal-text-primary">
                        <i class="fas fa-lightbulb portal-mr-2"></i>Tips for Better Support
                    </h6>
                </div>
                <div class="portal-card-body">
                    <ul class="portal-text-xs text-gray-600 space-y-2">
                        <li>• Be specific about the issue and when it occurs</li>
                        <li>• Include error messages if any</li>
                        <li>• Mention what you were trying to do</li>
                        <li>• Attach screenshots if helpful</li>
                        <li>• Include browser/device information for technical issues</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.portal-form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    background-color: white;
}

.portal-form-control:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
}

.portal-form-control.is-invalid {
    border-color: #ef4444;
}

.space-y-1 > * + * { margin-top: 0.25rem; }
.space-y-2 > * + * { margin-top: 0.5rem; }
.space-y-3 > * + * { margin-top: 0.75rem; }

@media (min-width: 1024px) {
    .portal-col-lg-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
    .portal-col-lg-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
}
</style>
@endpush