<div class="p-6">
    <form method="POST" action="{{ route('settings.security.update') }}">
        @csrf
        @method('PUT')
        
        <div class="space-y-8">
            <!-- Password Policy -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Password Policy</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="min_password_length" class="block text-sm font-medium text-gray-700 mb-1">
                            Minimum Password Length
                        </label>
                        <input type="number" 
                               id="min_password_length"
                               name="min_password_length"
                               value="{{ old('min_password_length', $settings['min_password_length'] ?? 8) }}"
                               min="6"
                               max="32"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="password_expiry_days" class="block text-sm font-medium text-gray-700 mb-1">
                            Password Expiry (Days)
                        </label>
                        <input type="number" 
                               id="password_expiry_days"
                               name="password_expiry_days"
                               value="{{ old('password_expiry_days', $settings['password_expiry_days'] ?? 90) }}"
                               min="30"
                               max="365"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>
                
                <div class="mt-4 space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="require_uppercase"
                               value="1"
                               {{ old('require_uppercase', $settings['require_uppercase'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">Require uppercase letters</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="require_lowercase"
                               value="1"
                               {{ old('require_lowercase', $settings['require_lowercase'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">Require lowercase letters</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="require_numbers"
                               value="1"
                               {{ old('require_numbers', $settings['require_numbers'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-gray-700">Require numbers</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="require_special_chars"
                               value="1"
                               {{ old('require_special_chars', $settings['require_special_chars'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">Require special characters</span>
                    </label>
                </div>
            </div>

            <!-- Two-Factor Authentication -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Two-Factor Authentication</h3>
                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="require_2fa"
                               value="1"
                               {{ old('require_2fa', $settings['require_2fa'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">Require two-factor authentication for all users</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="require_2fa_admin"
                               value="1"
                               {{ old('require_2fa_admin', $settings['require_2fa_admin'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">Require two-factor authentication for administrators only</span>
                    </label>
                </div>
            </div>

            <!-- Session Management -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Session Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="session_timeout" class="block text-sm font-medium text-gray-700 mb-1">
                            Session Timeout (minutes)
                        </label>
                        <input type="number" 
                               id="session_timeout"
                               name="session_timeout"
                               value="{{ old('session_timeout', $settings['session_timeout'] ?? 30) }}"
                               min="5"
                               max="1440"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="max_login_attempts" class="block text-sm font-medium text-gray-700 mb-1">
                            Max Login Attempts
                        </label>
                        <input type="number" 
                               id="max_login_attempts"
                               name="max_login_attempts"
                               value="{{ old('max_login_attempts', $settings['max_login_attempts'] ?? 5) }}"
                               min="3"
                               max="20"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               name="force_logout_on_password_change"
                               value="1"
                               {{ old('force_logout_on_password_change', $settings['force_logout_on_password_change'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">Force logout on password change</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 mt-8">
            <a href="{{ route('settings.index') }}" 
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Save Changes
            </button>
        </div>
    </form>
</div>
