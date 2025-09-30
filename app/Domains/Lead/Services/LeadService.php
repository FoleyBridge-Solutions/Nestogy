<?php

namespace App\Domains\Lead\Services;

use App\Domains\Client\Services\ClientBaseService;
use App\Domains\Lead\Models\Lead;

class LeadService extends ClientBaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = Lead::class;
        $this->defaultEagerLoad = ['leadSource', 'assignedUser', 'client'];
        $this->searchableFields = ['first_name', 'last_name', 'email', 'company_name', 'phone'];
    }
}
