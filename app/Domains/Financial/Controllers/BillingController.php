<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    public function schedules(Request $request)
    {
        $selectedClient = $this->navigationService->getSelectedClient();
        
        // TODO: Implement billing schedules logic
        $schedules = [];
        
        return view('financial.billing.schedules', compact('selectedClient', 'schedules'));
    }
    
    public function usage(Request $request)
    {
        $selectedClient = $this->navigationService->getSelectedClient();
        
        // TODO: Implement usage billing logic
        $usageData = [];
        
        return view('financial.billing.usage', compact('selectedClient', 'usageData'));
    }
}