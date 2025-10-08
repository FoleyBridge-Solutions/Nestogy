<?php

namespace App\Domains\Financial\Exceptions;

use Throwable;

class TaxServiceException extends FinancialException
{
    protected $operation;

    protected $details = [];

    protected $errorType = 'tax_service_error';

    public function __construct(
        string $message = 'Tax service operation failed',
        ?string $operation = null,
        array $details = [],
        int $code = 500,
        ?Throwable $previous = null
    ) {
        $this->operation = $operation;
        $this->details = $details;
        $this->context = $details;
        parent::__construct($message, $code, $previous);
    }

    public function getOperation(): ?string
    {
        return $this->operation;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function serviceClassNotFound(string $serviceClass): self
    {
        return new static(
            "Service class {$serviceClass} does not exist",
            'register_service',
            ['service_class' => $serviceClass],
            500
        );
    }

    public static function invalidServiceClass(string $serviceClass, string $interfaceName): self
    {
        return new static(
            "Service class {$serviceClass} must implement {$interfaceName}",
            'register_service',
            ['service_class' => $serviceClass, 'required_interface' => $interfaceName],
            500
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'operation' => $this->operation,
            'details' => $this->details,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
