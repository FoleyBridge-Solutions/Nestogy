<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Services\NavigationService;

class FluxDomainSidebarSimple extends Component
{
    public $activeDomain;
    public $activeItem;
    public $mobile;
    public $selectedClient;

    public function __construct($activeDomain = null, $activeItem = null, $mobile = false)
    {
        $this->activeDomain = $activeDomain;
        $this->activeItem = $activeItem;
        $this->mobile = $mobile ?? false;
        $this->selectedClient = NavigationService::getSelectedClient();
    }

    public function render()
    {
        return view('components.flux-domain-sidebar-simple');
    }
}