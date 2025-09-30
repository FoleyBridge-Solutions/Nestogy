<?php

namespace App\Domains\Financial\Exceptions;

use Exception;

/**
 * Exception thrown when quote validation fails
 */
class QuoteValidationException extends FinancialException
{
    /**
     * Validation errors array
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Error type for API responses
     *
     * @var string
     */
    protected $errorType = 'validation_error';

    /**
     * Create a new quote validation exception
     */
    public function __construct(string $message = 'Quote validation failed', array $errors = [], int $code = 422, ?Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if has specific field error
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Get error for specific field
     */
    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstField = array_key_first($this->errors);

        return $this->errors[$firstField];
    }

    /**
     * Convert to array format for API responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'type' => $this->getErrorType(),
            'context' => $this->getContext(),
        ];
    }
}
