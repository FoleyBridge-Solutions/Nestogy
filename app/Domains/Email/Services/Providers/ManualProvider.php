<?php

namespace App\Domains\Email\Services\Providers;

use App\Models\Company;

class ManualProvider implements EmailProviderInterface
{
    private const OAUTH_NOT_SUPPORTED_MESSAGE = 'Manual provider does not support OAuth';

    protected Company $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function getAuthorizationUrl(string $state): string
    {
        // Manual provider doesn't use OAuth
        throw new \Exception(self::OAUTH_NOT_SUPPORTED_MESSAGE);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        // Manual provider doesn't use OAuth
        throw new \Exception(self::OAUTH_NOT_SUPPORTED_MESSAGE);
    }

    public function refreshTokens(string $refreshToken): array
    {
        // Manual provider doesn't use OAuth
        throw new \Exception(self::OAUTH_NOT_SUPPORTED_MESSAGE);
    }

    public function getAccountData(array $tokens, string $email): array
    {
        // For manual provider, we don't have OAuth tokens
        return [
            'name' => 'Manual Email Account',
            'email' => $email,
        ];
    }

    public function getConfig(): array
    {
        return [
            'type' => 'manual',
            'requires_oauth' => false,
        ];
    }

    public function validateConfig(): bool
    {
        // Manual provider doesn't need configuration validation
        return true;
    }
}
