<?php

namespace App\Domains\Email\Services\Providers;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleWorkspaceProvider implements EmailProviderInterface
{
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
            'scope' => 'https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('email.oauth.callback'),
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token exchange failed', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception('Failed to exchange authorization code for tokens');
        }

        return $response->json();
    }

    public function refreshTokens(string $refreshToken): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token refresh failed', [
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
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if ($response->failed()) {
            Log::error('Failed to get Google user profile', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            return [
                'name' => 'Google Workspace Account',
                'email' => $email,
            ];
        }

        $profile = $response->json();

        return [
            'name' => $profile['name'] ?? 'Google Workspace Account',
            'email' => $profile['email'] ?? $email,
            'first_name' => $profile['given_name'] ?? null,
            'last_name' => $profile['family_name'] ?? null,
        ];
    }

    public function getConfig(): array
    {
        return [
            'type' => 'google_workspace',
            'requires_oauth' => true,
            'client_id' => $this->config['client_id'] ?? null,
            'allowed_domains' => $this->config['allowed_domains'] ?? [],
            'scopes' => [
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ],
        ];
    }

    public function validateConfig(): bool
    {
        return ! empty($this->config['client_id']) &&
               ! empty($this->config['client_secret']);
    }

    /**
     * Get Gmail labels (folders) using Gmail API
     */
    public function getLabels(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/labels');

        if ($response->failed()) {
            Log::error('Failed to get Gmail labels', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception('Failed to retrieve Gmail labels');
        }

        return $response->json()['labels'] ?? [];
    }

    /**
     * Get Gmail messages using Gmail API
     */
    public function getMessages(string $accessToken, array $options = []): array
    {
        $params = [
            'maxResults' => $options['maxResults'] ?? 100,
            'q' => $options['query'] ?? '',
        ];

        if (! empty($options['pageToken'])) {
            $params['pageToken'] = $options['pageToken'];
        }

        $response = Http::withToken($accessToken)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', $params);

        if ($response->failed()) {
            Log::error('Failed to get Gmail messages', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception('Failed to retrieve Gmail messages');
        }

        return $response->json();
    }

    /**
     * Get specific Gmail message details using Gmail API
     */
    public function getMessage(string $accessToken, string $messageId): array
    {
        $response = Http::withToken($accessToken)
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$messageId}");

        if ($response->failed()) {
            Log::error('Failed to get Gmail message details', [
                'message_id' => $messageId,
                'response' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception("Failed to retrieve Gmail message: {$messageId}");
        }

        return $response->json();
    }
}
