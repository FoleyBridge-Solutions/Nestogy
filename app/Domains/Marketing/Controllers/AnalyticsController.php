<?php

namespace App\Domains\Marketing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function campaigns(): View
    {
        return view('marketing.analytics.campaigns');
    }

    public function emailTracking(): View
    {
        return view('marketing.analytics.email-tracking');
    }

    public function attribution(): View
    {
        return view('marketing.analytics.attribution');
    }

    public function revenue(): View
    {
        return view('marketing.analytics.revenue');
    }
}
