<?php

namespace App\Domains\PhysicalMail\Services;

use App\Domains\PhysicalMail\Exceptions\PostGridException;
use Illuminate\Support\Facades\Http;

class PostGridClient
{
    protected string $baseUrl = 'https://api.postgrid.com/print-mail/v1';

    private bool $testMode;

    private string $apiKey;

    public function __construct(?bool $testMode = null, ?string $apiKey = null)
    {
        $this->testMode = $testMode ?? config('physical_mail.postgrid.test_mode', true);
        $this->apiKey = $apiKey ?? ($this->testMode
            ? config('physical_mail.postgrid.test_key')
            : config('physical_mail.postgrid.live_key'));

        if (! $this->apiKey) {
            throw new PostGridException('PostGrid API key not configured', 0, 'configuration_error');
        }
    }

    protected function getHeaders(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function send(string $type, array $data, ?string $idempotencyKey = null): array
    {
        $endpoint = '/'.str($type)->plural()->lower();

        $headers = [];
        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->post($endpoint, $data, $headers);
    }

    public function create(string $resource, array $data): array
    {
        return $this->post("/{$resource}", $data);
    }

    public function getResource(string $resource, string $id): array
    {
        return $this->get("/{$resource}/{$id}");
    }

    public function list(string $resource, array $params = []): array
    {
        return $this->get("/{$resource}", $params);
    }

    public function cancel(string $resource, string $id): array
    {
        return $this->delete("/{$resource}/{$id}");
    }

    public function progressTest(string $resource, string $id): array
    {
        if (! $this->testMode) {
            throw new PostGridException('Can only progress test orders', 0, 'test_mode_required');
        }

        return $this->post("/{$resource}/{$id}/progressions");
    }

    public function searchOrders(string $resource, array $search, int $skip = 0, int $limit = 10): array
    {
        $params = [
            'skip' => $skip,
            'limit' => $limit,
            'search' => json_encode($search),
        ];

        return $this->list($resource, $params);
    }

    protected function get(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl.$endpoint, $params);

        return $this->handleResponse($response);
    }

    protected function post(string $endpoint, array $data = [], array $additionalHeaders = []): array
    {
        $headers = array_merge($this->getHeaders(), $additionalHeaders);

        $response = Http::withHeaders($headers)
            ->post($this->baseUrl.$endpoint, $data);

        return $this->handleResponse($response);
    }

    protected function delete(string $endpoint): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->delete($this->baseUrl.$endpoint);

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $error = $response->json() ?? [];
        $message = $error['error']['message'] ?? 'Unknown error';
        $type = $error['error']['type'] ?? 'unknown_error';
        $code = $response->status();

        throw new PostGridException($message, $code, $type);
    }
}
