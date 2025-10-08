<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\IntegrationException;

class EmailProviderException extends IntegrationException
{
    public function __construct(
        string $provider,
        string $message,
        array $context = [],
        string $userMessage = ''
    ) {
        parent::__construct(
            $provider,
            $message,
            $context,
            $userMessage ?: 'Failed to communicate with email provider. Please try again later.'
        );
    }
}
