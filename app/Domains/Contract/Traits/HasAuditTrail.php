<?php

namespace App\Domains\Contract\Traits;

/**
 * HasAuditTrail Trait
 * 
 * Provides common audit trail functionality for contract-related models.
 * Eliminates duplication of audit trail management across models.
 */
trait HasAuditTrail
{
    /**
     * Add entry to audit trail
     */
    protected function addToAuditTrail(string $action, array $data = []): void
    {
        $auditTrail = $this->audit_trail ?? [];
        $auditTrail[] = [
            'action' => $action,
            'timestamp' => now(),
            'user_id' => auth()->id(),
            'data' => $data,
        ];

        $this->update(['audit_trail' => $auditTrail]);
    }

    /**
     * Update status with audit trail entry
     */
    protected function updateStatusWithAudit(string $status, array $additionalData = [], string $auditAction = null): void
    {
        $updateData = array_merge(['status' => $status], $additionalData);
        
        $this->update($updateData);
        
        $this->addToAuditTrail(
            $auditAction ?? ($status . '_status_set'), 
            array_merge($additionalData, ['new_status' => $status])
        );
    }

    /**
     * Update with timestamp and audit trail
     */
    protected function updateWithTimestampAndAudit(array $updateData, string $timestampField, string $auditAction, array $auditData = []): void
    {
        $updateData[$timestampField] = now();
        $this->update($updateData);
        
        $this->addToAuditTrail($auditAction, array_merge($auditData, $updateData));
    }
}