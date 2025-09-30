<?php

namespace App\Traits;

use App\Domains\Core\Services\NavigationService;
use Illuminate\Http\Request;

trait UsesSelectedClient
{
    /**
     * Get the currently selected client from session
     */
    protected function getSelectedClient(Request $request = null)
    {
        return app(NavigationService::class)->getSelectedClient();
    }
}