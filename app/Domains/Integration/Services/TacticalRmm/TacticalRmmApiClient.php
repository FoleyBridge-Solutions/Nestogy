<?php

namespace App\Domains\Integration\Services\TacticalRmm;

use App\Domains\Integration\Models\RmmIntegration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tactical RMM API Client
 *
 * Handles HTTP communication with Tactical RMM API.
 * Provides methods for authentication and API calls.
 */
class TacticalRmmApiClient
{
    protected RmmIntegration $integration;

    protected string $baseUrl;

    protected string $apiKey;

    protected int $timeout;

    protected int $retryTimes;

    public function __construct(RmmIntegration $integration)
    {
        $this->integration = $integration;
        $this->baseUrl = rtrim($integration->api_url, '/');
        $this->apiKey = $integration->api_key;
        $this->timeout = 30; // 30 seconds timeout
        $this->retryTimes = 3; // Retry failed requests 3 times
    }

    /**
     * Make a GET request to the API.
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->makeRequest('GET', $endpoint, [], $params);
    }

    /**
     * Make a POST request to the API.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Make a PUT request to the API.
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PUT', $endpoint, $data);
    }

    /**
     * Make a PATCH request to the API.
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PATCH', $endpoint, $data);
    }

    /**
     * Make a DELETE request to the API.
     */
    public function delete(string $endpoint): array
    {
        return $this->makeRequest('DELETE', $endpoint);
    }

    /**
     * Make a request to the Tactical RMM API.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], array $params = []): array
    {
        try {
            $url = $this->baseUrl.'/'.ltrim($endpoint, '/');

            Log::debug('TacticalRMM API Request', [
                'method' => $method,
                'url' => $url,
                'integration_id' => $this->integration->id,
                'has_data' => ! empty($data),
                'has_params' => ! empty($params),
            ]);

            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->retry($this->retryTimes, 1000) // Retry with 1 second delay
                ->when($method === 'GET', function ($http) use ($params) {
                    return $http->withOptions(['query' => $params]);
                })
                ->when(in_array($method, ['POST', 'PUT', 'PATCH']), function ($http) use ($data) {
                    return $http->withBody(json_encode($data), 'application/json');
                })
                ->send($method, $url);

            return $this->handleResponse($response, $endpoint);

        } catch (\Exception $e) {
            Log::error('TacticalRMM API Request Failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'integration_id' => $this->integration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Handle API response and format it consistently.
     */
    protected function handleResponse(Response $response, string $endpoint): array
    {
        $statusCode = $response->status();
        $body = $response->body();

        Log::debug('TacticalRMM API Response', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'integration_id' => $this->integration->id,
            'response_size' => strlen($body),
        ]);

        // Handle successful responses
        if ($response->successful()) {
            $data = $response->json();

            // Log raw response if JSON parsing fails
            if ($data === null && ! empty($body)) {
                Log::warning('TacticalRMM API: JSON parsing failed', [
                    'endpoint' => $endpoint,
                    'integration_id' => $this->integration->id,
                    'response_body' => substr($body, 0, 1000), // First 1000 chars
                    'content_type' => $response->header('Content-Type'),
                ]);
            }

            return [
                'success' => true,
                'data' => $data,
                'status_code' => $statusCode,
                'headers' => $response->headers(),
                'raw_body' => $data === null ? $body : null, // Include raw body if JSON failed
            ];
        }

        // Handle error responses
        $errorData = $response->json() ?: ['message' => 'Unknown error'];
        $errorMessage = $this->getErrorMessage($statusCode, $errorData);

        Log::warning('TacticalRMM API Error Response', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'error_data' => $errorData,
            'integration_id' => $this->integration->id,
        ]);

        return [
            'success' => false,
            'error' => $errorMessage,
            'status_code' => $statusCode,
            'data' => $errorData,
        ];
    }

    /**
     * Get appropriate error message based on status code.
     */
    protected function getErrorMessage(int $statusCode, array $errorData): string
    {
        $message = $errorData['message'] ?? $errorData['detail'] ?? 'API request failed';

        switch ($statusCode) {
            case 401:
                return 'Authentication failed. Please check your API key.';
            case 403:
                return 'Access forbidden. Insufficient permissions.';
            case 404:
                return 'Resource not found.';
            case 429:
                return 'Rate limit exceeded. Please try again later.';
            case 500:
                return 'Internal server error on Tactical RMM.';
            case 502:
            case 503:
            case 504:
                return 'Tactical RMM server is temporarily unavailable.';
            default:
                return $message;
        }
    }

    /**
     * Test API connectivity.
     */
    public function testConnection(): array
    {
        try {
            // Test connection using the root endpoint or dashboard info
            $response = $this->get('/core/dashinfo/');

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'data' => [
                        'version' => $response['data']['version'] ?? 'Unknown',
                        'server_time' => $response['data']['server_time'] ?? null,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Connection test failed',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get API rate limit headers from last response.
     */
    public function getRateLimitInfo(): array
    {
        // This would be implemented based on Tactical RMM's rate limiting headers
        // For now, return empty array as Tactical RMM may not implement rate limiting
        return [
            'limit' => null,
            'remaining' => null,
            'reset' => null,
        ];
    }

    /**
     * Get formatted API URL for display.
     */
    public function getApiUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Check if credentials are valid.
     */
    public function hasValidCredentials(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->apiKey);
    }

    /**
     * Set custom timeout for specific requests.
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Set custom retry count for specific requests.
     */
    public function setRetryTimes(int $times): self
    {
        $this->retryTimes = $times;

        return $this;
    }

    /**
     * Get integration instance.
     */
    public function getIntegration(): RmmIntegration
    {
        return $this->integration;
    }
}
