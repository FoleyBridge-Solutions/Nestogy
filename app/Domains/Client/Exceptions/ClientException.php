<?php

namespace App\Domains\Client\Exceptions;

use App\Exceptions\BaseException;
use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException as BaseValidationException;
use App\Exceptions\NotFoundException as BaseNotFoundException;
use App\Exceptions\PermissionException as BasePermissionException;
use App\Exceptions\ServiceException;

/**
 * Base Client Exception
 */
class ClientException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing your client request.';
    }
}

/**
 * Client Validation Exception
 */
class ClientValidationException extends BaseValidationException
{
    protected function getDefaultUserMessage(): string
    {
        return 'The client data provided is invalid.';
    }
}

/**
 * Client Not Found Exception
 */
class ClientNotFoundException extends BaseNotFoundException
{
    public function __construct(mixed $clientId = null, array $context = [])
    {
        parent::__construct('Client', $clientId, $context);
    }
}

/**
 * Client Permission Exception
 */
class ClientPermissionException extends BasePermissionException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct($action, 'Client', $context);
    }
}

/**
 * Client Service Exception
 */
class ClientServiceException extends ServiceException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A client service error occurred. Please try again later.';
    }
}

/**
 * Client Business Exception
 */
class ClientBusinessException extends BusinessException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A client business rule was violated.';
    }
}

/**
 * Client Status Exception
 */
class ClientStatusException extends ClientBusinessException
{
    public function __construct(string $action, string $currentStatus, array $context = [])
    {
        parent::__construct(
            "Cannot {$action} client in {$currentStatus} status",
            400,
            null,
            array_merge($context, [
                'action' => $action,
                'current_status' => $currentStatus,
            ]),
            "This action cannot be performed on a client with status: {$currentStatus}.",
            400
        );
    }
}

/**
 * Client Lead Conversion Exception
 */
class ClientLeadConversionException extends ClientBusinessException
{
    public function __construct(string $reason, array $context = [])
    {
        parent::__construct(
            "Lead conversion failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason]),
            "Unable to convert lead to customer: {$reason}",
            400
        );
    }
}

/**
 * Client Contact Exception
 */
class ClientContactException extends ClientException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing client contact information.';
    }
}

/**
 * Client Location Exception
 */
class ClientLocationException extends ClientException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while processing client location information.';
    }
}

/**
 * Client Import Exception
 */
class ClientImportException extends ClientException
{
    protected array $failedRows;

    public function __construct(string $message, array $failedRows = [], array $context = [])
    {
        $this->failedRows = $failedRows;
        
        parent::__construct(
            $message,
            422,
            null,
            array_merge($context, ['failed_rows' => $failedRows]),
            'Some clients could not be imported due to validation errors.',
            422
        );
    }

    public function getFailedRows(): array
    {
        return $this->failedRows;
    }
}

/**
 * Client Export Exception
 */
class ClientExportException extends ClientException
{
    protected function getDefaultUserMessage(): string
    {
        return 'An error occurred while exporting client data.';
    }
}