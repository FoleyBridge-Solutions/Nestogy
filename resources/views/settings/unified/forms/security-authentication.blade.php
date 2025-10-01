<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Two-Factor Authentication</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure 2FA requirements for user accounts</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Two-Factor Authentication</flux:label>
                <flux:switch name="two_factor_enabled" :checked="$settings['two_factor_enabled'] ?? true" />
                <flux:text size="sm" variant="muted">Allow users to enable 2FA on their accounts</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Require Two-Factor Authentication</flux:label>
                <flux:switch name="two_factor_required" :checked="$settings['two_factor_required'] ?? false" />
                <flux:text size="sm" variant="muted">Force all users to enable 2FA (recommended for high-security environments)</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Trusted Devices</flux:label>
                <flux:switch name="trusted_devices_enabled" :checked="$settings['trusted_devices_enabled'] ?? true" />
                <flux:text size="sm" variant="muted">Allow users to mark devices as trusted to skip 2FA</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Trusted Device Lifetime (days)</flux:label>
                <flux:input type="number" name="trusted_devices_lifetime" value="{{ $settings['trusted_devices_lifetime'] ?? 30 }}" min="1" max="365" />
                <flux:text size="sm" variant="muted">How long a device remains trusted before requiring 2FA again</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Password Requirements</flux:heading>
        <flux:text variant="muted" class="mb-6">Set password complexity and security rules</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Minimum Password Length</flux:label>
                <flux:input type="number" name="password_min_length" value="{{ $settings['password_min_length'] ?? 12 }}" min="8" max="128" />
                <flux:text size="sm" variant="muted">Recommended: at least 12 characters</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Require Uppercase Letters</flux:label>
                <flux:switch name="password_require_uppercase" :checked="$settings['password_require_uppercase'] ?? true" />
            </flux:field>

            <flux:field>
                <flux:label>Require Lowercase Letters</flux:label>
                <flux:switch name="password_require_lowercase" :checked="$settings['password_require_lowercase'] ?? true" />
            </flux:field>

            <flux:field>
                <flux:label>Require Numbers</flux:label>
                <flux:switch name="password_require_numbers" :checked="$settings['password_require_numbers'] ?? true" />
            </flux:field>

            <flux:field>
                <flux:label>Require Special Symbols</flux:label>
                <flux:switch name="password_require_symbols" :checked="$settings['password_require_symbols'] ?? true" />
            </flux:field>

            <flux:field>
                <flux:label>Password Expiration (days)</flux:label>
                <flux:input type="number" name="password_expires_days" value="{{ $settings['password_expires_days'] ?? '' }}" placeholder="Never" />
                <flux:text size="sm" variant="muted">Force users to change passwords after this many days (leave empty to disable)</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Password History Count</flux:label>
                <flux:input type="number" name="password_history_count" value="{{ $settings['password_history_count'] ?? 5 }}" min="0" max="24" />
                <flux:text size="sm" variant="muted">Prevent reusing this many previous passwords</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Session Management</flux:heading>
        <flux:text variant="muted" class="mb-6">Control session timeouts and concurrent access</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Session Lifetime (minutes)</flux:label>
                <flux:input type="number" name="session_lifetime" value="{{ $settings['session_lifetime'] ?? 120 }}" min="5" max="43200" />
                <flux:text size="sm" variant="muted">Maximum session duration before requiring re-authentication</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Idle Timeout (minutes)</flux:label>
                <flux:input type="number" name="session_idle_timeout" value="{{ $settings['session_idle_timeout'] ?? 30 }}" min="5" />
                <flux:text size="sm" variant="muted">Log out users after this period of inactivity</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Concurrent Sessions Allowed</flux:label>
                <flux:input type="number" name="concurrent_sessions" value="{{ $settings['concurrent_sessions'] ?? 3 }}" min="1" max="10" />
                <flux:text size="sm" variant="muted">Maximum number of simultaneous sessions per user</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Login Protection</flux:heading>
        <flux:text variant="muted" class="mb-6">Protect against brute force attacks</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Max Login Attempts</flux:label>
                <flux:input type="number" name="max_login_attempts" value="{{ $settings['max_login_attempts'] ?? 5 }}" min="3" max="10" />
                <flux:text size="sm" variant="muted">Number of failed attempts before account lockout</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Lockout Duration (minutes)</flux:label>
                <flux:input type="number" name="lockout_duration" value="{{ $settings['lockout_duration'] ?? 15 }}" min="1" max="1440" />
                <flux:text size="sm" variant="muted">How long to lock the account after max attempts</flux:text>
            </flux:field>
        </div>
    </flux:card>
</div>
