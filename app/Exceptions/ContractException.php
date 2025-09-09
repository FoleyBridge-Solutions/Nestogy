<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ContractException
 * 
 * Custom exception class for contract-related errors with comprehensive
 * error handling, logging, and user-friendly messaging.
 */
class ContractException extends Exception
{
    protected array $context;
    protected string $userMessage;
    protected int $statusCode;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = [],
        string $userMessage = '',
        int $statusCode = 500
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->context = $context;
        $this->userMessage = $userMessage ?: 'An error occurred while processing your contract request.';
        $this->statusCode = $statusCode;
    }

    /**
     * Get the exception context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set additional context
     */
    public function setContext(array $context): self
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }

    /**
     * Render the exception into an HTTP response
     */
    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->userMessage,
                'error' => config('app.debug') ? [
                    'message' => $this->getMessage(),
                    'file' => $this->getFile(),
                    'line' => $this->getLine(),
                    'context' => $this->context,
                ] : null,
            ], $this->statusCode);
        }

        return response()->view('errors.contract', [
            'message' => $this->userMessage,
            'statusCode' => $this->statusCode,
        ], $this->statusCode);
    }

    /**
     * Report the exception to logging system
     */
    public function report(): void
    {
        \Log::error('Contract Exception: ' . $this->getMessage(), [
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'user_id' => auth()->id(),
            'request_id' => request()->header('X-Request-ID'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

/**
 * Contract validation exception
 */
class ContractValidationException extends ContractException
{
    public function __construct(string $message, array $errors = [], array $context = [])
    {
        parent::__construct(
            $message,
            422,
            null,
            array_merge($context, ['validation_errors' => $errors]),
            'The provided contract data is invalid.',
            422
        );
    }

    public function getValidationErrors(): array
    {
        return $this->context['validation_errors'] ?? [];
    }
}

/**
 * Contract not found exception
 */
class ContractNotFoundException extends ContractException
{
    public function __construct(int $contractId, array $context = [])
    {
        parent::__construct(
            "Contract with ID {$contractId} not found",
            404,
            null,
            array_merge($context, ['contract_id' => $contractId]),
            'The requested contract could not be found.',
            404
        );
    }
}

/**
 * Contract status exception
 */
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

/**
 * Contract signature exception
 */
class ContractSignatureException extends ContractException
{
    public function __construct(string $message, array $context = [])
    {
        parent::__construct(
            $message,
            400,
            null,
            $context,
            'An error occurred during the signature process.',
            400
        );
    }
}

/**
 * Contract generation exception
 */
class ContractGenerationException extends ContractException
{
    public function __construct(string $message, array $context = [])
    {
        parent::__construct(
            $message,
            500,
            null,
            $context,
            'Failed to generate contract document.',
            500
        );
    }
}

/**
 * Contract conversion exception
 */
class ContractConversionException extends ContractException
{
    public function __construct(string $fromType, string $toType, string $reason, array $context = [])
    {
        parent::__construct(
            "Failed to convert {$fromType} to {$toType}: {$reason}",
            400,
            null,
            array_merge($context, [
                'from_type' => $fromType,
                'to_type' => $toType,
                'reason' => $reason,
            ]),
            "Unable to convert {$fromType} to {$toType}.",
            400
        );
    }
}

/**
 * Contract permission exception
 */
class ContractPermissionException extends ContractException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct(
            "Permission denied for action: {$action}",
            403,
            null,
            array_merge($context, ['action' => $action]),
            'You do not have permission to perform this action.',
            403
        );
    }
}

/**
 * Contract business rule exception
 */
class ContractBusinessRuleException extends ContractException
{
    public function __construct(string $rule, string $reason, array $context = [])
    {
        parent::__construct(
            "Business rule violation: {$rule} - {$reason}",
            400,
            null,
            array_merge($context, [
                'rule' => $rule,
                'reason' => $reason,
            ]),
            $reason,
            400
        );
    }
}

/**
 * Contract integration exception
 */
class ContractIntegrationException extends ContractException
{
    public function __construct(string $service, string $message, array $context = [])
    {
        parent::__construct(
            "Integration error with {$service}: {$message}",
            502,
            null,
            array_merge($context, [
                'service' => $service,
                'integration_message' => $message,
            ]),
            'A third-party service error occurred. Please try again later.',
            502
        );
    }
}