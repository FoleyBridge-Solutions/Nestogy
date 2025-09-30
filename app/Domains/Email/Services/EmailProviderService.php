<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Services\Providers\EmailProviderInterface;
use App\Domains\Email\Services\Providers\GoogleWorkspaceProvider;
use App\Domains\Email\Services\Providers\ManualProvider;
use App\Domains\Email\Services\Providers\MicrosoftGraphProvider;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class EmailProviderService
{
    /**
     * Get the appropriate email provider for a company
     */
    public function getProvider(Company $company): EmailProviderInterface
    {
        return match ($company->email_provider_type) {
            'microsoft365' => new MicrosoftGraphProvider($company),
            'google_workspace' => new GoogleWorkspaceProvider($company),
            'exchange' => new ManualProvider($company), // For now, use manual for Exchange
            'custom_oauth' => new ManualProvider($company), // For now, use manual for custom OAuth
            default => new ManualProvider($company),
        };
    }

    /**
     * Get OAuth authorization URL for a company
     */
    public function getAuthorizationUrl(Company $company, string $state): string
    {
        $provider = $this->getProvider($company);

        return $provider->getAuthorizationUrl($state);
    }

    /**
     * Exchange authorization code for tokens
     */
    public function exchangeCodeForTokens(Company $company, string $code): array
    {
        $provider = $this->getProvider($company);

        return $provider->exchangeCodeForTokens($code);
    }

    /**
     * Create email account from OAuth tokens
     */
    public function createAccountFromOAuth(
        Company $company,
        array $tokens,
        string $email,
        int $userId
    ): EmailAccount {
        $provider = $this->getProvider($company);

        $accountData = $provider->getAccountData($tokens, $email);

        return EmailAccount::create([
            'company_id' => $company->id,
            'user_id' => $userId,
            'name' => $accountData['name'] ?? 'Connected Email',
            'email_address' => $email,
            'provider' => $company->email_provider_type,
            'connection_type' => 'oauth',
            'oauth_provider' => $company->email_provider_type,
            'oauth_access_token' => $tokens['access_token'],
            'oauth_refresh_token' => $tokens['refresh_token'] ?? null,
            'oauth_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            'oauth_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            'oauth_scopes' => $tokens['scope'] ?? null,
            'is_active' => true,
            'sync_interval_minutes' => 5,
            'auto_create_tickets' => false,
            'auto_log_communications' => true,
        ]);
    }

    /**
     * Refresh OAuth tokens
     */
    public function refreshTokens(EmailAccount $account): bool
    {
        try {
            $company = $account->company;
            $provider = $this->getProvider($company);

            $tokens = $provider->refreshTokens($account->oauth_refresh_token);

            $account->update([
                'oauth_access_token' => $tokens['access_token'],
                'oauth_refresh_token' => $tokens['refresh_token'] ?? $account->oauth_refresh_token,
                'oauth_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                'oauth_token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to refresh OAuth tokens', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if tokens need refresh
     */
    public function tokensNeedRefresh(EmailAccount $account): bool
    {
        if (! $account->oauth_expires_at) {
            return false;
        }

        // Refresh 5 minutes before expiry
        return $account->oauth_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Get available email providers
     */
    public static function getAvailableProviders(): array
    {
        return [
            'manual' => [
                'name' => 'Manual Configuration',
                'description' => 'Configure IMAP/SMTP settings manually',
                'requires_oauth' => false,
            ],
            'microsoft365' => [
                'name' => 'Microsoft 365',
                'description' => 'Connect with Microsoft 365 / Exchange Online',
                'requires_oauth' => true,
            ],
            'google_workspace' => [
                'name' => 'Google Workspace',
                'description' => 'Connect with Google Workspace',
                'requires_oauth' => true,
            ],
            'exchange' => [
                'name' => 'Exchange Server',
                'description' => 'Connect to on-premise Exchange server',
                'requires_oauth' => false,
            ],
            'custom_oauth' => [
                'name' => 'Custom OAuth',
                'description' => 'Configure custom OAuth provider',
                'requires_oauth' => true,
            ],
        ];
    }
}
