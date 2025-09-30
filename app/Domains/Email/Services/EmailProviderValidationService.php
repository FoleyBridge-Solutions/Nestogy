<?php

namespace App\Domains\Email\Services;

use App\Models\Company;

class EmailProviderValidationService
{
    /**
     * Validate company email provider configuration
     */
    public function validateProviderConfig(Company $company, array $config): array
    {
        $errors = [];

        switch ($company->email_provider_type) {
            case 'microsoft365':
                $errors = array_merge($errors, $this->validateMicrosoftConfig($config));
                break;
            case 'google_workspace':
                $errors = array_merge($errors, $this->validateGoogleConfig($config));
                break;
            case 'manual':
                // Manual configuration doesn't need validation
                break;
            default:
                $errors[] = 'Unsupported email provider type';
        }

        return $errors;
    }

    /**
     * Validate Microsoft 365 configuration
     */
    protected function validateMicrosoftConfig(array $config): array
    {
        $errors = [];

        // Required fields
        if (empty($config['client_id'])) {
            $errors[] = 'Client ID is required for Microsoft 365';
        }

        if (empty($config['client_secret'])) {
            $errors[] = 'Client Secret is required for Microsoft 365';
        }

        // Validate client ID format (basic UUID check)
        if (! empty($config['client_id']) && ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $config['client_id'])) {
            $errors[] = 'Client ID must be a valid UUID format';
        }

        // Validate tenant ID if provided
        if (! empty($config['tenant_id']) && $config['tenant_id'] !== 'common') {
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $config['tenant_id'])) {
                $errors[] = 'Tenant ID must be a valid UUID or "common"';
            }
        }

        // Validate allowed domains
        if (! empty($config['allowed_domains'])) {
            foreach ($config['allowed_domains'] as $domain) {
                if (! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    $errors[] = "Invalid domain format: {$domain}";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate Google Workspace configuration
     */
    protected function validateGoogleConfig(array $config): array
    {
        $errors = [];

        // Required fields
        if (empty($config['client_id'])) {
            $errors[] = 'Client ID is required for Google Workspace';
        }

        if (empty($config['client_secret'])) {
            $errors[] = 'Client Secret is required for Google Workspace';
        }

        // Validate Google client ID format
        if (! empty($config['client_id']) && ! preg_match('/\.apps\.googleusercontent\.com$/', $config['client_id'])) {
            $errors[] = 'Client ID must be a valid Google OAuth client ID';
        }

        // Validate allowed domains
        if (! empty($config['allowed_domains'])) {
            foreach ($config['allowed_domains'] as $domain) {
                if (! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                    $errors[] = "Invalid domain format: {$domain}";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate email domain against company configuration
     */
    public function validateEmailDomain(Company $company, string $email): bool
    {
        $config = $company->email_provider_config ?? [];

        // If no domain restrictions, allow all
        if (empty($config['allowed_domains'])) {
            return true;
        }

        $emailDomain = strtolower(explode('@', $email)[1] ?? '');

        foreach ($config['allowed_domains'] as $allowedDomain) {
            if (strtolower($allowedDomain) === $emailDomain) {
                return true;
            }
        }

        return false;
    }

    /**
     * Test OAuth configuration by attempting to get an authorization URL
     */
    public function testOAuthConfiguration(Company $company): array
    {
        try {
            $providerService = app(EmailProviderService::class);
            $authUrl = $providerService->getAuthorizationUrl($company, 'test_state_'.time());

            return [
                'success' => true,
                'message' => 'OAuth configuration appears valid',
                'auth_url' => $authUrl,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'OAuth configuration test failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Validate email account configuration before saving
     */
    public function validateEmailAccountConfig(array $data, Company $company): array
    {
        $errors = [];

        // Basic validation
        if (empty($data['name'])) {
            $errors[] = 'Account name is required';
        }

        if (empty($data['email_address']) || ! filter_var($data['email_address'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required';
        }

        // For OAuth connections, validate domain
        if (($data['connection_type'] ?? 'manual') === 'oauth') {
            if (! $this->validateEmailDomain($company, $data['email_address'])) {
                $errors[] = 'Email domain is not allowed for this company\'s email provider';
            }
        }

        // For manual connections, validate IMAP/SMTP settings
        if (($data['connection_type'] ?? 'manual') === 'manual') {
            $errors = array_merge($errors, $this->validateManualConfig($data));
        }

        return $errors;
    }

    /**
     * Validate manual IMAP/SMTP configuration
     */
    protected function validateManualConfig(array $data): array
    {
        $errors = [];

        if (empty($data['imap_host'])) {
            $errors[] = 'IMAP host is required';
        }

        if (empty($data['imap_port']) || ! is_numeric($data['imap_port']) || $data['imap_port'] < 1 || $data['imap_port'] > 65535) {
            $errors[] = 'Valid IMAP port is required (1-65535)';
        }

        if (empty($data['imap_username'])) {
            $errors[] = 'IMAP username is required';
        }

        if (empty($data['imap_password'])) {
            $errors[] = 'IMAP password is required';
        }

        if (empty($data['smtp_host'])) {
            $errors[] = 'SMTP host is required';
        }

        if (empty($data['smtp_port']) || ! is_numeric($data['smtp_port']) || $data['smtp_port'] < 1 || $data['smtp_port'] > 65535) {
            $errors[] = 'Valid SMTP port is required (1-65535)';
        }

        if (empty($data['smtp_username'])) {
            $errors[] = 'SMTP username is required';
        }

        if (empty($data['smtp_password'])) {
            $errors[] = 'SMTP password is required';
        }

        return $errors;
    }
}
