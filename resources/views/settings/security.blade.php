@extends('layouts.settings')

@section('title', 'Security Settings - Nestogy')

@section('settings-title', 'Security Settings')
@section('settings-description', 'Configure authentication, access control, and security policies')

@section('settings-content')
<div x-data="{ activeTab: 'authentication' }">
    
    <form method="POST" action="{{ route('settings.security.update') }}">
        @csrf
        @method('PUT')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6 pt-4">
                <button type="button" 
                        @click="activeTab = 'authentication'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'authentication', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'authentication'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Authentication
                </button>
                <button type="button" 
                        @click="activeTab = 'password'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'password', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'password'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Password Policy
                </button>
                <button type="button" 
                        @click="activeTab = 'sessions'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'sessions', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'sessions'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Sessions & Lockout
                </button>
                <button type="button" 
                        @click="activeTab = 'oauth'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'oauth', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'oauth'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    OAuth & SSO
                </button>
                <button type="button" 
                        @click="activeTab = 'audit'"
                        :class="{'border-blue-500 text-blue-600': activeTab === 'audit', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'audit'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Audit & Logging
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Authentication Tab -->
            <div x-show="activeTab === 'authentication'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Authentication Settings</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="two_factor_enabled"
                                       value="1"
                                       {{ old('two_factor_enabled', $setting->two_factor_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Two-Factor Authentication</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="remember_me_enabled"
                                       value="1"
                                       {{ old('remember_me_enabled', $setting->remember_me_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Allow "Remember Me" Option</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="email_verification_required"
                                       value="1"
                                       {{ old('email_verification_required', $setting->email_verification_required ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Require Email Verification</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="api_authentication_enabled"
                                       value="1"
                                       {{ old('api_authentication_enabled', $setting->api_authentication_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable API Authentication</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Login Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="max_login_attempts" class="block text-sm font-medium text-gray-700 mb-1">
                                    Max Login Attempts
                                </label>
                                <input type="number" 
                                       id="max_login_attempts"
                                       name="max_login_attempts"
                                       value="{{ old('max_login_attempts', $setting->max_login_attempts ?? 5) }}"
                                       min="1"
                                       max="10"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="5">
                            </div>
                            <div>
                                <label for="login_lockout_duration" class="block text-sm font-medium text-gray-700 mb-1">
                                    Lockout Duration (minutes)
                                </label>
                                <input type="number" 
                                       id="login_lockout_duration"
                                       name="login_lockout_duration"
                                       value="{{ old('login_lockout_duration', $setting->login_lockout_duration ?? 15) }}"
                                       min="1"
                                       max="1440"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="15">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Policy Tab -->
            <div x-show="activeTab === 'password'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Password Requirements</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password_min_length" class="block text-sm font-medium text-gray-700 mb-1">
                                    Minimum Length
                                </label>
                                <input type="number" 
                                       id="password_min_length"
                                       name="password_min_length"
                                       value="{{ old('password_min_length', $setting->password_min_length ?? 8) }}"
                                       min="6"
                                       max="32"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="8">
                            </div>
                            <div>
                                <label for="password_expiry_days" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password Expiry (days)
                                </label>
                                <input type="number" 
                                       id="password_expiry_days"
                                       name="password_expiry_days"
                                       value="{{ old('password_expiry_days', $setting->password_expiry_days ?? 90) }}"
                                       min="0"
                                       max="365"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="90">
                                <p class="mt-1 text-xs text-gray-500">Set to 0 to disable password expiry</p>
                            </div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="password_require_uppercase"
                                       value="1"
                                       {{ old('password_require_uppercase', $setting->password_require_uppercase ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Require Uppercase Letter</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="password_require_lowercase"
                                       value="1"
                                       {{ old('password_require_lowercase', $setting->password_require_lowercase ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Require Lowercase Letter</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="password_require_number"
                                       value="1"
                                       {{ old('password_require_number', $setting->password_require_number ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Require Number</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="password_require_special"
                                       value="1"
                                       {{ old('password_require_special', $setting->password_require_special ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Require Special Character</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Password History</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password_history_count" class="block text-sm font-medium text-gray-700 mb-1">
                                    Remember Last N Passwords
                                </label>
                                <input type="number" 
                                       id="password_history_count"
                                       name="password_history_count"
                                       value="{{ old('password_history_count', $setting->password_history_count ?? 5) }}"
                                       min="0"
                                       max="24"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="5">
                                <p class="mt-1 text-xs text-gray-500">Set to 0 to disable password history</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sessions & Lockout Tab -->
            <div x-show="activeTab === 'sessions'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Session Management</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="session_lifetime" class="block text-sm font-medium text-gray-700 mb-1">
                                    Session Lifetime (minutes)
                                </label>
                                <input type="number" 
                                       id="session_lifetime"
                                       name="session_lifetime"
                                       value="{{ old('session_lifetime', $setting->session_lifetime ?? 120) }}"
                                       min="15"
                                       max="1440"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="120">
                            </div>
                            <div>
                                <label for="idle_timeout" class="block text-sm font-medium text-gray-700 mb-1">
                                    Idle Timeout (minutes)
                                </label>
                                <input type="number" 
                                       id="idle_timeout"
                                       name="idle_timeout"
                                       value="{{ old('idle_timeout', $setting->idle_timeout ?? 30) }}"
                                       min="5"
                                       max="120"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="30">
                            </div>
                        </div>
                        <div class="mt-4 space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="single_session_per_user"
                                       value="1"
                                       {{ old('single_session_per_user', $setting->single_session_per_user ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Single Session Per User</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="session_ip_check"
                                       value="1"
                                       {{ old('session_ip_check', $setting->session_ip_check ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Verify Session IP Address</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OAuth & SSO Tab -->
            <div x-show="activeTab === 'oauth'" x-transition>
                <div class="space-y-6">
                    <!-- Google OAuth -->
                    <div class="border rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Google OAuth</h4>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="oauth_google_enabled"
                                       value="1"
                                       {{ old('oauth_google_enabled', $setting->oauth_google_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Google OAuth</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="oauth_google_client_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        Client ID
                                    </label>
                                    <input type="text" 
                                           id="oauth_google_client_id"
                                           name="oauth_google_client_id"
                                           value="{{ old('oauth_google_client_id', $setting->oauth_google_client_id ?? '') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="oauth_google_client_secret" class="block text-sm font-medium text-gray-700 mb-1">
                                        Client Secret
                                    </label>
                                    <input type="password" 
                                           id="oauth_google_client_secret"
                                           name="oauth_google_client_secret"
                                           value="{{ old('oauth_google_client_secret', $setting->oauth_google_client_secret ?? '') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Microsoft OAuth -->
                    <div class="border rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Microsoft OAuth</h4>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="oauth_microsoft_enabled"
                                       value="1"
                                       {{ old('oauth_microsoft_enabled', $setting->oauth_microsoft_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable Microsoft OAuth</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="oauth_microsoft_client_id" class="block text-sm font-medium text-gray-700 mb-1">
                                        Client ID
                                    </label>
                                    <input type="text" 
                                           id="oauth_microsoft_client_id"
                                           name="oauth_microsoft_client_id"
                                           value="{{ old('oauth_microsoft_client_id', $setting->oauth_microsoft_client_id ?? '') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="oauth_microsoft_client_secret" class="block text-sm font-medium text-gray-700 mb-1">
                                        Client Secret
                                    </label>
                                    <input type="password" 
                                           id="oauth_microsoft_client_secret"
                                           name="oauth_microsoft_client_secret"
                                           value="{{ old('oauth_microsoft_client_secret', $setting->oauth_microsoft_client_secret ?? '') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SAML Settings -->
                    <div class="border rounded-lg p-4">
                        <h4 class="text-md font-medium text-gray-900 mb-3">SAML Settings</h4>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="saml_enabled"
                                       value="1"
                                       {{ old('saml_enabled', $setting->saml_enabled ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Enable SAML Authentication</span>
                            </label>
                            <div>
                                <label for="saml_idp_url" class="block text-sm font-medium text-gray-700 mb-1">
                                    Identity Provider URL
                                </label>
                                <input type="url" 
                                       id="saml_idp_url"
                                       name="saml_idp_url"
                                       value="{{ old('saml_idp_url', $setting->saml_idp_url ?? '') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="https://idp.example.com/saml">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit & Logging Tab -->
            <div x-show="activeTab === 'audit'" x-transition>
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Settings</h3>
                        <div class="space-y-4">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_login_events"
                                       value="1"
                                       {{ old('audit_login_events', $setting->audit_login_events ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit Login Events</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_data_changes"
                                       value="1"
                                       {{ old('audit_data_changes', $setting->audit_data_changes ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit Data Changes</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_permission_changes"
                                       value="1"
                                       {{ old('audit_permission_changes', $setting->audit_permission_changes ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit Permission Changes</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       name="audit_failed_attempts"
                                       value="1"
                                       {{ old('audit_failed_attempts', $setting->audit_failed_attempts ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-3 text-sm text-gray-700">Audit Failed Access Attempts</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Log Retention</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="audit_retention_days" class="block text-sm font-medium text-gray-700 mb-1">
                                    Audit Log Retention (days)
                                </label>
                                <input type="number" 
                                       id="audit_retention_days"
                                       name="audit_retention_days"
                                       value="{{ old('audit_retention_days', $setting->audit_retention_days ?? 365) }}"
                                       min="30"
                                       max="2555"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="365">
                            </div>
                            <div>
                                <label for="activity_log_retention_days" class="block text-sm font-medium text-gray-700 mb-1">
                                    Activity Log Retention (days)
                                </label>
                                <input type="number" 
                                       id="activity_log_retention_days"
                                       name="activity_log_retention_days"
                                       value="{{ old('activity_log_retention_days', $setting->activity_log_retention_days ?? 90) }}"
                                       min="7"
                                       max="365"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="90">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200">
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
@endsection
