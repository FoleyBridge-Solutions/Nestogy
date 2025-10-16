<?php

namespace App\Http\ViewComposers;

use App\Domains\Core\Services\Navigation\NavigationContext;
use App\Domains\Core\Services\Navigation\SidebarBuilder;
use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view): void
    {
        $activeDomain = NavigationContext::getCurrentDomain();
        $company = auth()->user()?->company;
        $selectedClient = NavigationContext::getSelectedClient();
        
        $sidebarBuilder = new SidebarBuilder($activeDomain);
        $sidebarConfig = $sidebarBuilder->build();

        $view->with([
            'activeDomain' => $activeDomain,
            'sidebarContext' => $activeDomain,
            'sidebarConfig' => $sidebarConfig,
            'selectedClient' => $selectedClient,
            'activeItem' => null,
            'breadcrumbs' => [],
            'currentCompany' => $company,
        ]);
    }
}
