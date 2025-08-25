<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Contract Configuration Model
 * 
 * Manages dynamic contract system configurations per company
 * 
 * @property int $id
 * @property int $company_id
 * @property array|null $configuration
 * @property array|null $metadata
 * @property bool $is_active
 * @property string $version
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class ContractConfiguration extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * The table associated with the model.
     */
    protected $table = 'contract_configurations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'configuration',
        'metadata',
        'is_active',
        'version',
        'description',
        'activated_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'configuration' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the company that owns this configuration.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created this configuration.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this configuration.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get configurations by version.
     */
    public function scopeByVersion($query, string $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Get a configuration value by key.
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Set a configuration value by key.
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Get metadata value by key.
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set metadata value by key.
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
    }

    /**
     * Check if configuration is currently active.
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->activated_at !== null;
    }

    /**
     * Activate this configuration.
     */
    public function activate(): void
    {
        $this->update([
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    /**
     * Deactivate this configuration.
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Get validation rules for contract configuration.
     */
    public static function getValidationRules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'configuration' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'boolean',
            'version' => 'required|string|max:20',
            'description' => 'nullable|string',
            'activated_at' => 'nullable|date',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set activated_at when activating
        static::updating(function ($config) {
            if ($config->is_active && $config->getOriginal('is_active') === false) {
                $config->activated_at = now();
            }
        });
    }
}