<?php

namespace App\Exceptions;

class RmmClientNotFoundException extends IntegrationException
{
    public function __construct(
        string $clientId = '',
        array $context = []
    ) {
        $message = $clientId
            ? "RMM client '{$clientId}' not found"
            : 'RMM client not found';

        parent::__construct(
            'Tactical RMM',
            $message,
            $context,
            'The selected RMM client could not be found.'
        );
    }
}
