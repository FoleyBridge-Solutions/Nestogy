@extends('client-portal.layouts.app')

@section('title', 'Create Support Ticket')

@section('content')
<div class="container mx-auto mx-auto mx-auto px-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center">
            <a href="{{ route('client.tickets') }}" class="mr-4 px-6 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-gray-800 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Create Support Ticket</h1>
                <p class="text-gray-600 dark:text-gray-400">Submit a new support request and we'll get back to you soon</p>
            </div>
        </div>
    </div>

    <!-- Create Ticket Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:flex-1 px-6-span-2">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <i class="fas fa-ticket-alt mr-2"></i>Ticket Information
                    </flux:heading>
                </div>
                <div>
                    <form action="{{ route('client.tickets.store') ?? '#' }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <!-- Subject -->
                        <flux:field class="mb-6">
                            <flux:label for="subject" required>Subject</flux:label>
                            <flux:input 
                                type="text"
                                id="subject" 
                                name="subject" 
                                value="{{ old('subject') }}"
                                placeholder="Brief description of your issue"
                                required
                                :error="$errors->has('subject')" />
                            <flux:error for="subject" />
                        </flux:field>

                        <!-- Priority -->
                        <flux:field class="mb-6">
                            <flux:label for="priority" required>Priority</flux:label>
                            <flux:select 
                                id="priority" 
                                name="priority" 
                                required
                                :error="$errors->has('priority')">
                                <option value="">Select Priority</option>
                                <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low - General inquiry</option>
                                <option value="Medium" {{ old('priority') === 'Medium' ? 'selected' : '' }}>Medium - Standard issue</option>
                                <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High - Affecting productivity</option>
                                <option value="Critical" {{ old('priority') === 'Critical' ? 'selected' : '' }}>Critical - System down/urgent</option>
                            </flux:select>
                            <flux:error for="priority" />
                        </flux:field>

                        <!-- Category -->
                        <flux:field class="mb-6">
                            <flux:label for="category">Category</flux:label>
                            <flux:select 
                                id="category" 
                                name="category"
                                :error="$errors->has('category')">
                                <option value="">Select Category</option>
                                <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical Issue</option>
                                <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Billing Question</option>
                                <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Account Access</option>
                                <option value="feature_request" {{ old('category') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                                <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General Inquiry</option>
                                <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                            </flux:select>
                            <flux:error for="category" />
                        </flux:field>

                        <!-- Details -->
                        <flux:field class="mb-6">
                            <flux:label for="details" required>Details</flux:label>
                            <flux:textarea 
                                id="details" 
                                name="details" 
                                rows="6"
                                placeholder="Please provide as much detail as possible about your issue..."
                                required
                                :error="$errors->has('details')">{{ old('details') }}</flux:textarea>
                            <flux:error for="details" />
                        </flux:field>

                        <!-- Attachments -->
                        <flux:field class="mb-6">
                            <flux:label for="attachments">Attachments</flux:label>
                            <flux:input 
                                type="file" 
                                id="attachments" 
                                name="attachments[]" 
                                multiple
                                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" />
                            <flux:text size="sm" class="mt-1 text-gray-500">
                                You can attach multiple files (images, PDFs, documents, etc.). Max size: 10MB per file.
                            </flux:text>
                        </flux:field>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between">
                            <flux:button href="{{ route('client.tickets') }}" variant="ghost">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" variant="primary" icon="paper-plane">
                                Submit Ticket
                            </flux:button>
                        </div>
                    </form>
                </div>
            </flux:card>
        </div>

        <!-- Help Sidebar -->
        <div class="lg:flex-1 px-6-span-1">
            <flux:card class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <i class="fas fa-info-circle mr-2"></i>Help & Tips
                    </flux:heading>
                </div>
                <div>
                    <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <h6 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Priority Guidelines:</h6>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Critical:</strong> System is down or completely unusable</li>
                                <li><strong>High:</strong> Major functionality is affected</li>
                                <li><strong>Medium:</strong> Issue affects work but has workaround</li>
                                <li><strong>Low:</strong> General questions or minor issues</li>
                            </ul>
                        </div>
                        <flux:separator />
                        <div>
                            <h6 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Tips for Faster Resolution:</h6>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Be specific about the issue</li>
                                <li>Include error messages if any</li>
                                <li>Mention when the issue started</li>
                                <li>Attach relevant screenshots</li>
                                <li>List steps to reproduce</li>
                            </ul>
                        </div>
                        <flux:separator />
                        <div>
                            <h6 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">Response Times:</h6>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Critical:</strong> Within 1 hour</li>
                                <li><strong>High:</strong> Within 4 hours</li>
                                <li><strong>Medium:</strong> Within 1 business day</li>
                                <li><strong>Low:</strong> Within 2 business days</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Contact Support -->
            <flux:card class="mt-6 space-y-4">
                <div>
                    <h6 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">
                        <i class="fas fa-phone mr-2"></i>Need Immediate Help?
                    </h6>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        For critical issues, you can reach us directly:
                    </p>
                    <div class="space-y-2 text-sm">
                        @if($company = auth()->user()->company ?? null)
                            @if($company->support_phone)
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-blue-600 mr-2"></i>
                                    <a href="tel:{{ $company->support_phone }}" class="text-blue-600 hover:underline">
                                        {{ $company->support_phone }}
                                    </a>
                                </div>
                            @endif
                            @if($company->support_email)
                                <div class="flex items-center">
                                    <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                    <a href="mailto:{{ $company->support_email }}" class="text-blue-600 hover:underline">
                                        {{ $company->support_email }}
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="flex items-center">
                                <i class="fas fa-phone text-blue-600 mr-2"></i>
                                <span class="text-gray-600 dark:text-gray-400">1-800-SUPPORT</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                <span class="text-gray-600 dark:text-gray-400">support@example.com</span>
                            </div>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
@endsection
