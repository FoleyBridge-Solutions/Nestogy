<?php

namespace App\Exceptions;

class DeviceSyncException extends IntegrationException
{
    public function __construct(
        string $service,
        string $message,
        array $context = [],
        string $userMessage = ''
    ) {
        parent::__construct(
            $service,
            $message,
            $context,
            $userMessage ?: 'Failed to synchronize device data. Please check the integration configuration and try again.'
        );
    }
}
