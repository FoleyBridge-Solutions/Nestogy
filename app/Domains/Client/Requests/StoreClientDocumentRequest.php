<?php

namespace App\Domains\Client\Requests;

use App\Http\Requests\BaseStoreRequest;
use App\Models\ClientDocument;

class StoreClientDocumentRequest extends BaseStoreRequest
{
    protected function getModelClass(): string
    {
        return ClientDocument::class;
    }

    protected function getValidationRules(): array
    {
        return $this->mergeRules(
            [
                'client_id' => $this->getClientValidationRule(),
                'category' => 'required|in:'.implode(',', array_keys(ClientDocument::getCategories())),
                'file' => 'required|file|max:51200', // 50MB max
                'expires_at' => 'nullable|date|after:today',
                'tags' => 'nullable|string|max:500',
                'is_confidential' => 'boolean',
            ],
            $this->getStandardTextRules()
        );
    }

    protected function getBooleanFields(): array
    {
        return ['is_confidential'];
    }

    protected function customMessages(): array
    {
        return [
            'file.max' => 'The file may not be larger than 50MB.',
            'expires_at.after' => 'The expiry date must be in the future.',
        ];
    }
}
