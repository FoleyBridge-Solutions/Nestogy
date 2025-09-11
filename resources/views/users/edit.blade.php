@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<flux:container class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <flux:card>
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Edit User</flux:heading>
                <flux:text>Update user account for {{ $user->name }}</flux:text>
            </div>
            <flux:button href="{{ route('users.index') }}" variant="ghost" icon="arrow-left">
                Back to Users
            </flux:button>
        </div>
    </flux:card>

    <!-- Form -->
    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')

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
                    value="{{ old('name', $user->name) }}" 
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
                    value="{{ old('email', $user->email) }}" 
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
                    value="{{ old('phone', $user->phone) }}" 
                />
                @error('phone')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Company Info (read-only) -->
                <flux:field>
                    <flux:label>Company</flux:label>
                    <flux:text>{{ $user->company->name }}</flux:text>
                </flux:field>
            </div>
        </flux:card>

        <!-- Access & Security -->
        <flux:card>
            <flux:heading size="lg">Access & Security</flux:heading>
            <flux:separator class="my-4" />
            
            <div class="space-y-4">
                <!-- Role -->
                @can('updateRole', $user)
                <flux:select 
                    name="role" 
                    label="User Role" 
                    placeholder="Select Role"
                    value="{{ old('role', $user->userSetting->role ?? 1) }}"
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
                @else
                <flux:field>
                    <flux:label>User Role</flux:label>
                    <flux:text>{{ $availableRoles[$user->userSetting->role ?? 1] ?? 'User' }}</flux:text>
                </flux:field>
                @endcan

                <!-- Password (optional for updates) -->
                <flux:input 
                    type="password" 
                    name="password" 
                    label="New Password (optional)" 
                    placeholder="Leave blank to keep current password" 
                    description="Only fill this if you want to change the password. Must be at least 8 characters."
                />
                @error('password')
                    <flux:error>{{ $message }}</flux:error>
                @enderror

                <!-- Confirm Password -->
                <flux:input 
                    type="password" 
                    name="password_confirmation" 
                    label="Confirm New Password" 
                    placeholder="Re-enter new password" 
                />

                <!-- Status -->
                <flux:field>
                    <flux:label>Account Status</flux:label>
                    <flux:radio.group name="status" value="{{ old('status', $user->userSetting->status ?? 1) }}">
                        <flux:radio value="1" label="Active - User can log in" />
                        <flux:radio value="0" label="Inactive - Login disabled" />
                    </flux:radio.group>
                    @error('status')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <!-- Force Password Reset -->
                @can('forcePasswordReset', $user)
                <flux:checkbox 
                    name="force_password_reset" 
                    value="1"
                    label="Force password reset on next login" 
                />
                @endcan
            </div>
        </flux:card>

        <!-- Additional Settings -->
        <flux:card>
            <flux:heading size="lg">Additional Settings</flux:heading>
            <flux:separator class="my-4" />
            
            <div class="space-y-4">
                <!-- Email Notifications -->
                <flux:checkbox 
                    name="email_notifications" 
                    value="1"
                    label="Receive email notifications" 
                />

                <!-- Two Factor Authentication -->
                @if($user->two_factor_secret)
                <flux:field>
                    <flux:label>Two-Factor Authentication</flux:label>
                    <flux:badge variant="success">Enabled</flux:badge>
                    @can('disableTwoFactor', $user)
                    <flux:checkbox 
                        name="disable_two_factor" 
                        value="1"
                        label="Disable two-factor authentication" 
                    />
                    @endcan
                </flux:field>
                @else
                <flux:field>
                    <flux:label>Two-Factor Authentication</flux:label>
                    <flux:badge variant="ghost">Not Enabled</flux:badge>
                </flux:field>
                @endif
            </div>
        </flux:card>

        <!-- Form Actions -->
        <div class="flex justify-end gap-3">
            <flux:button href="{{ route('users.index') }}" variant="ghost">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" icon="check">
                Save Changes
            </flux:button>
        </div>
    </form>
</flux:container>
@endsection