<?php

namespace App\Domains\Integration\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\IntegrationException as BaseIntegrationException;
use App\Exceptions\ServiceException;

class IntegrationException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your integration request.';
    }
}

class DeviceInventoryException extends BaseIntegrationException
{
    public function __construct(
        string $service,
        string $agentId,
        string $errorDetails = 'Unknown error',
        array $context = []
    ) {
        parent::__construct(
            $service,
            "Failed to get device inventory for agent {$agentId}: {$errorDetails}",
            array_merge($context, [
                'agent_id' => $agentId,
                'error_details' => $errorDetails,
            ]),
            'Failed to retrieve device inventory from the RMM system. Please try again later.'
        );
    }
}

class RmmSyncException extends BaseIntegrationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'Failed to synchronize with the RMM system. Please try again later.';
    }
}

class RmmServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An RMM service error occurred. Please try again later.';
    }
}
