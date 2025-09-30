<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Exception;

class ConfigurationValidationService
{
    /**
     * Required configuration keys and their validation rules
     */
    protected array $requiredConfigs = [
        // Database
        'database.connections.mysql.host' => 'string',
        'database.connections.mysql.database' => 'string',
        'database.connections.mysql.username' => 'string',
        
        // Application
        'app.name' => 'string',
        'app.env' => 'string',
        'app.url' => 'url',
        'app.timezone' => 'string',
        
        // Mail
        'mail.default' => 'string',
        'mail.from.address' => 'email',
        'mail.from.name' => 'string',
        
        // Nestogy Core
        'nestogy.company.default_currency' => 'string|size:3',
        'nestogy.company.default_timezone' => 'string',
        'nestogy.company.default_country' => 'string|size:2',
        
        // Security
        'security.password.min_length' => 'integer|min:6',
        'security.session.timeout' => 'integer|min:1',
        'security.rate_limiting.login_attempts' => 'integer|min:1',
    ];

    /**
     * Optional but recommended configuration keys
     */
    protected array $recommendedConfigs = [
        // Integrations
        'integrations.stripe.enabled' => 'boolean',
        'integrations.plaid.enabled' => 'boolean',
        
        // Uploads
        'uploads.max_size' => 'integer|min:1',
        'uploads.virus_scanning.enabled' => 'boolean',
        
        // Notifications
        'notifications.channels.mail' => 'boolean',
        'notifications.channels.database' => 'boolean',
        
        // Security
        'security.two_factor.enabled' => 'boolean',
        'security.audit.enabled' => 'boolean',
    ];

    /**
     * Configuration warnings
     */
    protected array $warnings = [];

    /**
     * Configuration errors
     */
    protected array $errors = [];

    /**
     * Validate all configuration
     */
    public function validate(): bool
    {
        $this->warnings = [];
        $this->errors = [];

        // Validate required configurations
        $this->validateRequiredConfigs();

        // Validate recommended configurations
        $this->validateRecommendedConfigs();

        // Check for security issues
        $this->validateSecuritySettings();

        // Check for performance issues
        $this->validatePerformanceSettings();

        // Check integration configurations
        $this->validateIntegrationSettings();

        // Log results
        $this->logValidationResults();

        return empty($this->errors);
    }

    /**
     * Validate required configurations
     */
    protected function validateRequiredConfigs(): void
    {
        foreach ($this->requiredConfigs as $key => $rules) {
            $value = Config::get($key);

            if ($value === null) {
                $this->errors[] = "Required configuration '{$key}' is missing";
                continue;
            }

            $this->validateValue($key, $value, $rules);
        }
    }

    /**
     * Validate recommended configurations
     */
    protected function validateRecommendedConfigs(): void
    {
        foreach ($this->recommendedConfigs as $key => $rules) {
            $value = Config::get($key);

            if ($value === null) {
                $this->warnings[] = "Recommended configuration '{$key}' is not set";
                continue;
            }

            $this->validateValue($key, $value, $rules, true);
        }
    }

    /**
     * Validate a configuration value against rules
     */
    protected function validateValue(string $key, $value, string $rules, bool $isWarning = false): void
    {
        $ruleArray = explode('|', $rules);

        foreach ($ruleArray as $rule) {
            if (!$this->checkRule($value, $rule)) {
                $message = "Configuration '{$key}' failed validation rule '{$rule}'";
                
                if ($isWarning) {
                    $this->warnings[] = $message;
                } else {
                    $this->errors[] = $message;
                }
            }
        }
    }

    /**
     * Check a single validation rule
     */
    protected function checkRule($value, string $rule): bool
    {
        if (str_contains($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        switch ($ruleName) {
            case 'string':
                return is_string($value);
            
            case 'integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            
            case 'boolean':
                return is_bool($value) || in_array($value, ['true', 'false', '1', '0', 1, 0], true);
            
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            
            case 'min':
                return is_numeric($value) && $value >= (int)$parameter;
            
            case 'max':
                return is_numeric($value) && $value <= (int)$parameter;
            
            case 'size':
                return strlen((string)$value) === (int)$parameter;
            
            default:
                return true;
        }
    }

    /**
     * Validate security-specific settings
     */
    protected function validateSecuritySettings(): void
    {
        // Check if APP_DEBUG is enabled in production
        if (Config::get('app.env') === 'production' && Config::get('app.debug') === true) {
            $this->errors[] = "APP_DEBUG should be false in production environment";
        }

        // Check password policy
        $minLength = Config::get('security.password.min_length', 8);
        if ($minLength < 8) {
            $this->warnings[] = "Password minimum length is less than 8 characters";
        }

        // Check session security
        if (Config::get('app.env') === 'production') {
            if (!Config::get('session.secure', false)) {
                $this->warnings[] = "Session cookies should be secure in production (SESSION_SECURE_COOKIE=true)";
            }

            if (!Config::get('session.http_only', true)) {
                $this->warnings[] = "Session cookies should be HTTP only (SESSION_HTTP_ONLY=true)";
            }
        }

        // Check encryption key
        if (empty(Config::get('app.key'))) {
            $this->errors[] = "Application encryption key (APP_KEY) is not set";
        }

        // Check CORS settings
        if (Config::get('cors.allowed_origins') === ['*'] && Config::get('app.env') === 'production') {
            $this->warnings[] = "CORS is configured to allow all origins in production";
        }
    }

    /**
     * Validate performance-related settings
     */
    protected function validatePerformanceSettings(): void
    {
        // Check cache configuration
        if (Config::get('cache.default') === 'array' && Config::get('app.env') === 'production') {
            $this->warnings[] = "Array cache driver is not recommended for production";
        }

        // Check queue configuration
        if (Config::get('queue.default') === 'sync' && Config::get('app.env') === 'production') {
            $this->warnings[] = "Sync queue driver is not recommended for production";
        }

        // Check session driver
        if (Config::get('session.driver') === 'array' && Config::get('app.env') === 'production') {
            $this->warnings[] = "Array session driver is not recommended for production";
        }

        // Check file upload limits
        $maxFileSize = Config::get('uploads.max_size', 10240);
        if ($maxFileSize > 51200) { // 50MB
            $this->warnings[] = "Maximum file upload size is very large (>50MB), this may impact performance";
        }
    }

    /**
     * Validate integration settings
     */
    protected function validateIntegrationSettings(): void
    {
        // Stripe validation
        if (Config::get('integrations.stripe.enabled')) {
            if (empty(Config::get('integrations.stripe.public_key'))) {
                $this->errors[] = "Stripe is enabled but public key is not set";
            }
            if (empty(Config::get('integrations.stripe.secret_key'))) {
                $this->errors[] = "Stripe is enabled but secret key is not set";
            }
        }

        // Plaid validation
        if (Config::get('integrations.plaid.enabled')) {
            if (empty(Config::get('integrations.plaid.client_id'))) {
                $this->errors[] = "Plaid is enabled but client ID is not set";
            }
            if (empty(Config::get('integrations.plaid.secret'))) {
                $this->errors[] = "Plaid is enabled but secret is not set";
            }
        }

        // Email/IMAP validation
        if (Config::get('integrations.email.imap_enabled')) {
            if (empty(Config::get('integrations.email.imap_host'))) {
                $this->errors[] = "IMAP is enabled but host is not set";
            }
            if (empty(Config::get('integrations.email.imap_username'))) {
                $this->errors[] = "IMAP is enabled but username is not set";
            }
        }

        // AWS validation
        if (Config::get('integrations.aws.s3.enabled')) {
            if (empty(Config::get('integrations.aws.s3.key'))) {
                $this->errors[] = "AWS S3 is enabled but access key is not set";
            }
            if (empty(Config::get('integrations.aws.s3.bucket'))) {
                $this->errors[] = "AWS S3 is enabled but bucket is not set";
            }
        }
    }

    /**
     * Log validation results
     */
    protected function logValidationResults(): void
    {
        if (!empty($this->errors)) {
            Log::error('Configuration validation failed', [
                'errors' => $this->errors,
                'warnings' => $this->warnings
            ]);
        } elseif (!empty($this->warnings)) {
            Log::warning('Configuration validation completed with warnings', [
                'warnings' => $this->warnings
            ]);
        } else {
            Log::info('Configuration validation passed successfully');
        }
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get a formatted report of validation results
     */
    public function getReport(): array
    {
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'error_count' => count($this->errors),
            'warning_count' => count($this->warnings),
        ];
    }

    /**
     * Check if specific configuration exists and is valid
     */
    public function checkConfig(string $key, $expectedType = null): bool
    {
        $value = Config::get($key);

        if ($value === null) {
            return false;
        }

        if ($expectedType !== null) {
            switch ($expectedType) {
                case 'string':
                    return is_string($value);
                case 'integer':
                    return is_int($value);
                case 'boolean':
                    return is_bool($value);
                case 'array':
                    return is_array($value);
                default:
                    return true;
            }
        }

        return true;
    }

    /**
     * Get missing required configurations
     */
    public function getMissingConfigs(): array
    {
        $missing = [];

        foreach (array_keys($this->requiredConfigs) as $key) {
            if (Config::get($key) === null) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}