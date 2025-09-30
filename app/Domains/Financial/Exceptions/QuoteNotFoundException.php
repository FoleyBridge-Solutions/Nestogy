<?php

namespace App\Domains\Financial\Exceptions;

use Exception;

/**
 * Exception thrown when a quote is not found
 */
class QuoteNotFoundException extends FinancialException
{
    /**
     * Quote ID that was not found
     *
     * @var int|null
     */
    protected $quoteId;

    /**
     * Error type for API responses
     *
     * @var string
     */
    protected $errorType = 'not_found_error';

    /**
     * Create a new quote not found exception
     */
    public function __construct(string $message = 'Quote not found', ?int $quoteId = null, int $code = 404, ?Exception $previous = null)
    {
        $this->quoteId = $quoteId;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the quote ID that was not found
     */
    public function getQuoteId(): ?int
    {
        return $this->quoteId;
    }

    /**
     * Create exception for specific quote ID
     *
     * @return static
     */
    public static function forId(int $quoteId): self
    {
        return new static("Quote with ID {$quoteId} not found", $quoteId);
    }

    /**
     * Create exception for quote number
     *
     * @return static
     */
    public static function forNumber(string $quoteNumber): self
    {
        return new static("Quote with number {$quoteNumber} not found");
    }

    /**
     * Convert to array format for API responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'quote_id' => $this->quoteId,
            'type' => $this->getErrorType(),
            'context' => $this->getContext(),
            'code' => $this->getCode(),
        ];
    }
}
