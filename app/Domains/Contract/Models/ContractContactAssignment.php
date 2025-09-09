<?php

namespace App\Domains\Contract\Models;

use App\Domains\Contract\Models\Contract;
use App\Models\Contact;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ContractContactAssignment Model
 * 
 * Represents the assignment of contacts to contracts for per-seat billing.
 * Supports access levels, usage limits, and billing tiers.
 * 
 * @property int $id
 * @property int $company_id
 * @property int $contract_id
 * @property int $contact_id
 * @property string $access_level
 * @property string|null $access_tier_name
 * @property array|null $assigned_permissions
 * @property array|null $service_entitlements
 * @property bool $has_portal_access
 * @property bool $can_create_tickets
 * @property bool $can_view_all_tickets
 * @property bool $can_view_assets
 * @property bool $can_view_invoices
 * @property bool $can_download_files
 * @property int $max_tickets_per_month
 * @property int $max_support_hours_per_month
 * @property array|null $allowed_ticket_types
 * @property array|null $restricted_features
 * @property float $billing_rate
 * @property string $billing_frequency
 * @property float $per_ticket_rate
 * @property array|null $pricing_modifiers
 * @property string $status
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Illuminate\Support\Carbon|null $last_billed_at
 * @property \Illuminate\Support\Carbon|null $next_billing_date
 * @property int $current_month_tickets
 * @property float $current_month_support_hours
 * @property float $current_month_charges
 * @property \Illuminate\Support\Carbon|null $last_access_date
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property int $total_tickets_created
 * @property bool $auto_assigned
 * @property array|null $assignment_criteria
 * @property array|null $automation_rules
 * @property bool $auto_upgrade_tier
 * @property array|null $sla_entitlements
 * @property string $priority_level
 * @property array|null $escalation_rules
 * @property array|null $notification_preferences
 * @property bool $can_collaborate_with_team
 * @property array|null $collaboration_settings
 * @property bool $receives_service_updates
 * @property bool $receives_maintenance_notifications
 * @property array|null $security_requirements
 * @property array|null $compliance_settings
 * @property array|null $data_access_restrictions
 * @property bool $requires_mfa
 * @property array|null $usage_history
 * @property array|null $billing_history
 * @property float $lifetime_value
 * @property float $average_monthly_usage
 * @property string|null $assignment_notes
 * @property array|null $metadata
 * @property array|null $custom_fields
 * @property int|null $assigned_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ContractContactAssignment extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_contact_assignments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'contract_id',
        'contact_id',
        'access_level',
        'access_tier_name',
        'assigned_permissions',
        'service_entitlements',
        'has_portal_access',
        'can_create_tickets',
        'can_view_all_tickets',
        'can_view_assets',
        'can_view_invoices',
        'can_download_files',
        'max_tickets_per_month',
        'max_support_hours_per_month',
        'allowed_ticket_types',
        'restricted_features',
        'billing_rate',
        'billing_frequency',
        'per_ticket_rate',
        'pricing_modifiers',
        'status',
        'start_date',
        'end_date',
        'last_billed_at',
        'next_billing_date',
        'current_month_tickets',
        'current_month_support_hours',
        'current_month_charges',
        'last_access_date',
        'last_login_at',
        'total_tickets_created',
        'auto_assigned',
        'assignment_criteria',
        'automation_rules',
        'auto_upgrade_tier',
        'sla_entitlements',
        'priority_level',
        'escalation_rules',
        'notification_preferences',
        'can_collaborate_with_team',
        'collaboration_settings',
        'receives_service_updates',
        'receives_maintenance_notifications',
        'security_requirements',
        'compliance_settings',
        'data_access_restrictions',
        'requires_mfa',
        'usage_history',
        'billing_history',
        'lifetime_value',
        'average_monthly_usage',
        'assignment_notes',
        'metadata',
        'custom_fields',
        'assigned_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'contract_id' => 'integer',
        'contact_id' => 'integer',
        'assigned_permissions' => 'array',
        'service_entitlements' => 'array',
        'has_portal_access' => 'boolean',
        'can_create_tickets' => 'boolean',
        'can_view_all_tickets' => 'boolean',
        'can_view_assets' => 'boolean',
        'can_view_invoices' => 'boolean',
        'can_download_files' => 'boolean',
        'max_tickets_per_month' => 'integer',
        'max_support_hours_per_month' => 'integer',
        'allowed_ticket_types' => 'array',
        'restricted_features' => 'array',
        'billing_rate' => 'decimal:2',
        'per_ticket_rate' => 'decimal:2',
        'pricing_modifiers' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_billed_at' => 'datetime',
        'next_billing_date' => 'date',
        'current_month_tickets' => 'integer',
        'current_month_support_hours' => 'decimal:2',
        'current_month_charges' => 'decimal:2',
        'last_access_date' => 'date',
        'last_login_at' => 'datetime',
        'total_tickets_created' => 'integer',
        'auto_assigned' => 'boolean',
        'assignment_criteria' => 'array',
        'automation_rules' => 'array',
        'auto_upgrade_tier' => 'boolean',
        'sla_entitlements' => 'array',
        'escalation_rules' => 'array',
        'notification_preferences' => 'array',
        'can_collaborate_with_team' => 'boolean',
        'collaboration_settings' => 'array',
        'receives_service_updates' => 'boolean',
        'receives_maintenance_notifications' => 'boolean',
        'security_requirements' => 'array',
        'compliance_settings' => 'array',
        'data_access_restrictions' => 'array',
        'requires_mfa' => 'boolean',
        'usage_history' => 'array',
        'billing_history' => 'array',
        'lifetime_value' => 'decimal:2',
        'average_monthly_usage' => 'decimal:2',
        'metadata' => 'array',
        'custom_fields' => 'array',
        'assigned_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Access level constants
     */
    const ACCESS_BASIC = 'basic';
    const ACCESS_STANDARD = 'standard';
    const ACCESS_PREMIUM = 'premium';
    const ACCESS_ADMIN = 'admin';
    const ACCESS_CUSTOM = 'custom';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_TERMINATED = 'terminated';
    const STATUS_PENDING = 'pending';

    /**
     * Billing frequency constants
     */
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_ANNUALLY = 'annually';
    const FREQUENCY_PER_TICKET = 'per_ticket';

    /**
     * Priority level constants
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the contract this assignment belongs to.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the contact assigned to this contract.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user who assigned this contact.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user who last updated this assignment.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if assignment is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if assignment is auto-assigned.
     */
    public function isAutoAssigned(): bool
    {
        return $this->auto_assigned;
    }

    /**
     * Check if contact has portal access.
     */
    public function hasPortalAccess(): bool
    {
        return $this->has_portal_access;
    }

    /**
     * Check if contact can create tickets.
     */
    public function canCreateTickets(): bool
    {
        return $this->can_create_tickets && $this->isActive();
    }

    /**
     * Check if contact has reached ticket limit.
     */
    public function hasReachedTicketLimit(): bool
    {
        if ($this->max_tickets_per_month === -1) {
            return false; // Unlimited
        }
        
        return $this->current_month_tickets >= $this->max_tickets_per_month;
    }

    /**
     * Check if contact has reached support hours limit.
     */
    public function hasReachedSupportHoursLimit(): bool
    {
        if ($this->max_support_hours_per_month === -1) {
            return false; // Unlimited
        }
        
        return $this->current_month_support_hours >= $this->max_support_hours_per_month;
    }

    /**
     * Get assigned permissions.
     */
    public function getAssignedPermissions(): array
    {
        return $this->assigned_permissions ?? [];
    }

    /**
     * Check if contact has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getAssignedPermissions());
    }

    /**
     * Get service entitlements.
     */
    public function getServiceEntitlements(): array
    {
        return $this->service_entitlements ?? [];
    }

    /**
     * Check if contact is entitled to specific service.
     */
    public function isEntitledToService(string $service): bool
    {
        return in_array($service, $this->getServiceEntitlements());
    }

    /**
     * Get allowed ticket types.
     */
    public function getAllowedTicketTypes(): array
    {
        return $this->allowed_ticket_types ?? [];
    }

    /**
     * Check if contact can create specific ticket type.
     */
    public function canCreateTicketType(string $type): bool
    {
        $allowed = $this->getAllowedTicketTypes();
        return empty($allowed) || in_array($type, $allowed);
    }

    /**
     * Get restricted features.
     */
    public function getRestrictedFeatures(): array
    {
        return $this->restricted_features ?? [];
    }

    /**
     * Check if feature is restricted.
     */
    public function isFeatureRestricted(string $feature): bool
    {
        return in_array($feature, $this->getRestrictedFeatures());
    }

    /**
     * Calculate monthly billing charges.
     */
    public function calculateMonthlyCharges(): float
    {
        $total = 0;
        
        // Base billing rate
        if ($this->billing_frequency !== self::FREQUENCY_PER_TICKET) {
            $total += $this->billing_rate;
        }
        
        // Per-ticket charges
        if ($this->billing_frequency === self::FREQUENCY_PER_TICKET || $this->per_ticket_rate > 0) {
            $total += $this->current_month_tickets * $this->per_ticket_rate;
        }
        
        // Apply pricing modifiers
        if ($modifiers = $this->pricing_modifiers) {
            if (isset($modifiers['discount'])) {
                $total -= $modifiers['discount'];
            }
            if (isset($modifiers['surcharge'])) {
                $total += $modifiers['surcharge'];
            }
            if (isset($modifiers['percentage_discount'])) {
                $total *= (1 - ($modifiers['percentage_discount'] / 100));
            }
        }
        
        return max(0, $total);
    }

    /**
     * Record ticket creation.
     */
    public function recordTicketCreation(): void
    {
        $this->increment('current_month_tickets');
        $this->increment('total_tickets_created');
        $this->update(['last_access_date' => now()->toDateString()]);
    }

    /**
     * Record support hours usage.
     */
    public function recordSupportHours(float $hours): void
    {
        $this->increment('current_month_support_hours', $hours);
        $this->update(['last_access_date' => now()->toDateString()]);
    }

    /**
     * Reset monthly counters.
     */
    public function resetMonthlyCounters(): void
    {
        $this->update([
            'current_month_tickets' => 0,
            'current_month_support_hours' => 0,
            'current_month_charges' => 0,
        ]);
    }

    /**
     * Update billing history.
     */
    public function addBillingRecord(array $record): void
    {
        $history = $this->billing_history ?? [];
        $history[] = array_merge($record, ['recorded_at' => now()->toISOString()]);
        
        $this->update([
            'billing_history' => $history,
            'last_billed_at' => now(),
            'current_month_charges' => $record['amount'] ?? 0,
            'lifetime_value' => $this->lifetime_value + ($record['amount'] ?? 0),
        ]);
    }

    /**
     * Update usage history.
     */
    public function updateUsageHistory(array $usage): void
    {
        $history = $this->usage_history ?? [];
        $history[] = array_merge($usage, ['recorded_at' => now()->toISOString()]);
        
        // Keep only last 12 months
        if (count($history) > 12) {
            $history = array_slice($history, -12);
        }
        
        $this->update(['usage_history' => $history]);
        $this->calculateAverageUsage();
    }

    /**
     * Calculate average monthly usage.
     */
    protected function calculateAverageUsage(): void
    {
        $history = $this->usage_history ?? [];
        
        if (empty($history)) {
            return;
        }
        
        $totalHours = array_sum(array_column($history, 'support_hours'));
        $average = $totalHours / count($history);
        
        $this->update(['average_monthly_usage' => $average]);
    }

    /**
     * Get SLA entitlements.
     */
    public function getSlaEntitlements(): array
    {
        return $this->sla_entitlements ?? [];
    }

    /**
     * Get escalation rules.
     */
    public function getEscalationRules(): array
    {
        return $this->escalation_rules ?? [];
    }

    /**
     * Get notification preferences.
     */
    public function getNotificationPreferences(): array
    {
        return $this->notification_preferences ?? [];
    }

    /**
     * Check if auto-upgrade is enabled.
     */
    public function shouldAutoUpgradeTier(): bool
    {
        return $this->auto_upgrade_tier;
    }

    /**
     * Upgrade access tier based on usage.
     */
    public function evaluateAutoUpgrade(): void
    {
        if (!$this->shouldAutoUpgradeTier()) {
            return;
        }
        
        // Logic to determine if upgrade is needed
        // This would be implemented based on business rules
    }

    /**
     * Get security requirements.
     */
    public function getSecurityRequirements(): array
    {
        return $this->security_requirements ?? [];
    }

    /**
     * Check if MFA is required.
     */
    public function requiresMfa(): bool
    {
        return $this->requires_mfa;
    }

    /**
     * Get data access restrictions.
     */
    public function getDataAccessRestrictions(): array
    {
        return $this->data_access_restrictions ?? [];
    }

    /**
     * Scope for active assignments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for assignments by contract.
     */
    public function scopeByContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Scope for assignments by contact.
     */
    public function scopeByContact($query, int $contactId)
    {
        return $query->where('contact_id', $contactId);
    }

    /**
     * Scope for assignments by access level.
     */
    public function scopeByAccessLevel($query, string $level)
    {
        return $query->where('access_level', $level);
    }

    /**
     * Scope for auto-assigned contacts.
     */
    public function scopeAutoAssigned($query)
    {
        return $query->where('auto_assigned', true);
    }

    /**
     * Scope for assignments with portal access.
     */
    public function scopeWithPortalAccess($query)
    {
        return $query->where('has_portal_access', true);
    }

    /**
     * Scope for assignments due for billing.
     */
    public function scopeDueForBilling($query)
    {
        return $query->whereNotNull('next_billing_date')
                    ->whereDate('next_billing_date', '<=', now());
    }

    /**
     * Scope for assignments needing auto-upgrade evaluation.
     */
    public function scopeNeedsAutoUpgrade($query)
    {
        return $query->where('auto_upgrade_tier', true)
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set next billing date on creation
        static::creating(function ($assignment) {
            if (!$assignment->next_billing_date) {
                $assignment->next_billing_date = $assignment->start_date;
            }
        });
    }
}