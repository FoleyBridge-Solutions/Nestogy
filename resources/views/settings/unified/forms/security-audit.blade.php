<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Audit Logging</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure what actions are logged for security auditing</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Enable Audit Logging</flux:label>
                <flux:switch name="audit_enabled" :checked="$settings['audit_enabled'] ?? true" />
                <flux:text size="sm" variant="muted">Master switch for all audit logging functionality</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Log User Actions</flux:label>
                <flux:switch name="audit_user_actions" :checked="$settings['audit_user_actions'] ?? true" />
                <flux:text size="sm" variant="muted">Track user logins, logouts, and profile changes</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Log API Requests</flux:label>
                <flux:switch name="audit_api_requests" :checked="$settings['audit_api_requests'] ?? true" />
                <flux:text size="sm" variant="muted">Log all API requests and responses</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Log Settings Changes</flux:label>
                <flux:switch name="audit_settings_changes" :checked="$settings['audit_settings_changes'] ?? true" />
                <flux:text size="sm" variant="muted">Track all configuration and settings modifications</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Log Financial Changes</flux:label>
                <flux:switch name="audit_financial_changes" :checked="$settings['audit_financial_changes'] ?? true" />
                <flux:text size="sm" variant="muted">Track invoice, payment, and billing changes</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Audit Log Retention (days)</flux:label>
                <flux:input type="number" name="audit_retention_days" value="{{ $settings['audit_retention_days'] ?? 365 }}" min="30" max="2555" />
                <flux:text size="sm" variant="muted">How long to keep audit logs before automatic deletion (minimum 30 days, maximum 7 years)</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Security Alerts</flux:heading>
        <flux:text variant="muted" class="mb-6">Configure automated security alerts</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Failed Login Alerts</flux:label>
                <flux:switch name="failed_login_alerts" :checked="$settings['failed_login_alerts'] ?? true" />
                <flux:text size="sm" variant="muted">Send alerts when failed login attempts exceed threshold</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Failed Login Threshold</flux:label>
                <flux:input type="number" name="failed_login_threshold" value="{{ $settings['failed_login_threshold'] ?? 5 }}" min="3" max="20" />
                <flux:text size="sm" variant="muted">Number of failed attempts before triggering an alert</flux:text>
            </flux:field>

            <flux:field>
                <flux:label>Suspicious Activity Alerts</flux:label>
                <flux:switch name="suspicious_activity_alerts" :checked="$settings['suspicious_activity_alerts'] ?? true" />
                <flux:text size="sm" variant="muted">Alert on suspicious patterns like unusual access locations or times</flux:text>
            </flux:field>
        </div>
    </flux:card>
</div>
