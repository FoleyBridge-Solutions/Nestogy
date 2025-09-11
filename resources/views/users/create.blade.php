@extends('layouts.app')

@section('title', 'Add New User')

@section('content')
<flux:container class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Add New User</flux:heading>
                <flux:text>Create a new user account for {{ Auth::user()->company->name }}</flux:text>
            </div>
            <flux:button href="{{ route('users.index') }}" variant="ghost" icon="arrow-left">
                Back to Users
            </flux:button>
        </div>
    </flux:card>

    <!-- Form -->
    <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
        @csrf

        <!-- User Information -->
        <flux:card>
            <flux:heading size="lg">User Information</flux:heading>
            <flux:separator class="my-4" />
            
            <div class="space-y-4">
                <!-- Name -->
                <flux:input 
                    name="name" 
                    label="Full Name" 
                    placeholder="John Doe" 
                    value="{{ old('name') }}" 
                    required 
                />
                @error('name')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Email -->
                <flux:input 
                    type="email" 
                    name="email" 
                    label="Email Address" 
                    placeholder="john@example.com" 
                    value="{{ old('email') }}" 
                    required 
                />
                @error('email')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Phone -->
                <flux:input 
                    type="tel" 
                    name="phone" 
                    label="Phone Number" 
                    placeholder="+1 (555) 123-4567" 
                    value="{{ old('phone') }}" 
                />
                @error('phone')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Company (only for super admins) -->
                @if(Auth::user()->canAccessCrossTenant())
                    <flux:select 
                        name="company_id" 
                        label="Company" 
                        placeholder="Select Company"
                        value="{{ old('company_id', Auth::user()->company_id) }}"
                        required
                    >
                        @foreach($companies as $company)
                            <flux:select.option value="{{ $company->id }}">
                                {{ $company->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('company_id')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                @endif
            </div>
        </flux:card>

        <!-- Access & Security -->
        <flux:card>
            <flux:heading size="lg">Access & Security</flux:heading>
            <flux:separator class="my-4" />
            
            <div class="space-y-4">
                <!-- Role -->
                <flux:select 
                    name="role" 
                    label="User Role" 
                    placeholder="Select Role"
                    value="{{ old('role') }}"
                    required
                >
                    @foreach($availableRoles as $roleId => $roleName)
                        <flux:select.option value="{{ $roleId }}">
                            {{ $roleName }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @error('role')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Password -->
                <flux:input 
                    type="password" 
                    name="password" 
                    label="Password" 
                    placeholder="Minimum 8 characters" 
                    required 
                    description="Password must be at least 8 characters with mixed case letters and numbers"
                />
                @error('password')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Confirm Password -->
                <flux:input 
                    type="password" 
                    name="password_confirmation" 
                    label="Confirm Password" 
                    placeholder="Re-enter password" 
                    required 
                />

                <!-- Status -->
                <flux:field>
                    <flux:label>Account Status</flux:label>
                    <flux:radio.group name="status" value="{{ old('status', '1') }}">
                        <flux:radio value="1" label="Active - User can log in immediately" />
                        <flux:radio value="0" label="Inactive - Account created but login disabled" />
                    </flux:radio.group>
                    @error('status')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <!-- Send Welcome Email -->
                <flux:checkbox 
                    name="send_welcome_email" 
                    value="1"
                    label="Send welcome email with login credentials" 
                />
            </div>
        </flux:card>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('users.index') }}" variant="ghost">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" icon="plus">
                Create User
            </flux:button>
        </div>
    </form>
</flux:container>
@endsection