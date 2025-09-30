<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SettingsConfiguration extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'domain',
        'category',
        'settings',
        'metadata',
        'is_active',
        'last_modified_at',
        'last_modified_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_modified_at' => 'datetime',
    ];

    /**
     * Available domains
     */
    const DOMAIN_COMPANY = 'company';

    const DOMAIN_COMMUNICATION = 'communication';

    const DOMAIN_FINANCIAL = 'financial';

    const DOMAIN_SECURITY = 'security';

    const DOMAIN_INTEGRATIONS = 'integrations';

    const DOMAIN_OPERATIONS = 'operations';

    const DOMAIN_SYSTEM = 'system';

    /**
     * Get all available domains with metadata
     */
    public static function getDomains(): array
    {
        return [
            self::DOMAIN_COMPANY => [
                'name' => 'Company',
                'icon' => 'building-office',
                'description' => 'Company information and branding',
                'categories' => ['general', 'branding', 'localization'],
            ],
            self::DOMAIN_COMMUNICATION => [
                'name' => 'Communication',
                'icon' => 'chat-bubble-left-right',
                'description' => 'Email, mail, and notifications',
                'categories' => ['email', 'physical_mail', 'notifications'],
            ],
            self::DOMAIN_FINANCIAL => [
                'name' => 'Financial',
                'icon' => 'currency-dollar',
                'description' => 'Billing, invoicing, and payments',
                'categories' => ['billing', 'invoicing', 'taxes', 'payment_gateways'],
            ],
            self::DOMAIN_SECURITY => [
                'name' => 'Security',
                'icon' => 'shield-check',
                'description' => 'Security and access control',
                'categories' => ['authentication', 'permissions', 'audit'],
            ],
            self::DOMAIN_INTEGRATIONS => [
                'name' => 'Integrations',
                'icon' => 'puzzle-piece',
                'description' => 'Third-party integrations',
                'categories' => ['rmm', 'accounting', 'apis'],
            ],
            self::DOMAIN_OPERATIONS => [
                'name' => 'Operations',
                'icon' => 'cog-6-tooth',
                'description' => 'Operational settings',
                'categories' => ['tickets', 'projects', 'assets', 'contracts'],
            ],
            self::DOMAIN_SYSTEM => [
                'name' => 'System',
                'icon' => 'server',
                'description' => 'System configuration',
                'categories' => ['performance', 'backup', 'maintenance'],
            ],
        ];
    }

    /**
     * Get the user who last modified this configuration
     */
    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Get cached settings for a company/domain/category
     */
    public static function getSettings(int $companyId, string $domain, string $category): array
    {
        $cacheKey = "settings_{$companyId}_{$domain}_{$category}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($companyId, $domain, $category) {
            $config = self::where('company_id', $companyId)
                ->where('domain', $domain)
                ->where('category', $category)
                ->where('is_active', true)
                ->first();

            return $config ? $config->settings : [];
        });
    }

    /**
     * Save settings and clear cache
     */
    public static function saveSettings(int $companyId, string $domain, string $category, array $settings, ?int $userId = null): self
    {
        $config = self::updateOrCreate(
            [
                'company_id' => $companyId,
                'domain' => $domain,
                'category' => $category,
            ],
            [
                'settings' => $settings,
                'is_active' => true,
                'last_modified_at' => now(),
                'last_modified_by' => $userId ?? auth()->id(),
            ]
        );

        // Clear cache
        Cache::forget("settings_{$companyId}_{$domain}_{$category}");

        return $config;
    }

    /**
     * Get setting value with dot notation support
     */
    public static function get(int $companyId, string $domain, string $category, string $key, $default = null)
    {
        $settings = self::getSettings($companyId, $domain, $category);

        return data_get($settings, $key, $default);
    }

    /**
     * Set a specific setting value
     */
    public static function set(int $companyId, string $domain, string $category, string $key, $value): void
    {
        $settings = self::getSettings($companyId, $domain, $category);
        data_set($settings, $key, $value);
        self::saveSettings($companyId, $domain, $category, $settings);
    }

    /**
     * Clear cache for this configuration
     */
    public function clearCache(): void
    {
        Cache::forget("settings_{$this->company_id}_{$this->domain}_{$this->category}");
    }
}
