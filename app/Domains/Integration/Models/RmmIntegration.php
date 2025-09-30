<?php

namespace App\Domains\Integration\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * RMM Integration Model
 *
 * Represents RMM system integrations for companies.
 * Handles secure storage of API credentials and configuration.
 *
 * @property int $id
 * @property int $company_id
 * @property string $rmm_type
 * @property string $name
 * @property string $api_url_encrypted
 * @property string $api_key_encrypted
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_sync_at
 * @property array|null $settings
 * @property int $total_agents
 * @property int $last_alerts_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class RmmIntegration extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'rmm_type',
        'name',
        'api_url',
        'api_key',
        'api_url_encrypted',
        'api_key_encrypted',
        'is_active',
        'last_sync_at',
        'settings',
        'total_agents',
        'last_alerts_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'settings' => 'array',
        'total_agents' => 'integer',
        'last_alerts_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'api_url_encrypted',
        'api_key_encrypted',
    ];

    // RMM Type constants
    const RMM_TYPE_TACTICAL = 'TRMM';

    const RMM_TYPE_LABELS = [
        self::RMM_TYPE_TACTICAL => 'Tactical RMM',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    /**
     * Get the RMM alerts for this integration.
     */
    public function rmmAlerts(): HasMany
    {
        return $this->hasMany(RMMAlert::class, 'integration_id');
    }

    /**
     * Get the device mappings for this integration.
     */
    public function deviceMappings(): HasMany
    {
        return $this->hasMany(DeviceMapping::class, 'integration_id');
    }

    // ===========================================
    // ACCESSORS & MUTATORS
    // ===========================================

    /**
     * Get decrypted API URL.
     */
    public function getApiUrlAttribute(): ?string
    {
        if (! $this->api_url_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_url_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted API URL.
     */
    public function setApiUrlAttribute(?string $value): void
    {
        $this->attributes['api_url_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get decrypted API key.
     */
    public function getApiKeyAttribute(): ?string
    {
        if (! $this->api_key_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set encrypted API key.
     */
    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key_encrypted'] = $value ? Crypt::encryptString($value) : null;
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Check if integration is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get RMM type label.
     */
    public function getRmmTypeLabel(): string
    {
        return self::RMM_TYPE_LABELS[$this->rmm_type] ?? 'Unknown RMM';
    }

    /**
     * Test connection to RMM system.
     */
    public function testConnection(): array
    {
        try {
            // This will be implemented when we create the service classes
            $service = app('rmm.factory')->make($this);

            return $service->testConnection();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update last sync timestamp.
     */
    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Update agent count.
     */
    public function updateAgentCount(int $count): void
    {
        $this->update(['total_agents' => $count]);
    }

    /**
     * Update alerts count.
     */
    public function updateAlertsCount(int $count): void
    {
        $this->update(['last_alerts_count' => $count]);
    }

    /**
     * Get sync status information.
     */
    public function getSyncStatus(): array
    {
        $status = 'never';
        $message = 'Never synchronized';

        if ($this->last_sync_at) {
            $minutesAgo = $this->last_sync_at->diffInMinutes(now());

            if ($minutesAgo < 5) {
                $status = 'recent';
                $message = 'Recently synchronized';
            } elseif ($minutesAgo < 60) {
                $status = 'good';
                $message = "Synchronized {$minutesAgo} minutes ago";
            } elseif ($minutesAgo < 1440) { // 24 hours
                $hoursAgo = round($minutesAgo / 60);
                $status = 'warning';
                $message = "Synchronized {$hoursAgo} hours ago";
            } else {
                $daysAgo = round($minutesAgo / 1440);
                $status = 'error';
                $message = "Synchronized {$daysAgo} days ago";
            }
        }

        return [
            'status' => $status,
            'message' => $message,
            'last_sync' => $this->last_sync_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get default settings for RMM type.
     */
    public function getDefaultSettings(): array
    {
        switch ($this->rmm_type) {
            case self::RMM_TYPE_TACTICAL:
                return [
                    'sync_interval_minutes' => 15,
                    'sync_agents' => true,
                    'sync_alerts' => true,
                    'auto_create_tickets' => true,
                    'alert_severity_mapping' => [
                        'critical' => 'urgent',
                        'warning' => 'high',
                        'error' => 'high',
                        'info' => 'normal',
                    ],
                    'excluded_alert_types' => [],
                    'client_mapping_mode' => 'auto', // auto, manual
                ];
            default:
                return [];
        }
    }

    /**
     * Get setting value with fallback to default.
     */
    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?: [];
        $defaultSettings = $this->getDefaultSettings();

        return $settings[$key] ?? $defaultSettings[$key] ?? $default;
    }

    /**
     * Set setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?: [];
        $settings[$key] = $value;
        $this->update(['settings' => $settings]);
    }

    // ===========================================
    // SCOPES
    // ===========================================

    /**
     * Scope to filter by RMM type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rmm_type', $type);
    }

    /**
     * Scope to get active integrations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get integrations that need sync.
     */
    public function scopeNeedsSync($query, int $intervalMinutes = 15)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($intervalMinutes) {
                $q->whereNull('last_sync_at')
                    ->orWhere('last_sync_at', '<', now()->subMinutes($intervalMinutes));
            });
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    /**
     * Get available RMM types.
     */
    public static function getAvailableTypes(): array
    {
        return self::RMM_TYPE_LABELS;
    }

    /**
     * Create new integration with encrypted credentials.
     */
    public static function createWithCredentials(array $data): self
    {
        $integration = new self;
        $integration->fill($data);

        // Set default settings if not provided
        if (! isset($data['settings'])) {
            $integration->settings = $integration->getDefaultSettings();
        }

        $integration->save();

        return $integration;
    }

    // ===========================================
    // VALIDATION RULES
    // ===========================================

    /**
     * Get validation rules for creating integration.
     */
    public static function getValidationRules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'rmm_type' => 'required|in:'.implode(',', array_keys(self::RMM_TYPE_LABELS)),
            'name' => 'required|string|max:255',
            'api_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:255',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ];
    }

    /**
     * Get validation rules for updating integration.
     */
    public static function getUpdateValidationRules(): array
    {
        $rules = self::getValidationRules();

        // Make API credentials optional for updates
        $rules['api_url'] = 'nullable|url|max:255';
        $rules['api_key'] = 'nullable|string|max:255';

        return $rules;
    }
}
