<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\ServiceException;

class OAuthTokenRefreshException extends ServiceException
{
    public function __construct(
        string $provider,
        string $reason,
        array $context = []
    ) {
        parent::__construct(
            "Failed to refresh OAuth tokens for provider '{$provider}': {$reason}",
            500,
            null,
            array_merge($context, [
                'provider' => $provider,
                'reason' => $reason,
            ]),
            'Failed to refresh authentication tokens. Please try reconnecting your email account.',
            500
        );
    }

    protected function getDefaultUserMessage(): string
    {
        return 'Failed to refresh authentication tokens. Please try reconnecting your email account.';
    }
}
