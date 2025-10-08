<?php

namespace App\Domains\PhysicalMail\Services;

class PostGridPostcardClient
{
    public function __construct(private PostGridClient $client)
    {
    }

    public function create(array $data, ?string $idempotencyKey = null): array
    {
        return $this->client->send('postcards', $data, $idempotencyKey);
    }
}
