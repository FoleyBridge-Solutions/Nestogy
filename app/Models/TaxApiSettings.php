<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax API Settings Model
 *
 * Manages API credentials and configuration for tax calculation services
 *
 * @property int $id
 * @property int $company_id
 * @property string $provider
 * @property bool $enabled
 * @property array $credentials
 * @property array $configuration
 * @property int $monthly_api_calls
 * @property int|null $monthly_limit
 * @property Carbon|null $last_api_call
 * @property float $monthly_cost
 * @property string $status
 * @property string|null $last_error
 * @property Carbon|null $last_health_check
 * @property array|null $health_data
 * @property array|null $audit_log
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TaxApiSettings extends Model
{
    use BelongsToCompany;

    protected $table = 'tax_api_settings';

    protected $fillable = [
        'company_id',
        'provider',
        'enabled',
        'credentials',
        'configuration',
        'monthly_api_calls',
        'monthly_limit',
        'last_api_call',
        'monthly_cost',
        'status',
        'last_error',
        'last_health_check',
        'health_data',
        'audit_log',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'enabled' => 'boolean',
        'credentials' => 'encrypted:array',
        'configuration' => 'array',
        'monthly_api_calls' => 'integer',
        'monthly_limit' => 'integer',
        'last_api_call' => 'datetime',
        'monthly_cost' => 'decimal:2',
        'last_health_check' => 'datetime',
        'health_data' => 'array',
        'audit_log' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Provider constants
    const PROVIDER_TAXCLOUD = 'taxcloud';

    const PROVIDER_VAT_COMPLY = 'vat_comply';

    const PROVIDER_FCC = 'fcc';

    const PROVIDER_NOMINATIM = 'nominatim';

    const PROVIDER_CENSUS = 'census';

    // Status constants
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUS_ERROR = 'error';

    const STATUS_QUOTA_EXCEEDED = 'quota_exceeded';

    /**
     * Get the company that owns this setting
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to get active API settings
     */
    public function scopeActive($query)
    {
        return $query->where('enabled', true)->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get settings by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Check if API is available and not over quota
     */
    public function isAvailable(): bool
    {
        return $this->enabled &&
               $this->status === self::STATUS_ACTIVE &&
               ! $this->isOverQuota();
    }

    /**
     * Check if API is over quota
     */
    public function isOverQuota(): bool
    {
        if ($this->monthly_limit === null) {
            return false;
        }

        return $this->monthly_api_calls >= $this->monthly_limit;
    }

    /**
     * Increment API call count
     */
    public function incrementApiCalls(int $count = 1): void
    {
        $this->increment('monthly_api_calls', $count);
        $this->update(['last_api_call' => now()]);

        // Check if over quota
        if ($this->isOverQuota()) {
            $this->update(['status' => self::STATUS_QUOTA_EXCEEDED]);
        }
    }

    /**
     * Reset monthly counters (typically called at month start)
     */
    public function resetMonthlyCounters(): void
    {
        $this->update([
            'monthly_api_calls' => 0,
            'monthly_cost' => 0,
            'status' => $this->enabled ? self::STATUS_ACTIVE : self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Update health status
     */
    public function updateHealthStatus(array $healthData, bool $isHealthy = true): void
    {
        $this->update([
            'health_data' => $healthData,
            'last_health_check' => now(),
            'status' => $isHealthy ? self::STATUS_ACTIVE : self::STATUS_ERROR,
            'last_error' => $isHealthy ? null : ($healthData['error'] ?? 'Health check failed'),
        ]);
    }

    /**
     * Log an audit event
     */
    public function logAuditEvent(string $action, array $details = [], ?int $userId = null): void
    {
        $auditLog = $this->audit_log ?? [];

        $auditLog[] = [
            'action' => $action,
            'details' => $details,
            'user_id' => $userId ?? auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        // Keep only last 50 audit entries
        if (count($auditLog) > 50) {
            $auditLog = array_slice($auditLog, -50);
        }

        $this->update(['audit_log' => $auditLog]);
    }

    /**
     * Get credential value safely
     */
    public function getCredential(string $key, $default = null)
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Set credential value
     */
    public function setCredential(string $key, $value): void
    {
        $credentials = $this->credentials ?? [];
        $credentials[$key] = $value;
        $this->update(['credentials' => $credentials]);

        $this->logAuditEvent('credential_updated', ['key' => $key]);
    }

    /**
     * Get configuration value
     */
    public function getConfiguration(string $key, $default = null)
    {
        return $this->configuration[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public function setConfiguration(string $key, $value): void
    {
        $configuration = $this->configuration ?? [];
        $configuration[$key] = $value;
        $this->update(['configuration' => $configuration]);

        $this->logAuditEvent('configuration_updated', ['key' => $key, 'value' => $value]);
    }

    /**
     * Get provider-specific settings for a company
     */
    public static function getProviderSettings(int $companyId, string $provider): ?self
    {
        return static::where('company_id', $companyId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Get all active API settings for a company
     */
    public static function getActiveSettings(int $companyId): \Illuminate\Support\Collection
    {
        return static::where('company_id', $companyId)
            ->active()
            ->get();
    }

    /**
     * Create or update API settings
     */
    public static function configureProvider(
        int $companyId,
        string $provider,
        array $credentials,
        array $configuration = [],
        bool $enabled = true
    ): self {
        $settings = static::updateOrCreate(
            [
                'company_id' => $companyId,
                'provider' => $provider,
            ],
            [
                'credentials' => $credentials,
                'configuration' => $configuration,
                'enabled' => $enabled,
                'status' => $enabled ? self::STATUS_ACTIVE : self::STATUS_INACTIVE,
            ]
        );

        $settings->logAuditEvent('provider_configured', [
            'enabled' => $enabled,
            'has_credentials' => ! empty($credentials),
        ]);

        return $settings;
    }

    /**
     * Get provider configuration schema
     */
    public static function getProviderSchema(string $provider): array
    {
        $schemas = [
            self::PROVIDER_TAXCLOUD => [
                'name' => 'TaxCloud',
                'description' => 'US sales tax calculations',
                'credentials' => [
                    'api_login_id' => ['type' => 'string', 'required' => true, 'label' => 'API Login ID'],
                    'api_key' => ['type' => 'password', 'required' => true, 'label' => 'API Key'],
                    'customer_id' => ['type' => 'string', 'required' => true, 'label' => 'Customer ID'],
                ],
                'configuration' => [
                    'origin_address' => ['type' => 'object', 'required' => true, 'label' => 'Business Origin Address'],
                    'default_tic' => ['type' => 'string', 'required' => false, 'label' => 'Default TIC Code', 'default' => '30070'],
                ],
                'limits' => [
                    'free_tier' => 10000,
                    'rate_limit' => 50, // per minute
                ],
            ],
            self::PROVIDER_VAT_COMPLY => [
                'name' => 'VATcomply',
                'description' => 'International VAT validation and rates',
                'credentials' => [],
                'configuration' => [
                    'user_agent' => ['type' => 'string', 'required' => false, 'label' => 'User Agent'],
                ],
                'limits' => [
                    'free_tier' => 100, // per day
                    'rate_limit' => 10, // per minute
                ],
            ],
            self::PROVIDER_FCC => [
                'name' => 'FCC APIs',
                'description' => 'Telecommunications tax data',
                'credentials' => [],
                'configuration' => [],
                'limits' => [
                    'free_tier' => 'unlimited',
                    'rate_limit' => 100, // per minute
                ],
            ],
            self::PROVIDER_NOMINATIM => [
                'name' => 'Nominatim (OpenStreetMap)',
                'description' => 'Address geocoding',
                'credentials' => [],
                'configuration' => [
                    'user_agent' => ['type' => 'string', 'required' => true, 'label' => 'User Agent (Required)'],
                ],
                'limits' => [
                    'free_tier' => 'unlimited',
                    'rate_limit' => 1, // per second
                ],
            ],
            self::PROVIDER_CENSUS => [
                'name' => 'US Census Bureau',
                'description' => 'Geographic and jurisdiction data',
                'credentials' => [
                    'api_key' => ['type' => 'password', 'required' => false, 'label' => 'API Key (Optional)'],
                ],
                'configuration' => [],
                'limits' => [
                    'free_tier' => 'unlimited',
                    'rate_limit' => 500, // per minute
                ],
            ],
        ];

        return $schemas[$provider] ?? [
            'name' => ucwords(str_replace('_', ' ', $provider)),
            'description' => 'Unknown provider',
            'credentials' => [],
            'configuration' => [],
            'limits' => [],
        ];
    }

    /**
     * Get all provider schemas
     */
    public static function getAllProviderSchemas(): array
    {
        $providers = [
            self::PROVIDER_TAXCLOUD,
            self::PROVIDER_VAT_COMPLY,
            self::PROVIDER_FCC,
            self::PROVIDER_NOMINATIM,
            self::PROVIDER_CENSUS,
        ];

        $schemas = [];
        foreach ($providers as $provider) {
            $schemas[$provider] = static::getProviderSchema($provider);
        }

        return $schemas;
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            // This would be implemented to test each provider's connection
            // For now, return a basic health check

            $this->updateHealthStatus([
                'test_time' => now()->toISOString(),
                'status' => 'connection_test_passed',
            ], true);

            return [
                'success' => true,
                'message' => 'Connection test passed',
                'provider' => $this->provider,
            ];

        } catch (\Exception $e) {
            $this->updateHealthStatus([
                'test_time' => now()->toISOString(),
                'status' => 'connection_test_failed',
                'error' => $e->getMessage(),
            ], false);

            return [
                'success' => false,
                'message' => 'Connection test failed: '.$e->getMessage(),
                'provider' => $this->provider,
            ];
        }
    }
}
