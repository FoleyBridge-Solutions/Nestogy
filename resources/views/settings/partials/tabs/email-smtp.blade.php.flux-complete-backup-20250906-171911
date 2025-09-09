<div class="p-6">
    <div class="space-y-6">
        <!-- SMTP Configuration Section -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">SMTP Server Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SMTP Server Host -->
                <div class="col-span-2 md:col-span-1">
                    <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">
                        SMTP Host
                    </label>
                    <input type="text" 
                           id="smtp_host"
                           name="smtp_host"
                           value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="mail.yourdomain.com">
                </div>

                <!-- SMTP Port -->
                <div class="col-span-2 md:col-span-1">
                    <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">
                        SMTP Port
                    </label>
                    <select id="smtp_port"
                            name="smtp_port"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="25" {{ old('smtp_port', $settings['smtp_port'] ?? '587') == '25' ? 'selected' : '' }}>25 (Standard)</option>
                        <option value="587" {{ old('smtp_port', $settings['smtp_port'] ?? '587') == '587' ? 'selected' : '' }}>587 (TLS/STARTTLS)</option>
                        <option value="465" {{ old('smtp_port', $settings['smtp_port'] ?? '587') == '465' ? 'selected' : '' }}>465 (SSL)</option>
                        <option value="2525" {{ old('smtp_port', $settings['smtp_port'] ?? '587') == '2525' ? 'selected' : '' }}>2525 (Alternative)</option>
                    </select>
                </div>

                <!-- SMTP Security -->
                <div class="col-span-2 md:col-span-1">
                    <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 mb-1">
                        Encryption
                    </label>
                    <select id="smtp_encryption"
                            name="smtp_encryption"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') == '' ? 'selected' : '' }}>None</option>
                        <option value="tls" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS/STARTTLS</option>
                        <option value="ssl" {{ old('smtp_encryption', $settings['smtp_encryption'] ?? 'tls') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>

                <!-- SMTP Username -->
                <div class="col-span-2 md:col-span-1">
                    <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username
                    </label>
                    <input type="text" 
                           id="smtp_username"
                           name="smtp_username"
                           value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="noreply@yourdomain.com">
                </div>

                <!-- SMTP Password -->
                <div class="col-span-2">
                    <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="smtp_password"
                               name="smtp_password"
                               value="{{ old('smtp_password', $settings['smtp_password'] ? '••••••••' : '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm pr-10"
                               placeholder="Enter SMTP password">
                        <button type="button" 
                                onclick="togglePassword('smtp_password')"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- From Address Configuration -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">From Address Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- From Name -->
                <div>
                    <label for="smtp_from_name" class="block text-sm font-medium text-gray-700 mb-1">
                        From Name
                    </label>
                    <input type="text" 
                           id="smtp_from_name"
                           name="smtp_from_name"
                           value="{{ old('smtp_from_name', $settings['smtp_from_name'] ?? $company->name ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="Your Company Name">
                </div>

                <!-- From Email -->
                <div>
                    <label for="smtp_from_address" class="block text-sm font-medium text-gray-700 mb-1">
                        From Email Address
                    </label>
                    <input type="email" 
                           id="smtp_from_address"
                           name="smtp_from_address"
                           value="{{ old('smtp_from_address', $settings['smtp_from_address'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="noreply@yourdomain.com">
                </div>

                <!-- Reply-To Email -->
                <div>
                    <label for="smtp_reply_to" class="block text-sm font-medium text-gray-700 mb-1">
                        Reply-To Address (Optional)
                    </label>
                    <input type="email" 
                           id="smtp_reply_to"
                           name="smtp_reply_to"
                           value="{{ old('smtp_reply_to', $settings['smtp_reply_to'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="support@yourdomain.com">
                </div>

                <!-- BCC Address -->
                <div>
                    <label for="smtp_bcc_address" class="block text-sm font-medium text-gray-700 mb-1">
                        BCC Address (Optional)
                    </label>
                    <input type="email" 
                           id="smtp_bcc_address"
                           name="smtp_bcc_address"
                           value="{{ old('smtp_bcc_address', $settings['smtp_bcc_address'] ?? '') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                           placeholder="admin@yourdomain.com">
                </div>
            </div>
        </div>

        <!-- Advanced Options -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Options</h3>
            <div class="space-y-4">
                <!-- Timeout Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="smtp_timeout" class="block text-sm font-medium text-gray-700 mb-1">
                            Connection Timeout (seconds)
                        </label>
                        <input type="number" 
                               id="smtp_timeout"
                               name="smtp_timeout"
                               value="{{ old('smtp_timeout', $settings['smtp_timeout'] ?? 30) }}"
                               min="10" max="300"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="smtp_local_domain" class="block text-sm font-medium text-gray-700 mb-1">
                            Local Domain (HELO/EHLO)
                        </label>
                        <input type="text" 
                               id="smtp_local_domain"
                               name="smtp_local_domain"
                               value="{{ old('smtp_local_domain', $settings['smtp_local_domain'] ?? '') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                               placeholder="yourdomain.com">
                    </div>
                </div>

                <!-- Test Email Section -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Test SMTP Configuration</h4>
                            <p class="text-sm text-gray-600">Send a test email to verify your settings</p>
                        </div>
                        <div class="flex space-x-3">
                            <input type="email" 
                                   id="test_email"
                                   placeholder="test@example.com"
                                   class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <button type="button" 
                                    onclick="sendTestEmail()"
                                    class="px-4 py-2 bg-gray-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Send Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
        <button type="submit" 
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Save SMTP Settings
        </button>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
    field.setAttribute('type', type);
}

function sendTestEmail() {
    const email = document.getElementById('test_email').value;
    if (!email) {
        alert('Please enter a test email address');
        return;
    }
    
    // Implementation would call API endpoint to send test email
    console.log('Sending test email to:', email);
    alert('Test email functionality would be implemented here');
}
</script>