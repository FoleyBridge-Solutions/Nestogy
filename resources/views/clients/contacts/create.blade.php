@extends('layouts.app')

@section('title', 'Add Contact - ' . $client->name)

@section('content')
<div class="container-fluid max-w-4xl">
    <!-- Header -->
    <flux:card class="mb-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading>Add New Contact</flux:heading>
                <flux:text>Create a new contact for {{ $client->name }}</flux:text>
            </div>
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('clients.contacts.index') }}">
                Back to Contacts
            </flux:button>
        </div>
    </flux:card>

    <!-- Form -->
    <form method="POST" action="{{ route('clients.contacts.store') }}">
        @csrf
        
        <!-- Basic Information -->
        <flux:card class="mb-4">
            <flux:heading size="lg" class="mb-4">Basic Information</flux:heading>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Name *</flux:label>
                    <flux:input 
                        name="name" 
                        value="{{ old('name') }}" 
                        required 
                        placeholder="John Doe"
                    />
                    @error('name')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input 
                        name="title" 
                        value="{{ old('title') }}" 
                        placeholder="IT Manager"
                    />
                    @error('title')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Email *</flux:label>
                    <flux:input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        placeholder="john@example.com"
                    />
                    @error('email')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Phone</flux:label>
                    <flux:input 
                        type="tel" 
                        name="phone" 
                        value="{{ old('phone') }}" 
                        placeholder="+1 (555) 123-4567"
                    />
                    @error('phone')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Extension</flux:label>
                    <flux:input 
                        name="extension" 
                        value="{{ old('extension') }}" 
                        placeholder="123"
                    />
                    @error('extension')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Mobile</flux:label>
                    <flux:input 
                        type="tel" 
                        name="mobile" 
                        value="{{ old('mobile') }}" 
                        placeholder="+1 (555) 987-6543"
                    />
                    @error('mobile')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Department</flux:label>
                    <flux:input 
                        name="department" 
                        value="{{ old('department') }}" 
                        placeholder="Information Technology"
                    />
                    @error('department')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Role</flux:label>
                    <flux:input 
                        name="role" 
                        value="{{ old('role') }}" 
                        placeholder="Decision Maker"
                    />
                    @error('role')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>
            </div>
        </flux:card>

        <!-- Contact Type -->
        <flux:card class="mb-4">
            <flux:heading size="lg" class="mb-4">Contact Type</flux:heading>
            
            <div class="space-y-3">
                <flux:checkbox name="primary" value="1" :checked="old('primary')" label="Primary Contact" description="Main point of contact for this client" />
                <flux:checkbox name="billing" value="1" :checked="old('billing')" label="Billing Contact" description="Receives invoices and handles payments" />
                <flux:checkbox name="technical" value="1" :checked="old('technical')" label="Technical Contact" description="Handles technical issues and support requests" />
                <flux:checkbox name="important" value="1" :checked="old('important')" label="Important Contact" description="Key stakeholder or decision maker" />
            </div>
        </flux:card>

        <!-- Additional Information -->
        <flux:card class="mb-4">
            <flux:heading size="lg" class="mb-4">Additional Information</flux:heading>
            
            <flux:field>
                <flux:label>Notes</flux:label>
                <flux:textarea 
                    name="notes" 
                    rows="4" 
                    placeholder="Any additional notes about this contact..."
                >{{ old('notes') }}</flux:textarea>
                @error('notes')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </flux:field>
        </flux:card>

        <!-- Portal Access -->
        <flux:card class="mb-4">
            <flux:heading size="lg" class="mb-4">Client Portal Access</flux:heading>
            
            <flux:field>
                <div class="flex items-center justify-between">
                    <div>
                        <flux:label>Enable Portal Access</flux:label>
                        <flux:description>Allow this contact to access the client portal</flux:description>
                    </div>
                    <flux:switch name="has_portal_access" value="1" :checked="old('has_portal_access')" />
                </div>
            </flux:field>

            <div x-data="{ hasAccess: {{ old('has_portal_access') ? 'true' : 'false' }} }" 
                 x-show="hasAccess" 
                 x-transition
                 class="mt-4 space-y-4">
                
                <flux:field>
                    <flux:label>Authentication Method</flux:label>
                    <flux:select name="auth_method" value="{{ old('auth_method', 'password') }}">
                        <flux:select.option value="password">Password</flux:select.option>
                        <flux:select.option value="pin">PIN Code</flux:select.option>
                        <flux:select.option value="none">No Authentication</flux:select.option>
                    </flux:select>
                </flux:field>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Password</flux:label>
                        <flux:input 
                            type="password" 
                            name="password" 
                            placeholder="••••••••"
                        />
                        <flux:description>Leave blank to auto-generate</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Confirm Password</flux:label>
                        <flux:input 
                            type="password" 
                            name="password_confirmation" 
                            placeholder="••••••••"
                        />
                    </flux:field>
                </div>
            </div>
        </flux:card>

        <!-- Form Actions -->
        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" href="{{ route('clients.contacts.index') }}">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary">
                Create Contact
            </flux:button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Handle portal access toggle
    document.addEventListener('alpine:init', () => {
        Alpine.data('portalAccess', () => ({
            hasAccess: false,
            init() {
                const switchEl = this.$el.querySelector('[name="has_portal_access"]');
                if (switchEl) {
                    this.hasAccess = switchEl.checked;
                    switchEl.addEventListener('change', (e) => {
                        this.hasAccess = e.target.checked;
                    });
                }
            }
        }));
    });
</script>
@endpush
@endsection