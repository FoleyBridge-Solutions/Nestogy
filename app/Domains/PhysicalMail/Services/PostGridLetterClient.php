<?php

namespace App\Domains\PhysicalMail\Services;

class PostGridLetterClient
{
    public function __construct(private PostGridClient $client)
    {
    }

    public function create(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->send('letters', $data, $idempotencyKey);
    }

    public function get(string $id): array
    {
        return $this->client->getResource('letters', $id);
    }

    public function cancel(string $id): array
    {
        return $this->client->cancel('letters', $id);
    }
}
