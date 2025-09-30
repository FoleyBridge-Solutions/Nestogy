<?php

namespace App\Http\ViewComposers;

use App\Domains\Core\Services\NavigationService;
use Illuminate\View\View;

class ClientViewComposer
{
    /**
     * Create a new client view composer.
     */
    public function __construct(
        protected NavigationService $navigationService
    ) {}

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $selectedClient = $this->navigationService->getSelectedClient();

        $view->with([
            'selectedClient' => $selectedClient,
        ]);
    }
}
