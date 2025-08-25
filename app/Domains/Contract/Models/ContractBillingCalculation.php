<?php

namespace App\Domains\Contract\Models;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractAssetAssignment;
use App\Domains\Contract\Models\ContractContactAssignment;
use App\Models\Company;
use App\Models\User;
use App\Models\Invoice;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Contract Billing Calculation Model
 * 
 * Represents automated billing calculations for programmable contracts,
 * including asset-based, contact-based, and usage-based billing.
 */
class ContractBillingCalculation extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'contract_id',
        'billing_period_start',
        'billing_period_end',
        'billing_type',
        'period_description',
        'base_contract_amount',
        'fixed_monthly_charges',
        'base_charges_breakdown',
        'total_assets',
        'workstation_count',
        'server_count',
        'network_device_count',
        'mobile_device_count',
        'asset_counts_by_type',
        'asset_billing_total',
        'asset_billing_breakdown',
        'total_contacts',
        'basic_access_contacts',
        'standard_access_contacts',
        'premium_access_contacts',
        'admin_access_contacts',
        'contact_access_breakdown',
        'contact_billing_total',
        'contact_billing_breakdown',
        'total_tickets_created',
        'total_support_hours',
        'total_incidents_resolved',
        'usage_charges',
        'usage_breakdown',
        'service_charges',
        'monitoring_charges',
        'backup_charges',
        'security_charges',
        'maintenance_charges',
        'additional_service_charges',
        'discounts_applied',
        'surcharges_applied',
        'pricing_adjustments',
        'tax_amount',
        'tax_rate',
        'subtotal_before_tax',
        'total_amount',
        'currency_code',
        'calculation_method',
        'calculation_rules',
        'formula_applied',
        'line_items',
        'calculation_log',
        'status',
        'calculated_at',
        'reviewed_at',
        'approved_at',
        'invoiced_at',
        'invoice_id',
        'invoice_number',
        'auto_invoice',
        'invoice_due_date',
        'previous_period_amount',
        'amount_variance',
        'variance_percentage',
        'variance_analysis',
        'projected_next_period',
        'forecasting_data',
        'trend_analysis',
        'has_disputes',
        'dispute_details',
        'disputed_amount',
        'adjustments_made',
        'calculation_duration_ms',
        'performance_metrics',
        'calculation_notes',
        'calculated_by',
        'reviewed_by',
        'approved_by',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'base_contract_amount' => 'decimal:2',
        'fixed_monthly_charges' => 'decimal:2',
        'base_charges_breakdown' => 'array',
        'total_assets' => 'integer',
        'workstation_count' => 'integer',
        'server_count' => 'integer',
        'network_device_count' => 'integer',
        'mobile_device_count' => 'integer',
        'asset_counts_by_type' => 'array',
        'asset_billing_total' => 'decimal:2',
        'asset_billing_breakdown' => 'array',
        'total_contacts' => 'integer',
        'basic_access_contacts' => 'integer',
        'standard_access_contacts' => 'integer',
        'premium_access_contacts' => 'integer',
        'admin_access_contacts' => 'integer',
        'contact_access_breakdown' => 'array',
        'contact_billing_total' => 'decimal:2',
        'contact_billing_breakdown' => 'array',
        'total_tickets_created' => 'integer',
        'total_support_hours' => 'decimal:2',
        'total_incidents_resolved' => 'integer',
        'usage_charges' => 'decimal:2',
        'usage_breakdown' => 'array',
        'service_charges' => 'array',
        'monitoring_charges' => 'decimal:2',
        'backup_charges' => 'decimal:2',
        'security_charges' => 'decimal:2',
        'maintenance_charges' => 'decimal:2',
        'additional_service_charges' => 'array',
        'discounts_applied' => 'decimal:2',
        'surcharges_applied' => 'decimal:2',
        'pricing_adjustments' => 'array',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'subtotal_before_tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'calculation_rules' => 'array',
        'formula_applied' => 'array',
        'line_items' => 'array',
        'calculation_log' => 'array',
        'calculated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'invoiced_at' => 'datetime',
        'auto_invoice' => 'boolean',
        'invoice_due_date' => 'date',
        'previous_period_amount' => 'decimal:2',
        'amount_variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'variance_analysis' => 'array',
        'projected_next_period' => 'decimal:2',
        'forecasting_data' => 'array',
        'trend_analysis' => 'array',
        'has_disputes' => 'boolean',
        'dispute_details' => 'array',
        'disputed_amount' => 'decimal:2',
        'adjustments_made' => 'array',
        'calculation_duration_ms' => 'integer',
        'performance_metrics' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_CALCULATED = 'calculated';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_APPROVED = 'approved';
    const STATUS_INVOICED = 'invoiced';
    const STATUS_DISPUTED = 'disputed';

    /**
     * Billing type constants
     */
    const BILLING_TYPE_MONTHLY = 'monthly';
    const BILLING_TYPE_QUARTERLY = 'quarterly';
    const BILLING_TYPE_ANNUALLY = 'annually';
    const BILLING_TYPE_CUSTOM = 'custom';
    const BILLING_TYPE_ONE_TIME = 'one_time';

    /**
     * Calculation method constants
     */
    const CALCULATION_METHOD_MANUAL = 'manual';
    const CALCULATION_METHOD_AUTOMATIC = 'automatic';
    const CALCULATION_METHOD_SCHEDULED = 'scheduled';
    const CALCULATION_METHOD_TRIGGERED = 'triggered';

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get asset assignments for this contract (for period calculations)
     */
    public function assetAssignments(): HasMany
    {
        return $this->contract->hasMany(ContractAssetAssignment::class)
                    ->whereBetween('start_date', [$this->billing_period_start, $this->billing_period_end])
                    ->orWhere(function($query) {
                        $query->where('start_date', '<=', $this->billing_period_start)
                              ->where(function($q) {
                                  $q->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $this->billing_period_end);
                              });
                    });
    }

    /**
     * Get contact assignments for this contract (for period calculations)
     */
    public function contactAssignments(): HasMany
    {
        return $this->contract->hasMany(ContractContactAssignment::class)
                    ->whereBetween('start_date', [$this->billing_period_start, $this->billing_period_end])
                    ->orWhere(function($query) {
                        $query->where('start_date', '<=', $this->billing_period_start)
                              ->where(function($q) {
                                  $q->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $this->billing_period_end);
                              });
                    });
    }

    /**
     * Scopes
     */
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('billing_period_start', '>=', $start)
                    ->where('billing_period_end', '<=', $end);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByBillingType($query, $type)
    {
        return $query->where('billing_type', $type);
    }

    public function scopeWithDisputes($query)
    {
        return $query->where('has_disputes', true);
    }

    public function scopeAutoInvoice($query)
    {
        return $query->where('auto_invoice', true);
    }

    public function scopeReadyForInvoicing($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
                    ->where('auto_invoice', true)
                    ->whereNull('invoice_id');
    }

    /**
     * Business Logic Methods
     */

    /**
     * Calculate variance from previous period
     */
    public function calculateVariance(): void
    {
        if ($this->previous_period_amount && $this->previous_period_amount > 0) {
            $this->amount_variance = $this->total_amount - $this->previous_period_amount;
            $this->variance_percentage = ($this->amount_variance / $this->previous_period_amount) * 100;
        } else {
            $this->amount_variance = $this->total_amount;
            $this->variance_percentage = null;
        }
    }

    /**
     * Mark calculation as completed
     */
    public function markCalculated(?User $user = null): void
    {
        $this->status = self::STATUS_CALCULATED;
        $this->calculated_at = now();
        $this->calculated_by = $user?->id;
        $this->save();
    }

    /**
     * Mark calculation as reviewed
     */
    public function markReviewed(User $user): void
    {
        $this->status = self::STATUS_REVIEWED;
        $this->reviewed_at = now();
        $this->reviewed_by = $user->id;
        $this->save();
    }

    /**
     * Mark calculation as approved
     */
    public function markApproved(User $user): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_at = now();
        $this->approved_by = $user->id;
        $this->save();
    }

    /**
     * Mark calculation as invoiced
     */
    public function markInvoiced(Invoice $invoice): void
    {
        $this->status = self::STATUS_INVOICED;
        $this->invoiced_at = now();
        $this->invoice_id = $invoice->id;
        $this->invoice_number = $invoice->invoice_number;
        $this->save();
    }

    /**
     * Add dispute to calculation
     */
    public function addDispute(array $disputeDetails): void
    {
        $this->status = self::STATUS_DISPUTED;
        $this->has_disputes = true;
        $this->dispute_details = array_merge($this->dispute_details ?? [], [$disputeDetails]);
        $this->disputed_amount = $disputeDetails['amount'] ?? 0;
        $this->save();
    }

    /**
     * Get formatted period description
     */
    public function getPeriodDescription(): string
    {
        if ($this->period_description) {
            return $this->period_description;
        }

        return $this->billing_period_start->format('M Y') . ' - ' . $this->billing_period_end->format('M Y');
    }

    /**
     * Check if calculation is ready for invoicing
     */
    public function isReadyForInvoicing(): bool
    {
        return $this->status === self::STATUS_APPROVED && 
               $this->auto_invoice && 
               !$this->invoice_id &&
               !$this->has_disputes;
    }

    /**
     * Get total service charges
     */
    public function getTotalServiceCharges(): float
    {
        return $this->monitoring_charges + 
               $this->backup_charges + 
               $this->security_charges + 
               $this->maintenance_charges;
    }

    /**
     * Get breakdown of all charges
     */
    public function getChargesBreakdown(): array
    {
        return [
            'base_contract' => $this->base_contract_amount,
            'fixed_monthly' => $this->fixed_monthly_charges,
            'asset_billing' => $this->asset_billing_total,
            'contact_billing' => $this->contact_billing_total,
            'usage_charges' => $this->usage_charges,
            'service_charges' => $this->getTotalServiceCharges(),
            'discounts' => -$this->discounts_applied,
            'surcharges' => $this->surcharges_applied,
            'tax' => $this->tax_amount,
            'total' => $this->total_amount
        ];
    }

    /**
     * Add a line item to the calculation log
     */
    public function addCalculationLogEntry(string $step, $data): void
    {
        $log = $this->calculation_log ?? [];
        $log[] = [
            'step' => $step,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ];
        $this->calculation_log = $log;
    }

    /**
     * Check if this is an unusual variance
     */
    public function hasUnusualVariance(float $threshold = 20.0): bool
    {
        return abs($this->variance_percentage ?? 0) > $threshold;
    }
}
