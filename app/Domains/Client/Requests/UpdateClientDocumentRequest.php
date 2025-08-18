<?php

namespace App\Domains\Client\Requests;

use App\Models\ClientDocument;
use App\Http\Requests\BaseUpdateRequest;

class UpdateClientDocumentRequest extends BaseUpdateRequest
{
    protected function getValidationRules(): array
    {
        return $this->mergeRules(
            [
                'client_id' => $this->getClientValidationRule(),
                'category' => 'required|in:' . implode(',', array_keys(ClientDocument::getCategories())),
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
    
    protected function getRouteParameterName(): string
    {
        return 'document';
    }
    
    protected function customMessages(): array
    {
        return [
            'expires_at.after' => 'The expiry date must be in the future.',
        ];
    }
}