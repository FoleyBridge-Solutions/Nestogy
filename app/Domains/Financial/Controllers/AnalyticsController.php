<?php

namespace App\Domains\Financial\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('financial.analytics.index');
    }

    public function contracts()
    {
        // TODO: Add contract analytics logic
        return view('financial.analytics.contracts');
    }
}