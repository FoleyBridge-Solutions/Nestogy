<?php

namespace App\Domains\PhysicalMail\Services;

class PostGridContactClient
{
    public function __construct(private PostGridClient $client)
    {
    }

    public function create(array $data): array
    {
        return $this->client->create('contacts', $data);
    }

    public function get(string $id): array
    {
        return $this->client->getResource('contacts', $id);
    }

    public function list(array $params = []): array
    {
        return $this->client->list('contacts', $params);
    }
}
