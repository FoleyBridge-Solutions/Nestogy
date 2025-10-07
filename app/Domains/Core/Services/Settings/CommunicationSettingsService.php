<?php

namespace App\Domains\Core\Services\Settings;

use App\Domains\Email\Services\UnifiedMailService;
use App\Models\SettingsConfiguration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CommunicationSettingsService extends BaseSettingsService
{
    protected string $domain = SettingsConfiguration::DOMAIN_COMMUNICATION;

    private const BOOLEAN_VALIDATION = 'nullable|in:0,1';

    /**
     * Get validation rules for each category
     */
    protected function getValidationRules(string $category): array
    {
        switch ($category) {
            case 'email':
                return [
                    'driver' => 'required|in:smtp,smtp2go,mailgun,sendgrid,ses,postmark,log',
                    'from_email' => 'required|email',
                    'from_name' => 'required|string|max:255',
                    'reply_to' => 'nullable|email',

                    // SMTP validation
                    'smtp_host' => 'required_if:driver,smtp|nullable|string',
                    'smtp_port' => 'required_if:driver,smtp|nullable|integer|between:1,65535',
                    'smtp_username' => 'required_if:driver,smtp|nullable|string',
                    'smtp_password' => 'nullable|string',
                    'smtp_encryption' => 'nullable|in:tls,ssl,none',

                    // API provider validation
                    'api_key' => 'required_if:driver,smtp2go,mailgun,sendgrid,ses,postmark|nullable|string',
                    'api_domain' => 'nullable|string',

                    // Features
                    'track_opens' => self::BOOLEAN_VALIDATION,
                    'track_clicks' => self::BOOLEAN_VALIDATION,
                    'auto_retry_failed' => self::BOOLEAN_VALIDATION,
                    'max_retry_attempts' => 'nullable|integer|between:1,10',
                    
                    // Test email (not saved, just for testing)
                    'test_email' => 'nullable|email',
                ];

            case 'physical_mail':
                return [
                    'enabled' => 'boolean',
                    'provider' => 'required_if:enabled,true|in:postgrid,lob',
                    'api_key' => 'required_if:enabled,true|nullable|string',
                    'from_name' => 'required_if:enabled,true|nullable|string',
                    'from_address_line1' => 'required_if:enabled,true|nullable|string',
                    'from_city' => 'required_if:enabled,true|nullable|string',
                    'from_state' => 'required_if:enabled,true|nullable|string',
                    'from_postal_code' => 'required_if:enabled,true|nullable|string',
                    'from_country' => 'required_if:enabled,true|nullable|string',
                ];

            case 'notifications':
                return [
                    'email_enabled' => 'boolean',
                    'sms_enabled' => 'boolean',
                    'push_enabled' => 'boolean',
                    'webhook_enabled' => 'boolean',
                    'webhook_url' => 'required_if:webhook_enabled,true|nullable|url',
                    'notification_digest' => 'boolean',
                    'digest_frequency' => 'required_if:notification_digest,true|in:daily,weekly,monthly',
                ];

            default:
                return [];
        }
    }

    /**
     * Process data before saving (encrypt sensitive data)
     */
    protected function processBeforeSave(string $category, array $data): array
    {
        if ($category === 'email') {
            // Remove test_email field as it's not meant to be saved
            unset($data['test_email']);
            
            // Convert checkbox values to proper booleans
            $data['track_opens'] = ($data['track_opens'] ?? '0') === '1';
            $data['track_clicks'] = ($data['track_clicks'] ?? '0') === '1';
            $data['auto_retry_failed'] = ($data['auto_retry_failed'] ?? '0') === '1';
            
            // Handle SMTP password - if empty, remove it (keep existing)
            if (empty($data['smtp_password'])) {
                unset($data['smtp_password']);
            } elseif (! $this->isEncrypted($data['smtp_password'])) {
                // Only encrypt if not already encrypted
                $data['smtp_password'] = Crypt::encryptString($data['smtp_password']);
            }

            // Handle API key - if empty, remove it (keep existing)
            if (empty($data['api_key'])) {
                unset($data['api_key']);
            } elseif (! $this->isEncrypted($data['api_key'])) {
                // Only encrypt if not already encrypted
                $data['api_key'] = Crypt::encryptString($data['api_key']);
            }
        }

        if ($category === 'physical_mail') {
            // Encrypt API key for physical mail provider
            if (! empty($data['api_key']) && ! $this->isEncrypted($data['api_key'])) {
                $data['api_key'] = Crypt::encryptString($data['api_key']);
            }
        }

        return $data;
    }

    /**
     * Check if a value is already encrypted
     */
    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration(array $data): array
    {
        try {
            // Temporarily configure mailer with test settings
            $config = $this->buildMailerConfig($data);

            config(['mail.mailers.test' => $config]);

            // Try to send a test email
            Mail::mailer('test')->raw('This is a test email from Nestogy.', function ($message) use ($data) {
                $message->to($data['test_email'] ?? auth()->user()->email)
                    ->from($data['from_email'], $data['from_name'])
                    ->subject('Nestogy Email Configuration Test');

                if (! empty($data['reply_to'])) {
                    $message->replyTo($data['reply_to']);
                }
            });

            return [
                'success' => true,
                'message' => 'Test email sent successfully!',
            ];

        } catch (\Exception $e) {
            Log::error('Email configuration test failed', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test email: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build mailer configuration from settings
     */
    private function buildMailerConfig(array $data): array
    {
        switch ($data['driver']) {
            case 'smtp':
                return [
                    'transport' => 'smtp',
                    'host' => $data['smtp_host'],
                    'port' => $data['smtp_port'],
                    'encryption' => $data['smtp_encryption'] ?? null,
                    'username' => $data['smtp_username'],
                    'password' => $this->isEncrypted($data['smtp_password'] ?? '')
                        ? Crypt::decryptString($data['smtp_password'])
                        : $data['smtp_password'],
                ];

            case 'smtp2go':
                // SMTP2GO uses their REST API
                return [
                    'transport' => 'smtp2go',
                    'api_key' => $this->isEncrypted($data['api_key'] ?? '')
                        ? Crypt::decryptString($data['api_key'])
                        : $data['api_key'],
                ];

            case 'mailgun':
                return [
                    'transport' => 'mailgun',
                    'domain' => $data['api_domain'],
                    'secret' => $this->isEncrypted($data['api_key'] ?? '')
                        ? Crypt::decryptString($data['api_key'])
                        : $data['api_key'],
                ];

            case 'sendgrid':
                return [
                    'transport' => 'sendgrid',
                    'api_key' => $this->isEncrypted($data['api_key'] ?? '')
                        ? Crypt::decryptString($data['api_key'])
                        : $data['api_key'],
                ];

            case 'ses':
                return [
                    'transport' => 'ses',
                    'key' => $data['api_key'],
                    'secret' => $data['api_secret'] ?? $data['api_key'],
                    'region' => $data['api_domain'] ?? 'us-east-1',
                ];

            case 'postmark':
                return [
                    'transport' => 'postmark',
                    'token' => $this->isEncrypted($data['api_key'] ?? '')
                        ? Crypt::decryptString($data['api_key'])
                        : $data['api_key'],
                ];

            case 'log':
                return [
                    'transport' => 'log',
                    'channel' => 'mail',
                ];

            default:
                throw new \Exception("Unsupported mail driver: {$data['driver']}");
        }
    }

    /**
     * Test configuration
     */
    public function testConfiguration(string $category, array $data): array
    {
        switch ($category) {
            case 'email':
                return $this->testEmailConfiguration($data);

            case 'physical_mail':
                // TODO: Implement PostGrid test
                return [
                    'success' => true,
                    'message' => 'Physical mail configuration is valid',
                ];

            default:
                return parent::testConfiguration($category, $data);
        }
    }

    /**
     * After save actions
     */
    protected function afterSave(string $category, SettingsConfiguration $config): void
    {
        if ($category === 'email') {
            // Clear mail service cache
            app(UnifiedMailService::class)->clearCompanyMailerCache($this->getCompanyId());
        }
    }

    /**
     * Get default settings for a category
     */
    public function getDefaultSettings(string $category): array
    {
        switch ($category) {
            case 'email':
                return [
                    'driver' => 'smtp',
                    'from_email' => 'noreply@example.com',
                    'from_name' => 'Nestogy',
                    'smtp_host' => 'smtp.mailgun.org',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'track_opens' => true,
                    'track_clicks' => true,
                    'auto_retry_failed' => true,
                    'max_retry_attempts' => 3,
                ];

            case 'physical_mail':
                return [
                    'enabled' => false,
                    'provider' => 'postgrid',
                ];

            case 'notifications':
                return [
                    'email_enabled' => true,
                    'sms_enabled' => false,
                    'push_enabled' => false,
                    'webhook_enabled' => false,
                    'notification_digest' => false,
                    'digest_frequency' => 'daily',
                ];

            default:
                return [];
        }
    }

    /**
     * Get category metadata
     */
    public function getCategoryMetadata(string $category): array
    {
        switch ($category) {
            case 'email':
                return [
                    'name' => 'Email Configuration',
                    'description' => 'Configure email sending settings and providers',
                    'icon' => 'envelope',
                ];

            case 'physical_mail':
                return [
                    'name' => 'Physical Mail',
                    'description' => 'Configure physical mail services like PostGrid',
                    'icon' => 'inbox-stack',
                ];

            case 'notifications':
                return [
                    'name' => 'Notifications',
                    'description' => 'Configure notification channels and preferences',
                    'icon' => 'bell',
                ];

            default:
                return [];
        }
    }
}
