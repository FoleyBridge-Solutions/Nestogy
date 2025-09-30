<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CompanyMailSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'driver',
        'is_active',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'smtp_timeout',
        'api_key',         // Generic API key field
        'api_secret',      // Generic API secret field
        'api_domain',      // Generic domain/region field
        'ses_key',
        'ses_secret',
        'ses_region',
        'mailgun_domain',
        'mailgun_secret',
        'mailgun_endpoint',
        'postmark_token',
        'sendgrid_api_key',
        'from_email',
        'from_name',
        'reply_to',        // Simplified reply-to
        'reply_to_email',
        'reply_to_name',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'track_opens',
        'track_clicks',
        'auto_retry_failed',
        'max_retry_attempts',
        'last_test_at',
        'last_test_successful',
        'last_test_error',
        'fallback_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'smtp_port' => 'integer',
        'smtp_timeout' => 'integer',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_per_hour' => 'integer',
        'rate_limit_per_day' => 'integer',
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
        'auto_retry_failed' => 'boolean',
        'max_retry_attempts' => 'integer',
        'last_test_at' => 'datetime',
        'last_test_successful' => 'boolean',
        'fallback_config' => 'array',
    ];

    // Encrypted attributes
    protected $encryptedAttributes = [
        'smtp_password',
        'api_key',
        'api_secret',
        'ses_secret',
        'mailgun_secret',
        'postmark_token',
        'sendgrid_api_key',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Encrypt sensitive data before saving
        static::saving(function ($model) {
            foreach ($model->encryptedAttributes as $attribute) {
                if ($model->isDirty($attribute) && ! empty($model->$attribute)) {
                    // Only encrypt if it's not already encrypted
                    try {
                        Crypt::decryptString($model->$attribute);
                        // If we can decrypt it, it's already encrypted
                    } catch (\Exception $e) {
                        // Not encrypted yet, encrypt it
                        $model->$attribute = Crypt::encryptString($model->$attribute);
                    }
                }
            }
        });
    }

    /**
     * Get the company that owns the mail settings.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get decrypted SMTP password.
     */
    public function getSmtpPasswordDecryptedAttribute(): ?string
    {
        if (empty($this->smtp_password)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->smtp_password);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted SES secret.
     */
    public function getSesSecretDecryptedAttribute(): ?string
    {
        if (empty($this->ses_secret)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->ses_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted Mailgun secret.
     */
    public function getMailgunSecretDecryptedAttribute(): ?string
    {
        if (empty($this->mailgun_secret)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->mailgun_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted Postmark token.
     */
    public function getPostmarkTokenDecryptedAttribute(): ?string
    {
        if (empty($this->postmark_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->postmark_token);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted SendGrid API key.
     */
    public function getSendgridApiKeyDecryptedAttribute(): ?string
    {
        if (empty($this->sendgrid_api_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->sendgrid_api_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted generic API key.
     */
    public function getApiKeyDecryptedAttribute(): ?string
    {
        if (empty($this->api_key)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get decrypted generic API secret.
     */
    public function getApiSecretDecryptedAttribute(): ?string
    {
        if (empty($this->api_secret)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get mail configuration array for the driver.
     */
    public function getMailConfig(): array
    {
        switch ($this->driver) {
            case 'smtp':
                return [
                    'transport' => 'smtp',
                    'host' => $this->smtp_host,
                    'port' => $this->smtp_port,
                    'encryption' => $this->smtp_encryption === 'none' ? null : $this->smtp_encryption,
                    'username' => $this->smtp_username,
                    'password' => $this->smtp_password_decrypted,
                    'timeout' => $this->smtp_timeout,
                ];

            case 'ses':
                return [
                    'transport' => 'ses',
                    'key' => $this->ses_key ?: $this->api_key,
                    'secret' => $this->ses_secret_decrypted ?: $this->getApiSecretDecryptedAttribute(),
                    'region' => $this->ses_region ?: $this->api_domain ?: 'us-east-1',
                ];

            case 'mailgun':
                return [
                    'transport' => 'mailgun',
                    'domain' => $this->mailgun_domain ?: $this->api_domain,
                    'secret' => $this->mailgun_secret_decrypted ?: $this->getApiKeyDecryptedAttribute(),
                    'endpoint' => $this->mailgun_endpoint,
                ];

            case 'postmark':
                return [
                    'transport' => 'postmark',
                    'token' => $this->postmark_token_decrypted ?: $this->getApiKeyDecryptedAttribute(),
                ];

            case 'sendgrid':
                return [
                    'transport' => 'sendgrid',
                    'api_key' => $this->sendgrid_api_key_decrypted ?: $this->getApiKeyDecryptedAttribute(),
                ];

            case 'log':
                return [
                    'transport' => 'log',
                    'channel' => 'mail',
                ];

            default:
                return [];
        }
    }

    /**
     * Test the mail configuration.
     */
    public function testConfiguration(?string $testEmail = null): bool
    {
        try {
            $config = $this->getMailConfig();

            // Create a temporary mailer with this configuration
            $mailer = app('mail.manager')->mailer('array');
            config(['mail.mailers.test_mailer' => $config]);

            $testMailer = app('mail.manager')->mailer('test_mailer');

            // Send test email
            $testMailer->raw('This is a test email from Nestogy to verify your email configuration.', function ($message) use ($testEmail) {
                $message->to($testEmail ?? $this->from_email)
                    ->from($this->from_email, $this->from_name)
                    ->subject('Nestogy Email Configuration Test');
            });

            $this->update([
                'last_test_at' => now(),
                'last_test_successful' => true,
                'last_test_error' => null,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->update([
                'last_test_at' => now(),
                'last_test_successful' => false,
                'last_test_error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
