<?php

namespace App\Domains\Financial\Controllers;

use App\Domains\Core\Services\NavigationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    public function index(Request $request)
    {
        $selectedClient = $this->navigationService->getSelectedClient();

        // TODO: Implement payment methods listing
        $paymentMethods = [];

        return view('financial.payment-methods.index', compact('selectedClient', 'paymentMethods'));
    }

    public function create()
    {
        $selectedClient = $this->navigationService->getSelectedClient();

        return view('financial.payment-methods.create', compact('selectedClient'));
    }

    public function store(Request $request)
    {
        // TODO: Implement payment method storage

        return redirect()->route('financial.payment-methods.index')
            ->with('success', 'Payment method added successfully');
    }
}
