<?php

namespace App\Domains\Email\Services;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Services\EmailProviderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OAuthTokenManager
{
    protected EmailProviderService $providerService;

    public function __construct(EmailProviderService $providerService)
    {
        $this->providerService = $providerService;
    }

    /**
     * Ensure account has valid OAuth tokens
     */
    public function ensureValidTokens(EmailAccount $account): bool
    {
        if ($account->connection_type !== 'oauth') {
            return true; // Not OAuth, so tokens are "valid"
        }

        if (!$this->hasValidTokens($account)) {
            return $this->refreshTokens($account);
        }

        return true;
    }

    /**
     * Check if account has valid OAuth tokens
     */
    public function hasValidTokens(EmailAccount $account): bool
    {
        if (!$account->oauth_access_token || !$account->oauth_expires_at) {
            return false;
        }

        // Check if token expires within 5 minutes
        return $account->oauth_expires_at->subMinutes(5)->isFuture();
    }

    /**
     * Refresh OAuth tokens for account
     */
    public function refreshTokens(EmailAccount $account): bool
    {
        if (!$account->oauth_refresh_token) {
            Log::warning('Cannot refresh OAuth tokens: no refresh token available', [
                'account_id' => $account->id,
            ]);
            return false;
        }

        try {
            $success = $this->providerService->refreshTokens($account);

            if ($success) {
                Log::info('Successfully refreshed OAuth tokens', [
                    'account_id' => $account->id,
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Failed to refresh OAuth tokens', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            // Mark account as having sync error
            $account->update([
                'sync_error' => 'OAuth token refresh failed: ' . $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get valid access token for account
     */
    public function getValidAccessToken(EmailAccount $account): ?string
    {
        if (!$this->ensureValidTokens($account)) {
            return null;
        }

        return $account->oauth_access_token;
    }

    /**
     * Revoke OAuth tokens
     */
    public function revokeTokens(EmailAccount $account): bool
    {
        try {
            $company = $account->company;
            $provider = $this->providerService->getProvider($company);

            // Note: Most providers don't have a revoke endpoint that works reliably
            // We'll just clear the tokens from our database
            $account->update([
                'oauth_access_token' => null,
                'oauth_refresh_token' => null,
                'oauth_expires_at' => null,
                'oauth_token_expires_at' => null,
                'oauth_scopes' => null,
                'connection_type' => 'manual', // Fall back to manual
                'sync_error' => 'OAuth tokens revoked',
            ]);

            Log::info('OAuth tokens revoked for account', [
                'account_id' => $account->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke OAuth tokens', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Batch refresh tokens for accounts that need it
     */
    public function batchRefreshTokens(): array
    {
        $results = [
            'processed' => 0,
            'refreshed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $accounts = EmailAccount::where('connection_type', 'oauth')
            ->whereNotNull('oauth_refresh_token')
            ->where(function ($query) {
                $query->whereNull('oauth_expires_at')
                      ->orWhere('oauth_expires_at', '<=', now()->addMinutes(10));
            })
            ->get();

        foreach ($accounts as $account) {
            $results['processed']++;

            try {
                if ($this->refreshTokens($account)) {
                    $results['refreshed']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'account_id' => $account->id,
                    'email' => $account->email_address,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Batch OAuth token refresh completed', $results);

        return $results;
    }

    /**
     * Get token expiry information
     */
    public function getTokenExpiryInfo(EmailAccount $account): array
    {
        if (!$account->oauth_expires_at) {
            return [
                'has_tokens' => false,
                'expires_at' => null,
                'is_expired' => true,
                'minutes_until_expiry' => null,
            ];
        }

        $now = now();
        $isExpired = $account->oauth_expires_at->isPast();
        $minutesUntilExpiry = $isExpired ? 0 : $now->diffInMinutes($account->oauth_expires_at, false);

        return [
            'has_tokens' => true,
            'expires_at' => $account->oauth_expires_at,
            'is_expired' => $isExpired,
            'minutes_until_expiry' => $minutesUntilExpiry,
        ];
    }
}