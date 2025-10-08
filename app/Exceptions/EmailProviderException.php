<?php

namespace App\Exceptions;

class EmailProviderException extends IntegrationException
{
    public function __construct(
        string $provider,
        string $message,
        array $context = []
    ) {
        parent::__construct(
            $provider,
            $message,
            $context,
            "Email provider error occurred. Please try again later."
        );
    }
}
