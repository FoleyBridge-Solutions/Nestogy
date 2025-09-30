<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * Credit Note Item Model
 *
 * Represents individual line items within a credit note,
 * supporting complex VoIP billing scenarios, tax calculations,
 * equipment returns, and proration adjustments.
 */
class CreditNoteItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'credit_note_items';

    protected $fillable = [
        'company_id', 'credit_note_id', 'invoice_item_id', 'product_id', 'tax_category_id',
        'item_code', 'name', 'description', 'item_type', 'quantity', 'unit_price',
        'line_total', 'discount_amount', 'discount_percentage', 'tax_rate', 'tax_amount',
        'tax_breakdown', 'tax_inclusive', 'tax_exempt', 'tax_exemption_code',
        'voip_service_type', 'usage_details', 'regulatory_fees', 'jurisdiction_breakdown',
        'is_prorated', 'proration_details', 'service_period_start', 'service_period_end',
        'proration_days', 'total_period_days', 'original_quantity', 'original_unit_price',
        'original_line_total', 'original_tax_amount', 'credited_quantity', 'credited_amount',
        'remaining_credit', 'fully_credited', 'equipment_details', 'equipment_condition',
        'condition_adjustment', 'serial_number', 'equipment_returned', 'return_date',
        'gl_account_code', 'revenue_account_code', 'tax_account_code', 'accounting_entries',
        'sort_order', 'metadata',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'credit_note_id' => 'integer',
        'invoice_item_id' => 'integer',
        'product_id' => 'integer',
        'tax_category_id' => 'integer',
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'tax_inclusive' => 'boolean',
        'tax_exempt' => 'boolean',
        'regulatory_fees' => 'decimal:4',
        'is_prorated' => 'boolean',
        'proration_days' => 'integer',
        'total_period_days' => 'integer',
        'original_quantity' => 'decimal:4',
        'original_unit_price' => 'decimal:2',
        'original_line_total' => 'decimal:2',
        'original_tax_amount' => 'decimal:2',
        'credited_quantity' => 'decimal:4',
        'credited_amount' => 'decimal:2',
        'remaining_credit' => 'decimal:2',
        'fully_credited' => 'boolean',
        'condition_adjustment' => 'decimal:4',
        'equipment_returned' => 'boolean',
        'sort_order' => 'integer',
        'tax_breakdown' => 'array',
        'usage_details' => 'array',
        'jurisdiction_breakdown' => 'array',
        'proration_details' => 'array',
        'equipment_details' => 'array',
        'accounting_entries' => 'array',
        'metadata' => 'array',
        'service_period_start' => 'date',
        'service_period_end' => 'date',
        'return_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Item Types
    const TYPE_PRODUCT = 'product';

    const TYPE_SERVICE = 'service';

    const TYPE_VOIP_SERVICE = 'voip_service';

    const TYPE_EQUIPMENT = 'equipment';

    const TYPE_INSTALLATION = 'installation';

    const TYPE_MAINTENANCE = 'maintenance';

    const TYPE_REGULATORY_FEE = 'regulatory_fee';

    const TYPE_TAX_ADJUSTMENT = 'tax_adjustment';

    const TYPE_DISCOUNT = 'discount';

    const TYPE_OTHER = 'other';

    // VoIP Service Types
    const VOIP_LOCAL_SERVICE = 'local_service';

    const VOIP_LONG_DISTANCE = 'long_distance';

    const VOIP_INTERNATIONAL = 'international';

    const VOIP_TOLL_FREE = 'toll_free';

    const VOIP_DIRECTORY_ASSISTANCE = 'directory_assistance';

    const VOIP_EQUIPMENT_RENTAL = 'equipment_rental';

    const VOIP_INSTALLATION_FEE = 'installation_fee';

    const VOIP_ACTIVATION_FEE = 'activation_fee';

    const VOIP_REGULATORY_FEE = 'regulatory_fee';

    const VOIP_E911_FEE = 'e911_fee';

    const VOIP_USF_FEE = 'usf_fee';

    const VOIP_NUMBER_PORTING = 'number_porting';

    const VOIP_OTHER = 'other';

    // Equipment Conditions
    const CONDITION_NEW = 'new';

    const CONDITION_EXCELLENT = 'excellent';

    const CONDITION_GOOD = 'good';

    const CONDITION_FAIR = 'fair';

    const CONDITION_POOR = 'poor';

    const CONDITION_DAMAGED = 'damaged';

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeForCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? Auth::user()?->company_id;

        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeVoipServices($query)
    {
        return $query->where('item_type', self::TYPE_VOIP_SERVICE);
    }

    public function scopeEquipment($query)
    {
        return $query->where('item_type', self::TYPE_EQUIPMENT);
    }

    public function scopeProrated($query)
    {
        return $query->where('is_prorated', true);
    }

    public function scopeWithTax($query)
    {
        return $query->where('tax_amount', '>', 0);
    }

    public function scopeFullyCredited($query)
    {
        return $query->where('fully_credited', true);
    }

    public function scopeEquipmentReturned($query)
    {
        return $query->where('equipment_returned', true);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Calculate line total including tax
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price - $this->discount_amount;

        if ($this->tax_inclusive) {
            return $subtotal;
        }

        return $subtotal + $this->tax_amount;
    }

    /**
     * Calculate proration amount for partial periods
     */
    public function calculateProrationAmount(): float
    {
        if (! $this->is_prorated || ! $this->proration_days || ! $this->total_period_days) {
            return $this->line_total;
        }

        $prorationRatio = $this->proration_days / $this->total_period_days;

        return $this->line_total * $prorationRatio;
    }

    /**
     * Calculate equipment condition adjustment
     */
    public function calculateConditionAdjustment(): float
    {
        if (! $this->equipment_condition || $this->condition_adjustment == 0) {
            return 0;
        }

        return $this->line_total * ($this->condition_adjustment / 100);
    }

    /**
     * Get net credit amount after adjustments
     */
    public function getNetCreditAmount(): float
    {
        $baseAmount = $this->is_prorated ?
            $this->calculateProrationAmount() :
            $this->line_total;

        $conditionAdjustment = $this->calculateConditionAdjustment();

        return $baseAmount - $conditionAdjustment;
    }

    /**
     * Check if item is fully credited
     */
    public function isFullyCredited(): bool
    {
        return $this->fully_credited || $this->remaining_credit <= 0;
    }

    /**
     * Get VoIP service types
     */
    public static function getVoipServiceTypes(): array
    {
        return [
            self::VOIP_LOCAL_SERVICE => 'Local Service',
            self::VOIP_LONG_DISTANCE => 'Long Distance',
            self::VOIP_INTERNATIONAL => 'International',
            self::VOIP_TOLL_FREE => 'Toll Free',
            self::VOIP_DIRECTORY_ASSISTANCE => 'Directory Assistance',
            self::VOIP_EQUIPMENT_RENTAL => 'Equipment Rental',
            self::VOIP_INSTALLATION_FEE => 'Installation Fee',
            self::VOIP_ACTIVATION_FEE => 'Activation Fee',
            self::VOIP_REGULATORY_FEE => 'Regulatory Fee',
            self::VOIP_E911_FEE => 'E911 Fee',
            self::VOIP_USF_FEE => 'USF Fee',
            self::VOIP_NUMBER_PORTING => 'Number Porting',
            self::VOIP_OTHER => 'Other',
        ];
    }

    /**
     * Get equipment conditions
     */
    public static function getEquipmentConditions(): array
    {
        return [
            self::CONDITION_NEW => 'New',
            self::CONDITION_EXCELLENT => 'Excellent',
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_FAIR => 'Fair',
            self::CONDITION_POOR => 'Poor',
            self::CONDITION_DAMAGED => 'Damaged',
        ];
    }

    /**
     * Get item types
     */
    public static function getItemTypes(): array
    {
        return [
            self::TYPE_PRODUCT => 'Product',
            self::TYPE_SERVICE => 'Service',
            self::TYPE_VOIP_SERVICE => 'VoIP Service',
            self::TYPE_EQUIPMENT => 'Equipment',
            self::TYPE_INSTALLATION => 'Installation',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_REGULATORY_FEE => 'Regulatory Fee',
            self::TYPE_TAX_ADJUSTMENT => 'Tax Adjustment',
            self::TYPE_DISCOUNT => 'Discount',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Format currency amount
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, 2).' '.($this->creditNote->currency_code ?? 'USD');
    }

    /**
     * Get formatted line total
     */
    public function getFormattedLineTotalAttribute(): string
    {
        return $this->formatAmount($this->line_total);
    }

    /**
     * Get formatted net credit amount
     */
    public function getFormattedNetCreditAttribute(): string
    {
        return $this->formatAmount($this->getNetCreditAmount());
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (! $item->company_id) {
                $item->company_id = Auth::user()?->company_id;
            }

            // Auto-calculate line total if not provided
            if (! $item->line_total) {
                $subtotal = $item->quantity * $item->unit_price - ($item->discount_amount ?? 0);
                $item->line_total = $item->tax_inclusive ?
                    $subtotal :
                    $subtotal + ($item->tax_amount ?? 0);
            }

            // Initialize remaining credit
            if (! $item->remaining_credit) {
                $item->remaining_credit = $item->line_total;
            }
        });

        static::updating(function ($item) {
            // Recalculate line total if quantity or price changed
            if ($item->isDirty(['quantity', 'unit_price', 'discount_amount', 'tax_amount'])) {
                $subtotal = $item->quantity * $item->unit_price - ($item->discount_amount ?? 0);
                $item->line_total = $item->tax_inclusive ?
                    $subtotal :
                    $subtotal + ($item->tax_amount ?? 0);
            }

            // Update fully_credited status
            if ($item->isDirty('credited_amount')) {
                $item->remaining_credit = $item->line_total - $item->credited_amount;
                $item->fully_credited = $item->remaining_credit <= 0;
            }
        });
    }
}
