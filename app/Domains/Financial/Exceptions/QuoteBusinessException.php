<?php

namespace App\Domains\Financial\Exceptions;

use Exception;

/**
 * Exception thrown when quote business logic fails
 */
class QuoteBusinessException extends FinancialException
{
    /**
     * Business rule that was violated
     *
     * @var string|null
     */
    protected $businessRule;

    /**
     * Error type for API responses
     *
     * @var string
     */
    protected $errorType = 'business_error';

    /**
     * Create a new quote business exception
     */
    public function __construct(
        string $message = 'Quote business rule violation',
        ?string $businessRule = null,
        array $context = [],
        int $code = 422,
        ?Exception $previous = null
    ) {
        $this->businessRule = $businessRule;
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the business rule that was violated
     */
    public function getBusinessRule(): ?string
    {
        return $this->businessRule;
    }

    /**
     * Create exception for quote expiration
     *
     * @return static
     */
    public static function quoteExpired(int $quoteId, string $expireDate): self
    {
        return new static(
            "Quote {$quoteId} expired on {$expireDate}",
            'quote_expiration',
            ['quote_id' => $quoteId, 'expire_date' => $expireDate]
        );
    }

    /**
     * Create exception for invalid quote status transition
     *
     * @return static
     */
    public static function invalidStatusTransition(int $quoteId, string $currentStatus, string $targetStatus): self
    {
        return new static(
            "Cannot change quote {$quoteId} status from {$currentStatus} to {$targetStatus}",
            'invalid_status_transition',
            [
                'quote_id' => $quoteId,
                'current_status' => $currentStatus,
                'target_status' => $targetStatus,
            ]
        );
    }

    /**
     * Create exception for minimum quote amount
     *
     * @return static
     */
    public static function belowMinimumAmount(float $amount, float $minimumAmount): self
    {
        return new static(
            "Quote amount {$amount} is below minimum required amount {$minimumAmount}",
            'minimum_amount_violation',
            ['amount' => $amount, 'minimum_amount' => $minimumAmount]
        );
    }

    /**
     * Create exception for missing required items
     *
     * @return static
     */
    public static function noItems(int $quoteId): self
    {
        return new static(
            "Quote {$quoteId} must have at least one item",
            'no_items',
            ['quote_id' => $quoteId]
        );
    }

    /**
     * Create exception for client credit limit exceeded
     *
     * @return static
     */
    public static function creditLimitExceeded(int $clientId, float $quoteAmount, float $creditLimit): self
    {
        return new static(
            "Quote amount {$quoteAmount} exceeds client credit limit {$creditLimit}",
            'credit_limit_exceeded',
            [
                'client_id' => $clientId,
                'quote_amount' => $quoteAmount,
                'credit_limit' => $creditLimit,
            ]
        );
    }

    /**
     * Create exception for duplicate quote
     *
     * @return static
     */
    public static function duplicateQuoteNumber(string $quoteNumber): self
    {
        return new static(
            "Quote number {$quoteNumber} already exists",
            'duplicate_quote_number',
            ['quote_number' => $quoteNumber]
        );
    }

    /**
     * Convert to array format for API responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'business_rule' => $this->businessRule,
            'context' => $this->getContext(),
            'type' => $this->getErrorType(),
            'code' => $this->getCode(),
        ];
    }
}
