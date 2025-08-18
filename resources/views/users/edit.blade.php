@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">Edit User</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Update user account for {{ $user->name }}
                    </p>
                </div>
                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 bg-white dark:bg-gray-800 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 dark:hover:bg-gray-700 dark:bg-gray-900">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Users
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- User Information -->
        <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white">User Information</h3>
            </div>
            <div class="px-4 py-5 sm:px-6 space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('name') border-red-300 @enderror"
                           placeholder="John Doe">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('email') border-red-300 @enderror"
                           placeholder="john@example.com">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                        Phone Number
                    </label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('phone') border-red-300 @enderror"
                           placeholder="+1 (555) 123-4567">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Company Info (read-only) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Company</label>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-white">{{ $user->company->name }}</p>
                </div>
            </div>
        </div>

        <!-- Access & Security -->
        <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white">Access & Security</h3>
            </div>
            <div class="px-4 py-5 sm:px-6 space-y-6">
                <!-- Role -->
                @can('updateRole', $user)
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                        User Role <span class="text-red-500">*</span>
                    </label>
                    <select name="role" id="role" required
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('role') border-red-300 @enderror">
                        @foreach($availableRoles as $roleId => $roleName)
                            <option value="{{ $roleId }}" {{ old('role', $user->userSetting->role ?? 1) == $roleId ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">User Role</label>
                    @php
                        $roleNames = [
                            1 => 'User',
                            2 => 'Technician',
                            3 => 'Admin',
                            4 => 'Super Admin'
                        ];
                    @endphp
                    <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-white">{{ $roleNames[$user->userSetting->role ?? 1] ?? 'User' }}</p>
                </div>
                @endcan

                <!-- Password Change Section -->
                <div class="border-t pt-6">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white dark:text-white mb-4">Change Password (Optional)</h4>
                    <p class="text-sm text-gray-500 mb-4">Leave blank to keep current password</p>
                    
                    <!-- New Password -->
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                            New Password
                        </label>
                        <input type="password" name="password" id="password"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('password') border-red-300 @enderror"
                               placeholder="Minimum 8 characters">
                        <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters with mixed case letters and numbers</p>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                            Confirm New Password
                        </label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                               placeholder="Re-enter new password">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Account Status</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="1" {{ old('status', $user->status) == '1' ? 'checked' : '' }}
                                   class="rounded-full border-gray-300 dark:border-gray-600 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">Active - User can log in</span>
                        </label>
                        <br>
                        <label class="inline-flex items-center">
                            <input type="radio" name="status" value="0" {{ old('status', $user->status) == '0' ? 'checked' : '' }}
                                   class="rounded-full border-gray-300 dark:border-gray-600 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">Inactive - Login disabled</span>
                        </label>
                    </div>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="bg-white dark:bg-gray-800 dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white">Account Information</h3>
            </div>
            <div class="px-4 py-5 sm:px-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Created</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-white">{{ $user->created_at->format('M d, Y g:i A') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Last Updated</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-white">{{ $user->updated_at->format('M d, Y g:i A') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Last Login</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-white">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y g:i A') : 'Never' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">Email Verified</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-white">{{ $user->email_verified_at ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-between">
            <div>
                @can('delete', $user)
                @if($user->id !== Auth::id())
                <button type="button" onclick="if(confirm('Are you sure you want to archive this user?')) { document.getElementById('archive-form').submit(); }"
                        class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white dark:bg-gray-800 dark:bg-gray-800 hover:bg-red-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2M10 12l4 4m0-4l-4 4"></path>
                    </svg>
                    Archive User
                </button>
                @endif
                @endcan
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('users.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 bg-white dark:bg-gray-800 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900 dark:hover:bg-gray-700 dark:bg-gray-900">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update User
                </button>
            </div>
        </div>
    </form>

    <!-- Archive Form (hidden) -->
    @can('delete', $user)
    @if($user->id !== Auth::id())
    <form id="archive-form" action="{{ route('users.archive', $user) }}" method="POST" style="display: none;">
        @csrf
        @method('POST')
    </form>
    @endif
    @endcan
</div>
@endsection