<?php

namespace App\Domains\PhysicalMail\Services;

class PostGridTemplateClient
{
    public function __construct(private PostGridClient $client)
    {
    }

    public function create(array $data): array
    {
        return $this->client->create('templates', $data);
    }

    public function get(string $id): array
    {
        return $this->client->getResource('templates', $id);
    }

    public function list(array $params = []): array
    {
        return $this->client->list('templates', $params);
    }
}
