<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * QuoteTemplate Model
 *
 * Reusable quote templates with predefined items and configurations.
 * Generic implementation supporting any service type (VoIP, cloud, hosting, etc.).
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property array|null $template_items
 * @property array|null $service_config
 * @property array|null $pricing_config
 * @property array|null $tax_config
 * @property string|null $terms_conditions
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class QuoteTemplate extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'quote_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'category',
        'template_items',
        'service_config',
        'pricing_config',
        'tax_config',
        'terms_conditions',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'template_items' => 'array',
        'service_config' => 'array',
        'pricing_config' => 'array',
        'tax_config' => 'array',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Template categories - Generic for any service type
     */
    const CATEGORY_BASIC = 'basic';

    const CATEGORY_STANDARD = 'standard';

    const CATEGORY_PREMIUM = 'premium';

    const CATEGORY_ENTERPRISE = 'enterprise';

    const CATEGORY_CUSTOM = 'custom';

    const CATEGORY_EQUIPMENT = 'equipment';

    const CATEGORY_MAINTENANCE = 'maintenance';

    const CATEGORY_PROFESSIONAL = 'professional';

    const CATEGORY_MANAGED = 'managed';

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get quotes created from this template.
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'template_name', 'name');
    }

    /**
     * Check if template is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabel(): string
    {
        $labels = [
            self::CATEGORY_BASIC => 'Basic',
            self::CATEGORY_STANDARD => 'Standard',
            self::CATEGORY_PREMIUM => 'Premium',
            self::CATEGORY_ENTERPRISE => 'Enterprise',
            self::CATEGORY_CUSTOM => 'Custom',
            self::CATEGORY_EQUIPMENT => 'Equipment',
            self::CATEGORY_MAINTENANCE => 'Maintenance',
            self::CATEGORY_PROFESSIONAL => 'Professional Services',
            self::CATEGORY_MANAGED => 'Managed Services',
        ];

        return $labels[$this->category] ?? 'Unknown';
    }

    /**
     * Get service configuration.
     */
    public function getServiceConfig(): array
    {
        return $this->service_config ?: [];
    }

    /**
     * Get service type from configuration.
     */
    public function getServiceType(): string
    {
        return $this->service_config['service_type'] ?? 'general';
    }

    /**
     * Get pricing configuration.
     */
    public function getPricingConfig(): array
    {
        return $this->pricing_config ?: [
            'setup_fee' => 0.00,
            'recurring_fee' => 0.00,
            'per_unit_fee' => 0.00,
            'overage_rate' => 0.00,
        ];
    }

    /**
     * Get tax configuration.
     */
    public function getTaxConfig(): array
    {
        return $this->tax_config ?: [];
    }

    /**
     * Create quote from template.
     */
    public function createQuote(Client $client, array $customizations = []): Quote
    {
        $quoteData = [
            'company_id' => $this->company_id,
            'client_id' => $client->id,
            'template_name' => $this->name,
            'date' => now(),
            'expire_date' => now()->addDays(30),
            'currency_code' => 'USD',
            'status' => Quote::STATUS_DRAFT,
            'approval_status' => Quote::APPROVAL_PENDING,
            'service_config' => array_merge($this->getServiceConfig(), $customizations['service_config'] ?? []),
            'pricing_model' => array_merge($this->getPricingConfig(), $customizations['pricing'] ?? []),
            'tax_config' => array_merge($this->getTaxConfig(), $customizations['tax_config'] ?? []),
            'terms_conditions' => $this->terms_conditions,
        ];

        // Apply customizations
        foreach ($customizations as $key => $value) {
            if ($key !== 'service_config' && $key !== 'pricing' && $key !== 'tax_config') {
                $quoteData[$key] = $value;
            }
        }

        $quote = Quote::create($quoteData);

        // Add template items
        if ($this->template_items) {
            $order = 1;
            foreach ($this->template_items as $item) {
                $quote->items()->create([
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'order' => $order++,
                    'tax_id' => $item['tax_id'] ?? null,
                    'category_id' => $item['category_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                ]);
            }
        }

        $quote->calculateTotals();

        return $quote;
    }

    /**
     * Get template usage statistics.
     */
    public function getUsageStats(): array
    {
        return [
            'quotes_created' => $this->quotes()->count(),
            'quotes_accepted' => $this->quotes()->where('status', Quote::STATUS_ACCEPTED)->count(),
            'quotes_converted' => $this->quotes()->where('status', Quote::STATUS_CONVERTED)->count(),
            'total_value' => $this->quotes()->where('status', Quote::STATUS_ACCEPTED)->sum('amount'),
        ];
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get templates by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search templates.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('description', 'like', '%'.$search.'%');
        });
    }

    /**
     * Get validation rules for template creation.
     */
    public static function getValidationRules(): array
    {
        $categories = implode(',', static::getAvailableCategories());

        return [
            'name' => 'required|string|max:255|unique:quote_templates,name',
            'description' => 'nullable|string',
            'category' => 'required|in:'.$categories,
            'template_items' => 'nullable|array',
            'template_items.*.name' => 'required|string|max:255',
            'template_items.*.description' => 'nullable|string',
            'template_items.*.quantity' => 'required|numeric|min:0.01',
            'template_items.*.price' => 'required|numeric|min:0',
            'template_items.*.discount' => 'nullable|numeric|min:0',
            'service_config' => 'nullable|array',
            'pricing_config' => 'nullable|array',
            'tax_config' => 'nullable|array',
            'terms_conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get validation rules for template update.
     */
    public static function getUpdateValidationRules(int $templateId): array
    {
        $rules = static::getValidationRules();
        $rules['name'] = 'required|string|max:255|unique:quote_templates,name,'.$templateId;

        return $rules;
    }

    /**
     * Get available categories.
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_BASIC,
            self::CATEGORY_STANDARD,
            self::CATEGORY_PREMIUM,
            self::CATEGORY_ENTERPRISE,
            self::CATEGORY_CUSTOM,
            self::CATEGORY_EQUIPMENT,
            self::CATEGORY_MAINTENANCE,
            self::CATEGORY_PROFESSIONAL,
            self::CATEGORY_MANAGED,
        ];
    }

    /**
     * Create default service templates.
     */
    public static function createDefaultTemplates(int $companyId): void
    {
        $templates = [
            [
                'name' => 'Basic Telecom Package',
                'description' => 'Entry-level telecommunications solution for small businesses',
                'category' => self::CATEGORY_BASIC,
                'template_items' => [
                    [
                        'name' => 'Service Setup',
                        'description' => 'Initial setup and configuration',
                        'quantity' => 1,
                        'price' => 199.00,
                        'discount' => 0,
                    ],
                    [
                        'name' => 'Monthly Service',
                        'description' => 'Basic service package',
                        'quantity' => 1,
                        'price' => 89.00,
                        'discount' => 0,
                    ],
                ],
                'service_config' => [
                    'service_type' => 'voip',
                    'units' => 5,
                    'features' => [
                        'basic_features' => true,
                        'premium_features' => false,
                    ],
                ],
                'pricing_config' => [
                    'setup_fee' => 199.00,
                    'recurring_fee' => 89.00,
                    'per_unit_fee' => 18.00,
                ],
                'tax_config' => [
                    'apply_regulatory_fees' => true,
                    'tax_exempt' => false,
                ],
            ],
            [
                'name' => 'Managed IT Services',
                'description' => 'Comprehensive IT management for businesses',
                'category' => self::CATEGORY_MANAGED,
                'template_items' => [
                    [
                        'name' => 'Managed Service Setup',
                        'description' => 'Initial assessment and configuration',
                        'quantity' => 1,
                        'price' => 999.00,
                        'discount' => 0,
                    ],
                    [
                        'name' => 'Monthly Management Fee',
                        'description' => 'Per device management',
                        'quantity' => 20,
                        'price' => 49.00,
                        'discount' => 0,
                    ],
                ],
                'service_config' => [
                    'service_type' => 'managed_services',
                    'device_count' => 20,
                    'support_level' => 'premium',
                ],
                'pricing_config' => [
                    'setup_fee' => 999.00,
                    'recurring_fee' => 980.00,
                    'per_unit_fee' => 49.00,
                ],
                'tax_config' => [
                    'apply_regulatory_fees' => false,
                    'tax_exempt' => false,
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $templateData['company_id'] = $companyId;
            $templateData['is_active'] = true;
            $templateData['created_by'] = auth()->id();
            static::create($templateData);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set creator when creating
        static::creating(function ($template) {
            if (! $template->created_by && auth()->check()) {
                $template->created_by = auth()->id();
            }
        });
    }
}
