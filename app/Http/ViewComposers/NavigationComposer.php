<?php

namespace App\Http\ViewComposers;

use App\Domains\Core\Services\Navigation\NavigationContext;
use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view): void
    {
        $activeDomain = NavigationContext::getCurrentDomain();
        $company = auth()->user()?->company;

        $view->with([
            'activeDomain' => $activeDomain,
            'sidebarContext' => $activeDomain,
            'activeItem' => null,
            'breadcrumbs' => [],
            'currentCompany' => $company,
        ]);
    }
}
