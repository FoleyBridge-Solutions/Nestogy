<?php

namespace App\Domains\Financial\Exceptions;

use Exception;

/**
 * Base exception for all Financial domain exceptions
 */
abstract class FinancialException extends Exception
{
    /**
     * Exception context data
     *
     * @var array
     */
    protected $context = [];

    /**
     * Error type for API responses
     *
     * @var string
     */
    protected $errorType = 'financial_error';

    /**
     * Get context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set context data
     *
     * @param array $context
     * @return $this
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Add context data
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Get error type
     *
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Convert exception to array for API responses
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Get suggested HTTP status code
     *
     * @return int
     */
    public function getHttpStatusCode(): int
    {
        $code = $this->getCode();
        
        // Map common exception codes to valid HTTP status codes
        return match ($code) {
            401, 403, 404, 422, 500, 502, 503 => $code,
            // Default any other code to 500
            default => 500,
        };
    }

    /**
     * Check if exception should be logged
     *
     * @return bool
     */
    public function shouldLog(): bool
    {
        return $this->getHttpStatusCode() >= 500;
    }

    /**
     * Get log level for this exception
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        $statusCode = $this->getHttpStatusCode();
        
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        }
        
        return 'info';
    }
}