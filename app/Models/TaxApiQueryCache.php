<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax API Query Cache Model
 *
 * Caches API responses to prevent duplicate calls within a month
 * and improve performance of tax calculations.
 *
 * @property int $id
 * @property int $company_id
 * @property string $api_provider
 * @property string $query_type
 * @property string $query_hash
 * @property array $query_parameters
 * @property array $api_response
 * @property Carbon $api_called_at
 * @property Carbon $expires_at
 * @property string $status
 * @property string|null $error_message
 * @property float|null $response_time_ms
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class TaxApiQueryCache extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'tax_api_query_cache';

    protected $fillable = [
        'company_id',
        'api_provider',
        'query_type',
        'query_hash',
        'query_parameters',
        'api_response',
        'api_called_at',
        'expires_at',
        'status',
        'error_message',
        'response_time_ms',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'query_parameters' => 'array',
        'api_response' => 'array',
        'api_called_at' => 'datetime',
        'expires_at' => 'datetime',
        'response_time_ms' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // API Provider constants
    const PROVIDER_VATCOMPLY = 'vat_comply';

    const PROVIDER_NOMINATIM = 'nominatim';

    const PROVIDER_FCC = 'fcc';

    const PROVIDER_TAXCLOUD = 'taxcloud';

    const PROVIDER_CENSUS = 'census';

    // Query Type constants
    const TYPE_GEOCODING = 'geocoding';

    const TYPE_REVERSE_GEOCODING = 'reverse_geocoding';

    const TYPE_VAT_VALIDATION = 'vat_validation';

    const TYPE_VAT_RATES = 'vat_rates';

    const TYPE_TAX_RATES = 'tax_rates';

    const TYPE_USF_RATES = 'usf_rates';

    const TYPE_JURISDICTION = 'jurisdiction';

    const TYPE_BOUNDARY = 'boundary';

    // Status constants
    const STATUS_SUCCESS = 'success';

    const STATUS_ERROR = 'error';

    const STATUS_RATE_LIMITED = 'rate_limited';

    /**
     * Get the company that owns this cache entry
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Generate a hash for query parameters
     */
    public static function generateQueryHash(array $parameters): string
    {
        // Sort parameters to ensure consistent hashing
        ksort($parameters);

        return hash('sha256', json_encode($parameters));
    }

    /**
     * Check if cache entry is still valid
     */
    public function isValid(): bool
    {
        return $this->expires_at > now() && $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if cache entry has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    /**
     * Scope to get valid cache entries
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope to get expired cache entries
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get entries by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('api_provider', $provider);
    }

    /**
     * Scope to get entries by query type
     */
    public function scopeByQueryType($query, string $queryType)
    {
        return $query->where('query_type', $queryType);
    }

    /**
     * Find cached response for specific query
     */
    public static function findCachedResponse(
        int $companyId,
        string $provider,
        string $queryType,
        array $parameters
    ): ?self {
        $hash = self::generateQueryHash($parameters);

        return self::where('company_id', $companyId)
            ->where('api_provider', $provider)
            ->where('query_type', $queryType)
            ->where('query_hash', $hash)
            ->valid()
            ->first();
    }

    /**
     * Cache an API response
     */
    public static function cacheResponse(
        int $companyId,
        string $provider,
        string $queryType,
        array $parameters,
        array $response,
        ?float $responseTimeMs = null,
        int $cacheDays = 30
    ): self {
        $hash = self::generateQueryHash($parameters);

        return self::updateOrCreate(
            [
                'company_id' => $companyId,
                'api_provider' => $provider,
                'query_type' => $queryType,
                'query_hash' => $hash,
            ],
            [
                'query_parameters' => $parameters,
                'api_response' => $response,
                'api_called_at' => now(),
                'expires_at' => now()->addDays($cacheDays),
                'status' => self::STATUS_SUCCESS,
                'error_message' => null,
                'response_time_ms' => $responseTimeMs,
            ]
        );
    }

    /**
     * Cache an API error
     */
    public static function cacheError(
        int $companyId,
        string $provider,
        string $queryType,
        array $parameters,
        string $errorMessage,
        string $status = self::STATUS_ERROR,
        int $cacheDays = 1
    ): self {
        $hash = self::generateQueryHash($parameters);

        return self::updateOrCreate(
            [
                'company_id' => $companyId,
                'api_provider' => $provider,
                'query_type' => $queryType,
                'query_hash' => $hash,
            ],
            [
                'query_parameters' => $parameters,
                'api_response' => [],
                'api_called_at' => now(),
                'expires_at' => now()->addDays($cacheDays),
                'status' => $status,
                'error_message' => $errorMessage,
                'response_time_ms' => null,
            ]
        );
    }

    /**
     * Clean up expired cache entries
     */
    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }

    /**
     * Get cache statistics for a company
     */
    public static function getCacheStats(int $companyId): array
    {
        $stats = self::where('company_id', $companyId)
            ->selectRaw('
                         api_provider,
                         query_type,
                         status,
                         COUNT(*) as total_queries,
                         AVG(response_time_ms) as avg_response_time,
                         MIN(api_called_at) as first_query,
                         MAX(api_called_at) as last_query
                     ')
            ->groupBy(['api_provider', 'query_type', 'status'])
            ->get();

        return $stats->toArray();
    }
}
