<div class="space-y-6">
    <!-- Mail Driver Selection -->
    <flux:field>
        <flux:label>Mail Driver</flux:label>
        <select name="driver" id="driver" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
            <option value="smtp" {{ ($settings['driver'] ?? 'smtp') == 'smtp' ? 'selected' : '' }}>SMTP</option>
            <option value="smtp2go" {{ ($settings['driver'] ?? '') == 'smtp2go' ? 'selected' : '' }}>SMTP2GO</option>
            <option value="mailgun" {{ ($settings['driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</option>
            <option value="sendgrid" {{ ($settings['driver'] ?? '') == 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
            <option value="ses" {{ ($settings['driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</option>
            <option value="postmark" {{ ($settings['driver'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</option>
            <option value="log" {{ ($settings['driver'] ?? '') == 'log' ? 'selected' : '' }}>Log (Testing)</option>
        </select>
    </flux:field>

    <!-- SMTP Settings -->
    <div id="smtp-settings" class="{{ ($settings['driver'] ?? 'smtp') != 'smtp' ? 'hidden' : '' }}">
        <flux:heading size="md" class="mb-4">SMTP Configuration</flux:heading>
        
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>SMTP Host</flux:label>
                    <flux:input type="text" name="smtp_host" value="{{ $settings['smtp_host'] ?? '' }}" placeholder="smtp.mailgun.org" />
                </flux:field>
                
                <flux:field>
                    <flux:label>SMTP Port</flux:label>
                    <flux:input type="number" name="smtp_port" value="{{ $settings['smtp_port'] ?? 587 }}" placeholder="587" />
                </flux:field>
                
                <flux:field>
                    <flux:label>SMTP Username</flux:label>
                    <flux:input type="text" name="smtp_username" value="{{ $settings['smtp_username'] ?? '' }}" />
                </flux:field>
                
                <flux:field>
                    <flux:label>SMTP Password</flux:label>
                    <flux:input type="password" name="smtp_password" placeholder="{{ !empty($settings['smtp_password']) ? 'Password is set' : 'Enter password' }}" />
                </flux:field>
                
                <flux:field>
                    <flux:label>Encryption</flux:label>
                    <select name="smtp_encryption" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="none" {{ ($settings['smtp_encryption'] ?? 'tls') == 'none' ? 'selected' : '' }}>None</option>
                        <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </flux:field>
            </div>
        </div>
    </div>

    <!-- SMTP2GO Settings -->
    <div id="smtp2go-settings" class="{{ ($settings['driver'] ?? '') != 'smtp2go' ? 'hidden' : '' }}">
        <flux:heading size="md" class="mb-4">SMTP2GO Configuration</flux:heading>
        
        <div class="space-y-6">
            <flux:field>
                <flux:label>SMTP2GO API Key</flux:label>
                <flux:input type="password" id="smtp2go_api_key" name="api_key" value="{{ $settings['api_key'] ?? '' }}" placeholder="{{ !empty($settings['api_key']) ? 'API key is set' : 'Enter SMTP2GO API key' }}" />
                <flux:text size="xs" variant="muted" class="mt-1">You can get your API key from your SMTP2GO dashboard</flux:text>
            </flux:field>
            
            <flux:card>
                <flux:heading size="sm">About SMTP2GO</flux:heading>
                <flux:text size="sm" class="mt-2">SMTP2GO provides reliable email delivery with:</flux:text>
                <ul class="list-disc list-inside mt-2 text-sm">
                    <li>High deliverability rates</li>
                    <li>Real-time analytics and tracking</li>
                    <li>Dedicated IPs available</li>
                    <li>24/7 support</li>
                </ul>
            </flux:card>
        </div>
    </div>

    <!-- API Settings -->
    <div id="api-settings" class="{{ in_array($settings['driver'] ?? 'smtp', ['smtp', 'smtp2go', 'log']) ? 'hidden' : '' }}">
        <flux:heading size="md" class="mb-4">API Configuration</flux:heading>
        
        <div class="space-y-6">
            <flux:field>
                <flux:label>API Key</flux:label>
                <flux:input type="password" id="api_key" name="api_key" value="{{ $settings['api_key'] ?? '' }}" placeholder="{{ !empty($settings['api_key']) ? 'API key is set' : 'Enter API key' }}" />
            </flux:field>
            
            <flux:field id="api-domain" class="{{ !in_array($settings['driver'] ?? '', ['mailgun', 'ses']) ? 'hidden' : '' }}">
                <flux:label>
                    <span id="domain-label">{{ ($settings['driver'] ?? '') == 'mailgun' ? 'Domain' : 'Region' }}</span>
                </flux:label>
                <flux:input type="text" name="api_domain" value="{{ $settings['api_domain'] ?? '' }}" placeholder="{{ ($settings['driver'] ?? '') == 'ses' ? 'us-east-1' : 'mg.yourdomain.com' }}" />
            </flux:field>
        </div>
    </div>

    <!-- Sender Information -->
    <flux:separator class="my-6" />
    
    <flux:heading size="md" class="mb-4">Sender Information</flux:heading>
    
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:field>
                <flux:label required>From Name</flux:label>
                <flux:input type="text" name="from_name" value="{{ $settings['from_name'] ?? '' }}" required />
            </flux:field>
            
            <flux:field>
                <flux:label required>From Email</flux:label>
                <flux:input type="email" name="from_email" value="{{ $settings['from_email'] ?? '' }}" required />
            </flux:field>
        </div>
        
        <flux:field>
            <flux:label>Reply-To Email (optional)</flux:label>
            <flux:input type="email" name="reply_to" value="{{ $settings['reply_to'] ?? '' }}" />
        </flux:field>
    </div>

    <!-- Email Features -->
    <flux:separator class="my-6" />
    
    <flux:heading size="md" class="mb-4">Email Features</flux:heading>
    
    <div class="space-y-6">
        <div class="space-y-3">
            <label class="flex items-center gap-2">
                <input type="hidden" name="track_opens" value="0">
                <input type="checkbox" name="track_opens" value="1" {{ ($settings['track_opens'] ?? true) ? 'checked' : '' }} class="rounded border-zinc-300 dark:border-zinc-600">
                <span>Track email opens</span>
            </label>
            
            <label class="flex items-center gap-2">
                <input type="hidden" name="track_clicks" value="0">
                <input type="checkbox" name="track_clicks" value="1" {{ ($settings['track_clicks'] ?? true) ? 'checked' : '' }} class="rounded border-zinc-300 dark:border-zinc-600">
                <span>Track link clicks</span>
            </label>
            
            <label class="flex items-center gap-2">
                <input type="hidden" name="auto_retry_failed" value="0">
                <input type="checkbox" name="auto_retry_failed" value="1" {{ ($settings['auto_retry_failed'] ?? true) ? 'checked' : '' }} class="rounded border-zinc-300 dark:border-zinc-600">
                <span>Automatically retry failed emails</span>
            </label>
        </div>
        
        <flux:field>
            <flux:label>Maximum Retry Attempts</flux:label>
            <flux:input type="number" name="max_retry_attempts" value="{{ $settings['max_retry_attempts'] ?? 3 }}" min="1" max="10" />
        </flux:field>
    </div>

    <!-- Test Email -->
    <flux:separator class="my-6" />
    
    <flux:heading size="md" class="mb-4">Test Configuration</flux:heading>
    
    <flux:field>
        <flux:label>Test Email Address</flux:label>
        <flux:input type="email" name="test_email" value="{{ auth()->user()->email }}" />
        <flux:text size="xs" variant="muted" class="mt-1">A test email will be sent to this address when you click "Test Configuration"</flux:text>
    </flux:field>
</div>

@push('scripts')
<script>
function toggleDriverSettings() {
    const driver = document.getElementById('driver').value;
    const smtpSettings = document.getElementById('smtp-settings');
    const smtp2goSettings = document.getElementById('smtp2go-settings');
    const apiSettings = document.getElementById('api-settings');
    const apiDomain = document.getElementById('api-domain');
    const domainLabel = document.getElementById('domain-label');
    
    // Disable all inputs in hidden sections first
    const disableInputs = (section) => {
        section.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = true;
        });
    };
    
    const enableInputs = (section) => {
        section.querySelectorAll('input, select, textarea').forEach(input => {
            input.disabled = false;
        });
    };
    
    // Hide all sections and disable inputs
    smtpSettings.classList.add('hidden');
    smtp2goSettings.classList.add('hidden');
    apiSettings.classList.add('hidden');
    disableInputs(smtpSettings);
    disableInputs(smtp2goSettings);
    disableInputs(apiSettings);
    
    if (driver === 'smtp') {
        smtpSettings.classList.remove('hidden');
        enableInputs(smtpSettings);
    } else if (driver === 'smtp2go') {
        smtp2goSettings.classList.remove('hidden');
        enableInputs(smtp2goSettings);
    } else if (driver === 'log') {
        // All settings remain hidden for log driver
    } else {
        apiSettings.classList.remove('hidden');
        enableInputs(apiSettings);
        
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
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleDriverSettings();
});

// Update on driver change
document.getElementById('driver').addEventListener('change', toggleDriverSettings);
</script>
@endpush