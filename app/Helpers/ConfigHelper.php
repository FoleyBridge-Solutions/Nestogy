<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class ConfigHelper
{
    /**
     * Get Nestogy configuration value
     */
    public static function get(string $key, $default = null)
    {
        return Config::get("nestogy.{$key}", $default);
    }

    /**
     * Get integration configuration value
     */
    public static function integration(string $key, $default = null)
    {
        return Config::get("integrations.{$key}", $default);
    }

    /**
     * Get upload configuration value
     */
    public static function upload(string $key, $default = null)
    {
        return Config::get("uploads.{$key}", $default);
    }

    /**
     * Get security configuration value
     */
    public static function security(string $key, $default = null)
    {
        return Config::get("security.{$key}", $default);
    }

    /**
     * Get notification configuration value
     */
    public static function notification(string $key, $default = null)
    {
        return Config::get("notifications.{$key}", $default);
    }

    /**
     * Check if a module is enabled
     */
    public static function isModuleEnabled(string $module): bool
    {
        return (bool) Config::get("nestogy.modules.{$module}", false);
    }

    /**
     * Check if a feature is enabled
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return (bool) Config::get("nestogy.features.{$feature}", false);
    }

    /**
     * Check if an integration is enabled
     */
    public static function isIntegrationEnabled(string $integration): bool
    {
        return (bool) Config::get("integrations.{$integration}.enabled", false);
    }

    /**
     * Get upload path for a specific type
     */
    public static function getUploadPath(string $type): string
    {
        $path = Config::get("uploads.paths.{$type}", $type);

        return storage_path("app/{$path}");
    }

    /**
     * Get relative upload path for a specific type
     */
    public static function getRelativeUploadPath(string $type): string
    {
        return Config::get("uploads.paths.{$type}", $type);
    }

    /**
     * Get allowed file extensions for a type
     */
    public static function getAllowedFileTypes(?string $type = null): array
    {
        if ($type) {
            return Config::get("uploads.allowed_types.{$type}", []);
        }

        $allTypes = [];
        $categories = Config::get('uploads.allowed_types', []);

        foreach ($categories as $types) {
            $allTypes = array_merge($allTypes, $types);
        }

        return array_unique($allTypes);
    }

    /**
     * Get maximum file size in bytes
     */
    public static function getMaxFileSize(): int
    {
        $sizeInKb = Config::get('uploads.max_size', 10240);

        return $sizeInKb * 1024; // Convert to bytes
    }

    /**
     * Get maximum file size formatted for display
     */
    public static function getMaxFileSizeFormatted(): string
    {
        $sizeInMb = Config::get('uploads.max_size_mb', 10);

        return "{$sizeInMb}MB";
    }

    /**
     * Get password policy
     */
    public static function getPasswordPolicy(): array
    {
        return Config::get('security.password', [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false,
        ]);
    }

    /**
     * Get session timeout in seconds
     */
    public static function getSessionTimeout(): int
    {
        $minutes = Config::get('security.session.timeout', 480);

        return $minutes * 60; // Convert to seconds
    }

    /**
     * Get notification channels
     */
    public static function getEnabledNotificationChannels(): array
    {
        $channels = Config::get('notifications.channels', []);

        return array_keys(array_filter($channels));
    }

    /**
     * Get invoice number format
     */
    public static function getInvoiceNumberFormat(): string
    {
        return Config::get('nestogy.invoices.number_format', 'INV-{YEAR}-{NUMBER}');
    }

    /**
     * Format invoice number
     */
    public static function formatInvoiceNumber(int $number, ?int $year = null): string
    {
        $format = self::getInvoiceNumberFormat();
        $year = $year ?: date('Y');

        return str_replace(
            ['{YEAR}', '{NUMBER}', '{MONTH}', '{DAY}'],
            [$year, str_pad($number, 4, '0', STR_PAD_LEFT), date('m'), date('d')],
            $format
        );
    }

    /**
     * Get default currency
     */
    public static function getDefaultCurrency(): string
    {
        return Config::get('nestogy.company.default_currency', 'USD');
    }

    /**
     * Get default timezone
     */
    public static function getDefaultTimezone(): string
    {
        return Config::get('nestogy.company.default_timezone', 'UTC');
    }

    /**
     * Get date format
     */
    public static function getDateFormat(): string
    {
        return Config::get('nestogy.company.date_format', 'Y-m-d');
    }

    /**
     * Get time format
     */
    public static function getTimeFormat(): string
    {
        return Config::get('nestogy.company.time_format', 'H:i:s');
    }

    /**
     * Get datetime format
     */
    public static function getDateTimeFormat(): string
    {
        return self::getDateFormat().' '.self::getTimeFormat();
    }

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool
    {
        return (bool) Config::get('nestogy.maintenance.enabled', false);
    }

    /**
     * Get maintenance message
     */
    public static function getMaintenanceMessage(): string
    {
        return Config::get('nestogy.maintenance.message', 'System is under maintenance. Please check back later.');
    }

    /**
     * Get API rate limit for a specific service
     */
    public static function getApiRateLimit(string $service = 'default'): int
    {
        return Config::get("integrations.rate_limits.{$service}", 60);
    }

    /**
     * Get webhook configuration
     */
    public static function getWebhookConfig(): array
    {
        return Config::get('integrations.webhooks', [
            'timeout' => 30,
            'retry_times' => 3,
            'retry_delay' => 10,
            'verify_ssl' => true,
        ]);
    }

    /**
     * Check if two-factor authentication is enabled
     */
    public static function is2FAEnabled(): bool
    {
        return (bool) Config::get('security.two_factor.enabled', true);
    }

    /**
     * Check if two-factor authentication is enforced
     */
    public static function is2FAEnforced(): bool
    {
        return (bool) Config::get('security.two_factor.enforced', false);
    }

    /**
     * Get available 2FA methods
     */
    public static function get2FAMethods(): array
    {
        $methods = Config::get('security.two_factor.methods', []);

        return array_keys(array_filter($methods));
    }

    /**
     * Get CORS configuration
     */
    public static function getCorsConfig(): array
    {
        return Config::get('security.cors', [
            'enabled' => true,
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        ]);
    }

    /**
     * Get audit log retention days
     */
    public static function getAuditRetentionDays(): int
    {
        return Config::get('security.audit.retention_days', 365);
    }

    /**
     * Check if a specific audit type is enabled
     */
    public static function isAuditEnabled(?string $type = null): bool
    {
        if ($type === null) {
            return (bool) Config::get('security.audit.enabled', true);
        }

        return (bool) Config::get("security.audit.log_{$type}", true);
    }

    /**
     * Get emergency notification recipients
     */
    public static function getEmergencyRecipients(): array
    {
        $recipients = Config::get('notifications.emergency.recipients', []);

        return is_array($recipients) ? $recipients : [];
    }

    /**
     * Get ticket configuration
     */
    public static function getTicketConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return Config::get('nestogy.tickets', []);
        }

        return Config::get("nestogy.tickets.{$key}", $default);
    }

    /**
     * Get project configuration
     */
    public static function getProjectConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return Config::get('nestogy.projects', []);
        }

        return Config::get("nestogy.projects.{$key}", $default);
    }

    /**
     * Get asset configuration
     */
    public static function getAssetConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return Config::get('nestogy.assets', []);
        }

        return Config::get("nestogy.assets.{$key}", $default);
    }
}
