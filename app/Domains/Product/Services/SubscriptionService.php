<?php

namespace App\Domains\Product\Services;

use App\Domains\Product\Models\Subscription;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Client\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SubscriptionService
{
    public function createSubscription(Client $client, array $data): Subscription
    {
        return Subscription::create([
            'client_id' => $client->id,
            'product_id' => $data['product_id'] ?? null,
            'bundle_id' => $data['bundle_id'] ?? null,
            'name' => $data['name'],
            'status' => $data['status'] ?? 'active',
            'start_date' => $data['start_date'] ?? now(),
            'end_date' => $data['end_date'] ?? null,
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'amount' => $data['amount'],
            'quantity' => $data['quantity'] ?? 1,
            'next_billing_date' => $this->calculateNextBillingDate($data['billing_cycle'] ?? 'monthly', $data['start_date'] ?? now()),
        ]);
    }

    public function updateSubscription(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);
        return $subscription->fresh();
    }

    public function cancelSubscription(Subscription $subscription, bool $immediate = false): Subscription
    {
        if ($immediate) {
            $subscription->update([
                'status' => 'cancelled',
                'end_date' => now(),
            ]);
        } else {
            $subscription->update([
                'status' => 'pending_cancellation',
                'end_date' => $subscription->next_billing_date,
            ]);
        }

        return $subscription;
    }

    public function renewSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'next_billing_date' => $this->calculateNextBillingDate($subscription->billing_cycle, $subscription->next_billing_date),
        ]);

        return $subscription;
    }

    public function getActiveSubscriptions(Client $client = null): Collection
    {
        $query = Subscription::where('status', 'active');
        
        if ($client) {
            $query->where('client_id', $client->id);
        }

        return $query->get();
    }

    public function getExpiringSubscriptions(int $days = 30): Collection
    {
        return Subscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->get();
    }

    public function processRecurringBilling(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $subscriptions = Subscription::where('status', 'active')
            ->where('next_billing_date', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $this->createInvoiceForSubscription($subscription);
                $this->renewSubscription($subscription);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    protected function createInvoiceForSubscription(Subscription $subscription): Invoice
    {
        return Invoice::create([
            'client_id' => $subscription->client_id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->amount * $subscription->quantity,
            'tax' => 0, // Will be calculated by tax service
            'total' => $subscription->amount * $subscription->quantity,
            'status' => 'pending',
            'due_date' => now()->addDays(30),
            'description' => "Subscription: {$subscription->name}",
        ]);
    }

    protected function calculateNextBillingDate(string $cycle, $fromDate = null): Carbon
    {
        $date = $fromDate ? Carbon::parse($fromDate) : now();

        switch ($cycle) {
            case 'weekly':
                return $date->addWeek();
            case 'monthly':
                return $date->addMonth();
            case 'quarterly':
                return $date->addQuarters(1);
            case 'semi_annually':
                return $date->addMonths(6);
            case 'annually':
                return $date->addYear();
            default:
                return $date->addMonth();
        }
    }

    public function suspendSubscription(Subscription $subscription): Subscription
    {
        $subscription->update(['status' => 'suspended']);
        return $subscription;
    }

    public function reactivateSubscription(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'active',
            'next_billing_date' => $this->calculateNextBillingDate($subscription->billing_cycle),
        ]);
        return $subscription;
    }

    public function changeSubscriptionPlan(Subscription $subscription, $newProductId, $prorate = true): Subscription
    {
        // This would handle plan changes with optional proration
        // Implementation depends on business logic
        $subscription->update(['product_id' => $newProductId]);
        return $subscription;
    }
}