@extends('client-portal.layouts.app')

@section('title', 'Create Support Ticket')

@section('content')
<!-- Header -->
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">Create Support Ticket</h1>
            <p class="text-gray-600 dark:text-gray-400">Submit a new support request and we'll get back to you soon</p>
        </div>
        <flux:button href="{{ route('client.tickets') }}" variant="ghost" icon="arrow-left">
            Back to Tickets
        </flux:button>
    </div>
</div>

<!-- Create Ticket Form -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg">Ticket Information</flux:heading>
                <flux:subheading>Provide details about the issue or request</flux:subheading>
            </div>

            <form action="{{ route('client.tickets.store') ?? '#' }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <!-- Subject -->
                <flux:field>
                    <flux:label for="subject" required>Subject</flux:label>
                    <flux:input 
                        type="text"
                        id="subject" 
                        name="subject" 
                        value="{{ old('subject') }}"
                        placeholder="Brief description of your issue"
                        required />
                    @error('subject')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <!-- Priority -->
                <flux:field>
                    <flux:label for="priority" required>Priority</flux:label>
                    <flux:select 
                        id="priority" 
                        name="priority" 
                        required>
                        <option value="">Select Priority</option>
                        <option value="Low" {{ old('priority') === 'Low' ? 'selected' : '' }}>Low - General inquiry</option>
                        <option value="Medium" {{ old('priority') === 'Medium' ? 'selected' : '' }}>Medium - Standard issue</option>
                        <option value="High" {{ old('priority') === 'High' ? 'selected' : '' }}>High - Affecting productivity</option>
                        <option value="Critical" {{ old('priority') === 'Critical' ? 'selected' : '' }}>Critical - System down/urgent</option>
                    </flux:select>
                    @error('priority')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <!-- Category -->
                <flux:field>
                    <flux:label for="category">Category</flux:label>
                    <flux:select 
                        id="category" 
                        name="category">
                        <option value="">Select Category</option>
                        <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical Issue</option>
                        <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Billing Question</option>
                        <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Account Access</option>
                        <option value="feature_request" {{ old('category') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                        <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General Inquiry</option>
                        <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other</option>
                    </flux:select>
                    @error('category')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <!-- Details -->
                <flux:field>
                    <flux:label for="details" required>Details</flux:label>
                    <flux:textarea 
                        id="details" 
                        name="details" 
                        rows="6"
                        placeholder="Please provide as much detail as possible about your issue..."
                        required>{{ old('details') }}</flux:textarea>
                    @error('details')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <!-- Attachments -->
                <flux:field>
                    <flux:label for="attachments">Attachments</flux:label>
                    <flux:input 
                        type="file" 
                        id="attachments" 
                        name="attachments[]" 
                        multiple
                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" />
                    <flux:description>
                        You can attach multiple files (images, PDFs, documents, etc.). Max size: 10MB per file.
                    </flux:description>
                </flux:field>

                <flux:separator />

                <!-- Action Buttons -->
                <div class="flex items-center justify-between">
                    <flux:button href="{{ route('client.tickets') }}" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Submit Ticket
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>

    <!-- Help Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <flux:card class="space-y-4">
            <flux:heading size="lg">Help & Tips</flux:heading>
            
            <div class="space-y-4">
                <div>
                    <flux:subheading>Priority Guidelines:</flux:subheading>
                    <ul class="list-disc list-inside space-y-1 text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                        <li><strong>Critical:</strong> System is down or completely unusable</li>
                        <li><strong>High:</strong> Major functionality is affected</li>
                        <li><strong>Medium:</strong> Issue affects work but has workaround</li>
                        <li><strong>Low:</strong> General questions or minor issues</li>
                    </ul>
                </div>

                <flux:separator />

                <div>
                    <flux:subheading>Tips for Faster Resolution:</flux:subheading>
                    <ul class="list-disc list-inside space-y-1 text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                        <li>Be specific about the issue</li>
                        <li>Include error messages if any</li>
                        <li>Mention when the issue started</li>
                        <li>Attach relevant screenshots</li>
                        <li>List steps to reproduce</li>
                    </ul>
                </div>

                <flux:separator />

                <div>
                    <flux:subheading>Response Times:</flux:subheading>
                    <ul class="list-disc list-inside space-y-1 text-sm text-zinc-600 dark:text-zinc-400 mt-2">
                        <li><strong>Critical:</strong> Within 1 hour</li>
                        <li><strong>High:</strong> Within 4 hours</li>
                        <li><strong>Medium:</strong> Within 1 business day</li>
                        <li><strong>Low:</strong> Within 2 business days</li>
                    </ul>
                </div>
            </div>
        </flux:card>
    </div>
</div>
@endsection
