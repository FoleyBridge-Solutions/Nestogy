<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Standardized API Response Helper
 * Provides consistent response format across the application
 */
class ApiResponse
{
    /**
     * Create a success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    public static function success(
        $data = null,
        string $message = 'Operation completed successfully',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data instanceof JsonResource || $data instanceof ResourceCollection 
                ? $data->toArray(request()) 
                : $data;
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @param array $context
     * @param array $headers
     * @return JsonResponse
     */
    public static function error(
        string $message = 'An error occurred',
        int $statusCode = 400,
        array $errors = [],
        array $context = [],
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($context)) {
            $response['context'] = $context;
        }

        // Add request ID if available
        if (request()->header('X-Request-ID')) {
            $response['request_id'] = request()->header('X-Request-ID');
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Create a validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): JsonResponse {
        return static::error($message, 422, $errors, ['type' => 'validation_error']);
    }

    /**
     * Create a not found response
     *
     * @param string $resource
     * @param mixed $identifier
     * @return JsonResponse
     */
    public static function notFound(
        string $resource = 'Resource',
        $identifier = null
    ): JsonResponse {
        $message = $identifier 
            ? "{$resource} with identifier '{$identifier}' not found"
            : "{$resource} not found";

        return static::error($message, 404, [], ['type' => 'not_found_error']);
    }

    /**
     * Create an unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(
        string $message = 'Unauthorized access'
    ): JsonResponse {
        return static::error($message, 401, [], ['type' => 'unauthorized_error']);
    }

    /**
     * Create a forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(
        string $message = 'Access forbidden'
    ): JsonResponse {
        return static::error($message, 403, [], ['type' => 'forbidden_error']);
    }

    /**
     * Create a server error response
     *
     * @param string $message
     * @param array $context
     * @return JsonResponse
     */
    public static function serverError(
        string $message = 'Internal server error',
        array $context = []
    ): JsonResponse {
        return static::error($message, 500, [], array_merge($context, ['type' => 'server_error']));
    }

    /**
     * Create a created response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function created(
        $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return static::success($data, $message, 201);
    }

    /**
     * Create an updated response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function updated(
        $data = null,
        string $message = 'Resource updated successfully'
    ): JsonResponse {
        return static::success($data, $message, 200);
    }

    /**
     * Create a deleted response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function deleted(
        string $message = 'Resource deleted successfully'
    ): JsonResponse {
        return static::success(null, $message, 200);
    }

    /**
     * Create a no content response
     *
     * @return JsonResponse
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Create a paginated response
     *
     * @param ResourceCollection $collection
     * @param string $message
     * @return JsonResponse
     */
    public static function paginated(
        ResourceCollection $collection,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return static::success($collection, $message);
    }

    /**
     * Create a response with custom metadata
     *
     * @param mixed $data
     * @param array $meta
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function withMeta(
        $data,
        array $meta,
        string $message = 'Operation completed successfully',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data instanceof JsonResource || $data instanceof ResourceCollection 
                ? $data->toArray(request()) 
                : $data,
            'meta' => array_merge([
                'timestamp' => now()->toISOString(),
            ], $meta),
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Create a bulk operation response
     *
     * @param array $results
     * @param string $operation
     * @return JsonResponse
     */
    public static function bulk(
        array $results,
        string $operation = 'bulk operation'
    ): JsonResponse {
        $successful = collect($results)->where('success', true)->count();
        $failed = collect($results)->where('success', false)->count();
        $total = count($results);

        $message = "Bulk {$operation} completed: {$successful} successful, {$failed} failed out of {$total} total";

        return static::withMeta($results, [
            'bulk_operation' => [
                'total' => $total,
                'successful' => $successful,
                'failed' => $failed,
                'operation' => $operation,
            ]
        ], $message);
    }

    /**
     * Create an async operation response
     *
     * @param string $jobId
     * @param string $status
     * @param string $message
     * @return JsonResponse
     */
    public static function async(
        string $jobId,
        string $status = 'queued',
        string $message = 'Operation queued for processing'
    ): JsonResponse {
        return static::withMeta(null, [
            'async_operation' => [
                'job_id' => $jobId,
                'status' => $status,
                'check_url' => route('jobs.status', $jobId),
            ]
        ], $message, 202);
    }

    /**
     * Create a rate limited response
     *
     * @param int $retryAfter
     * @param string $message
     * @return JsonResponse
     */
    public static function rateLimited(
        int $retryAfter = 60,
        string $message = 'Rate limit exceeded'
    ): JsonResponse {
        return static::error($message, 429, [], [
            'type' => 'rate_limit_error',
            'retry_after' => $retryAfter,
        ], ['Retry-After' => $retryAfter]);
    }

    /**
     * Create a maintenance mode response
     *
     * @param string $message
     * @param string $estimatedTime
     * @return JsonResponse
     */
    public static function maintenance(
        string $message = 'Service temporarily unavailable for maintenance',
        string $estimatedTime = null
    ): JsonResponse {
        $context = ['type' => 'maintenance_error'];
        
        if ($estimatedTime) {
            $context['estimated_completion'] = $estimatedTime;
        }

        return static::error($message, 503, [], $context);
    }
}