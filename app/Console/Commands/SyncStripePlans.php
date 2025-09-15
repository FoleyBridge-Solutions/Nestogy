<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use App\Services\StripeSubscriptionService;
use Illuminate\Console\Command;

class SyncStripePlans extends Command
{
    protected $signature = 'stripe:sync-plans';

    protected $description = 'Sync subscription plans with Stripe, creating products and prices as needed';

    protected $stripeService;

    public function __construct(StripeSubscriptionService $stripeService)
    {
        parent::__construct();
        $this->stripeService = $stripeService;
    }

    public function handle()
    {
        $this->info('Syncing subscription plans with Stripe...');

        $plans = SubscriptionPlan::where('is_active', true)
            ->where(function ($query) {
                $query->where('price_monthly', '>', 0)
                    ->orWhere('price_per_user_monthly', '>', 0);
            })
            ->get();

        if ($plans->isEmpty()) {
            $this->info('No paid plans found to sync.');

            return 0;
        }

        foreach ($plans as $plan) {
            if ($plan->pricing_model === 'per_user') {
                $this->info("Processing plan: {$plan->name} (\${$plan->price_per_user_monthly}/user/month, min {$plan->minimum_users} users)");
            } else {
                $this->info("Processing plan: {$plan->name} (\${$plan->price_monthly}/month)");
            }

            try {
                $priceId = $this->stripeService->ensureStripePriceExists($plan);
                if ($priceId) {
                    $this->info("✓ Plan synced: {$plan->name} (Price ID: {$priceId})");
                } else {
                    $this->info("⊘ Skipped free plan: {$plan->name}");
                }
            } catch (\Exception $e) {
                $this->error("✗ Failed to sync {$plan->name}: ".$e->getMessage());
            }
        }

        $this->info('Sync complete!');

        return 0;
    }
}
