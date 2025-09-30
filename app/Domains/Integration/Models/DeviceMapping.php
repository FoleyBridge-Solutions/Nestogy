<?php

namespace App\Domains\Integration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * DeviceMapping Model
 *
 * Maps devices from RMM systems to internal assets and clients.
 * Handles synchronization and device identification.
 *
 * @property int $id
 * @property string $uuid
 * @property int $integration_id
 * @property string $rmm_device_id
 * @property int|null $asset_id
 * @property int $client_id
 * @property string $device_name
 * @property array|null $sync_data
 * @property \Illuminate\Support\Carbon $last_updated
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DeviceMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_id',
        'rmm_device_id',
        'asset_id',
        'client_id',
        'device_name',
        'sync_data',
        'last_updated',
        'is_active',
    ];

    protected $casts = [
        'sync_data' => 'array',
        'last_updated' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mapping) {
            $mapping->uuid = Str::uuid();

            if (! $mapping->last_updated) {
                $mapping->last_updated = now();
            }
        });
    }

    /**
     * Get the integration that owns this mapping.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get the associated asset.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Asset::class);
    }

    /**
     * Get the associated client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    /**
     * Check if mapping is active.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Check if device is mapped to an asset.
     */
    public function hasAsset(): bool
    {
        return ! is_null($this->asset_id);
    }

    /**
     * Update sync data and last updated timestamp.
     */
    public function updateSyncData(array $data): void
    {
        $this->update([
            'sync_data' => array_merge($this->sync_data ?? [], $data),
            'last_updated' => now(),
        ]);
    }

    /**
     * Mark mapping as inactive.
     */
    public function markInactive(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Mark mapping as active.
     */
    public function markActive(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Link device to an asset.
     */
    public function linkToAsset(int $assetId): void
    {
        $this->update(['asset_id' => $assetId]);
    }

    /**
     * Unlink device from asset.
     */
    public function unlinkFromAsset(): void
    {
        $this->update(['asset_id' => null]);
    }

    /**
     * Get device information from sync data.
     */
    public function getDeviceInfo(): array
    {
        return $this->sync_data ?? [];
    }

    /**
     * Get specific sync data field.
     */
    public function getSyncDataField(string $field, $default = null)
    {
        return data_get($this->sync_data, $field, $default);
    }

    /**
     * Set specific sync data field.
     */
    public function setSyncDataField(string $field, $value): void
    {
        $syncData = $this->sync_data ?? [];
        data_set($syncData, $field, $value);

        $this->update([
            'sync_data' => $syncData,
            'last_updated' => now(),
        ]);
    }

    /**
     * Check if device data is stale.
     */
    public function isStale(int $hoursThreshold = 24): bool
    {
        return $this->last_updated->addHours($hoursThreshold)->isPast();
    }

    /**
     * Scope to filter by integration.
     */
    public function scopeForIntegration($query, int $integrationId)
    {
        return $query->where('integration_id', $integrationId);
    }

    /**
     * Scope to filter by client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to get active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get inactive mappings.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to get mapped devices (linked to assets).
     */
    public function scopeMapped($query)
    {
        return $query->whereNotNull('asset_id');
    }

    /**
     * Scope to get unmapped devices (not linked to assets).
     */
    public function scopeUnmapped($query)
    {
        return $query->whereNull('asset_id');
    }

    /**
     * Scope to get stale mappings.
     */
    public function scopeStale($query, int $hoursThreshold = 24)
    {
        return $query->where('last_updated', '<', now()->subHours($hoursThreshold));
    }

    /**
     * Scope to get fresh mappings.
     */
    public function scopeFresh($query, int $hoursThreshold = 24)
    {
        return $query->where('last_updated', '>=', now()->subHours($hoursThreshold));
    }

    /**
     * Scope to search by device name.
     */
    public function scopeSearchByName($query, string $search)
    {
        return $query->where('device_name', 'like', '%'.$search.'%');
    }

    /**
     * Find or create device mapping.
     */
    public static function findOrCreateMapping(
        int $integrationId,
        string $rmmDeviceId,
        int $clientId,
        string $deviceName,
        array $syncData = []
    ): self {
        return static::firstOrCreate(
            [
                'integration_id' => $integrationId,
                'rmm_device_id' => $rmmDeviceId,
            ],
            [
                'client_id' => $clientId,
                'device_name' => $deviceName,
                'sync_data' => $syncData,
                'last_updated' => now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Update or create device mapping.
     */
    public static function updateOrCreateMapping(
        int $integrationId,
        string $rmmDeviceId,
        int $clientId,
        string $deviceName,
        array $syncData = []
    ): self {
        return static::updateOrCreate(
            [
                'integration_id' => $integrationId,
                'rmm_device_id' => $rmmDeviceId,
            ],
            [
                'client_id' => $clientId,
                'device_name' => $deviceName,
                'sync_data' => $syncData,
                'last_updated' => now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Sync device information from RMM payload.
     */
    public function syncFromPayload(array $payload, array $fieldMappings): void
    {
        $deviceName = data_get($payload, $fieldMappings['device_name'], $this->device_name);

        $this->update([
            'device_name' => $deviceName,
            'sync_data' => array_merge($this->sync_data ?? [], [
                'last_payload' => $payload,
                'last_sync' => now()->toISOString(),
            ]),
            'last_updated' => now(),
        ]);
    }
}
