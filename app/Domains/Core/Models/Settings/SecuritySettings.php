<?php

namespace App\Domains\Core\Models\Settings;

class SecuritySettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'security';
    }

    public function getAttributes(): array
    {
        return [
            'password_min_length',
            'password_require_special',
            'password_require_numbers',
            'password_require_uppercase',
            'password_require_lowercase',
            'password_require_number',
            'password_expiry_days',
            'password_history_count',
            'two_factor_enabled',
            'two_factor_methods',
            'remember_me_enabled',
            'email_verification_required',
            'api_authentication_enabled',
            'session_timeout_minutes',
            'session_lifetime',
            'idle_timeout',
            'force_single_session',
            'single_session_per_user',
            'logout_on_browser_close',
            'max_login_attempts',
            'login_lockout_duration',
            'lockout_duration_minutes',
            'ip_whitelist_enabled',
            'whitelisted_ips',
            'oauth_google_enabled',
            'oauth_google_client_id',
            'oauth_google_client_secret',
            'oauth_microsoft_enabled',
            'oauth_microsoft_client_id',
            'oauth_microsoft_client_secret',
            'saml_enabled',
            'saml_entity_id',
            'saml_sso_url',
            'saml_certificate',
            'audit_login_attempts',
            'audit_password_changes',
            'audit_permission_changes',
            'audit_data_access',
            'audit_log_retention_days',
            'audit_logging_enabled',
            'audit_retention_days',
            'alert_suspicious_activity',
            'alert_multiple_failed_logins',
            'security_alert_email',
            'allowed_ip_ranges',
            'blocked_ip_ranges',
            'geo_blocking_enabled',
            'allowed_countries',
            'sso_settings',
            'login_message',
            'login_key_required',
            'login_key_secret',
        ];
    }

    public function getPasswordMinLength(): int
    {
        return $this->get('password_min_length', 8);
    }

    public function setPasswordMinLength(int $length): self
    {
        $this->set('password_min_length', $length);
        return $this;
    }

    public function isPasswordSpecialCharRequired(): bool
    {
        return (bool) $this->get('password_require_special', true);
    }

    public function setPasswordSpecialCharRequired(bool $required): self
    {
        $this->set('password_require_special', $required);
        return $this;
    }

    public function isPasswordNumberRequired(): bool
    {
        return (bool) $this->get('password_require_numbers', true);
    }

    public function setPasswordNumberRequired(bool $required): self
    {
        $this->set('password_require_numbers', $required);
        return $this;
    }

    public function isPasswordUppercaseRequired(): bool
    {
        return (bool) $this->get('password_require_uppercase', true);
    }

    public function setPasswordUppercaseRequired(bool $required): self
    {
        $this->set('password_require_uppercase', $required);
        return $this;
    }

    public function getPasswordExpiryDays(): int
    {
        return $this->get('password_expiry_days', 90);
    }

    public function setPasswordExpiryDays(int $days): self
    {
        $this->set('password_expiry_days', $days);
        return $this;
    }

    public function getPasswordHistoryCount(): int
    {
        return $this->get('password_history_count', 5);
    }

    public function setPasswordHistoryCount(int $count): self
    {
        $this->set('password_history_count', $count);
        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return (bool) $this->get('two_factor_enabled', false);
    }

    public function setTwoFactorEnabled(bool $enabled): self
    {
        $this->set('two_factor_enabled', $enabled);
        return $this;
    }

    public function getTwoFactorMethods(): array
    {
        return $this->get('two_factor_methods', []);
    }

    public function setTwoFactorMethods(array $methods): self
    {
        $this->set('two_factor_methods', $methods);
        return $this;
    }

    public function getSessionTimeoutMinutes(): int
    {
        return $this->get('session_timeout_minutes', 480);
    }

    public function setSessionTimeoutMinutes(int $minutes): self
    {
        $this->set('session_timeout_minutes', $minutes);
        return $this;
    }

    public function getMaxLoginAttempts(): int
    {
        return $this->get('max_login_attempts', 5);
    }

    public function setMaxLoginAttempts(int $attempts): self
    {
        $this->set('max_login_attempts', $attempts);
        return $this;
    }

    public function getLockoutDurationMinutes(): int
    {
        return $this->get('lockout_duration_minutes', 15);
    }

    public function setLockoutDurationMinutes(int $minutes): self
    {
        $this->set('lockout_duration_minutes', $minutes);
        return $this;
    }

    public function isIpWhitelistEnabled(): bool
    {
        return (bool) $this->get('ip_whitelist_enabled', false);
    }

    public function setIpWhitelistEnabled(bool $enabled): self
    {
        $this->set('ip_whitelist_enabled', $enabled);
        return $this;
    }

    public function getWhitelistedIps(): array
    {
        return $this->get('whitelisted_ips', []);
    }

    public function setWhitelistedIps(array $ips): self
    {
        $this->set('whitelisted_ips', $ips);
        return $this;
    }

    public function hasStrongPasswordPolicy(): bool
    {
        return $this->getPasswordMinLength() >= 8
            && $this->isPasswordSpecialCharRequired()
            && $this->isPasswordNumberRequired()
            && $this->isPasswordUppercaseRequired()
            && $this->getPasswordExpiryDays() <= 90;
    }

    public function isTwoFactorProperlyConfigured(): bool
    {
        return $this->isTwoFactorEnabled()
            && ! empty($this->getTwoFactorMethods());
    }

    public function getAuditRetentionDays(): int
    {
        return $this->get('audit_retention_days', 365);
    }

    public function setAuditRetentionDays(int $days): self
    {
        $this->set('audit_retention_days', $days);
        return $this;
    }

    public function isAuditLoggingEnabled(): bool
    {
        return (bool) $this->get('audit_logging_enabled', true);
    }

    public function setAuditLoggingEnabled(bool $enabled): self
    {
        $this->set('audit_logging_enabled', $enabled);
        return $this;
    }
}
