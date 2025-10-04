<?php

namespace App\Domains\Contract\Traits;

use Carbon\Carbon;

/**
 * HasStatusWorkflow Trait
 *
 * Provides common status workflow methods for contract models.
 * Eliminates duplication of status change logic with configuration support.
 */
trait HasStatusWorkflow
{
    /**
     * Update status using company configuration
     */
    protected function updateStatusWithConfig(
        string $configKey,
        array $additionalData = [],
        ?string $timestampField = null
    ): void {
        $config = $this->getCompanyConfig();
        $newStatus = $config[$configKey] ?? $this->getDefaultStatus($configKey);

        $updateData = array_merge(['status' => $newStatus], $additionalData);

        if ($timestampField) {
            $updateData[$timestampField] = now();
        }

        $this->update($updateData);
    }

    /**
     * Get default status for a config key
     */
    protected function getDefaultStatus(string $configKey): string
    {
        $defaults = [
            'default_signed_status' => 'signed',
            'default_active_status' => 'active',
            'default_terminated_status' => 'terminated',
            'default_suspended_status' => 'suspended',
            'default_expired_status' => 'expired',
            'default_signature_status' => 'pending',
            'default_signed_signature_status' => 'fully_executed',
        ];

        return $defaults[$configKey] ?? 'active';
    }

    /**
     * Check if current status is in allowed list
     */
    public function hasStatus(string $configKey, ?string $currentStatus = null): bool
    {
        $status = $currentStatus ?? $this->status;
        $allowedStatuses = $this->getConfigValue($configKey, []);

        return in_array($status, $allowedStatuses);
    }

    /**
     * Mark contract as signed
     */
    public function markAsSigned(?Carbon $signedAt = null): void
    {
        $config = $this->getCompanyConfig();

        $this->update([
            'status' => $config['default_signed_status'] ?? 'signed',
            'signature_status' => $config['default_signed_signature_status'] ?? 'fully_executed',
            'signed_at' => $signedAt ?? now(),
        ]);
    }

    /**
     * Mark contract as active
     */
    public function markAsActive(?Carbon $executedAt = null): void
    {
        $additionalData = [];
        
        try {
            if (\Schema::hasColumn($this->getTable(), 'executed_at')) {
                $additionalData['executed_at'] = $executedAt ?? now();
            }
        } catch (\Exception $e) {
            // Column doesn't exist, skip it
        }
        
        $this->updateStatusWithConfig('default_active_status', $additionalData);
    }

    /**
     * Terminate contract
     */
    public function terminate(?string $reason = null, ?Carbon $terminationDate = null): void
    {
        $additionalData = [];
        
        try {
            if (\Schema::hasColumn($this->getTable(), 'terminated_at')) {
                $additionalData['terminated_at'] = $terminationDate ?? now();
            }
            if (\Schema::hasColumn($this->getTable(), 'termination_reason')) {
                $additionalData['termination_reason'] = $reason;
            }
        } catch (\Exception $e) {
            // Columns don't exist, skip them
        }
        
        $this->updateStatusWithConfig('default_terminated_status', $additionalData);
    }

    /**
     * Suspend contract
     */
    public function suspend(?string $reason = null): void
    {
        $config = $this->getCompanyConfig();
        $suspendedStatus = $config['default_suspended_status'] ?? 'suspended';

        $metadata = $this->metadata ?? [];
        $metadata['suspension_reason'] = $reason;
        $metadata['suspended_at'] = now();

        $this->update([
            'status' => $suspendedStatus,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Reactivate suspended contract
     */
    public function reactivate(): void
    {
        $config = $this->getCompanyConfig();
        $activeStatus = $config['default_active_status'] ?? 'active';

        $metadata = $this->metadata ?? [];
        $metadata['reactivated_at'] = now();
        unset($metadata['suspension_reason'], $metadata['suspended_at']);

        $this->update([
            'status' => $activeStatus,
            'metadata' => $metadata,
        ]);
    }
}
