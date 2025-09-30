<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Base Exception Class for Nestogy MSP Platform
 *
 * Provides common functionality for all custom exceptions including
 * context handling, user-friendly messaging, and proper HTTP responses.
 */
abstract class BaseException extends Exception
{
    protected array $context;

    protected string $userMessage;

    protected int $statusCode;

    protected string $domain;

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
        $this->userMessage = $userMessage ?: $this->getDefaultUserMessage();
        $this->statusCode = $statusCode;
        $this->domain = $this->getDomainName();
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
     * Get domain name
     */
    public function getDomain(): string
    {
        return $this->domain;
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
                'domain' => $this->domain,
                'error' => config('app.debug') ? [
                    'message' => $this->getMessage(),
                    'file' => $this->getFile(),
                    'line' => $this->getLine(),
                    'context' => $this->context,
                ] : null,
            ], $this->statusCode);
        }

        $errorView = $this->getErrorView();

        return response()->view($errorView, [
            'message' => $this->userMessage,
            'statusCode' => $this->statusCode,
            'domain' => $this->domain,
            'context' => config('app.debug') ? $this->context : [],
        ], $this->statusCode);
    }

    /**
     * Report the exception to logging system
     */
    public function report(): void
    {
        Log::error("{$this->domain} Exception: ".$this->getMessage(), [
            'exception' => get_class($this),
            'domain' => $this->domain,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()?->company_id,
            'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
        ]);
    }

    /**
     * Get the domain name from the exception class
     */
    protected function getDomainName(): string
    {
        $className = class_basename($this);

        return str_replace('Exception', '', $className);
    }

    /**
     * Get the default user message for this exception type
     */
    abstract protected function getDefaultUserMessage(): string;

    /**
     * Get the error view for this exception
     */
    protected function getErrorView(): string
    {
        $statusCode = $this->statusCode;
        $domain = strtolower($this->domain);

        // Try domain-specific error view first
        $domainView = "errors.{$domain}.{$statusCode}";
        if (view()->exists($domainView)) {
            return $domainView;
        }

        // Fallback to generic error view
        return "errors.{$statusCode}";
    }
}

/**
 * Business Logic Exception
 */
abstract class BusinessException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A business rule violation occurred.';
    }
}

/**
 * Validation Exception
 */
abstract class ValidationException extends BaseException
{
    protected array $validationErrors;

    public function __construct(
        string $message,
        array $validationErrors = [],
        array $context = [],
        string $userMessage = ''
    ) {
        $this->validationErrors = $validationErrors;

        parent::__construct(
            $message,
            422,
            null,
            array_merge($context, ['validation_errors' => $validationErrors]),
            $userMessage,
            422
        );
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    protected function getDefaultUserMessage(): string
    {
        return 'The provided data is invalid.';
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->userMessage,
                'errors' => $this->validationErrors,
                'domain' => $this->domain,
            ], 422);
        }

        return redirect()->back()
            ->withErrors($this->validationErrors)
            ->withInput()
            ->with('error', $this->userMessage);
    }
}

/**
 * Not Found Exception
 */
abstract class NotFoundException extends BaseException
{
    public function __construct(
        string $resource,
        mixed $identifier = null,
        array $context = []
    ) {
        $message = $identifier
            ? "{$resource} with identifier '{$identifier}' not found"
            : "{$resource} not found";

        parent::__construct(
            $message,
            404,
            null,
            array_merge($context, [
                'resource' => $resource,
                'identifier' => $identifier,
            ]),
            "The requested {$resource} could not be found.",
            404
        );
    }

    protected function getDefaultUserMessage(): string
    {
        return 'The requested resource could not be found.';
    }
}

/**
 * Permission Exception
 */
abstract class PermissionException extends BaseException
{
    public function __construct(
        string $action,
        ?string $resource = null,
        array $context = []
    ) {
        $message = $resource
            ? "Permission denied for action '{$action}' on resource '{$resource}'"
            : "Permission denied for action '{$action}'";

        parent::__construct(
            $message,
            403,
            null,
            array_merge($context, [
                'action' => $action,
                'resource' => $resource,
            ]),
            'You do not have permission to perform this action.',
            403
        );
    }

    protected function getDefaultUserMessage(): string
    {
        return 'You do not have permission to perform this action.';
    }
}

/**
 * Service Exception
 */
abstract class ServiceException extends BaseException
{
    protected function getDefaultUserMessage(): string
    {
        return 'A service error occurred. Please try again later.';
    }
}

/**
 * Integration Exception
 */
abstract class IntegrationException extends BaseException
{
    protected string $service;

    public function __construct(
        string $service,
        string $message,
        array $context = [],
        string $userMessage = ''
    ) {
        $this->service = $service;

        parent::__construct(
            "Integration error with {$service}: {$message}",
            502,
            null,
            array_merge($context, [
                'service' => $service,
                'integration_message' => $message,
            ]),
            $userMessage ?: 'A third-party service error occurred. Please try again later.',
            502
        );
    }

    public function getService(): string
    {
        return $this->service;
    }

    protected function getDefaultUserMessage(): string
    {
        return 'A third-party service error occurred. Please try again later.';
    }
}
