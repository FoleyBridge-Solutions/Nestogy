<?php

namespace App\Domains\Financial\Exceptions;

use Exception;

/**
 * Exception thrown when user lacks permission to access/modify a quote
 */
class QuotePermissionException extends FinancialException
{
    /**
     * Quote ID user tried to access
     *
     * @var int|null
     */
    protected $quoteId;

    /**
     * User ID who attempted the action
     *
     * @var int|null
     */
    protected $userId;

    /**
     * Action that was attempted
     *
     * @var string|null
     */
    protected $action;

    /**
     * Error type for API responses
     *
     * @var string
     */
    protected $errorType = 'permission_error';

    /**
     * Create a new quote permission exception
     */
    public function __construct(
        string $message = 'Insufficient permissions for quote operation',
        ?int $quoteId = null,
        ?int $userId = null,
        ?string $action = null,
        int $code = 403,
        ?Exception $previous = null
    ) {
        $this->quoteId = $quoteId;
        $this->userId = $userId;
        $this->action = $action;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the quote ID
     */
    public function getQuoteId(): ?int
    {
        return $this->quoteId;
    }

    /**
     * Get the user ID
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get the attempted action
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Create exception for viewing quote
     *
     * @return static
     */
    public static function cannotView(int $quoteId, ?int $userId = null): self
    {
        return new static(
            "You do not have permission to view quote {$quoteId}",
            $quoteId,
            $userId,
            'view'
        );
    }

    /**
     * Create exception for editing quote
     *
     * @return static
     */
    public static function cannotEdit(int $quoteId, ?int $userId = null): self
    {
        return new static(
            "You do not have permission to edit quote {$quoteId}",
            $quoteId,
            $userId,
            'edit'
        );
    }

    /**
     * Create exception for deleting quote
     *
     * @return static
     */
    public static function cannotDelete(int $quoteId, ?int $userId = null): self
    {
        return new static(
            "You do not have permission to delete quote {$quoteId}",
            $quoteId,
            $userId,
            'delete'
        );
    }

    /**
     * Create exception for company mismatch
     *
     * @return static
     */
    public static function companyMismatch(int $quoteId, ?int $userId = null): self
    {
        return new static(
            "Quote {$quoteId} belongs to a different company",
            $quoteId,
            $userId,
            'company_check'
        );
    }

    /**
     * Create exception for status-based restriction
     *
     * @return static
     */
    public static function statusRestriction(int $quoteId, string $status, string $action, ?int $userId = null): self
    {
        return new static(
            "Cannot {$action} quote {$quoteId} because it is {$status}",
            $quoteId,
            $userId,
            $action
        );
    }

    /**
     * Convert to array format for API responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'quote_id' => $this->quoteId,
            'user_id' => $this->userId,
            'action' => $this->action,
            'type' => $this->getErrorType(),
            'context' => $this->getContext(),
            'code' => $this->getCode(),
        ];
    }
}
