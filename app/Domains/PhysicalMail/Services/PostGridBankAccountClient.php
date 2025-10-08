<?php

namespace App\Domains\PhysicalMail\Services;

class PostGridBankAccountClient
{
    public function __construct(private PostGridClient $client)
    {
    }

    public function create(array $data): array
    {
        return $this->client->create('bank_accounts', $data);
    }

    public function get(string $id): array
    {
        return $this->client->getResource('bank_accounts', $id);
    }
}
