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
 * Specialized for VoIP services with equipment, lines, and features.
 * 
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property array|null $template_items
 * @property array|null $voip_config
 * @property array|null $default_pricing
 * @property string|null $terms_conditions
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 */
class QuoteTemplate extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

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
        'voip_config',
        'default_pricing',
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
        'voip_config' => 'array',
        'default_pricing' => 'array',
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
     * Template categories
     */
    const CATEGORY_VOIP_BASIC = 'voip_basic';
    const CATEGORY_VOIP_PREMIUM = 'voip_premium';
    const CATEGORY_VOIP_ENTERPRISE = 'voip_enterprise';
    const CATEGORY_PHONE_SYSTEMS = 'phone_systems';
    const CATEGORY_SIP_TRUNKS = 'sip_trunks';
    const CATEGORY_EQUIPMENT = 'equipment';
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_CUSTOM = 'custom';

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
            self::CATEGORY_VOIP_BASIC => 'VoIP Basic',
            self::CATEGORY_VOIP_PREMIUM => 'VoIP Premium',
            self::CATEGORY_VOIP_ENTERPRISE => 'VoIP Enterprise',
            self::CATEGORY_PHONE_SYSTEMS => 'Phone Systems',
            self::CATEGORY_SIP_TRUNKS => 'SIP Trunks',
            self::CATEGORY_EQUIPMENT => 'Equipment',
            self::CATEGORY_MAINTENANCE => 'Maintenance',
            self::CATEGORY_CUSTOM => 'Custom',
        ];

        return $labels[$this->category] ?? 'Unknown';
    }

    /**
     * Get default VoIP configuration.
     */
    public function getVoipConfig(): array
    {
        return $this->voip_config ?: [
            'extensions' => 10,
            'concurrent_calls' => 5,
            'features' => [
                'voicemail' => true,
                'call_forwarding' => true,
                'conference_calling' => false,
                'auto_attendant' => false,
            ],
            'equipment' => [
                'desk_phones' => 5,
                'wireless_phones' => 2,
                'conference_phone' => 0,
            ],
            'monthly_allowances' => [
                'local_minutes' => 'unlimited',
                'long_distance_minutes' => 500,
                'international_minutes' => 0,
            ],
        ];
    }

    /**
     * Get default pricing structure.
     */
    public function getDefaultPricing(): array
    {
        return $this->default_pricing ?: [
            'setup_fee' => 0.00,
            'monthly_recurring' => 0.00,
            'per_extension' => 0.00,
            'per_minute_overage' => 0.05,
            'equipment_lease' => 0.00,
        ];
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
            'voip_config' => array_merge($this->getVoipConfig(), $customizations['voip_config'] ?? []),
            'pricing_model' => array_merge($this->getDefaultPricing(), $customizations['pricing'] ?? []),
            'terms_conditions' => $this->terms_conditions,
        ];

        // Apply customizations
        foreach ($customizations as $key => $value) {
            if ($key !== 'voip_config' && $key !== 'pricing') {
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
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
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
            'category' => 'required|in:' . $categories,
            'template_items' => 'nullable|array',
            'template_items.*.name' => 'required|string|max:255',
            'template_items.*.description' => 'nullable|string',
            'template_items.*.quantity' => 'required|numeric|min:0.01',
            'template_items.*.price' => 'required|numeric|min:0',
            'template_items.*.discount' => 'nullable|numeric|min:0',
            'voip_config' => 'nullable|array',
            'default_pricing' => 'nullable|array',
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
        $rules['name'] = 'required|string|max:255|unique:quote_templates,name,' . $templateId;
        return $rules;
    }

    /**
     * Get available categories.
     */
    public static function getAvailableCategories(): array
    {
        return [
            self::CATEGORY_VOIP_BASIC,
            self::CATEGORY_VOIP_PREMIUM,
            self::CATEGORY_VOIP_ENTERPRISE,
            self::CATEGORY_PHONE_SYSTEMS,
            self::CATEGORY_SIP_TRUNKS,
            self::CATEGORY_EQUIPMENT,
            self::CATEGORY_MAINTENANCE,
            self::CATEGORY_CUSTOM,
        ];
    }

    /**
     * Create default VoIP templates.
     */
    public static function createDefaultTemplates(int $companyId): void
    {
        $templates = [
            [
                'name' => 'Basic VoIP Package',
                'description' => 'Entry-level VoIP solution for small businesses',
                'category' => self::CATEGORY_VOIP_BASIC,
                'template_items' => [
                    [
                        'name' => 'VoIP Service Setup',
                        'description' => 'Initial setup and configuration',
                        'quantity' => 1,
                        'price' => 199.00,
                        'discount' => 0,
                    ],
                    [
                        'name' => 'Monthly VoIP Service',
                        'description' => 'Up to 5 extensions with basic features',
                        'quantity' => 1,
                        'price' => 89.00,
                        'discount' => 0,
                    ],
                    [
                        'name' => 'IP Desk Phone',
                        'description' => 'Standard business IP phone',
                        'quantity' => 5,
                        'price' => 89.00,
                        'discount' => 0,
                    ],
                ],
                'voip_config' => [
                    'extensions' => 5,
                    'concurrent_calls' => 3,
                    'features' => [
                        'voicemail' => true,
                        'call_forwarding' => true,
                        'conference_calling' => false,
                        'auto_attendant' => false,
                    ],
                ],
                'default_pricing' => [
                    'setup_fee' => 199.00,
                    'monthly_recurring' => 89.00,
                    'per_extension' => 18.00,
                ],
            ],
            [
                'name' => 'Premium VoIP Package',
                'description' => 'Full-featured VoIP solution for medium businesses',
                'category' => self::CATEGORY_VOIP_PREMIUM,
                'template_items' => [
                    [
                        'name' => 'VoIP Service Setup',
                        'description' => 'Professional setup and configuration',
                        'quantity' => 1,
                        'price' => 399.00,
                        'discount' => 0,
                    ],
                    [
                        'name' => 'Monthly VoIP Service',
                        'description' => 'Up to 20 extensions with premium features',
                        'quantity' => 1,
                        'price' => 199.00,
                        'discount' => 0,
                    ],
                    [
                        'name' => 'IP Desk Phone Premium',
                        'description' => 'Advanced business IP phone with color display',
                        'quantity' => 10,
                        'price' => 149.00,
                        'discount' => 0,
                    ],
                ],
                'voip_config' => [
                    'extensions' => 20,
                    'concurrent_calls' => 10,
                    'features' => [
                        'voicemail' => true,
                        'call_forwarding' => true,
                        'conference_calling' => true,
                        'auto_attendant' => true,
                    ],
                ],
                'default_pricing' => [
                    'setup_fee' => 399.00,
                    'monthly_recurring' => 199.00,
                    'per_extension' => 15.00,
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
            if (!$template->created_by && auth()->check()) {
                $template->created_by = auth()->id();
            }
        });
    }
}