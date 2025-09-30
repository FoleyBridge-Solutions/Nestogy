<?php

namespace App\Domains\Integration\Models;

use App\Models\Client;
use App\Models\Company;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RMM Client Mapping Model
 *
 * Maps Nestogy clients to RMM system clients for proper agent synchronization.
 * Ensures that agents from the RMM system are assigned to the correct clients
 * in the Nestogy system.
 */
class RmmClientMapping extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'integration_id',
        'rmm_client_id',
        'rmm_client_name',
        'rmm_client_data',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rmm_client_data' => 'array',
        'last_sync_at' => 'datetime',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    /**
     * Get the company that owns this mapping.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Nestogy client for this mapping.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the RMM integration for this mapping.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(RmmIntegration::class, 'integration_id');
    }

    // ===========================================
    // SCOPES
    // ===========================================

    /**
     * Scope to only active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to mappings for a specific integration.
     */
    public function scopeForIntegration($query, $integrationId)
    {
        return $query->where('integration_id', $integrationId);
    }

    /**
     * Scope to mappings for a specific RMM client.
     */
    public function scopeForRmmClient($query, $rmmClientId)
    {
        return $query->where('rmm_client_id', $rmmClientId);
    }

    // ===========================================
    // BUSINESS LOGIC METHODS
    // ===========================================

    /**
     * Find Nestogy client by RMM client ID.
     */
    public static function findClientByRmmId(int $integrationId, string $rmmClientId): ?Client
    {
        $mapping = static::where('integration_id', $integrationId)
            ->where('rmm_client_id', $rmmClientId)
            ->where('is_active', true)
            ->first();

        return $mapping?->client;
    }

    /**
     * Find Nestogy client by RMM client name.
     */
    public static function findClientByRmmName(int $integrationId, string $rmmClientName): ?Client
    {
        $mapping = static::where('integration_id', $integrationId)
            ->where('rmm_client_name', $rmmClientName)
            ->where('is_active', true)
            ->first();

        return $mapping?->client;
    }

    /**
     * Find RMM client ID by Nestogy client.
     */
    public static function findRmmIdByClient(int $integrationId, int $clientId): ?string
    {
        $mapping = static::where('integration_id', $integrationId)
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        return $mapping?->rmm_client_id;
    }

    /**
     * Create or update mapping.
     */
    public static function createOrUpdateMapping(array $data): self
    {
        return static::updateOrCreate([
            'integration_id' => $data['integration_id'],
            'client_id' => $data['client_id'],
        ], $data);
    }

    /**
     * Update last sync time.
     */
    public function markSynced(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Check if mapping needs sync (based on age).
     */
    public function needsSync(int $hoursThreshold = 24): bool
    {
        if (! $this->last_sync_at) {
            return true;
        }

        return $this->last_sync_at->diffInHours(now()) > $hoursThreshold;
    }

    /**
     * Get mapping statistics.
     */
    public static function getStats(int $integrationId): array
    {
        $query = static::where('integration_id', $integrationId);

        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
            'synced_today' => $query->where('last_sync_at', '>=', now()->startOfDay())->count(),
            'never_synced' => $query->whereNull('last_sync_at')->count(),
        ];
    }
}
