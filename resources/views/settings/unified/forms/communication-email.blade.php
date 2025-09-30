<flux:field.group>
    <!-- Mail Driver Selection -->
    <flux:field>
        <flux:label>Mail Driver</flux:label>
        <flux:select name="driver" id="driver">
            <flux:option value="smtp" {{ ($settings['driver'] ?? 'smtp') == 'smtp' ? 'selected' : '' }}>SMTP</flux:option>
            <flux:option value="smtp2go" {{ ($settings['driver'] ?? '') == 'smtp2go' ? 'selected' : '' }}>SMTP2GO</flux:option>
            <flux:option value="mailgun" {{ ($settings['driver'] ?? '') == 'mailgun' ? 'selected' : '' }}>Mailgun</flux:option>
            <flux:option value="sendgrid" {{ ($settings['driver'] ?? '') == 'sendgrid' ? 'selected' : '' }}>SendGrid</flux:option>
            <flux:option value="ses" {{ ($settings['driver'] ?? '') == 'ses' ? 'selected' : '' }}>Amazon SES</flux:option>
            <flux:option value="postmark" {{ ($settings['driver'] ?? '') == 'postmark' ? 'selected' : '' }}>Postmark</flux:option>
            <flux:option value="log" {{ ($settings['driver'] ?? '') == 'log' ? 'selected' : '' }}>Log (Testing)</flux:option>
        </flux:select>
    </flux:field>

    <!-- SMTP Settings -->
    <div id="smtp-settings" class="{{ ($settings['driver'] ?? 'smtp') != 'smtp' ? 'hidden' : '' }}">
        <flux:heading size="md" class="mb-4">SMTP Configuration</flux:heading>
        
        <flux:field.group>
            <flux:grid cols="2">
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
                    <flux:select name="smtp_encryption">
                        <flux:option value="">None</flux:option>
                        <flux:option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</flux:option>
                        <flux:option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</flux:option>
                    </flux:select>
                </flux:field>
            </flux:grid>
        </flux:field.group>
    </div>

    <!-- SMTP2GO Settings -->
    <div id="smtp2go-settings" class="{{ ($settings['driver'] ?? '') != 'smtp2go' ? 'hidden' : '' }}">
        <flux:heading size="md" class="mb-4">SMTP2GO Configuration</flux:heading>
        
        <flux:field.group>
            <flux:field>
                <flux:label>SMTP2GO API Key</flux:label>
                <flux:input type="password" name="smtp2go_api_key" placeholder="{{ !empty($settings['smtp2go_api_key']) ? 'API key is set' : 'Enter SMTP2GO API key' }}" />
                <flux:text size="xs" variant="muted" class="mt-1">You can get your API key from your SMTP2GO dashboard</flux:text>
            </flux:field>
            
            <flux:card variant="info">
                <flux:card.body>
                    <flux:heading size="sm">About SMTP2GO</flux:heading>
                    <flux:text size="sm" class="mt-2">SMTP2GO provides reliable email delivery with:</flux:text>
                    <ul class="list-disc list-inside mt-2 text-sm">
                        <li>High deliverability rates</li>
                        <li>Real-time analytics and tracking</li>
                        <li>Dedicated IPs available</li>
                        <li>24/7 support</li>
                    </ul>
                </flux:card.body>
            </flux:card>
        </flux:field.group>
    </div>

    <!-- API Settings -->
    <div id="api-settings" class="{{ in_array($settings['driver'] ?? 'smtp', ['smtp', 'smtp2go', 'log']) ? 'hidden' : '' }}">
        <flux:heading size="md" class="mb-4">API Configuration</flux:heading>
        
        <flux:field.group>
            <flux:field>
                <flux:label>API Key</flux:label>
                <flux:input type="password" name="api_key" placeholder="{{ !empty($settings['api_key']) ? 'API key is set' : 'Enter API key' }}" />
            </flux:field>
            
            <flux:field id="api-domain" class="{{ !in_array($settings['driver'] ?? '', ['mailgun', 'ses']) ? 'hidden' : '' }}">
                <flux:label>
                    <span id="domain-label">{{ ($settings['driver'] ?? '') == 'mailgun' ? 'Domain' : 'Region' }}</span>
                </flux:label>
                <flux:input type="text" name="api_domain" value="{{ $settings['api_domain'] ?? '' }}" placeholder="{{ ($settings['driver'] ?? '') == 'ses' ? 'us-east-1' : 'mg.yourdomain.com' }}" />
            </flux:field>
        </flux:field.group>
    </div>

    <!-- Sender Information -->
    <flux:separator class="my-6" />
    
    <flux:heading size="md" class="mb-4">Sender Information</flux:heading>
    
    <flux:field.group>
        <flux:grid cols="2">
            <flux:field>
                <flux:label required>From Name</flux:label>
                <flux:input type="text" name="from_name" value="{{ $settings['from_name'] ?? '' }}" required />
            </flux:field>
            
            <flux:field>
                <flux:label required>From Email</flux:label>
                <flux:input type="email" name="from_email" value="{{ $settings['from_email'] ?? '' }}" required />
            </flux:field>
        </flux:grid>
        
        <flux:field>
            <flux:label>Reply-To Email (optional)</flux:label>
            <flux:input type="email" name="reply_to" value="{{ $settings['reply_to'] ?? '' }}" />
        </flux:field>
    </flux:field.group>

    <!-- Email Features -->
    <flux:separator class="my-6" />
    
    <flux:heading size="md" class="mb-4">Email Features</flux:heading>
    
    <flux:field.group>
        <flux:checkbox.group>
            <flux:checkbox name="track_opens" value="1" {{ ($settings['track_opens'] ?? true) ? 'checked' : '' }}>
                Track email opens
            </flux:checkbox>
            
            <flux:checkbox name="track_clicks" value="1" {{ ($settings['track_clicks'] ?? true) ? 'checked' : '' }}>
                Track link clicks
            </flux:checkbox>
            
            <flux:checkbox name="auto_retry_failed" value="1" {{ ($settings['auto_retry_failed'] ?? true) ? 'checked' : '' }}>
                Automatically retry failed emails
            </flux:checkbox>
        </flux:checkbox.group>
        
        <flux:field>
            <flux:label>Maximum Retry Attempts</flux:label>
            <flux:input type="number" name="max_retry_attempts" value="{{ $settings['max_retry_attempts'] ?? 3 }}" min="1" max="10" />
        </flux:field>
    </flux:field.group>

    <!-- Test Email -->
    <flux:separator class="my-6" />
    
    <flux:heading size="md" class="mb-4">Test Configuration</flux:heading>
    
    <flux:field>
        <flux:label>Test Email Address</flux:label>
        <flux:input type="email" name="test_email" value="{{ auth()->user()->email }}" />
        <flux:text size="xs" variant="muted" class="mt-1">A test email will be sent to this address when you click "Test Configuration"</flux:text>
    </flux:field>
</flux:field.group>

@push('scripts')
<script>
document.getElementById('driver').addEventListener('change', function() {
    const driver = this.value;
    const smtpSettings = document.getElementById('smtp-settings');
    const smtp2goSettings = document.getElementById('smtp2go-settings');
    const apiSettings = document.getElementById('api-settings');
    const apiDomain = document.getElementById('api-domain');
    const domainLabel = document.getElementById('domain-label');
    
    // Hide all sections first
    smtpSettings.classList.add('hidden');
    smtp2goSettings.classList.add('hidden');
    apiSettings.classList.add('hidden');
    
    if (driver === 'smtp') {
        smtpSettings.classList.remove('hidden');
    } else if (driver === 'smtp2go') {
        smtp2goSettings.classList.remove('hidden');
    } else if (driver === 'log') {
        // All settings remain hidden for log driver
    } else {
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
@endpush