<?php

namespace App\Domains\Email\Exceptions;

use App\Exceptions\IntegrationException;

class GoogleWorkspaceException extends IntegrationException
{
    public function __construct(
        string $message,
        array $context = [],
        string $userMessage = ''
    ) {
        parent::__construct(
            'Google Workspace',
            $message,
            $context,
            $userMessage ?: 'Failed to communicate with Google Workspace. Please try again later.'
        );
    }
}
