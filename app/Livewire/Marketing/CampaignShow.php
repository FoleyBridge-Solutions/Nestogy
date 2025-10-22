<?php

namespace App\Livewire\Marketing;

use App\Domains\Marketing\Models\MarketingCampaign;
use Livewire\Component;

class CampaignShow extends Component
{
    public MarketingCampaign $campaign;
    public array $metrics;

    public function mount(MarketingCampaign $campaign, array $metrics)
    {
        $this->campaign = $campaign;
        $this->metrics = $metrics;
    }

    public function render()
    {
        return view('livewire.marketing.campaign-show');
    }
}
