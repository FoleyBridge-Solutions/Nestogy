<?php

namespace App\Domains\Core\Services\Settings;

use App\Models\SettingsConfiguration;

class SecuritySettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_SECURITY;

    protected function getValidationRules(string $category): array
    {
        $rules = [
            'authentication' => [
                'two_factor_enabled' => 'boolean',
                'two_factor_required' => 'boolean',
                'password_min_length' => 'integer|min:8|max:128',
                'password_require_uppercase' => 'boolean',
                'password_require_lowercase' => 'boolean',
                'password_require_numbers' => 'boolean',
                'password_require_symbols' => 'boolean',
                'password_expires_days' => 'nullable|integer|min:0',
                'password_history_count' => 'integer|min:0|max:24',
                'session_lifetime' => 'integer|min:5|max:43200',
                'session_idle_timeout' => 'nullable|integer|min:5',
                'max_login_attempts' => 'integer|min:3|max:10',
                'lockout_duration' => 'integer|min:1|max:1440',
                'trusted_devices_enabled' => 'boolean',
                'trusted_devices_lifetime' => 'integer|min:1|max:365',
            ],
            'access' => [
                'ip_whitelist_enabled' => 'boolean',
                'ip_whitelist' => 'nullable|array',
                'ip_whitelist.*' => 'ip',
                'allowed_countries' => 'nullable|array',
                'allowed_countries.*' => 'string|size:2',
                'block_tor_vpn' => 'boolean',
                'api_rate_limit' => 'integer|min:10|max:10000',
                'concurrent_sessions' => 'integer|min:1|max:10',
            ],
            'audit' => [
                'audit_enabled' => 'boolean',
                'audit_user_actions' => 'boolean',
                'audit_api_requests' => 'boolean',
                'audit_settings_changes' => 'boolean',
                'audit_financial_changes' => 'boolean',
                'audit_retention_days' => 'integer|min:30|max:2555',
                'failed_login_alerts' => 'boolean',
                'failed_login_threshold' => 'integer|min:3|max:20',
                'suspicious_activity_alerts' => 'boolean',
            ],
        ];

        return $rules[$category] ?? [];
    }

    public function getDefaultSettings(string $category): array
    {
        switch ($category) {
            case 'authentication':
                return [
                    'two_factor_enabled' => true,
                    'two_factor_required' => false,
                    'password_min_length' => 12,
                    'password_require_uppercase' => true,
                    'password_require_lowercase' => true,
                    'password_require_numbers' => true,
                    'password_require_symbols' => true,
                    'password_expires_days' => null,
                    'password_history_count' => 5,
                    'session_lifetime' => 120,
                    'session_idle_timeout' => 30,
                    'max_login_attempts' => 5,
                    'lockout_duration' => 15,
                    'trusted_devices_enabled' => true,
                    'trusted_devices_lifetime' => 30,
                ];

            case 'access':
                return [
                    'ip_whitelist_enabled' => false,
                    'ip_whitelist' => [],
                    'allowed_countries' => [],
                    'block_tor_vpn' => false,
                    'api_rate_limit' => 1000,
                    'concurrent_sessions' => 3,
                ];

            case 'audit':
                return [
                    'audit_enabled' => true,
                    'audit_user_actions' => true,
                    'audit_api_requests' => true,
                    'audit_settings_changes' => true,
                    'audit_financial_changes' => true,
                    'audit_retention_days' => 365,
                    'failed_login_alerts' => true,
                    'failed_login_threshold' => 5,
                    'suspicious_activity_alerts' => true,
                ];

            default:
                return [];
        }
    }

    public function getCategoryMetadata(string $category): array
    {
        switch ($category) {
            case 'access':
                return [
                    'name' => 'Access Control',
                    'description' => 'Manage IP restrictions, rate limiting, and access rules',
                    'icon' => 'shield-exclamation',
                ];

            case 'authentication':
                return [
                    'name' => 'Authentication',
                    'description' => 'Configure password policies, 2FA, and session management',
                    'icon' => 'lock-closed',
                ];

            case 'compliance':
                return [
                    'name' => 'Compliance',
                    'description' => 'Compliance and regulatory settings',
                    'icon' => 'clipboard-document-check',
                ];

            case 'permissions':
                return [
                    'name' => 'Permissions',
                    'description' => 'Manage user permissions and access control',
                    'icon' => 'key',
                ];

            case 'roles':
                return [
                    'name' => 'Roles',
                    'description' => 'Manage user roles and role-based access',
                    'icon' => 'user-group',
                ];

            default:
                return [];
        }
    }
}
