<?php

namespace App\Models\Traits;

use App\Domains\PhysicalMail\Services\PostGridClient;

trait HasPostGridIntegration
{
    /**
     * Sync this model with PostGrid
     */
    public function syncWithPostGrid(): void
    {
        if (!$this->postgrid_id) {
            $client = app(PostGridClient::class);
            $response = $client->create($this->getPostGridResource(), $this->toPostGridArray());
            $this->update(['postgrid_id' => $response['id']]);
        }
    }

    /**
     * Get the PostGrid resource name for this model
     */
    abstract protected function getPostGridResource(): string;

    /**
     * Convert this model to PostGrid API format
     */
    abstract public function toPostGridArray(): array;
}