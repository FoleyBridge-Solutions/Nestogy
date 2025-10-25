<?php

namespace App\Exceptions;

class ContractStatusException extends ContractException
{
    public function __construct(string $action, string $currentStatus, array $context = [])
    {
        parent::__construct(
            "Cannot {$action} contract in {$currentStatus} status",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'current_status' => $currentStatus,
            ]),
            "This action cannot be performed on a contract with status: {$currentStatus}.",
            400
        );
    }
}
