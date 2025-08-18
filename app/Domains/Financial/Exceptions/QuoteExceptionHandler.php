<?php

namespace App\Domains\Financial\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handler for quote-related exceptions
 */
class QuoteExceptionHandler
{
    /**
     * Handle a quote exception and return appropriate response
     *
     * @param FinancialException $exception
     * @param Request|null $request
     * @return JsonResponse|Response
     */
    public static function handle(FinancialException $exception, ?Request $request = null)
    {
        // Log the exception if needed
        if ($exception->shouldLog()) {
            Log::log($exception->getLogLevel(), $exception->getMessage(), [
                'exception' => get_class($exception),
                'context' => $exception->getContext(),
                'trace' => $exception->getTraceAsString()
            ]);
        }

        // Determine if we should return JSON or HTML
        $wantsJson = $request && ($request->wantsJson() || $request->is('api/*'));

        if ($wantsJson) {
            return static::jsonResponse($exception);
        }

        return static::htmlResponse($exception);
    }

    /**
     * Create JSON response for API requests
     *
     * @param FinancialException $exception
     * @return JsonResponse
     */
    protected static function jsonResponse(FinancialException $exception): JsonResponse
    {
        $data = $exception->toArray();
        
        // Add timestamp
        $data['timestamp'] = now()->toISOString();
        
        // Add request ID if available
        if (request()->header('X-Request-ID')) {
            $data['request_id'] = request()->header('X-Request-ID');
        }

        return response()->json($data, $exception->getHttpStatusCode());
    }

    /**
     * Create HTML response for web requests
     *
     * @param FinancialException $exception
     * @return Response
     */
    protected static function htmlResponse(FinancialException $exception): Response
    {
        $statusCode = $exception->getHttpStatusCode();
        
        // Determine view based on status code
        $view = match($statusCode) {
            404 => 'errors.404',
            403 => 'errors.403',
            422 => 'errors.422',
            default => 'errors.500'
        };

        $data = [
            'exception' => $exception,
            'message' => $exception->getMessage(),
            'context' => $exception->getContext()
        ];

        return response()->view($view, $data, $statusCode);
    }

    /**
     * Map Laravel validation exception to QuoteValidationException
     *
     * @param \Illuminate\Validation\ValidationException $exception
     * @return QuoteValidationException
     */
    public static function fromValidationException(\Illuminate\Validation\ValidationException $exception): QuoteValidationException
    {
        return new QuoteValidationException(
            $exception->getMessage(),
            $exception->errors()
        );
    }

    /**
     * Map model not found exception to QuoteNotFoundException
     *
     * @param \Illuminate\Database\Eloquent\ModelNotFoundException $exception
     * @return QuoteNotFoundException
     */
    public static function fromModelNotFoundException(\Illuminate\Database\Eloquent\ModelNotFoundException $exception): QuoteNotFoundException
    {
        $model = $exception->getModel();
        $ids = $exception->getIds();
        
        if (strpos($model, 'Quote') !== false && !empty($ids)) {
            return QuoteNotFoundException::forId($ids[0]);
        }
        
        return new QuoteNotFoundException('Quote not found');
    }

    /**
     * Map authorization exception to QuotePermissionException
     *
     * @param \Illuminate\Auth\Access\AuthorizationException $exception
     * @return QuotePermissionException
     */
    public static function fromAuthorizationException(\Illuminate\Auth\Access\AuthorizationException $exception): QuotePermissionException
    {
        return new QuotePermissionException($exception->getMessage());
    }

    /**
     * Check if exception is a quote-related exception
     *
     * @param \Throwable $exception
     * @return bool
     */
    public static function isQuoteException(\Throwable $exception): bool
    {
        return $exception instanceof FinancialException;
    }

    /**
     * Convert any exception to a FinancialException if possible
     *
     * @param \Throwable $exception
     * @return FinancialException
     */
    public static function normalize(\Throwable $exception): FinancialException
    {
        if ($exception instanceof FinancialException) {
            return $exception;
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return static::fromValidationException($exception);
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return static::fromModelNotFoundException($exception);
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return static::fromAuthorizationException($exception);
        }

        // Default to service exception
        return new QuoteServiceException(
            $exception->getMessage(),
            'unknown_operation',
            ['original_exception' => get_class($exception)],
            (int)($exception->getCode() ?: 500),
            $exception
        );
    }

    /**
     * Get user-friendly error message
     *
     * @param FinancialException $exception
     * @return string
     */
    public static function getUserFriendlyMessage(FinancialException $exception): string
    {
        return match(get_class($exception)) {
            QuoteValidationException::class => 'Please check your input and try again.',
            QuoteNotFoundException::class => 'The requested quote could not be found.',
            QuotePermissionException::class => 'You do not have permission to perform this action.',
            QuoteBusinessException::class => 'This operation violates business rules.',
            QuoteServiceException::class => 'A system error occurred. Please try again later.',
            default => 'An unexpected error occurred.'
        };
    }

    /**
     * Get suggested actions for the user
     *
     * @param FinancialException $exception
     * @return array
     */
    public static function getSuggestedActions(FinancialException $exception): array
    {
        return match(get_class($exception)) {
            QuoteValidationException::class => [
                'Check all required fields are filled',
                'Verify the format of your input',
                'Contact support if the problem persists'
            ],
            QuoteNotFoundException::class => [
                'Check the quote ID or number',
                'Verify you have access to this quote',
                'Try searching for the quote in the list'
            ],
            QuotePermissionException::class => [
                'Contact your administrator for access',
                'Verify you are logged in correctly',
                'Check if the quote belongs to your company'
            ],
            QuoteBusinessException::class => [
                'Review the business rules',
                'Check quote status and conditions',
                'Contact support for clarification'
            ],
            default => [
                'Refresh the page and try again',
                'Contact technical support',
                'Check your internet connection'
            ]
        };
    }
}