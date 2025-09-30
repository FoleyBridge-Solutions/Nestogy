<?php

namespace App\Domains\PhysicalMail\Services;

use App\Domains\PhysicalMail\Exceptions\PostGridException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PostGridClient
{
    protected string $baseUrl = 'https://api.postgrid.com/print-mail/v1';

    private bool $testMode;

    private string $apiKey;

    public function __construct()
    {
        $this->testMode = config('physical_mail.postgrid.test_mode', true);
        $this->apiKey = $this->testMode
            ? config('physical_mail.postgrid.test_key')
            : config('physical_mail.postgrid.live_key');

        if (! $this->apiKey) {
            throw new PostGridException('PostGrid API key not configured', 0, 'configuration_error');
        }
    }

    /**
     * Get headers for PostGrid API
     */
    protected function getHeaders(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Check if in test mode
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * Generic send method for all mail types
     */
    public function send(string $type, array $data, ?string $idempotencyKey = null): array
    {
        $endpoint = '/'.Str::plural(strtolower($type));

        $headers = [];
        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->post($endpoint, $data, $headers);
    }

    /**
     * Create a resource
     */
    public function create(string $resource, array $data): array
    {
        return $this->post("/{$resource}", $data);
    }

    /**
     * Get a resource
     */
    public function getResource(string $resource, string $id): array
    {
        return $this->get("/{$resource}/{$id}");
    }

    /**
     * List resources
     */
    public function list(string $resource, array $params = []): array
    {
        return $this->get("/{$resource}", $params);
    }

    /**
     * Cancel an order
     */
    public function cancel(string $resource, string $id): array
    {
        return $this->delete("/{$resource}/{$id}");
    }

    /**
     * Progress a test order (test mode only)
     */
    public function progressTest(string $resource, string $id): array
    {
        if (! $this->testMode) {
            throw new PostGridException('Can only progress test orders', 0, 'test_mode_required');
        }

        return $this->post("/{$resource}/{$id}/progressions");
    }

    // Specific methods for each resource type

    /**
     * Create a letter
     */
    public function createLetter(array $data, ?string $idempotencyKey = null): array
    {
        return $this->send('letters', $data, $idempotencyKey);
    }

    /**
     * Get a letter
     */
    public function getLetter(string $id): array
    {
        return $this->getResource('letters', $id);
    }

    /**
     * Cancel a letter
     */
    public function cancelLetter(string $id): array
    {
        return $this->cancel('letters', $id);
    }

    /**
     * Create a postcard
     */
    public function createPostcard(array $data, ?string $idempotencyKey = null): array
    {
        return $this->send('postcards', $data, $idempotencyKey);
    }

    /**
     * Create a cheque
     */
    public function createCheque(array $data, ?string $idempotencyKey = null): array
    {
        return $this->send('cheques', $data, $idempotencyKey);
    }

    /**
     * Create a contact
     */
    public function createContact(array $data): array
    {
        return $this->create('contacts', $data);
    }

    /**
     * Get a contact
     */
    public function getContact(string $id): array
    {
        return $this->getResource('contacts', $id);
    }

    /**
     * List contacts
     */
    public function listContacts(array $params = []): array
    {
        return $this->list('contacts', $params);
    }

    /**
     * Create a template
     */
    public function createTemplate(array $data): array
    {
        return $this->create('templates', $data);
    }

    /**
     * Get a template
     */
    public function getTemplate(string $id): array
    {
        return $this->getResource('templates', $id);
    }

    /**
     * List templates
     */
    public function listTemplates(array $params = []): array
    {
        return $this->list('templates', $params);
    }

    /**
     * Create a webhook
     */
    public function createWebhook(array $data): array
    {
        return $this->create('webhooks', $data);
    }

    /**
     * List webhooks
     */
    public function listWebhooks(array $params = []): array
    {
        return $this->list('webhooks', $params);
    }

    /**
     * Delete a webhook
     */
    public function deleteWebhook(string $id): array
    {
        return $this->delete("/webhooks/{$id}");
    }

    /**
     * Create a bank account
     */
    public function createBankAccount(array $data): array
    {
        return $this->create('bank_accounts', $data);
    }

    /**
     * Get a bank account
     */
    public function getBankAccount(string $id): array
    {
        return $this->getResource('bank_accounts', $id);
    }

    /**
     * Search orders with advanced query
     */
    public function searchOrders(string $resource, array $search, int $skip = 0, int $limit = 10): array
    {
        $params = [
            'skip' => $skip,
            'limit' => $limit,
            'search' => json_encode($search),
        ];

        return $this->list($resource, $params);
    }

    // HTTP Methods

    /**
     * Send GET request
     */
    protected function get(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get($this->baseUrl.$endpoint, $params);

        return $this->handleResponse($response);
    }

    /**
     * Send POST request
     */
    protected function post(string $endpoint, array $data = [], array $additionalHeaders = []): array
    {
        $headers = array_merge($this->getHeaders(), $additionalHeaders);

        $response = Http::withHeaders($headers)
            ->post($this->baseUrl.$endpoint, $data);

        return $this->handleResponse($response);
    }

    /**
     * Send DELETE request
     */
    protected function delete(string $endpoint): array
    {
        $response = Http::withHeaders($this->getHeaders())
            ->delete($this->baseUrl.$endpoint);

        return $this->handleResponse($response);
    }

    /**
     * Handle API response
     */
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
