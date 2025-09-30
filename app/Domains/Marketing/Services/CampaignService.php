<?php

namespace App\Domains\Marketing\Services;

use App\Domains\Client\Services\ClientBaseService;
use App\Domains\Marketing\Models\MarketingCampaign;

class CampaignService extends ClientBaseService
{
    protected function initializeService(): void
    {
        $this->modelClass = MarketingCampaign::class;
        $this->defaultEagerLoad = ['createdBy', 'sequences'];
        $this->searchableFields = ['name', 'description'];
    }
}
