@extends('layouts.settings')

@section('title', 'User Management Settings - Nestogy')

@section('settings-title', 'User Management Settings')
@section('settings-description', 'Configure user limits, onboarding, authentication, and monitoring settings')

@section('settings-content')
<div x-data="{ activeTab: 'limits' }">
    <form method="POST" action="{{ route('settings.user-management.update') }}" id="userManagementForm">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'limits'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'limits', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'limits'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    User Limits & Subscription
                </button>
                <button type="button" 
                        @click="activeTab = 'onboarding'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'onboarding', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'onboarding'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    User Onboarding
                </button>
                <button type="button" 
                        @click="activeTab = 'authentication'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'authentication', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'authentication'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Authentication
                </button>
                <button type="button" 
                        @click="activeTab = 'sessions'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'sessions', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'sessions'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Session Management
                </button>
                <button type="button" 
                        @click="activeTab = 'monitoring'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'monitoring', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'monitoring'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Activity & Monitoring
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- User Limits & Subscription Tab -->
            <div x-show="activeTab === 'limits'" x-transition>
                <div class="space-y-6">
                    <!-- User Limits & Subscription Management -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                User Limits & Subscription Management
                            </h3>
                        </div>
                        <div class="p-6">
                            @if($company->id === 1)
                                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-blue-700">Your organization has unlimited user access.</p>
                                    </div>
                                </div>
                            @elseif($subscription)
                                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-blue-700">Your subscription allows up to {{ $subscription->user_limit }} users. Current users: {{ $company->users()->count() }}</p>
                                    </div>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="max_users" class="block text-sm font-medium text-gray-700 mb-1">Maximum Users</label>
                                    <input type="number" 
                                           id="max_users" 
                                           name="max_users" 
                                           value="{{ old('max_users', $setting->max_users ?? ($subscription ? $subscription->user_limit : 5)) }}"
                                           min="1" 
                                           @if($company->id !== 1 && $subscription) max="{{ $subscription->user_limit }}" @endif
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('max_users') border-red-500 @enderror">
                                    @error('max_users')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="user_invite_limit_per_month" class="block text-sm font-medium text-gray-700 mb-1">Monthly Invite Limit</label>
                                    <input type="number" 
                                           id="user_invite_limit_per_month" 
                                           name="user_invite_limit_per_month" 
                                           value="{{ old('user_invite_limit_per_month', $setting->user_invite_limit_per_month ?? 10) }}"
                                           min="0" 
                                           max="100"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('user_invite_limit_per_month') border-red-500 @enderror">
                                    @error('user_invite_limit_per_month')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="require_admin_approval_for_new_users" 
                                               name="require_admin_approval_for_new_users" 
                                               value="1"
                                               {{ old('require_admin_approval_for_new_users', $setting->require_admin_approval_for_new_users ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Require Admin Approval for New Users</span>
                                    </label>
                                </div>

                                <div>
                                    <label for="auto_deactivate_unused_accounts_days" class="block text-sm font-medium text-gray-700 mb-1">Auto-deactivate Unused Accounts (days)</label>
                                    <input type="number" 
                                           id="auto_deactivate_unused_accounts_days" 
                                           name="auto_deactivate_unused_accounts_days" 
                                           value="{{ old('auto_deactivate_unused_accounts_days', $setting->auto_deactivate_unused_accounts_days ?? '') }}"
                                           min="30" 
                                           max="365" 
                                           placeholder="Leave empty to disable"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('auto_deactivate_unused_accounts_days') border-red-500 @enderror">
                                    @error('auto_deactivate_unused_accounts_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Onboarding Tab -->
            <div x-show="activeTab === 'onboarding'" x-transition>
                <div class="space-y-6">
                    <!-- User Onboarding Settings -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                User Onboarding Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_onboarding_settings[enabled]" 
                                               name="user_onboarding_settings[enabled]" 
                                               value="1"
                                               {{ old('user_onboarding_settings.enabled', $setting->user_onboarding_settings['enabled'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Enable Onboarding Process</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_onboarding_settings[welcome_email]" 
                                               name="user_onboarding_settings[welcome_email]" 
                                               value="1"
                                               {{ old('user_onboarding_settings.welcome_email', $setting->user_onboarding_settings['welcome_email'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Send Welcome Email</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_onboarding_settings[setup_wizard]" 
                                               name="user_onboarding_settings[setup_wizard]" 
                                               value="1"
                                               {{ old('user_onboarding_settings.setup_wizard', $setting->user_onboarding_settings['setup_wizard'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Show Setup Wizard</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_onboarding_settings[training_materials]" 
                                               name="user_onboarding_settings[training_materials]" 
                                               value="1"
                                               {{ old('user_onboarding_settings.training_materials', $setting->user_onboarding_settings['training_materials'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Provide Training Materials</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_onboarding_settings[mentor_assignment]" 
                                               name="user_onboarding_settings[mentor_assignment]" 
                                               value="1"
                                               {{ old('user_onboarding_settings.mentor_assignment', $setting->user_onboarding_settings['mentor_assignment'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Assign Mentor</span>
                                    </label>
                                </div>

                                <div>
                                    <label for="user_onboarding_settings[probation_period_days]" class="block text-sm font-medium text-gray-700 mb-1">Probation Period (days)</label>
                                    <input type="number" 
                                           id="user_onboarding_settings[probation_period_days]" 
                                           name="user_onboarding_settings[probation_period_days]" 
                                           value="{{ old('user_onboarding_settings.probation_period_days', $setting->user_onboarding_settings['probation_period_days'] ?? 30) }}"
                                           min="0" 
                                           max="90"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('user_onboarding_settings.probation_period_days') border-red-500 @enderror">
                                    @error('user_onboarding_settings.probation_period_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authentication Tab -->
            <div x-show="activeTab === 'authentication'" x-transition>
                <div class="space-y-6">
                    <!-- Authentication Settings -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v-2H7v-2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                                Authentication Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="authentication_settings[allow_local_login]" 
                                               name="authentication_settings[allow_local_login]" 
                                               value="1"
                                               {{ old('authentication_settings.allow_local_login', $setting->authentication_settings['allow_local_login'] ?? true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Allow Local Login</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="authentication_settings[require_email_verification]" 
                                               name="authentication_settings[require_email_verification]" 
                                               value="1"
                                               {{ old('authentication_settings.require_email_verification', $setting->authentication_settings['require_email_verification'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Require Email Verification</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="authentication_settings[force_password_change_first_login]" 
                                               name="authentication_settings[force_password_change_first_login]" 
                                               value="1"
                                               {{ old('authentication_settings.force_password_change_first_login', $setting->authentication_settings['force_password_change_first_login'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Force Password Change on First Login</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="authentication_settings[remember_me_enabled]" 
                                               name="authentication_settings[remember_me_enabled]" 
                                               value="1"
                                               {{ old('authentication_settings.remember_me_enabled', $setting->authentication_settings['remember_me_enabled'] ?? true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Enable "Remember Me" Option</span>
                                    </label>
                                </div>

                                <div>
                                    <label for="authentication_settings[remember_me_duration_days]" class="block text-sm font-medium text-gray-700 mb-1">Remember Me Duration (days)</label>
                                    <input type="number" 
                                           id="authentication_settings[remember_me_duration_days]" 
                                           name="authentication_settings[remember_me_duration_days]" 
                                           value="{{ old('authentication_settings.remember_me_duration_days', $setting->authentication_settings['remember_me_duration_days'] ?? 30) }}"
                                           min="1" 
                                           max="90"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('authentication_settings.remember_me_duration_days') border-red-500 @enderror">
                                    @error('authentication_settings.remember_me_duration_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session Management Tab -->
            <div x-show="activeTab === 'sessions'" x-transition>
                <div class="space-y-6">
                    <!-- Session Management -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Session Management
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label for="session_management_settings[max_concurrent_sessions]" class="block text-sm font-medium text-gray-700 mb-1">Max Concurrent Sessions</label>
                                    <input type="number" 
                                           id="session_management_settings[max_concurrent_sessions]" 
                                           name="session_management_settings[max_concurrent_sessions]" 
                                           value="{{ old('session_management_settings.max_concurrent_sessions', $setting->session_management_settings['max_concurrent_sessions'] ?? 3) }}"
                                           min="1" 
                                           max="10"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('session_management_settings.max_concurrent_sessions') border-red-500 @enderror">
                                    @error('session_management_settings.max_concurrent_sessions')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="session_management_settings[idle_timeout_minutes]" class="block text-sm font-medium text-gray-700 mb-1">Idle Timeout (minutes)</label>
                                    <input type="number" 
                                           id="session_management_settings[idle_timeout_minutes]" 
                                           name="session_management_settings[idle_timeout_minutes]" 
                                           value="{{ old('session_management_settings.idle_timeout_minutes', $setting->session_management_settings['idle_timeout_minutes'] ?? 30) }}"
                                           min="5" 
                                           max="1440"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('session_management_settings.idle_timeout_minutes') border-red-500 @enderror">
                                    @error('session_management_settings.idle_timeout_minutes')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="session_management_settings[absolute_timeout_hours]" class="block text-sm font-medium text-gray-700 mb-1">Absolute Timeout (hours)</label>
                                    <input type="number" 
                                           id="session_management_settings[absolute_timeout_hours]" 
                                           name="session_management_settings[absolute_timeout_hours]" 
                                           value="{{ old('session_management_settings.absolute_timeout_hours', $setting->session_management_settings['absolute_timeout_hours'] ?? 8) }}"
                                           min="1" 
                                           max="24"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('session_management_settings.absolute_timeout_hours') border-red-500 @enderror">
                                    @error('session_management_settings.absolute_timeout_hours')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="session_management_settings[force_logout_on_password_change]" 
                                               name="session_management_settings[force_logout_on_password_change]" 
                                               value="1"
                                               {{ old('session_management_settings.force_logout_on_password_change', $setting->session_management_settings['force_logout_on_password_change'] ?? true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Force Logout on Password Change</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="session_management_settings[track_session_activity]" 
                                               name="session_management_settings[track_session_activity]" 
                                               value="1"
                                               {{ old('session_management_settings.track_session_activity', $setting->session_management_settings['track_session_activity'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Track Session Activity</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity & Monitoring Tab -->
            <div x-show="activeTab === 'monitoring'" x-transition>
                <div class="space-y-6">
                    <!-- User Activity & Monitoring -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                User Activity & Monitoring
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_activity_settings[track_login_history]" 
                                               name="user_activity_settings[track_login_history]" 
                                               value="1"
                                               {{ old('user_activity_settings.track_login_history', $setting->user_activity_settings['track_login_history'] ?? true) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Track Login History</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_activity_settings[track_user_actions]" 
                                               name="user_activity_settings[track_user_actions]" 
                                               value="1"
                                               {{ old('user_activity_settings.track_user_actions', $setting->user_activity_settings['track_user_actions'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Track User Actions</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               id="user_activity_settings[notify_on_suspicious_activity]" 
                                               name="user_activity_settings[notify_on_suspicious_activity]" 
                                               value="1"
                                               {{ old('user_activity_settings.notify_on_suspicious_activity', $setting->user_activity_settings['notify_on_suspicious_activity'] ?? false) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="ml-3 text-sm text-gray-700">Notify on Suspicious Activity</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="user_activity_settings[login_history_retention_days]" class="block text-sm font-medium text-gray-700 mb-1">Login History Retention (days)</label>
                                    <input type="number" 
                                           id="user_activity_settings[login_history_retention_days]" 
                                           name="user_activity_settings[login_history_retention_days]" 
                                           value="{{ old('user_activity_settings.login_history_retention_days', $setting->user_activity_settings['login_history_retention_days'] ?? 90) }}"
                                           min="30" 
                                           max="365"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('user_activity_settings.login_history_retention_days') border-red-500 @enderror">
                                    @error('user_activity_settings.login_history_retention_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="user_activity_settings[activity_log_retention_days]" class="block text-sm font-medium text-gray-700 mb-1">Activity Log Retention (days)</label>
                                    <input type="number" 
                                           id="user_activity_settings[activity_log_retention_days]" 
                                           name="user_activity_settings[activity_log_retention_days]" 
                                           value="{{ old('user_activity_settings.activity_log_retention_days', $setting->user_activity_settings['activity_log_retention_days'] ?? 90) }}"
                                           min="30" 
                                           max="365"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('user_activity_settings.activity_log_retention_days') border-red-500 @enderror">
                                    @error('user_activity_settings.activity_log_retention_days')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="user_activity_settings[failed_login_threshold]" class="block text-sm font-medium text-gray-700 mb-1">Failed Login Threshold</label>
                                    <input type="number" 
                                           id="user_activity_settings[failed_login_threshold]" 
                                           name="user_activity_settings[failed_login_threshold]" 
                                           value="{{ old('user_activity_settings.failed_login_threshold', $setting->user_activity_settings['failed_login_threshold'] ?? 5) }}"
                                           min="3" 
                                           max="10"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('user_activity_settings.failed_login_threshold') border-red-500 @enderror">
                                    @error('user_activity_settings.failed_login_threshold')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
            <button type="button" 
                    onclick="resetForm()"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Reset
            </button>
            <a href="{{ route('settings.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Settings
            </button>
        </div>
    </form>
</div>

<script>
function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        document.getElementById('userManagementForm').reset();
    }
}
</script>
@endsection