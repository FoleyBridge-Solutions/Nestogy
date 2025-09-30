<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Domains\Core\Services\NavigationService;

class NavigationComposer
{
    /**
     * Create a new navigation composer.
     */
    public function __construct(
        protected NavigationService $navigationService
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $activeDomain = $this->navigationService->getActiveDomain();
        $badgeCounts = $activeDomain ? $this->navigationService->getBadgeCounts($activeDomain) : [];
        
        // Get current user's company
        $company = auth()->user()?->company;
        
        $view->with([
            'activeDomain' => $activeDomain,
            'activeItem' => $this->navigationService->getActiveNavigationItem(),
            'breadcrumbs' => $this->navigationService->getBreadcrumbs(),
            'badgeCounts' => $badgeCounts,
            'currentCompany' => $company,
        ]);
    }
}