<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ContractException;
use Symfony\Component\HttpFoundation\Response;

/**
 * ContractErrorHandler Middleware
 * 
 * Comprehensive error handling middleware for contract operations
 * with logging, user-friendly responses, and recovery mechanisms.
 */
class ContractErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ContractException $e) {
            // Contract-specific exceptions are already handled
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->handleModelNotFound($request, $e);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->handleValidationException($request, $e);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->handleAuthorizationException($request, $e);
        } catch (\Exception $e) {
            return $this->handleGenericException($request, $e);
        }
    }

    /**
     * Handle model not found exceptions
     */
    protected function handleModelNotFound(Request $request, $exception): Response
    {
        Log::warning('Contract resource not found', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'exception' => $exception->getMessage(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
                'error_code' => 'RESOURCE_NOT_FOUND',
            ], 404);
        }

        return response()->view('errors.404', [
            'message' => 'The requested contract or resource was not found.',
        ], 404);
    }

    /**
     * Handle validation exceptions
     */
    protected function handleValidationException(Request $request, $exception): Response
    {
        Log::info('Contract validation failed', [
            'url' => $request->fullUrl(),
            'errors' => $exception->errors(),
            'user_id' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'The provided data is invalid.',
                'errors' => $exception->errors(),
                'error_code' => 'VALIDATION_FAILED',
            ], 422);
        }

        return redirect()->back()
            ->withErrors($exception->errors())
            ->withInput()
            ->with('error', 'Please correct the highlighted errors and try again.');
    }

    /**
     * Handle authorization exceptions
     */
    protected function handleAuthorizationException(Request $request, $exception): Response
    {
        Log::warning('Contract authorization failed', [
            'url' => $request->fullUrl(),
            'user_id' => auth()->id(),
            'exception' => $exception->getMessage(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'error_code' => 'ACCESS_DENIED',
            ], 403);
        }

        return response()->view('errors.403', [
            'message' => 'You do not have permission to access this contract or perform this action.',
        ], 403);
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Request $request, $exception): Response
    {
        Log::error('Unexpected contract error', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again or contact support.',
                'error_code' => 'INTERNAL_ERROR',
                'debug' => config('app.debug') ? [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ] : null,
            ], 500);
        }

        return response()->view('errors.500', [
            'message' => 'An unexpected error occurred while processing your contract request.',
            'debug' => config('app.debug') ? $exception : null,
        ], 500);
    }

    /**
     * Determine if the exception should be reported
     */
    protected function shouldReport($exception): bool
    {
        // Don't report validation exceptions or 404s
        return !($exception instanceof \Illuminate\Validation\ValidationException ||
                 $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException);
    }

    /**
     * Get sanitized request data for logging
     */
    protected function getSanitizedRequestData(Request $request): array
    {
        $data = $request->all();
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'signature_data'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }
}