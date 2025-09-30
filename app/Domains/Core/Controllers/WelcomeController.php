<?php

namespace App\Domains\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

class WelcomeController extends Controller
{
    /**
     * Show the welcome page with subscription plans.
     */
    public function index()
    {
        try {
            $subscriptionPlans = SubscriptionPlan::active()->ordered()->get();
        } catch (\Exception $e) {
            // Fallback if database is not set up yet
            $subscriptionPlans = collect();
        }

        return view('welcome', compact('subscriptionPlans'));
    }
}
