<?php

namespace App\Domains\Email\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailException extends Exception
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
        $this->userMessage = $userMessage ?: 'An error occurred while processing your email request.';
        $this->statusCode = $statusCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

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

        return response()->view('errors.'.$this->statusCode, [
            'message' => $this->userMessage,
            'statusCode' => $this->statusCode,
        ], $this->statusCode);
    }
}

class EmailPermissionException extends EmailException
{
    public function __construct(string $action, array $context = [])
    {
        parent::__construct(
            "Permission denied for email action: {$action}",
            403,
            null,
            array_merge($context, ['action' => $action]),
            'You do not have permission to perform this action.',
            403
        );
    }
}
