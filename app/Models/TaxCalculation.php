<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Tax Calculation Model
 *
 * Comprehensive audit trail for all tax calculations performed in the system.
 * Stores calculation inputs, outputs, API calls made, and validation status.
 *
 * @property int $id
 * @property int $company_id
 * @property string $calculable_type
 * @property int $calculable_id
 * @property string $calculation_id
 * @property string $engine_type
 * @property string|null $category_type
 * @property string $calculation_type
 * @property float $base_amount
 * @property int $quantity
 * @property array $input_parameters
 * @property array|null $customer_data
 * @property array|null $service_address
 * @property float $total_tax_amount
 * @property float $final_amount
 * @property float $effective_tax_rate
 * @property array $tax_breakdown
 * @property array|null $api_enhancements
 * @property array|null $jurisdictions
 * @property array|null $exemptions_applied
 * @property array $engine_metadata
 * @property array|null $api_calls_made
 * @property bool $validated
 * @property Carbon|null $validated_at
 * @property int|null $validated_by
 * @property string|null $validation_notes
 * @property string $status
 * @property array|null $status_history
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property array|null $change_log
 * @property int|null $calculation_time_ms
 * @property int $api_calls_count
 * @property float $api_cost
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TaxCalculation extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'tax_calculations';

    protected $fillable = [
        'company_id',
        'calculable_type',
        'calculable_id',
        'calculation_id',
        'engine_type',
        'category_type',
        'calculation_type',
        'base_amount',
        'quantity',
        'input_parameters',
        'customer_data',
        'service_address',
        'total_tax_amount',
        'final_amount',
        'effective_tax_rate',
        'tax_breakdown',
        'api_enhancements',
        'jurisdictions',
        'exemptions_applied',
        'engine_metadata',
        'api_calls_made',
        'validated',
        'validated_at',
        'validated_by',
        'validation_notes',
        'status',
        'status_history',
        'created_by',
        'updated_by',
        'change_log',
        'calculation_time_ms',
        'api_calls_count',
        'api_cost',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'calculable_id' => 'integer',
        'base_amount' => 'decimal:2',
        'quantity' => 'integer',
        'input_parameters' => 'array',
        'customer_data' => 'array',
        'service_address' => 'array',
        'total_tax_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'effective_tax_rate' => 'decimal:6',
        'tax_breakdown' => 'array',
        'api_enhancements' => 'array',
        'jurisdictions' => 'array',
        'exemptions_applied' => 'array',
        'engine_metadata' => 'array',
        'api_calls_made' => 'array',
        'validated' => 'boolean',
        'validated_at' => 'datetime',
        'validated_by' => 'integer',
        'status_history' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'change_log' => 'array',
        'calculation_time_ms' => 'integer',
        'api_calls_count' => 'integer',
        'api_cost' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Engine type constants
    const ENGINE_VOIP = 'voip';

    const ENGINE_GENERAL = 'general';

    const ENGINE_DIGITAL = 'digital';

    const ENGINE_EQUIPMENT = 'equipment';

    // Calculation type constants
    const TYPE_QUOTE = 'quote';

    const TYPE_INVOICE = 'invoice';

    const TYPE_PREVIEW = 'preview';

    const TYPE_ADJUSTMENT = 'adjustment';

    // Status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_CALCULATED = 'calculated';

    const STATUS_APPLIED = 'applied';

    const STATUS_ADJUSTED = 'adjusted';

    const STATUS_VOIDED = 'voided';

    /**
     * Get the company that owns this calculation
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the calculable entity (quote, invoice, etc.)
     */
    public function calculable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this calculation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this calculation
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who validated this calculation
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Generate a unique calculation ID
     */
    public static function generateCalculationId(): string
    {
        return 'tax_'.uniqid().'_'.now()->format('Ymd');
    }

    /**
     * Create a new tax calculation record
     */
    public static function createCalculation(
        int $companyId,
        $calculable,
        array $calculationResult,
        array $inputParameters,
        string $calculationType = self::TYPE_PREVIEW
    ): self {
        $calculationId = static::generateCalculationId();

        $calculation = static::create([
            'company_id' => $companyId,
            'calculable_type' => $calculable ? get_class($calculable) : null,
            'calculable_id' => $calculable ? $calculable->id : null,
            'calculation_id' => $calculationId,
            'engine_type' => $calculationResult['engine_used'] ?? 'general',
            'category_type' => $inputParameters['category_type'] ?? null,
            'calculation_type' => $calculationType,
            'base_amount' => $calculationResult['base_amount'] ?? 0,
            'quantity' => $inputParameters['quantity'] ?? 1,
            'input_parameters' => $inputParameters,
            'customer_data' => $inputParameters['customer_data'] ?? null,
            'service_address' => $inputParameters['customer_address'] ?? null,
            'total_tax_amount' => $calculationResult['total_tax_amount'] ?? 0,
            'final_amount' => $calculationResult['final_amount'] ?? 0,
            'effective_tax_rate' => $calculationResult['effective_tax_rate'] ?? 0,
            'tax_breakdown' => $calculationResult['tax_breakdown'] ?? [],
            'api_enhancements' => $calculationResult['api_enhancements'] ?? null,
            'jurisdictions' => $calculationResult['jurisdictions'] ?? null,
            'exemptions_applied' => $calculationResult['exemptions_applied'] ?? null,
            'engine_metadata' => [
                'engine_used' => $calculationResult['engine_used'] ?? 'general',
                'tax_profile' => $calculationResult['tax_profile'] ?? null,
                'calculation_date' => $calculationResult['calculation_date'] ?? now()->toISOString(),
                'api_enhancement_status' => $calculationResult['api_enhancements'] ?? null,
            ],
            'api_calls_made' => static::extractApiCallsInfo($calculationResult),
            'status' => self::STATUS_CALCULATED,
            'created_by' => auth()->id(),
        ]);

        $calculation->logStatusChange(self::STATUS_CALCULATED, 'Calculation completed');

        return $calculation;
    }

    /**
     * Extract API calls information from calculation result
     */
    protected static function extractApiCallsInfo(array $calculationResult): ?array
    {
        $apiCalls = [];

        if (isset($calculationResult['api_enhancements'])) {
            $enhancements = $calculationResult['api_enhancements'];

            foreach ($enhancements as $enhancementType => $data) {
                switch ($enhancementType) {
                    case 'vat':
                        $apiCalls[] = [
                            'provider' => 'vat_comply',
                            'endpoint' => 'vat_validation',
                            'success' => isset($data['vat_validation']['valid']),
                        ];
                        break;

                    case 'telecom':
                        $apiCalls[] = [
                            'provider' => 'fcc',
                            'endpoint' => 'telecom_taxes',
                            'success' => isset($data['telecom_taxes']),
                        ];
                        break;

                    case 'us_sales_tax':
                        $apiCalls[] = [
                            'provider' => 'taxcloud',
                            'endpoint' => 'sales_tax_calculation',
                            'success' => isset($data['sales_tax_calculation']['success']) && $data['sales_tax_calculation']['success'],
                        ];
                        break;

                    case 'jurisdictions':
                        if (isset($data['census_geography'])) {
                            $apiCalls[] = [
                                'provider' => 'census',
                                'endpoint' => 'geographic_info',
                                'success' => isset($data['census_geography']['found']) && $data['census_geography']['found'],
                            ];
                        }
                        break;
                }
            }
        }

        return ! empty($apiCalls) ? $apiCalls : null;
    }

    /**
     * Log a status change
     */
    public function logStatusChange(string $newStatus, string $reason = '', ?int $userId = null): void
    {
        $history = $this->status_history ?? [];

        $history[] = [
            'from_status' => $this->status,
            'to_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => $userId ?? auth()->id(),
            'changed_at' => now()->toISOString(),
        ];

        $this->update([
            'status' => $newStatus,
            'status_history' => $history,
            'updated_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Log a change to the calculation
     */
    public function logChange(string $action, array $changes = [], ?int $userId = null): void
    {
        $changeLog = $this->change_log ?? [];

        $changeLog[] = [
            'action' => $action,
            'changes' => $changes,
            'changed_by' => $userId ?? auth()->id(),
            'changed_at' => now()->toISOString(),
        ];

        // Keep only last 100 changes
        if (count($changeLog) > 100) {
            $changeLog = array_slice($changeLog, -100);
        }

        $this->update([
            'change_log' => $changeLog,
            'updated_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Validate the calculation
     */
    public function validateCalculation(string $notes = '', ?int $validatorId = null): void
    {
        $this->update([
            'validated' => true,
            'validated_at' => now(),
            'validated_by' => $validatorId ?? auth()->id(),
            'validation_notes' => $notes,
        ]);

        $this->logChange('validated', ['notes' => $notes], $validatorId);
    }

    /**
     * Apply the calculation (mark as applied to an invoice/quote)
     */
    public function applyCalculation(): void
    {
        $this->logStatusChange(self::STATUS_APPLIED, 'Calculation applied to document');
    }

    /**
     * Void the calculation
     */
    public function voidCalculation(string $reason = ''): void
    {
        $this->logStatusChange(self::STATUS_VOIDED, $reason);
    }

    /**
     * Scope for active calculations
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_VOIDED]);
    }

    /**
     * Scope for validated calculations
     */
    public function scopeValidated($query)
    {
        return $query->where('validated', true);
    }

    /**
     * Scope by calculation type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('calculation_type', $type);
    }

    /**
     * Scope by engine type
     */
    public function scopeByEngine($query, string $engine)
    {
        return $query->where('engine_type', $engine);
    }

    /**
     * Get calculation summary for reporting
     */
    public function getSummary(): array
    {
        return [
            'calculation_id' => $this->calculation_id,
            'calculation_type' => $this->calculation_type,
            'engine_type' => $this->engine_type,
            'category_type' => $this->category_type,
            'base_amount' => $this->base_amount,
            'total_tax_amount' => $this->total_tax_amount,
            'final_amount' => $this->final_amount,
            'effective_tax_rate' => $this->effective_tax_rate,
            'status' => $this->status,
            'validated' => $this->validated,
            'api_calls_count' => $this->api_calls_count,
            'api_cost' => $this->api_cost,
            'calculation_time_ms' => $this->calculation_time_ms,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Get tax breakdown summary
     */
    public function getTaxBreakdownSummary(): array
    {
        $breakdown = $this->tax_breakdown ?? [];
        $summary = [];

        foreach ($breakdown as $taxType => $taxData) {
            $summary[] = [
                'tax_type' => $taxType,
                'description' => $taxData['description'] ?? ucwords(str_replace('_', ' ', $taxType)),
                'rate' => $taxData['rate'] ?? null,
                'amount' => $taxData['amount'] ?? 0,
                'source' => $taxData['source'] ?? 'internal',
            ];
        }

        return $summary;
    }

    /**
     * Get jurisdiction breakdown
     */
    public function getJurisdictionBreakdown(): array
    {
        $jurisdictions = $this->jurisdictions ?? [];
        $breakdown = [];

        foreach ($jurisdictions as $jurisdiction) {
            $breakdown[] = [
                'type' => $jurisdiction['type'] ?? 'unknown',
                'name' => $jurisdiction['name'] ?? 'Unknown',
                'code' => $jurisdiction['code'] ?? $jurisdiction['fips_code'] ?? null,
                'level' => $jurisdiction['level'] ?? 0,
                'tax_rate' => $jurisdiction['tax_rate'] ?? null,
                'tax_amount' => $jurisdiction['tax_amount'] ?? null,
            ];
        }

        return $breakdown;
    }

    /**
     * Compare this calculation with another
     */
    public function compareWith(TaxCalculation $other): array
    {
        return [
            'base_amount_diff' => $this->base_amount - $other->base_amount,
            'tax_amount_diff' => $this->total_tax_amount - $other->total_tax_amount,
            'final_amount_diff' => $this->final_amount - $other->final_amount,
            'rate_diff' => $this->effective_tax_rate - $other->effective_tax_rate,
            'engine_changed' => $this->engine_type !== $other->engine_type,
            'api_enhancements_changed' => $this->api_enhancements !== $other->api_enhancements,
        ];
    }

    /**
     * Get calculation statistics for a company
     */
    public static function getCompanyStatistics(int $companyId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = static::where('company_id', $companyId);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $calculations = $query->get();

        return [
            'total_calculations' => $calculations->count(),
            'by_type' => $calculations->groupBy('calculation_type')->map->count(),
            'by_engine' => $calculations->groupBy('engine_type')->map->count(),
            'by_status' => $calculations->groupBy('status')->map->count(),
            'total_base_amount' => $calculations->sum('base_amount'),
            'total_tax_amount' => $calculations->sum('total_tax_amount'),
            'total_final_amount' => $calculations->sum('final_amount'),
            'average_tax_rate' => $calculations->avg('effective_tax_rate'),
            'validation_rate' => $calculations->where('validated', true)->count() / max(1, $calculations->count()) * 100,
            'total_api_calls' => $calculations->sum('api_calls_count'),
            'total_api_cost' => $calculations->sum('api_cost'),
            'average_calculation_time' => $calculations->avg('calculation_time_ms'),
        ];
    }
}
