<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

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