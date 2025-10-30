<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Events\ServiceActivated;
use App\Domains\Client\Events\ServiceCancelled;
use App\Domains\Client\Events\ServiceProvisioned;
use App\Domains\Client\Events\ServiceRenewed;
use App\Domains\Client\Events\ServiceResumed;
use App\Domains\Client\Events\ServiceSuspended;
use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\ClientService;
use App\Domains\Product\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Client Service Management Service
 * 
 * Handles the complete lifecycle of client services:
 * - Provisioning new services
 * - Activation and suspension
 * - Cancellation with fee calculation
 * - Renewal management
 * - MRR calculations
 * - Service health tracking
 */
class ClientServiceManagementService
{
    public function __construct(
        private ?ServiceProvisioningService $provisioning = null,
        private ?ServiceBillingService $billing = null,
        private ?ServiceRenewalService $renewal = null
    ) {
        // Services are optional to avoid circular dependencies
        // They will be resolved when needed
    }

    /**
     * Provision a new service for a client from a product template
     */
    public function provisionService(
        Client $client,
        Product $serviceTemplate,
        array $config = []
    ): ClientService {
        return DB::transaction(function () use ($client, $serviceTemplate, $config) {
            // Create the client service record
            $service = ClientService::create([
                'company_id' => $client->company_id,
                'client_id' => $client->id,
                'product_id' => $serviceTemplate->id,
                'contract_id' => $config['contract_id'] ?? null,
                'name' => $config['name'] ?? $serviceTemplate->name,
                'description' => $config['description'] ?? $serviceTemplate->description,
                'service_type' => $config['service_type'] ?? 'other',
                'category' => $config['category'] ?? null,
                'status' => 'pending',
                'provisioning_status' => 'pending',
                'start_date' => $config['start_date'] ?? now(),
                'end_date' => $config['end_date'] ?? null,
                'renewal_date' => $config['renewal_date'] ?? null,
                'billing_cycle' => $config['billing_cycle'] ?? $serviceTemplate->billing_cycle ?? 'monthly',
                'monthly_cost' => $config['monthly_cost'] ?? $serviceTemplate->base_price,
                'setup_cost' => $config['setup_cost'] ?? 0,
                'total_contract_value' => $config['total_contract_value'] ?? null,
                'currency' => $config['currency'] ?? $serviceTemplate->currency_code ?? 'USD',
                'auto_renewal' => $config['auto_renewal'] ?? false,
                'assigned_technician' => $config['assigned_technician'] ?? null,
                'backup_technician' => $config['backup_technician'] ?? null,
                'service_level' => $config['service_level'] ?? 'standard',
                'priority_level' => $config['priority_level'] ?? 'normal',
                'monitoring_enabled' => $config['monitoring_enabled'] ?? false,
            ]);

            Log::info('Service provisioned', [
                'service_id' => $service->id,
                'client_id' => $client->id,
                'product_id' => $serviceTemplate->id,
                'company_id' => $client->company_id,
            ]);

            // Start provisioning workflow if service is available
            if ($this->provisioning) {
                try {
                    $this->provisioning->startProvisioning($service);
                } catch (\Exception $e) {
                    Log::error('Failed to start provisioning workflow', [
                        'service_id' => $service->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Dispatch ServiceProvisioned event
            ServiceProvisioned::dispatch($service);

            return $service->fresh();
        });
    }

    /**
     * Activate a pending service
     */
    public function activateService(ClientService $service): bool
    {
        if ($service->isActive()) {
            return true; // Already activated
        }

        return DB::transaction(function () use ($service) {
            $service->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

            Log::info('Service activated', [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'company_id' => $service->company_id,
            ]);

            // Dispatch ServiceActivated event
            // Listeners will handle:
            // - Creating recurring billing
            // - Sending notifications
            // - Setting up monitoring
            ServiceActivated::dispatch($service);

            return true;
        });
    }

    /**
     * Suspend a service (for non-payment, breach, etc.)
     */
    public function suspendService(ClientService $service, string $reason): bool
    {
        if ($service->isSuspended()) {
            return true; // Already suspended
        }

        return DB::transaction(function () use ($service, $reason) {
            $service->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'notes' => ($service->notes ?? '') . "\n\nSuspended: " . $reason,
            ]);

            Log::warning('Service suspended', [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'reason' => $reason,
                'company_id' => $service->company_id,
            ]);

            // Dispatch ServiceSuspended event
            // Listeners will handle:
            // - Suspending billing
            // - Sending notifications
            ServiceSuspended::dispatch($service, $reason);

            return true;
        });
    }

    /**
     * Cancel a service with optional cancellation fee
     */
    public function cancelService(ClientService $service, ?Carbon $effectiveDate = null): float
    {
        $effectiveDate = $effectiveDate ?? now();
        
        return DB::transaction(function () use ($service, $effectiveDate) {
            // Calculate cancellation fee if applicable
            $cancellationFee = 0;
            if ($this->billing) {
                try {
                    $cancellationFee = $this->billing->calculateCancellationFee($service, $effectiveDate);
                } catch (\Exception $e) {
                    Log::error('Failed to calculate cancellation fee', [
                        'service_id' => $service->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $service->update([
                'status' => 'cancelled',
                'cancelled_at' => $effectiveDate,
                'cancellation_fee' => $cancellationFee,
                'end_date' => $effectiveDate,
            ]);

            Log::info('Service cancelled', [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'cancellation_fee' => $cancellationFee,
                'company_id' => $service->company_id,
            ]);

            // Dispatch ServiceCancelled event
            // Listeners will handle:
            // - Stopping billing
            // - Generating final invoice
            // - Sending notifications
            ServiceCancelled::dispatch($service, $cancellationFee);

            return $cancellationFee;
        });
    }

    /**
     * Renew a service for additional months
     */
    public function renewService(ClientService $service, int $months = 12): ClientService
    {
        return DB::transaction(function () use ($service, $months) {
            // Calculate new renewal date
            $currentRenewalDate = $service->renewal_date ?? $service->end_date ?? now();
            $newRenewalDate = $currentRenewalDate->copy()->addMonths($months);
            
            // Update service
            $service->update([
                'renewal_date' => $newRenewalDate,
                'end_date' => $newRenewalDate,
                'renewal_count' => ($service->renewal_count ?? 0) + 1,
                'last_renewed_at' => now(),
            ]);

            Log::info('Service renewed', [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'months' => $months,
                'new_renewal_date' => $newRenewalDate->toDateString(),
                'company_id' => $service->company_id,
            ]);

            // Dispatch ServiceRenewed event
            ServiceRenewed::dispatch($service, $months, null);

            return $service->fresh();
        });
    }

    /**
     * Get services due for renewal within specified days
     */
    public function getDueForRenewal(int $days = 30): Collection
    {
        return ClientService::where('status', 'active')
            ->whereNotNull('renewal_date')
            ->whereBetween('renewal_date', [now(), now()->addDays($days)])
            ->with(['client', 'technician'])
            ->get();
    }

    /**
     * Get services ending soon within specified days
     */
    public function getEndingSoon(int $days = 30): Collection
    {
        return ClientService::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->with(['client', 'technician'])
            ->get();
    }

    /**
     * Calculate Monthly Recurring Revenue (MRR)
     */
    public function calculateMRR(Client $client = null): float
    {
        $query = ClientService::where('status', 'active')
            ->whereNotNull('monthly_cost');

        if ($client) {
            $query->where('client_id', $client->id);
        } else {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query->sum('monthly_cost');
    }

    /**
     * Get service health score and metrics
     */
    public function getServiceHealth(ClientService $service): array
    {
        $health = [
            'score' => $service->health_score ?? 50,
            'status' => 'unknown',
            'factors' => [],
        ];

        // Calculate health based on various factors
        $score = 100;

        // Factor 1: SLA breaches
        if ($service->sla_breaches_count > 0) {
            $score -= min(30, $service->sla_breaches_count * 5);
            $health['factors'][] = [
                'name' => 'SLA Breaches',
                'impact' => -min(30, $service->sla_breaches_count * 5),
                'value' => $service->sla_breaches_count,
            ];
        }

        // Factor 2: Client satisfaction
        if ($service->client_satisfaction) {
            $satisfactionScore = ($service->client_satisfaction / 10) * 20;
            $health['factors'][] = [
                'name' => 'Client Satisfaction',
                'impact' => $satisfactionScore - 10,
                'value' => $service->client_satisfaction,
            ];
            $score += ($satisfactionScore - 10);
        }

        // Factor 3: Service age without review
        if ($service->last_review_date) {
            $daysSinceReview = $service->last_review_date->diffInDays(now(), false);
            if ($daysSinceReview > 90) {
                $score -= min(20, ($daysSinceReview - 90) / 10);
                $health['factors'][] = [
                    'name' => 'Review Overdue',
                    'impact' => -min(20, ($daysSinceReview - 90) / 10),
                    'value' => $daysSinceReview,
                ];
            }
        }

        $score = max(0, min(100, $score));
        $health['score'] = (int) $score;

        // Determine status
        if ($score >= 80) {
            $health['status'] = 'healthy';
        } elseif ($score >= 60) {
            $health['status'] = 'fair';
        } elseif ($score >= 40) {
            $health['status'] = 'poor';
        } else {
            $health['status'] = 'critical';
        }

        // Update service record
        $service->update([
            'health_score' => $health['score'],
            'last_health_check_at' => now(),
        ]);

        return $health;
    }

    /**
     * Transfer service to a different client
     */
    public function transferToClient(ClientService $service, Client $newClient): ClientService
    {
        return DB::transaction(function () use ($service, $newClient) {
            $oldClientId = $service->client_id;

            $service->update([
                'client_id' => $newClient->id,
                'notes' => ($service->notes ?? '') . "\n\nTransferred from client #{$oldClientId} on " . now()->toDateString(),
            ]);

            Log::info('Service transferred', [
                'service_id' => $service->id,
                'old_client_id' => $oldClientId,
                'new_client_id' => $newClient->id,
                'company_id' => $service->company_id,
            ]);

            // TODO: Dispatch ServiceTransferred event

            return $service->fresh();
        });
    }

    /**
     * Get services for a client with optional filtering
     */
    public function getClientServices(Client $client, array $filters = []): Collection
    {
        $query = $client->services()->with(['technician', 'backupTechnician', 'product']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['service_type'])) {
            $query->where('service_type', $filters['service_type']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->get();
    }

    /**
     * Resume a suspended service
     */
    public function resumeService(ClientService $service): bool
    {
        if (!$service->isSuspended()) {
            return false;
        }

        return DB::transaction(function () use ($service) {
            $service->update([
                'status' => 'active',
                'suspended_at' => null,
            ]);

            Log::info('Service resumed', [
                'service_id' => $service->id,
                'client_id' => $service->client_id,
                'company_id' => $service->company_id,
            ]);

            // Dispatch ServiceResumed event
            // Listeners will handle:
            // - Resuming billing
            // - Sending notifications
            ServiceResumed::dispatch($service);

            return true;
        });
    }
}
