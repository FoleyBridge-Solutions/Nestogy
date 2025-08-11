<?php

namespace App\Domains\Integration\Models;

use App\Domains\Ticket\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * RMMAlert Model
 * 
 * Represents alerts received from RMM systems.
 * Tracks processing status and ticket creation.
 * 
 * @property int $id
 * @property string $uuid
 * @property int $integration_id
 * @property string $external_alert_id
 * @property string|null $device_id
 * @property int|null $asset_id
 * @property string $alert_type
 * @property string $severity
 * @property string $message
 * @property array $raw_payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property int|null $ticket_id
 * @property bool $is_duplicate
 * @property string|null $duplicate_hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RMMAlert extends Model
{
    use HasFactory;

    protected $table = 'rmm_alerts';

    protected $fillable = [
        'integration_id',
        'external_alert_id',
        'device_id',
        'asset_id',
        'alert_type',
        'severity',
        'message',
        'raw_payload',
        'processed_at',
        'ticket_id',
        'is_duplicate',
        'duplicate_hash',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
        'is_duplicate' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Severity constants
    const SEVERITY_URGENT = 'urgent';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_NORMAL = 'normal';
    const SEVERITY_LOW = 'low';

    const SEVERITY_LABELS = [
        self::SEVERITY_URGENT => 'Urgent',
        self::SEVERITY_HIGH => 'High',
        self::SEVERITY_NORMAL => 'Normal',
        self::SEVERITY_LOW => 'Low',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($alert) {
            $alert->uuid = Str::uuid();
            
            // Generate duplicate hash for deduplication
            if (!$alert->duplicate_hash) {
                $alert->duplicate_hash = $alert->generateDuplicateHash();
            }
        });
    }

    /**
     * Get the integration that owns this alert.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get the ticket created from this alert.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the associated asset.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Asset::class);
    }

    /**
     * Check if alert has been processed.
     */
    public function isProcessed(): bool
    {
        return !is_null($this->processed_at);
    }

    /**
     * Check if alert is a duplicate.
     */
    public function isDuplicate(): bool
    {
        return $this->is_duplicate === true;
    }

    /**
     * Mark alert as processed.
     */
    public function markProcessed(): void
    {
        $this->update(['processed_at' => now()]);
    }

    /**
     * Mark alert as duplicate.
     */
    public function markDuplicate(): void
    {
        $this->update(['is_duplicate' => true]);
    }

    /**
     * Get severity label.
     */
    public function getSeverityLabel(): string
    {
        return self::SEVERITY_LABELS[$this->severity] ?? 'Unknown';
    }

    /**
     * Generate duplicate hash for deduplication.
     */
    public function generateDuplicateHash(): string
    {
        $data = [
            'integration_id' => $this->integration_id,
            'device_id' => $this->device_id,
            'alert_type' => $this->alert_type,
            'message' => $this->message,
        ];

        return md5(json_encode($data));
    }

    /**
     * Check for similar recent alerts to prevent duplicates.
     */
    public function hasSimilarRecentAlert(int $hoursBack = 1): bool
    {
        return static::where('duplicate_hash', $this->duplicate_hash)
            ->where('id', '!=', $this->id ?? 0)
            ->where('created_at', '>=', now()->subHours($hoursBack))
            ->exists();
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to get unprocessed alerts.
     */
    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    /**
     * Scope to get processed alerts.
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    /**
     * Scope to get non-duplicate alerts.
     */
    public function scopeNonDuplicate($query)
    {
        return $query->where('is_duplicate', false);
    }

    /**
     * Scope to get duplicate alerts.
     */
    public function scopeDuplicate($query)
    {
        return $query->where('is_duplicate', true);
    }

    /**
     * Scope to get alerts for a specific integration.
     */
    public function scopeForIntegration($query, int $integrationId)
    {
        return $query->where('integration_id', $integrationId);
    }

    /**
     * Scope to get recent alerts.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope to order by severity priority.
     */
    public function scopeOrderBySeverity($query)
    {
        return $query->orderByRaw("
            CASE severity
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END
        ");
    }

    /**
     * Get available severities.
     */
    public static function getAvailableSeverities(): array
    {
        return self::SEVERITY_LABELS;
    }

    /**
     * Convert severity from RMM provider format to internal format.
     */
    public static function normalizeSeverity(string $providerSeverity, string $provider): string
    {
        $mappings = [
            Integration::PROVIDER_CONNECTWISE => [
                'Critical' => self::SEVERITY_URGENT,
                'High' => self::SEVERITY_HIGH,
                'Medium' => self::SEVERITY_NORMAL,
                'Low' => self::SEVERITY_LOW,
            ],
            Integration::PROVIDER_DATTO => [
                'critical' => self::SEVERITY_URGENT,
                'warning' => self::SEVERITY_HIGH,
                'info' => self::SEVERITY_NORMAL,
            ],
            Integration::PROVIDER_NINJA => [
                'Critical' => self::SEVERITY_URGENT,
                'Major' => self::SEVERITY_HIGH,
                'Minor' => self::SEVERITY_NORMAL,
                'Trivial' => self::SEVERITY_LOW,
            ],
        ];

        return $mappings[$provider][$providerSeverity] ?? self::SEVERITY_NORMAL;
    }
}