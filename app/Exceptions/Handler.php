<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Global Exception Handler for Nestogy MSP Platform
 *
 * Handles all exceptions with proper error responses, logging, and user-friendly messages
 * while maintaining security and providing detailed information for debugging.
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        TokenMismatchException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'stripe_token',
        'card_number',
        'cvv',
        'pin',
        'secret',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic for all exceptions
            $this->logExceptionWithContext($e);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->renderException($e, $request);
        });
    }

    /**
     * Enhanced exception reporting with context
     */
    protected function logExceptionWithContext(Throwable $exception): void
    {
        // Skip if already handled by custom exception classes
        if (method_exists($exception, 'report') && $exception->report() !== false) {
            return;
        }

        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => request()->url(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => Auth::id(),
            'company_id' => Auth::user()?->company_id,
            'request_id' => request()->header('X-Request-ID') ?? uniqid(),
        ];

        // Add query parameters (sanitized)
        $queryParams = request()->query();
        $context['query_params'] = $this->sanitizeLogData($queryParams);

        // Add POST data (sanitized) for non-GET requests
        if (! request()->isMethod('GET') && ! empty(request()->input())) {
            $context['request_data'] = $this->sanitizeLogData(request()->input());
        }

        // Add database query info for query exceptions
        if ($exception instanceof QueryException) {
            $context['sql'] = [
                'query' => $exception->getSql(),
                'bindings' => $exception->getBindings(),
                'error_code' => $exception->getCode(),
            ];
        }

        Log::error('Application Exception', $context);
    }

    /**
     * Render an exception into an HTTP response
     */
    protected function renderException(Throwable $exception, Request $request): ?Response
    {
        // Let custom exceptions handle themselves
        if (method_exists($exception, 'render')) {
            $response = $exception->render($request);
            if ($response) {
                return $response;
            }
        }

        // Handle specific exception types
        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception, $request);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->handleAuthenticationException($exception, $request);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->handleAuthorizationException($exception, $request);
        }

        if ($exception instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($exception, $request);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->handleNotFoundHttpException($exception, $request);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->handleMethodNotAllowedException($exception, $request);
        }

        if ($exception instanceof TokenMismatchException) {
            return $this->handleTokenMismatchException($exception, $request);
        }

        if ($exception instanceof QueryException) {
            return $this->handleDatabaseException($exception, $request);
        }

        if ($exception instanceof HttpException) {
            return $this->handleHttpException($exception, $request);
        }

        // Handle generic exceptions
        return $this->handleGenericException($exception, $request);
    }

    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(ValidationException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return redirect()->back()
            ->withErrors($exception->errors())
            ->withInput()
            ->with('error', 'Please correct the errors below.');
    }

    /**
     * Handle authentication exceptions
     */
    protected function handleAuthenticationException(AuthenticationException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        return redirect()->route('login')
            ->with('error', 'Please log in to access this page.');
    }

    /**
     * Handle authorization exceptions
     */
    protected function handleAuthorizationException(AuthorizationException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return back()->with('error', 'You do not have permission to perform this action.');
    }

    /**
     * Handle model not found exceptions
     */
    protected function handleModelNotFoundException(ModelNotFoundException $exception, Request $request): Response
    {
        $model = class_basename($exception->getModel());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => "{$model} not found.",
            ], 404);
        }

        return response()->view('errors.404', [
            'message' => "The requested {$model} could not be found.",
        ], 404);
    }

    /**
     * Handle 404 not found exceptions
     */
    protected function handleNotFoundHttpException(NotFoundHttpException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
            ], 404);
        }

        return response()->view('errors.404', [
            'message' => 'The requested page could not be found.',
        ], 404);
    }

    /**
     * Handle method not allowed exceptions
     */
    protected function handleMethodNotAllowedException(MethodNotAllowedHttpException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Method not allowed.',
            ], 405);
        }

        return response()->view('errors.405', [
            'message' => 'The requested method is not allowed.',
        ], 405);
    }

    /**
     * Handle CSRF token mismatch exceptions
     */
    protected function handleTokenMismatchException(TokenMismatchException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'CSRF token mismatch. Please refresh the page and try again.',
            ], 419);
        }

        return redirect()->back()
            ->with('error', 'Your session has expired. Please refresh the page and try again.');
    }

    /**
     * Handle database exceptions
     */
    protected function handleDatabaseException(QueryException $exception, Request $request): Response
    {
        $message = $this->getDatabaseErrorMessage($exception);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => config('app.debug') ? [
                    'sql' => $exception->getSql(),
                    'bindings' => $exception->getBindings(),
                    'code' => $exception->getCode(),
                ] : null,
            ], 500);
        }

        return back()->with('error', $message);
    }

    /**
     * Handle HTTP exceptions
     */
    protected function handleHttpException(HttpException $exception, Request $request): Response
    {
        $statusCode = $exception->getStatusCode();
        $message = $this->getHttpErrorMessage($statusCode);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        }

        return response()->view("errors.{$statusCode}", [
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Throwable $exception, Request $request): Response
    {
        $message = config('app.debug')
            ? $exception->getMessage()
            : 'An unexpected error occurred. Please try again later.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => config('app.debug') ? [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ] : null,
            ], 500);
        }

        return response()->view('errors.500', [
            'message' => $message,
        ], 500);
    }

    /**
     * Get user-friendly database error message
     */
    protected function getDatabaseErrorMessage(QueryException $exception): string
    {
        $errorCode = $exception->getCode();
        $message = $exception->getMessage();

        // Handle common database errors
        if (str_contains($message, 'Duplicate entry')) {
            return 'A record with this information already exists.';
        }

        if (str_contains($message, 'foreign key constraint')) {
            return 'This record cannot be deleted because it is referenced by other data.';
        }

        if (str_contains($message, 'Data too long')) {
            return 'The provided data is too long for the field.';
        }

        if (str_contains($message, 'cannot be null')) {
            return 'Required information is missing.';
        }

        if (str_contains($message, 'Deadlock found')) {
            return 'A temporary database conflict occurred. Please try again.';
        }

        if (str_contains($message, 'Connection refused') || str_contains($message, 'server has gone away')) {
            return 'Database connection error. Please try again later.';
        }

        return 'A database error occurred. Please try again later.';
    }

    /**
     * Get user-friendly HTTP error message
     */
    protected function getHttpErrorMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad request. Please check your input and try again.',
            401 => 'Authentication required.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The requested resource was not found.',
            405 => 'Method not allowed.',
            408 => 'Request timeout. Please try again.',
            413 => 'Request too large.',
            422 => 'The provided data was invalid.',
            429 => 'Too many requests. Please try again later.',
            500 => 'Internal server error. Please try again later.',
            502 => 'Bad gateway. Please try again later.',
            503 => 'Service temporarily unavailable. Please try again later.',
            504 => 'Gateway timeout. Please try again later.',
            default => 'An error occurred. Please try again later.',
        };
    }

    /**
     * Sanitize log data to remove sensitive information
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'token', 'api_key', 'stripe_token', 'card_number', 'cvv', 'pin',
            'secret', 'private_key', 'access_token', 'refresh_token',
            'ssn', 'social_security_number', 'credit_card', 'bank_account',
        ];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '[REDACTED]';
            }
        });

        return $data;
    }

    /**
     * Determine if the exception should be reported based on company settings
     */
    public function shouldReport(Throwable $e): bool
    {
        // Always report critical errors
        if ($e instanceof \Error || $e instanceof \ErrorException) {
            return true;
        }

        // Check if user has opted out of error reporting
        if (Auth::check() && Auth::user()->company?->error_reporting_disabled) {
            return false;
        }

        return parent::shouldReport($e);
    }

    /**
     * Get the default context for exception reporting
     */
    protected function context(): array
    {
        try {
            return array_filter([
                'userId' => Auth::id(),
                'companyId' => Auth::user()?->company_id,
                'environment' => app()->environment(),
                'version' => config('app.version', '1.0.0'),
                'requestId' => request()->header('X-Request-ID') ?? uniqid(),
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }
}
