<?php

namespace App\Domains\PhysicalMail\Services;

class PostGridWebhookClient
{
    public function __construct(private PostGridClient $client)
    {
    }

    public function create(array $data): array
    {
        return $this->client->create('webhooks', $data);
    }

    public function list(array $params = []): array
    {
        return $this->client->list('webhooks', $params);
    }

    public function delete(string $id): array
    {
        return $this->client->delete("/webhooks/{$id}");
    }
}
