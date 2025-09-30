<?php

namespace App\Domains\Email\Services\Providers;

interface EmailProviderInterface
{
    /**
     * Get OAuth authorization URL
     */
    public function getAuthorizationUrl(string $state): string;

    /**
     * Exchange authorization code for tokens
     */
    public function exchangeCodeForTokens(string $code): array;

    /**
     * Refresh OAuth tokens
     */
    public function refreshTokens(string $refreshToken): array;

    /**
     * Get account data from OAuth tokens
     */
    public function getAccountData(array $tokens, string $email): array;

    /**
     * Get provider-specific configuration
     */
    public function getConfig(): array;

    /**
     * Validate provider configuration
     */
    public function validateConfig(): bool;
}
