<?php

namespace App\Traits;

use App\Helpers\StatusColorHelper;

/**
 * HasStatusColors Trait
 * 
 * Provides status and priority color functionality for models.
 * Automatically detects the domain from the model class name.
 * 
 * Usage:
 *   use HasStatusColors;
 * 
 * Provides:
 *   $model->status_color - Returns Flux UI color for status
 *   $model->priority_color - Returns Flux UI color for priority
 *   $model->getStatusColor() - Method access to status color
 *   $model->getPriorityColor() - Method access to priority color
 */
trait HasStatusColors
{
    /**
     * Get status color attribute
     * 
     * @return string Flux UI color name
     */
    public function getStatusColorAttribute(): string
    {
        if (!isset($this->status) || $this->status === null) {
            return 'zinc';
        }

        $domain = $this->getStatusDomain();
        return StatusColorHelper::get($domain, $this->status);
    }

    /**
     * Get status color (as method)
     * 
     * @return string Flux UI color name
     */
    public function getStatusColor(): string
    {
        return $this->status_color;
    }

    /**
     * Get priority color attribute
     * 
     * @return string Flux UI color name
     */
    public function getPriorityColorAttribute(): string
    {
        if (!isset($this->priority) || $this->priority === null) {
            return 'zinc';
        }

        return StatusColorHelper::priority($this->priority);
    }

    /**
     * Get priority color (as method)
     * 
     * @return string Flux UI color name
     */
    public function getPriorityColor(): string
    {
        return $this->priority_color;
    }

    /**
     * Get status badge color (alias for backward compatibility)
     * 
     * @return string Flux UI color name
     */
    public function getStatusBadgeColor(): string
    {
        return $this->getStatusColor();
    }

    /**
     * Define the domain for this model
     * 
     * Override this method in your model if the auto-detection doesn't work.
     * 
     * @return string Domain name for config lookup
     */
    protected function getStatusDomain(): string
    {
        // Auto-detect domain from class name
        $className = class_basename($this);
        
        // Map of class names to domain config keys
        $domainMap = [
            'Ticket' => 'ticket',
            'Invoice' => 'invoice',
            'Contract' => 'contract',
            'Asset' => 'asset',
            'ClientService' => 'service',
            'Project' => 'project',
            'CompanySubscription' => 'subscription',
            'PlaidItem' => 'bank_connection',
            'CreditNote' => 'credit_note',
            'AssetWarranty' => 'warranty',
            'AssetMaintenance' => 'maintenance',
            'ClientDomain' => 'domain',
            'ClientCertificate' => 'certificate',
            'ClientCalendarEvent' => 'calendar_event',
            'MailQueue' => 'mail_queue',
            'Lead' => 'lead',
            'Quote' => 'quote',
            'Payment' => 'payment',
            'TimeEntry' => 'time_entry',
            'RecurringTicket' => 'recurring_ticket',
            'Workflow' => 'workflow',
            'ClientCredit' => 'credit',
            'Campaign' => 'campaign',
        ];

        return $domainMap[$className] ?? strtolower($className);
    }

    /**
     * Get support status color attribute (for Asset model)
     * 
     * @return string Flux UI color name
     */
    public function getSupportStatusColorAttribute(): string
    {
        if (!isset($this->support_status) || $this->support_status === null) {
            return 'zinc';
        }

        return StatusColorHelper::get('asset_support', $this->support_status);
    }
}
