<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\ServiceException;

class EmailException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your email request.';
    }
}

class EmailProviderException extends ServiceException
{
    protected string $provider;

    public function __construct(
        string $provider,
        string $message,
        array $context = [],
        string $userMessage = ''
    ) {
        $this->provider = $provider;

        parent::__construct(
            "Email provider error ({$provider}): {$message}",
            502,
            null,
            array_merge($context, [
                'provider' => $provider,
                'provider_message' => $message,
            ]),
            $userMessage ?: "An error occurred while communicating with your email provider. Please try again later.",
            502
        );
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while communicating with your email provider.';
    }
}

class EmailOAuthException extends EmailProviderException
{
    protected function getDefaultUserMessage(): string
    {
        return 'Failed to authenticate with your email provider. Please try reconnecting your account.';
    }
}
