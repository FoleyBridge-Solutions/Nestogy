<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxServiceException extends FinancialException
{
    protected $errorType = 'tax_service_error';

    public function __construct(
        string $message = 'Tax service operation failed',
        int $code = 500,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function serviceClassNotFound(string $serviceClass): self
    {
        return new static(
            "Service class {$serviceClass} does not exist",
            500
        );
    }

    public static function invalidServiceClass(string $serviceClass): self
    {
        return new static(
            "Service class {$serviceClass} must implement TaxDataServiceInterface",
            500
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
