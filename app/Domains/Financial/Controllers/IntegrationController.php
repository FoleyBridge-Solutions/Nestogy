<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function quickbooks(Request $request)
    {
        // Placeholder for QuickBooks integration
        return view('financial.integrations.quickbooks');
    }
}
