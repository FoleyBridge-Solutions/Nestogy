<?php

namespace App\Domains\Core\Services\Settings;

use App\Domains\Core\Models\SettingsConfiguration;

class SecuritySettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_SECURITY;

    protected function getValidationRules(string $category): array
    {
        $rules = [
            'authentication' => [
                'two_factor_enabled' => 'nullable|in:on,true,false,1,0',
                'two_factor_required' => 'nullable|in:on,true,false,1,0',
                'password_min_length' => 'integer|min:8|max:128',
                'password_require_uppercase' => 'nullable|in:on,true,false,1,0',
                'password_require_lowercase' => 'nullable|in:on,true,false,1,0',
                'password_require_numbers' => 'nullable|in:on,true,false,1,0',
                'password_require_symbols' => 'nullable|in:on,true,false,1,0',
                'password_expires_days' => 'nullable|integer|min:0',
                'password_history_count' => 'integer|min:0|max:24',
                'session_lifetime' => 'integer|min:5|max:43200',
                'session_idle_timeout' => 'nullable|integer|min:5',
                'max_login_attempts' => 'integer|min:3|max:10',
                'lockout_duration' => 'integer|min:1|max:1440',
                'trusted_devices_enabled' => 'nullable|in:on,true,false,1,0',
                'trusted_devices_lifetime' => 'integer|min:1|max:365',
            ],
            'access' => [
                'ip_whitelist_enabled' => 'nullable|in:on,true,false,1,0',
                'ip_whitelist' => 'nullable|string',
                'allowed_countries' => 'nullable|string',
                'block_tor_vpn' => 'nullable|in:on,true,false,1,0',
                'api_rate_limit' => 'integer|min:10|max:10000',
                'concurrent_sessions' => 'integer|min:1|max:10',
            ],
            'audit' => [
                'audit_enabled' => 'nullable|in:on,true,false,1,0',
                'audit_user_actions' => 'nullable|in:on,true,false,1,0',
                'audit_api_requests' => 'nullable|in:on,true,false,1,0',
                'audit_settings_changes' => 'nullable|in:on,true,false,1,0',
                'audit_financial_changes' => 'nullable|in:on,true,false,1,0',
                'audit_retention_days' => 'integer|min:30|max:2555',
                'failed_login_alerts' => 'nullable|in:on,true,false,1,0',
                'failed_login_threshold' => 'integer|min:3|max:20',
                'suspicious_activity_alerts' => 'nullable|in:on,true,false,1,0',
            ],
        ];

        return $rules[$category] ?? [];
    }

    public function getDefaultSettings(string $category): array
    {
        $defaults = [];

        switch ($category) {
            case 'authentication':
                $defaults = [
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
                break;

            case 'access':
                $defaults = [
                    'ip_whitelist_enabled' => false,
                    'ip_whitelist' => [],
                    'allowed_countries' => [],
                    'block_tor_vpn' => false,
                    'api_rate_limit' => 1000,
                    'concurrent_sessions' => 3,
                ];
                break;

            case 'audit':
                $defaults = [
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
                break;

            default:
                break;
        }

        return $defaults;
    }

    protected function processBeforeSave(string $category, array $data): array
    {
        // Convert switch values to proper booleans
        $booleanFields = [
            'two_factor_enabled',
            'two_factor_required',
            'password_require_uppercase',
            'password_require_lowercase',
            'password_require_numbers',
            'password_require_symbols',
            'trusted_devices_enabled',
            'ip_whitelist_enabled',
            'block_tor_vpn',
            'audit_enabled',
            'audit_user_actions',
            'audit_api_requests',
            'audit_settings_changes',
            'audit_financial_changes',
            'failed_login_alerts',
            'suspicious_activity_alerts',
        ];

        foreach ($booleanFields as $field) {
            if (array_key_exists($field, $data)) {
                // Convert "on", "1", "true" to true (boolean), everything else to false
                $value = $data[$field];
                $data[$field] = in_array($value, ['on', '1', 'true', true, 1], true);
            } else {
                // If field is missing (checkbox unchecked), set to false
                $data[$field] = false;
            }
        }

        // Convert newline-separated strings to arrays for IP whitelist
        if (isset($data['ip_whitelist']) && is_string($data['ip_whitelist'])) {
            $data['ip_whitelist'] = array_filter(
                array_map('trim', explode("\n", $data['ip_whitelist']))
            );
        }

        // Convert newline-separated strings to arrays for allowed countries
        if (isset($data['allowed_countries']) && is_string($data['allowed_countries'])) {
            $data['allowed_countries'] = array_filter(
                array_map('trim', array_map('strtoupper', explode("\n", $data['allowed_countries'])))
            );
        }

        return $data;
    }

    public function getCategoryMetadata(string $category): array
    {
        $metadata = [
            'access' => [
                'name' => 'Access Control',
                'description' => 'Manage IP restrictions, rate limiting, and access rules',
                'icon' => 'shield-exclamation',
            ],
            'authentication' => [
                'name' => 'Authentication',
                'description' => 'Configure password policies, 2FA, and session management',
                'icon' => 'lock-closed',
            ],
            'audit' => [
                'name' => 'Audit & Logging',
                'description' => 'Configure audit logging, retention policies, and security alerts',
                'icon' => 'document-text',
            ],
            'compliance' => [
                'name' => 'Compliance',
                'description' => 'Compliance and regulatory settings',
                'icon' => 'clipboard-document-check',
            ],
            'permissions' => [
                'name' => 'Permissions',
                'description' => 'Manage user permissions and access control',
                'icon' => 'key',
            ],
            'roles' => [
                'name' => 'Roles',
                'description' => 'Manage user roles and role-based access',
                'icon' => 'user-group',
            ],
        ];

        return $metadata[$category] ?? [];
    }
}
