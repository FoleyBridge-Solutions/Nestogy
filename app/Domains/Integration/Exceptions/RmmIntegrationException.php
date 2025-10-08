<?php

namespace App\Domains\Integration\Exceptions;

use App\Exceptions\BaseException;

class RmmIntegrationException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred with the RMM integration.';
    }
}

class RmmIntegrationNotFoundException extends RmmIntegrationException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            'No saved integration found',
            404,
            null,
            $context,
            'No saved integration found. Please configure the integration first.',
            404
        );
    }
}
