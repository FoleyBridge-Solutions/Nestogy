<?php

namespace App\Jobs;

use App\Models\Client;
use App\Domains\Core\Services\StripeSubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncStripeSubscriptions
 * 
 * Background job that syncs subscription statuses with Stripe to ensure
 * our local data stays in sync with Stripe's records.
 */
class SyncStripeSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(StripeSubscriptionService $stripeService)
    {
        Log::info('Starting Stripe subscription sync');

        $clients = Client::where('company_id', 1)
            ->whereNotNull('company_link_id')
            ->whereNotNull('stripe_subscription_id')
            ->whereIn('subscription_status', ['trialing', 'active', 'past_due'])
            ->get();

        $syncCount = 0;
        $errorCount = 0;

        foreach ($clients as $client) {
            try {
                $success = $stripeService->syncSubscriptionStatus($client);
                
                if ($success) {
                    $syncCount++;
                    Log::debug('Subscription synced successfully', [
                        'client_id' => $client->id,
                        'subscription_id' => $client->stripe_subscription_id
                    ]);
                } else {
                    $errorCount++;
                    Log::warning('Failed to sync subscription', [
                        'client_id' => $client->id,
                        'subscription_id' => $client->stripe_subscription_id
                    ]);
                }
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Error syncing subscription', [
                    'client_id' => $client->id,
                    'subscription_id' => $client->stripe_subscription_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Stripe subscription sync completed', [
            'total_clients' => $clients->count(),
            'successful_syncs' => $syncCount,
            'errors' => $errorCount
        ]);
    }
}