<?php

namespace App\Domains\Integration\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Integration Model
 * 
 * Represents RMM and other external system integrations.
 * Handles configuration, authentication, and field mappings.
 * 
 * @property int $id
 * @property string $uuid
 * @property int $company_id
 * @property string $provider
 * @property string $name
 * @property string|null $api_endpoint
 * @property string|null $webhook_url
 * @property string $credentials_encrypted
 * @property array|null $field_mappings
 * @property array|null $alert_rules
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_sync
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Integration extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'provider',
        'name',
        'api_endpoint',
        'webhook_url',
        'credentials_encrypted',
        'field_mappings',
        'alert_rules',
        'is_active',
        'last_sync',
    ];

    protected $casts = [
        'field_mappings' => 'array',
        'alert_rules' => 'array',
        'is_active' => 'boolean',
        'last_sync' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials_encrypted',
    ];

    // Provider constants
    const PROVIDER_CONNECTWISE = 'connectwise';
    const PROVIDER_DATTO = 'datto';
    const PROVIDER_NINJA = 'ninja';
    const PROVIDER_GENERIC = 'generic';

    const PROVIDER_LABELS = [
        self::PROVIDER_CONNECTWISE => 'ConnectWise Automate',
        self::PROVIDER_DATTO => 'Datto RMM',
        self::PROVIDER_NINJA => 'NinjaOne',
        self::PROVIDER_GENERIC => 'Generic RMM',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($integration) {
            $integration->uuid = Str::uuid();
        });
    }

    /**
     * Get the RMM alerts for this integration.
     */
    public function rmmAlerts(): HasMany
    {
        return $this->hasMany(RMMAlert::class);
    }

    /**
     * Get the device mappings for this integration.
     */
    public function deviceMappings(): HasMany
    {
        return $this->hasMany(DeviceMapping::class);
    }

    /**
     * Get decrypted credentials.
     */
    public function getCredentials(): array
    {
        if (!$this->credentials_encrypted) {
            return [];
        }

        try {
            return json_decode(decrypt($this->credentials_encrypted), true) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set encrypted credentials.
     */
    public function setCredentials(array $credentials): void
    {
        $this->credentials_encrypted = encrypt(json_encode($credentials));
    }

    /**
     * Get provider label.
     */
    public function getProviderLabel(): string
    {
        return self::PROVIDER_LABELS[$this->provider] ?? 'Unknown Provider';
    }

    /**
     * Check if integration is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get the webhook endpoint for this integration.
     */
    public function getWebhookEndpoint(): string
    {
        return route('api.webhooks.' . $this->provider, ['integration' => $this->uuid]);
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get active integrations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get default alert rules for provider.
     */
    public static function getDefaultAlertRules(string $provider): array
    {
        switch ($provider) {
            case self::PROVIDER_CONNECTWISE:
                return [
                    'severity_mapping' => [
                        'Critical' => 'urgent',
                        'High' => 'high',
                        'Medium' => 'normal',
                        'Low' => 'low',
                    ],
                    'auto_create_tickets' => true,
                    'auto_assign_technician' => false,
                    'notify_client' => false,
                ];
            case self::PROVIDER_DATTO:
                return [
                    'severity_mapping' => [
                        'critical' => 'urgent',
                        'warning' => 'high',
                        'info' => 'normal',
                    ],
                    'auto_create_tickets' => true,
                    'auto_assign_technician' => false,
                    'notify_client' => false,
                ];
            case self::PROVIDER_NINJA:
                return [
                    'severity_mapping' => [
                        'Critical' => 'urgent',
                        'Major' => 'high',
                        'Minor' => 'normal',
                        'Trivial' => 'low',
                    ],
                    'auto_create_tickets' => true,
                    'auto_assign_technician' => false,
                    'notify_client' => false,
                ];
            default:
                return [
                    'severity_mapping' => [
                        'critical' => 'urgent',
                        'high' => 'high',
                        'medium' => 'normal',
                        'low' => 'low',
                    ],
                    'auto_create_tickets' => true,
                    'auto_assign_technician' => false,
                    'notify_client' => false,
                ];
        }
    }

    /**
     * Get default field mappings for provider.
     */
    public static function getDefaultFieldMappings(string $provider): array
    {
        switch ($provider) {
            case self::PROVIDER_CONNECTWISE:
                return [
                    'device_id' => 'ComputerID',
                    'device_name' => 'ComputerName',
                    'client_id' => 'ClientID',
                    'alert_id' => 'AlertID',
                    'message' => 'AlertMessage',
                    'severity' => 'Severity',
                    'timestamp' => 'DateStamp',
                ];
            case self::PROVIDER_DATTO:
                return [
                    'device_id' => 'uid',
                    'device_name' => 'device_name',
                    'client_id' => 'site_name',
                    'alert_id' => 'alert_uid',
                    'message' => 'alert_message',
                    'severity' => 'alert_type',
                    'timestamp' => 'timestamp',
                ];
            case self::PROVIDER_NINJA:
                return [
                    'device_id' => 'deviceId',
                    'device_name' => 'deviceName',
                    'client_id' => 'organizationId',
                    'alert_id' => 'alertId',
                    'message' => 'alertMessage',
                    'severity' => 'alertType',
                    'timestamp' => 'createdAt',
                ];
            default:
                return [
                    'device_id' => 'device_id',
                    'device_name' => 'device_name',
                    'client_id' => 'client_id',
                    'alert_id' => 'alert_id',
                    'message' => 'message',
                    'severity' => 'severity',
                    'timestamp' => 'timestamp',
                ];
        }
    }

    /**
     * Get available providers.
     */
    public static function getAvailableProviders(): array
    {
        return self::PROVIDER_LABELS;
    }

    /**
     * Update last sync timestamp.
     */
    public function updateLastSync(): void
    {
        $this->update(['last_sync' => now()]);
    }
}