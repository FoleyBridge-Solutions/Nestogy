<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Models\ClientLicense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientLicenseService
{
    /**
     * Get all licenses for a client
     */
    public function getLicenses(Client $client, array $filters = []): Collection
    {
        $query = $client->licenses();

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['vendor'])) {
            $query->where('vendor', $filters['vendor']);
        }

        if (! empty($filters['expiring_soon'])) {
            $query->where('expiry_date', '<=', Carbon::now()->addDays(30))
                ->where('expiry_date', '>=', Carbon::now());
        }

        if (! empty($filters['expired'])) {
            $query->where('expiry_date', '<', Carbon::now());
        }

        return $query->orderBy('expiry_date', 'asc')->get();
    }

    /**
     * Get expiring licenses for a client
     */
    public function getExpiringLicenses(Client $client, int $daysAhead = 30): Collection
    {
        return $client->licenses()
            ->where('expiry_date', '<=', Carbon::now()->addDays($daysAhead))
            ->where('expiry_date', '>=', Carbon::now())
            ->where('status', 'active')
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired licenses for a client
     */
    public function getExpiredLicenses(Client $client): Collection
    {
        return $client->licenses()
            ->where('expiry_date', '<', Carbon::now())
            ->where('status', '!=', 'expired')
            ->get();
    }

    /**
     * Create a new license for a client
     */
    public function createLicense(Client $client, array $data): ClientLicense
    {
        DB::beginTransaction();

        try {
            $license = $client->licenses()->create([
                'name' => $data['name'],
                'type' => $data['type'] ?? 'software',
                'vendor' => $data['vendor'] ?? null,
                'product' => $data['product'] ?? null,
                'license_key' => $data['license_key'] ?? null,
                'seats' => $data['seats'] ?? 1,
                'seats_used' => $data['seats_used'] ?? 0,
                'purchase_date' => $data['purchase_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'renewal_date' => $data['renewal_date'] ?? null,
                'cost' => $data['cost'] ?? null,
                'renewal_cost' => $data['renewal_cost'] ?? null,
                'billing_cycle' => $data['billing_cycle'] ?? null,
                'status' => $data['status'] ?? 'active',
                'auto_renew' => $data['auto_renew'] ?? false,
                'notes' => $data['notes'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'company_id' => $client->company_id,
            ]);

            // Set up renewal reminder if auto-renew is enabled
            if ($license->auto_renew && $license->renewal_date) {
                $this->scheduleRenewalReminder($license);
            }

            DB::commit();

            Log::info('License created for client', [
                'client_id' => $client->id,
                'license_id' => $license->id,
            ]);

            return $license;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create license', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update a license
     */
    public function updateLicense(ClientLicense $license, array $data): ClientLicense
    {
        DB::beginTransaction();

        try {
            $oldExpiryDate = $license->expiry_date;
            $oldAutoRenew = $license->auto_renew;

            $license->update([
                'name' => $data['name'] ?? $license->name,
                'type' => $data['type'] ?? $license->type,
                'vendor' => $data['vendor'] ?? $license->vendor,
                'product' => $data['product'] ?? $license->product,
                'license_key' => $data['license_key'] ?? $license->license_key,
                'seats' => $data['seats'] ?? $license->seats,
                'seats_used' => $data['seats_used'] ?? $license->seats_used,
                'purchase_date' => $data['purchase_date'] ?? $license->purchase_date,
                'expiry_date' => $data['expiry_date'] ?? $license->expiry_date,
                'renewal_date' => $data['renewal_date'] ?? $license->renewal_date,
                'cost' => $data['cost'] ?? $license->cost,
                'renewal_cost' => $data['renewal_cost'] ?? $license->renewal_cost,
                'billing_cycle' => $data['billing_cycle'] ?? $license->billing_cycle,
                'status' => $data['status'] ?? $license->status,
                'auto_renew' => $data['auto_renew'] ?? $license->auto_renew,
                'notes' => $data['notes'] ?? $license->notes,
                'assigned_to' => $data['assigned_to'] ?? $license->assigned_to,
                'contact_id' => $data['contact_id'] ?? $license->contact_id,
            ]);

            // Update renewal reminder if auto-renew changed
            if ($license->auto_renew !== $oldAutoRenew || $license->renewal_date !== $oldExpiryDate) {
                if ($license->auto_renew && $license->renewal_date) {
                    $this->scheduleRenewalReminder($license);
                } else {
                    $this->cancelRenewalReminder($license);
                }
            }

            DB::commit();

            Log::info('License updated', ['license_id' => $license->id]);

            return $license->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update license', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a license
     */
    public function deleteLicense(ClientLicense $license): bool
    {
        try {
            // Cancel any renewal reminders
            $this->cancelRenewalReminder($license);

            $license->delete();

            Log::info('License deleted', ['license_id' => $license->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete license', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Renew a license
     */
    public function renewLicense(ClientLicense $license, array $renewalData = []): ClientLicense
    {
        DB::beginTransaction();

        try {
            $newExpiryDate = $renewalData['expiry_date'] ??
                Carbon::parse($license->expiry_date)->addYear();

            $license->update([
                'expiry_date' => $newExpiryDate,
                'renewal_date' => Carbon::now(),
                'last_renewal_date' => Carbon::now(),
                'renewal_cost' => $renewalData['cost'] ?? $license->renewal_cost,
                'status' => 'active',
            ]);

            // Create renewal record
            if (class_exists(\App\Models\LicenseRenewal::class)) {
                \App\Models\LicenseRenewal::create([
                    'license_id' => $license->id,
                    'renewal_date' => Carbon::now(),
                    'expiry_date' => $newExpiryDate,
                    'cost' => $renewalData['cost'] ?? $license->renewal_cost,
                    'invoice_id' => $renewalData['invoice_id'] ?? null,
                    'renewed_by' => auth()->id(),
                    'company_id' => $license->company_id,
                ]);
            }

            // Schedule next renewal reminder if auto-renew
            if ($license->auto_renew) {
                $this->scheduleRenewalReminder($license);
            }

            DB::commit();

            Log::info('License renewed', [
                'license_id' => $license->id,
                'new_expiry' => $newExpiryDate,
            ]);

            return $license->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to renew license', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check and update expired licenses
     */
    public function checkAndUpdateExpiredLicenses(): int
    {
        $expiredLicenses = ClientLicense::where('expiry_date', '<', Carbon::now())
            ->where('status', '!=', 'expired')
            ->get();

        $count = 0;
        foreach ($expiredLicenses as $license) {
            $license->update(['status' => 'expired']);
            $count++;

            // Send expiration notification
            $this->sendExpirationNotification($license);
        }

        Log::info('Updated expired licenses', ['count' => $count]);

        return $count;
    }

    /**
     * Get license utilization statistics
     */
    public function getLicenseUtilization(ClientLicense $license): array
    {
        $utilization = [
            'total_seats' => $license->seats,
            'used_seats' => $license->seats_used,
            'available_seats' => $license->seats - $license->seats_used,
            'utilization_percentage' => $license->seats > 0
                ? round(($license->seats_used / $license->seats) * 100, 2)
                : 0,
            'days_until_expiry' => $license->expiry_date
                ? Carbon::now()->diffInDays($license->expiry_date, false)
                : null,
            'is_expired' => $license->expiry_date
                ? Carbon::now()->isAfter($license->expiry_date)
                : false,
        ];

        return $utilization;
    }

    /**
     * Assign license seats to users
     */
    public function assignSeats(ClientLicense $license, array $userIds): ClientLicense
    {
        $requiredSeats = count($userIds);
        $availableSeats = $license->seats - $license->seats_used;

        if ($requiredSeats > $availableSeats) {
            throw new \Exception("Not enough available seats. Required: {$requiredSeats}, Available: {$availableSeats}");
        }

        DB::beginTransaction();

        try {
            // Store seat assignments (would need a license_assignments table)
            foreach ($userIds as $userId) {
                // Create assignment record
                DB::table('license_assignments')->insert([
                    'license_id' => $license->id,
                    'user_id' => $userId,
                    'assigned_at' => Carbon::now(),
                    'company_id' => $license->company_id,
                ]);
            }

            // Update used seats count
            $license->update([
                'seats_used' => $license->seats_used + $requiredSeats,
            ]);

            DB::commit();

            Log::info('License seats assigned', [
                'license_id' => $license->id,
                'assigned_count' => $requiredSeats,
            ]);

            return $license->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign license seats', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get license cost analysis
     */
    public function getLicenseCostAnalysis(Client $client, string $period = 'year'): array
    {
        $licenses = $client->licenses;

        $totalCost = 0;
        $recurringCost = 0;
        $oneTimeCost = 0;
        $costByVendor = [];
        $costByType = [];

        foreach ($licenses as $license) {
            $annualCost = $this->calculateAnnualCost($license);
            $totalCost += $annualCost;

            if ($license->billing_cycle) {
                $recurringCost += $annualCost;
            } else {
                $oneTimeCost += $license->cost ?? 0;
            }

            // Group by vendor
            $vendor = $license->vendor ?? 'Unknown';
            if (! isset($costByVendor[$vendor])) {
                $costByVendor[$vendor] = 0;
            }
            $costByVendor[$vendor] += $annualCost;

            // Group by type
            $type = $license->type ?? 'Unknown';
            if (! isset($costByType[$type])) {
                $costByType[$type] = 0;
            }
            $costByType[$type] += $annualCost;
        }

        return [
            'total_annual_cost' => $totalCost,
            'recurring_cost' => $recurringCost,
            'one_time_cost' => $oneTimeCost,
            'cost_by_vendor' => $costByVendor,
            'cost_by_type' => $costByType,
            'license_count' => $licenses->count(),
            'expiring_soon_count' => $this->getExpiringLicenses($client)->count(),
            'expired_count' => $this->getExpiredLicenses($client)->count(),
        ];
    }

    /**
     * Calculate annual cost for a license
     */
    protected function calculateAnnualCost(ClientLicense $license): float
    {
        if (! $license->renewal_cost) {
            return 0;
        }

        switch ($license->billing_cycle) {
            case 'monthly':
                return $license->renewal_cost * 12;
            case 'quarterly':
                return $license->renewal_cost * 4;
            case 'semi-annual':
                return $license->renewal_cost * 2;
            case 'annual':
                return $license->renewal_cost;
            case 'biennial':
                return $license->renewal_cost / 2;
            case 'triennial':
                return $license->renewal_cost / 3;
            default:
                return 0;
        }
    }

    /**
     * Schedule renewal reminder
     */
    protected function scheduleRenewalReminder(ClientLicense $license): void
    {
        // This would integrate with a job scheduling system
        // For now, just log the intent
        Log::info('Renewal reminder scheduled', [
            'license_id' => $license->id,
            'renewal_date' => $license->renewal_date,
        ]);
    }

    /**
     * Cancel renewal reminder
     */
    protected function cancelRenewalReminder(ClientLicense $license): void
    {
        // This would cancel scheduled jobs
        Log::info('Renewal reminder cancelled', [
            'license_id' => $license->id,
        ]);
    }

    /**
     * Send expiration notification
     */
    protected function sendExpirationNotification(ClientLicense $license): void
    {
        // This would send email/notification
        Log::info('Expiration notification sent', [
            'license_id' => $license->id,
        ]);
    }
}
