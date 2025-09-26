<div class="space-y-6">
    <!-- Mail Driver Selection -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mail Driver</label>
        <select name="driver" id="driver" class="form-select rounded-md border-gray-300 w-full">
            <option value="smtp" {{ ($settings['driver'] ?? 'smtp') == 'smtp' ? 'selected' : '' }}>SMTP</option>
            <option value="mailgun" {{ ($settings['driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
            <option value="sendgrid" {{ ($settings['driver'] ?? '') == 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
            <option value="ses" {{ ($settings['driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
            <option value="postmark" {{ ($settings['driver'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</option>
            <option value="log" {{ ($settings['driver'] ?? '') == 'log' ? 'selected' : '' }}>Log (Testing)</option>
        </select>
    </div>

    <!-- SMTP Settings -->
    <div id="smtp-settings" class="{{ ($settings['driver'] ?? 'smtp') != 'smtp' ? 'hidden' : '' }}">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SMTP Configuration</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Host</label>
                <input type="text" name="smtp_host" value="{{ $settings['smtp_host'] ?? '' }}" 
                       class="form-input rounded-md border-gray-300 w-full"
                       placeholder="smtp.mailgun.org">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Port</label>
                <input type="number" name="smtp_port" value="{{ $settings['smtp_port'] ?? 587 }}" 
                       class="form-input rounded-md border-gray-300 w-full"
                       placeholder="587">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Username</label>
                <input type="text" name="smtp_username" value="{{ $settings['smtp_username'] ?? '' }}" 
                       class="form-input rounded-md border-gray-300 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP Password</label>
                <input type="password" name="smtp_password" 
                       placeholder="{{ !empty($settings['smtp_password']) ? 'Password is set' : 'Enter password' }}"
                       class="form-input rounded-md border-gray-300 w-full">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Encryption</label>
                <select name="smtp_encryption" class="form-select rounded-md border-gray-300 w-full">
                    <option value="">None</option>
                    <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                </select>
            </div>
        </div>
    </div>

    <!-- API Settings -->
    <div id="api-settings" class="{{ in_array($settings['driver'] ?? 'smtp', ['smtp', 'log']) ? 'hidden' : '' }}">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">API Configuration</h3>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                <input type="password" name="api_key" 
                       placeholder="{{ !empty($settings['api_key']) ? 'API key is set' : 'Enter API key' }}"
                       class="form-input rounded-md border-gray-300 w-full">
            </div>
            
            <div id="api-domain" class="{{ !in_array($settings['driver'] ?? '', ['mailgun', 'ses']) ? 'hidden' : '' }}">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <span id="domain-label">{{ ($settings['driver'] ?? '') == 'mailgun' ? 'Domain' : 'Region' }}</span>
                </label>
                <input type="text" name="api_domain" value="{{ $settings['api_domain'] ?? '' }}" 
                       class="form-input rounded-md border-gray-300 w-full"
                       placeholder="{{ ($settings['driver'] ?? '') == 'ses' ? 'us-east-1' : 'mg.yourdomain.com' }}">
            </div>
        </div>
    </div>

    <!-- Sender Information -->
    <div class="border-t pt-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Sender Information</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Name</label>
                <input type="text" name="from_name" value="{{ $settings['from_name'] ?? '' }}" 
                       class="form-input rounded-md border-gray-300 w-full" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Email</label>
                <input type="email" name="from_email" value="{{ $settings['from_email'] ?? '' }}" 
                       class="form-input rounded-md border-gray-300 w-full" required>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Reply-To Email (optional)</label>
                <input type="email" name="reply_to" value="{{ $settings['reply_to'] ?? '' }}" 
                       class="form-input rounded-md border-gray-300 w-full">
            </div>
        </div>
    </div>

    <!-- Email Features -->
    <div class="border-t pt-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Email Features</h3>
        
        <div class="space-y-4">
            <div class="flex items-center">
                <input type="hidden" name="track_opens" value="0">
                <input type="checkbox" name="track_opens" value="1" 
                       {{ ($settings['track_opens'] ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Track email opens
                </label>
            </div>
            
            <div class="flex items-center">
                <input type="hidden" name="track_clicks" value="0">
                <input type="checkbox" name="track_clicks" value="1" 
                       {{ ($settings['track_clicks'] ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Track link clicks
                </label>
            </div>
            
            <div class="flex items-center">
                <input type="hidden" name="auto_retry_failed" value="0">
                <input type="checkbox" name="auto_retry_failed" value="1" 
                       {{ ($settings['auto_retry_failed'] ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Automatically retry failed emails
                </label>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maximum Retry Attempts</label>
                <input type="number" name="max_retry_attempts" value="{{ $settings['max_retry_attempts'] ?? 3 }}" 
                       min="1" max="10"
                       class="form-input rounded-md border-gray-300 w-32">
            </div>
        </div>
    </div>

    <!-- Test Email -->
    <div class="border-t pt-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Test Configuration</h3>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Test Email Address</label>
            <input type="email" name="test_email" value="{{ auth()->user()->email }}" 
                   class="form-input rounded-md border-gray-300 w-full">
            <p class="text-sm text-gray-500 mt-1">A test email will be sent to this address when you click "Test Configuration"</p>
        </div>
    </div>
</div>

<script>
document.getElementById('driver').addEventListener('change', function() {
    const driver = this.value;
    const smtpSettings = document.getElementById('smtp-settings');
    const apiSettings = document.getElementById('api-settings');
    const apiDomain = document.getElementById('api-domain');
    const domainLabel = document.getElementById('domain-label');
    
    if (driver === 'smtp') {
        smtpSettings.classList.remove('hidden');
        apiSettings.classList.add('hidden');
    } else if (driver === 'log') {
        smtpSettings.classList.add('hidden');
        apiSettings.classList.add('hidden');
    } else {
        smtpSettings.classList.add('hidden');
        apiSettings.classList.remove('hidden');
        
        if (driver === 'mailgun') {
            apiDomain.classList.remove('hidden');
            domainLabel.textContent = 'Domain';
        } else if (driver === 'ses') {
            apiDomain.classList.remove('hidden');
            domainLabel.textContent = 'Region';
        } else {
            apiDomain.classList.add('hidden');
        }
    }
});
</script>