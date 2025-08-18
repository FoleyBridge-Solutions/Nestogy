@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="space-y-4">
    <!-- Page Header -->
    <x-page-header 
        title="My Profile"
        subtitle="Manage your account settings and preferences"
        :compact="true"
    />

    <!-- Profile Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="profileTabs()">
        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
            <x-content-card :compact="true">
                <nav class="space-y-1">
                    <button @click="activeTab = 'profile'" 
                            :class="activeTab === 'profile' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            class="w-full group border-l-4 px-3 py-2 flex items-center text-sm font-medium transition-colors">
                        <svg class="flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="truncate">Profile Information</span>
                    </button>
                    
                    <button @click="activeTab = 'security'" 
                            :class="activeTab === 'security' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            class="w-full group border-l-4 px-3 py-2 flex items-center text-sm font-medium transition-colors">
                        <svg class="flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span class="truncate">Security Settings</span>
                    </button>
                    
                    <button @click="activeTab = 'preferences'" 
                            :class="activeTab === 'preferences' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-50'"
                            class="w-full group border-l-4 px-3 py-2 flex items-center text-sm font-medium transition-colors">
                        <svg class="flex-shrink-0 -ml-1 mr-3 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="truncate">Preferences</span>
                    </button>
                </nav>
            </x-content-card>
        </div>

        <!-- Main Content Area -->
        <div class="lg:col-span-2">
            <!-- Profile Information Tab -->
            <div x-show="activeTab === 'profile'" x-transition>
                <x-content-card title="Profile Information" :compact="true">
                    <form method="POST" action="{{ route('users.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- Avatar Section -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                            <div class="flex items-center space-x-6">
                                <div class="flex-shrink-0">
                                    <img class="h-24 w-24 rounded-full" 
                                         src="{{ $user->getAvatarUrl() }}" 
                                         alt="{{ $user->name }}">
                                </div>
                                <div>
                                    <input type="file" name="avatar" accept="image/*" 
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-xs text-gray-500">JPG, GIF or PNG. Max size 2MB</p>
                                    @error('avatar')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone ?? '') }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-300 @enderror">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Company Info (Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Company</label>
                                <input type="text" value="{{ $user->company->name ?? 'N/A' }}" readonly
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50">
                            </div>

                            <!-- Role Info (Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Role</label>
                                <input type="text" value="{{ $user->userSetting ? $user->userSetting->getRoleLabel() : 'Not Assigned' }}" readonly
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50">
                            </div>

                            <!-- Member Since (Read-only) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Member Since</label>
                                <input type="text" value="{{ $user->created_at->format('M d, Y') }}" readonly
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </x-content-card>
            </div>

            <!-- Security Settings Tab -->
            <div x-show="activeTab === 'security'" x-transition>
                <x-content-card title="Security Settings" :compact="true">
                    <form method="POST" action="{{ route('users.password.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- Password Change -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
                                <p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">
                                        Current Password
                                    </label>
                                    <input type="password" name="current_password" id="current_password" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('current_password') border-red-300 @enderror">
                                    @error('current_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">
                                        New Password
                                    </label>
                                    <input type="password" name="password" id="password" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-300 @enderror">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                        Confirm New Password
                                    </label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- MFA Settings -->
                            @if($user->userSetting)
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Two-Factor Authentication</h3>
                                <p class="mt-1 text-sm text-gray-500">Add additional security to your account using two-factor authentication.</p>
                                
                                <div class="mt-4">
                                    <div class="flex items-center">
                                        <input type="hidden" name="force_mfa" value="0">
                                        <input type="checkbox" name="force_mfa" id="force_mfa" 
                                               value="1" {{ $user->userSetting->force_mfa ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="force_mfa" class="ml-2 block text-sm text-gray-900">
                                            Enable Two-Factor Authentication
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update Security Settings
                            </button>
                        </div>
                    </form>
                </x-content-card>
            </div>

            <!-- Preferences Tab -->
            <div x-show="activeTab === 'preferences'" x-transition>
                <x-content-card title="Preferences" :compact="true">
                    <form method="POST" action="{{ route('users.settings.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <!-- Display Preferences -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Display Settings</h3>
                                <p class="mt-1 text-sm text-gray-500">Customize how information is displayed in the application.</p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                    <select name="timezone" id="timezone" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">System Default</option>
                                        <option value="America/New_York" {{ old('timezone', $user->timezone ?? '') == 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                        <option value="America/Chicago" {{ old('timezone', $user->timezone ?? '') == 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                        <option value="America/Denver" {{ old('timezone', $user->timezone ?? '') == 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                        <option value="America/Los_Angeles" {{ old('timezone', $user->timezone ?? '') == 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                        <option value="UTC" {{ old('timezone', $user->timezone ?? '') == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="date_format" class="block text-sm font-medium text-gray-700">Date Format</label>
                                    <select name="date_format" id="date_format" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="m/d/Y" {{ old('date_format', $user->date_format ?? '') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                        <option value="d/m/Y" {{ old('date_format', $user->date_format ?? '') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                        <option value="Y-m-d" {{ old('date_format', $user->date_format ?? '') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="time_format" class="block text-sm font-medium text-gray-700">Time Format</label>
                                    <select name="time_format" id="time_format" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="12" {{ old('time_format', $user->time_format ?? '') == '12' ? 'selected' : '' }}>12 Hour</option>
                                        <option value="24" {{ old('time_format', $user->time_format ?? '') == '24' ? 'selected' : '' }}>24 Hour</option>
                                    </select>
                                </div>

                                @if($user->userSetting)
                                <div>
                                    <label for="records_per_page" class="block text-sm font-medium text-gray-700">Records Per Page</label>
                                    <select name="records_per_page" id="records_per_page" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="10" {{ $user->userSetting->records_per_page == 10 ? 'selected' : '' }}>10</option>
                                        <option value="25" {{ $user->userSetting->records_per_page == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $user->userSetting->records_per_page == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $user->userSetting->records_per_page == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                                @endif
                            </div>

                            <!-- Dashboard Preferences -->
                            @if($user->userSetting)
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Dashboard Widgets</h3>
                                <p class="mt-1 text-sm text-gray-500">Choose which dashboard widgets to display.</p>
                                
                                <div class="mt-4 space-y-4">
                                    <div class="flex items-center">
                                        <input type="hidden" name="dashboard_financial_enable" value="0">
                                        <input type="checkbox" name="dashboard_financial_enable" id="dashboard_financial_enable" 
                                               value="1" {{ $user->userSetting->dashboard_financial_enable ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="dashboard_financial_enable" class="ml-2 block text-sm text-gray-900">
                                            Show Financial Dashboard
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="hidden" name="dashboard_technical_enable" value="0">
                                        <input type="checkbox" name="dashboard_technical_enable" id="dashboard_technical_enable" 
                                               value="1" {{ $user->userSetting->dashboard_technical_enable ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="dashboard_technical_enable" class="ml-2 block text-sm text-gray-900">
                                            Show Technical Dashboard
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Notification Preferences -->
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                                <p class="mt-1 text-sm text-gray-500">Choose how you want to receive notifications.</p>
                                
                                <div class="mt-4 space-y-4">
                                    <div class="flex items-center">
                                        <input type="hidden" name="notifications_email" value="0">
                                        <input type="checkbox" name="notifications_email" id="notifications_email" 
                                               value="1" checked
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="notifications_email" class="ml-2 block text-sm text-gray-900">
                                            Email Notifications
                                        </label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="hidden" name="notifications_browser" value="0">
                                        <input type="checkbox" name="notifications_browser" id="notifications_browser" 
                                               value="1"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="notifications_browser" class="ml-2 block text-sm text-gray-900">
                                            Browser Notifications
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Theme Selection -->
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Appearance</h3>
                                <p class="mt-1 text-sm text-gray-500">Customize the look and feel of the application.</p>
                                
                                <div class="mt-4">
                                    <label for="theme" class="block text-sm font-medium text-gray-700">Theme</label>
                                    <select name="theme" id="theme" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="light" {{ ($user->userSetting->theme ?? 'light') == 'light' ? 'selected' : '' }}>Light</option>
                                        <option value="dark" {{ ($user->userSetting->theme ?? 'light') == 'dark' ? 'selected' : '' }}>Dark</option>
                                        <option value="auto" {{ ($user->userSetting->theme ?? 'light') == 'auto' ? 'selected' : '' }}>System Default</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </x-content-card>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Handle tab persistence and theme switching
    document.addEventListener('alpine:init', () => {
        Alpine.data('profileTabs', () => ({
            activeTab: localStorage.getItem('profileActiveTab') || 'profile',
            init() {
                this.$watch('activeTab', value => {
                    localStorage.setItem('profileActiveTab', value);
                });
            }
        }));
    });

    // Handle theme change preview
    document.getElementById('theme').addEventListener('change', function() {
        const selectedTheme = this.value;
        const body = document.body;
        
        // Remove existing theme classes
        body.classList.remove('dark', 'light');
        
        // Add new theme class
        if (selectedTheme === 'dark') {
            body.classList.add('dark');
        } else if (selectedTheme === 'light') {
            body.classList.add('light');
        }
        // For 'auto', don't add any class - let CSS media query handle it
    });
</script>
@endpush
@endsection