<?php

namespace App\Domains\Financial\Exceptions;

use Exception;
use Throwable;

/**
 * General service-level exception for quote operations
 */
class QuoteServiceException extends FinancialException
{
    /**
     * Operation that failed
     *
     * @var string|null
     */
    protected $operation;

    /**
     * Additional error details
     *
     * @var array
     */
    protected $details = [];

    /**
     * Error type for API responses
     *
     * @var string
     */
    protected $errorType = 'service_error';

    /**
     * Create a new quote service exception
     */
    public function __construct(
        string $message = 'Quote service operation failed',
        ?string $operation = null,
        array $details = [],
        int $code = 500,
        ?Throwable $previous = null
    ) {
        $this->operation = $operation;
        $this->details = $details;
        $this->context = $details; // Map details to context for consistency
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the operation that failed
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * Get error details
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Create exception for database transaction failure
     *
     * @return static
     */
    public static function transactionFailed(string $operation, Throwable $previous): self
    {
        return new static(
            "Database transaction failed during {$operation}",
            $operation,
            ['error' => $previous->getMessage()],
            500,
            $previous
        );
    }

    /**
     * Create exception for external service failure
     *
     * @return static
     */
    public static function externalServiceFailed(string $service, string $operation, string $error): self
    {
        return new static(
            "External service {$service} failed during {$operation}",
            $operation,
            ['service' => $service, 'error' => $error],
            502
        );
    }

    /**
     * Create exception for configuration error
     *
     * @return static
     */
    public static function configurationError(string $configKey, string $operation): self
    {
        return new static(
            "Configuration error: {$configKey} is missing or invalid",
            $operation,
            ['config_key' => $configKey],
            500
        );
    }

    /**
     * Create exception for calculation error
     *
     * @return static
     */
    public static function calculationFailed(string $calculationType, Throwable $previous): self
    {
        return new static(
            "Calculation failed for {$calculationType}",
            'calculation',
            ['calculation_type' => $calculationType, 'error' => $previous->getMessage()],
            500,
            $previous
        );
    }

    /**
     * Create exception for file operation failure
     *
     * @return static
     */
    public static function fileOperationFailed(string $operation, string $filepath, string $error): self
    {
        return new static(
            "File operation {$operation} failed for {$filepath}",
            $operation,
            ['filepath' => $filepath, 'error' => $error],
            500
        );
    }

    /**
     * Convert to array format for API responses
     */
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
