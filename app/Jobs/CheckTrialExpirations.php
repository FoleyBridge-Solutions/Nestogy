<?php

namespace App\Jobs;

use App\Models\Client;
use App\Domains\Core\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CheckTrialExpirations
 * 
 * Background job that checks for trial expirations and sends notifications
 * to customers approaching trial end dates.
 */
class CheckTrialExpirations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService)
    {
        $now = Carbon::now();
        
        Log::info('Starting trial expiration check', ['timestamp' => $now]);

        // Find trials ending in 3 days (warning notification)
        $trialsEndingIn3Days = Client::where('company_id', 1)
            ->whereNotNull('company_link_id')
            ->where('subscription_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [
                $now->copy()->addDays(3)->startOfDay(),
                $now->copy()->addDays(3)->endOfDay()
            ])
            ->with(['linkedCompany', 'subscriptionPlan'])
            ->get();

        foreach ($trialsEndingIn3Days as $client) {
            try {
                $this->sendTrialWarningNotification($client, $notificationService);
                Log::info('Trial warning notification sent', ['client_id' => $client->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send trial warning notification', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Find trials ending in 1 day (final notice)
        $trialsEndingTomorrow = Client::where('company_id', 1)
            ->whereNotNull('company_link_id')
            ->where('subscription_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [
                $now->copy()->addDay()->startOfDay(),
                $now->copy()->addDay()->endOfDay()
            ])
            ->with(['linkedCompany', 'subscriptionPlan'])
            ->get();

        foreach ($trialsEndingTomorrow as $client) {
            try {
                $this->sendTrialFinalNotification($client, $notificationService);
                Log::info('Trial final notice sent', ['client_id' => $client->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send trial final notice', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Find trials that have expired today
        $expiredTrials = Client::where('company_id', 1)
            ->whereNotNull('company_link_id')
            ->where('subscription_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', $now->toDateString())
            ->with(['linkedCompany', 'subscriptionPlan'])
            ->get();

        foreach ($expiredTrials as $client) {
            try {
                $this->handleTrialExpired($client, $notificationService);
                Log::info('Trial expiration handled', ['client_id' => $client->id]);
            } catch (\Exception $e) {
                Log::error('Failed to handle trial expiration', [
                    'client_id' => $client->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Trial expiration check completed', [
            'warnings_sent' => $trialsEndingIn3Days->count(),
            'final_notices_sent' => $trialsEndingTomorrow->count(),
            'expirations_handled' => $expiredTrials->count()
        ]);
    }

    /**
     * Send trial warning notification (3 days before expiration).
     */
    protected function sendTrialWarningNotification(Client $client, NotificationService $notificationService)
    {
        $data = [
            'client' => $client,
            'company' => $client->linkedCompany,
            'plan' => $client->subscriptionPlan,
            'trial_ends_at' => $client->trial_ends_at,
            'days_remaining' => 3,
        ];

        // Send email notification
        $notificationService->sendEmail($client->email, 'trial-warning', $data);

        // Send in-app notification if company is active
        if ($client->linkedCompany && $client->linkedCompany->is_active) {
            $notificationService->sendInAppNotification(
                $client->linkedCompany->id,
                'Trial Ending Soon',
                'Your free trial ends in 3 days. Please add a payment method to continue using Nestogy.',
                'warning'
            );
        }
    }

    /**
     * Send final trial notification (1 day before expiration).
     */
    protected function sendTrialFinalNotification(Client $client, NotificationService $notificationService)
    {
        $data = [
            'client' => $client,
            'company' => $client->linkedCompany,
            'plan' => $client->subscriptionPlan,
            'trial_ends_at' => $client->trial_ends_at,
            'days_remaining' => 1,
        ];

        // Send email notification
        $notificationService->sendEmail($client->email, 'trial-final-notice', $data);

        // Send urgent in-app notification
        if ($client->linkedCompany && $client->linkedCompany->is_active) {
            $notificationService->sendInAppNotification(
                $client->linkedCompany->id,
                'Trial Expires Tomorrow',
                'Your free trial expires tomorrow! Add a payment method now to avoid service interruption.',
                'urgent'
            );
        }
    }

    /**
     * Handle trial expiration.
     */
    protected function handleTrialExpired(Client $client, NotificationService $notificationService)
    {
        // If client has a payment method, Stripe will handle the transition automatically
        if ($client->stripe_subscription_id && $client->paymentMethods()->active()->exists()) {
            Log::info('Trial expired with payment method available', [
                'client_id' => $client->id,
                'subscription_id' => $client->stripe_subscription_id
            ]);
            return;
        }

        // No payment method - suspend the account
        $client->update(['subscription_status' => 'unpaid']);

        if ($client->linkedCompany) {
            $client->linkedCompany->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => 'Trial expired without payment method',
            ]);
        }

        // Send expiration notification
        $data = [
            'client' => $client,
            'company' => $client->linkedCompany,
            'plan' => $client->subscriptionPlan,
            'trial_ends_at' => $client->trial_ends_at,
        ];

        $notificationService->sendEmail($client->email, 'trial-expired', $data);

        Log::warning('Trial expired and account suspended', [
            'client_id' => $client->id,
            'company_id' => $client->linkedCompany?->id
        ]);
    }
}