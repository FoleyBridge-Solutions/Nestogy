<?php

namespace App\Domains\Email\Services\Providers;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphProvider implements EmailProviderInterface
{
    private const OAUTH_SCOPES = 'https://graph.microsoft.com/Mail.ReadWrite https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/User.Read offline_access';

    protected Company $company;

    protected array $config;

    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->config = $company->email_provider_config ?? [];
    }

    public function getAuthorizationUrl(string $state): string
    {
        $params = [
            'client_id' => $this->config['client_id'] ?? '',
            'response_type' => 'code',
            'redirect_uri' => route('email.oauth.callback'),
            'scope' => self::OAUTH_SCOPES,
            'state' => $state,
            'response_mode' => 'query',
        ];

        $tenantId = $this->config['tenant_id'] ?? 'common';

        return "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?".http_build_query($params);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->getTenantId()}/oauth2/v2.0/token", [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('email.oauth.callback'),
            'scope' => self::OAUTH_SCOPES,
        ]);

        if ($response->failed()) {
            Log::error('Microsoft OAuth token exchange failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception('Failed to exchange authorization code for tokens');
        }

        return $response->json();
    }

    public function refreshTokens(string $refreshToken): array
    {
        $response = Http::asForm()->post("https://login.microsoftonline.com/{$this->getTenantId()}/oauth2/v2.0/token", [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
            'scope' => self::OAUTH_SCOPES,
        ]);

        if ($response->failed()) {
            Log::error('Microsoft OAuth token refresh failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception('Failed to refresh OAuth tokens');
        }

        return $response->json();
    }

    public function getAccountData(array $tokens, string $email): array
    {
        $response = Http::withToken($tokens['access_token'])
            ->get('https://graph.microsoft.com/v1.0/me');

        if ($response->failed()) {
            Log::error('Failed to get Microsoft user profile', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            return [
                'name' => 'Microsoft 365 Account',
                'email' => $email,
            ];
        }

        $profile = $response->json();

        return [
            'name' => $profile['displayName'] ?? 'Microsoft 365 Account',
            'email' => $profile['userPrincipalName'] ?? $email,
            'first_name' => $profile['givenName'] ?? null,
            'last_name' => $profile['surname'] ?? null,
        ];
    }

    public function getConfig(): array
    {
        return [
            'type' => 'microsoft365',
            'requires_oauth' => true,
            'client_id' => $this->config['client_id'] ?? null,
            'tenant_id' => $this->config['tenant_id'] ?? 'common',
            'allowed_domains' => $this->config['allowed_domains'] ?? [],
            'scopes' => [
                'https://graph.microsoft.com/Mail.ReadWrite',
                'https://graph.microsoft.com/Mail.Send',
                'https://graph.microsoft.com/User.Read',
                'offline_access',
            ],
        ];
    }

    public function validateConfig(): bool
    {
        return ! empty($this->config['client_id']) &&
               ! empty($this->config['client_secret']);
    }

    protected function getTenantId(): string
    {
        return $this->config['tenant_id'] ?? 'common';
    }
}
