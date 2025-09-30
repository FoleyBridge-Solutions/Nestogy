<?php

namespace App\Domains\Core\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Models\Asset;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Entity Resolver Service
 *
 * Provides intelligent entity resolution with fuzzy matching,
 * context awareness, and typo tolerance.
 */
class EntityResolverService
{
    /**
     * Entity model mappings
     */
    protected static array $entityModels = [
        'client' => Client::class,
        'ticket' => Ticket::class,
        'invoice' => Invoice::class,
        'quote' => Quote::class,
        'project' => Project::class,
        'asset' => Asset::class,
        'user' => User::class,
    ];

    /**
     * Primary search fields for each entity type
     */
    protected static array $searchFields = [
        'client' => ['name', 'email', 'phone'],
        'ticket' => ['subject', 'description'],
        'invoice' => ['invoice_number', 'prefix'],
        'quote' => ['quote_number', 'prefix'],
        'project' => ['name', 'description', 'project_code'],
        'asset' => ['name', 'asset_tag', 'serial_number', 'model'],
        'user' => ['name', 'email'],
    ];

    /**
     * Resolve entity reference to actual entity
     */
    public static function resolve(string $entityType, $identifier, array $context = []): ?Model
    {
        // Get model class
        $modelClass = static::$entityModels[$entityType] ?? null;
        if (! $modelClass) {
            return null;
        }

        $cacheKey = "entity_resolve:{$entityType}:{$identifier}:".md5(serialize($context));

        return Cache::remember($cacheKey, 600, function () use ($modelClass, $entityType, $identifier, $context) {
            // Try direct ID lookup first
            if (is_numeric($identifier)) {
                $entity = static::findById($modelClass, $identifier, $context);
                if ($entity) {
                    return $entity;
                }
            }

            // Try special ID formats (INV-123, QUOTE-456)
            if ($entity = static::findBySpecialId($modelClass, $entityType, $identifier, $context)) {
                return $entity;
            }

            // Fuzzy name matching
            return static::findByName($modelClass, $entityType, $identifier, $context);
        });
    }

    /**
     * Find entity by numeric ID
     */
    protected static function findById(string $modelClass, $id, array $context): ?Model
    {
        $query = $modelClass::where('id', $id);

        // Apply company scoping if model supports it
        if (method_exists($modelClass, 'scopeCompany') || in_array('App\Traits\BelongsToCompany', class_uses_recursive($modelClass))) {
            $companyId = $context['company_id'] ?? auth()->user()->company_id;
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    /**
     * Find entity by special ID formats
     */
    protected static function findBySpecialId(string $modelClass, string $entityType, string $identifier, array $context): ?Model
    {
        $query = $modelClass::query();

        // Apply company scoping
        if (method_exists($modelClass, 'scopeCompany') || in_array('App\Traits\BelongsToCompany', class_uses_recursive($modelClass))) {
            $companyId = $context['company_id'] ?? auth()->user()->company_id;
            $query->where('company_id', $companyId);
        }

        // Handle different ID formats
        switch ($entityType) {
            case 'invoice':
                // Handle INV-123, INV123, 123
                if (preg_match('/^(INV-?)?(\d+)$/i', $identifier, $matches)) {
                    $number = $matches[2];

                    return $query->where('invoice_number', $number)
                        ->orWhere('invoice_number', "INV-{$number}")
                        ->orWhere('invoice_number', "INV{$number}")
                        ->first();
                }
                break;

            case 'quote':
                // Handle QUOTE-123, QUO-123, 123
                if (preg_match('/^(QUO(?:TE)?-?)?(\d+)$/i', $identifier, $matches)) {
                    $number = $matches[2];

                    return $query->where('quote_number', $number)
                        ->orWhere('quote_number', "QUOTE-{$number}")
                        ->orWhere('quote_number', "QUO-{$number}")
                        ->first();
                }
                break;

            case 'ticket':
                // Handle #123 or just 123
                if (preg_match('/^#?(\d+)$/', $identifier, $matches)) {
                    return $query->where('id', $matches[1])->first();
                }
                break;
        }

        return null;
    }

    /**
     * Find entity by name with fuzzy matching
     */
    protected static function findByName(string $modelClass, string $entityType, string $name, array $context): ?Model
    {
        $searchFields = static::$searchFields[$entityType] ?? ['name'];

        $query = $modelClass::query();

        // Apply company scoping
        if (method_exists($modelClass, 'scopeCompany') || in_array('App\Traits\BelongsToCompany', class_uses_recursive($modelClass))) {
            $companyId = $context['company_id'] ?? auth()->user()->company_id;
            $query->where('company_id', $companyId);
        }

        // Build search query
        $query->where(function ($q) use ($searchFields, $name) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$name}%");
            }
        });

        // Get all potential matches
        $results = $query->limit(10)->get();

        if ($results->isEmpty()) {
            return null;
        }

        // Score and rank results
        $scoredResults = static::scoreResults($results, $name, $searchFields, $context);

        // Return best match
        return $scoredResults->first()['entity'] ?? null;
    }

    /**
     * Score and rank search results
     */
    protected static function scoreResults($results, string $searchTerm, array $searchFields, array $context): \Illuminate\Support\Collection
    {
        $scored = $results->map(function ($entity) use ($searchTerm, $searchFields, $context) {
            $score = 0;

            // Calculate base similarity score
            foreach ($searchFields as $field) {
                $fieldValue = $entity->{$field} ?? '';
                if ($fieldValue) {
                    $similarity = static::calculateSimilarity($searchTerm, $fieldValue);
                    $score = max($score, $similarity);
                }
            }

            // Apply context-based bonuses
            $score += static::calculateContextBonus($entity, $context);

            // Apply recency bonus
            $score += static::calculateRecencyBonus($entity);

            return [
                'entity' => $entity,
                'score' => $score,
            ];
        });

        // Sort by score (descending)
        return $scored->sortByDesc('score');
    }

    /**
     * Calculate string similarity
     */
    protected static function calculateSimilarity(string $search, string $target): float
    {
        $search = strtolower(trim($search));
        $target = strtolower(trim($target));

        // Exact match bonus
        if ($search === $target) {
            return 20.0;
        }

        // Starts with bonus
        if (str_starts_with($target, $search)) {
            return 15.0;
        }

        // Contains bonus
        if (str_contains($target, $search)) {
            return 10.0;
        }

        // Levenshtein similarity for typo tolerance
        $maxLength = max(strlen($search), strlen($target));
        if ($maxLength === 0) {
            return 0.0;
        }

        $distance = levenshtein($search, $target);
        $similarity = (($maxLength - $distance) / $maxLength) * 5.0;

        // Similar text similarity
        $percentage = 0;
        similar_text($search, $target, $percentage);
        $similarity += ($percentage / 100) * 3.0;

        return $similarity;
    }

    /**
     * Calculate context-based scoring bonus
     */
    protected static function calculateContextBonus(Model $entity, array $context): float
    {
        $bonus = 0.0;

        // Selected client bonus
        if (isset($context['client_id']) && method_exists($entity, 'client_id')) {
            if ($entity->client_id == $context['client_id']) {
                $bonus += 10.0;
            }
        }

        // Current user assignment bonus
        if (isset($context['user_id'])) {
            if (method_exists($entity, 'assigned_to') && $entity->assigned_to == $context['user_id']) {
                $bonus += 5.0;
            }
            if (method_exists($entity, 'created_by') && $entity->created_by == $context['user_id']) {
                $bonus += 3.0;
            }
        }

        // Status-based bonuses
        if (method_exists($entity, 'status')) {
            if (in_array($entity->status, ['open', 'active', 'pending'])) {
                $bonus += 2.0;
            }
        }

        return $bonus;
    }

    /**
     * Calculate recency bonus
     */
    protected static function calculateRecencyBonus(Model $entity): float
    {
        if (! $entity->updated_at) {
            return 0.0;
        }

        $daysSinceUpdate = $entity->updated_at->diffInDays(now());

        // More recent = higher bonus
        if ($daysSinceUpdate === 0) {
            return 5.0; // Today
        } elseif ($daysSinceUpdate <= 7) {
            return 3.0; // This week
        } elseif ($daysSinceUpdate <= 30) {
            return 1.0; // This month
        }

        return 0.0;
    }

    /**
     * Search across multiple entity types
     */
    public static function searchGlobal(string $query, array $context = [], ?array $entityTypes = null): array
    {
        $entityTypes = $entityTypes ?? array_keys(static::$entityModels);
        $results = [];

        foreach ($entityTypes as $entityType) {
            $entities = static::searchEntityType($entityType, $query, $context);
            foreach ($entities as $entity) {
                $results[] = [
                    'entity' => $entity,
                    'type' => $entityType,
                    'score' => static::calculateGlobalScore($entity, $query, $entityType, $context),
                ];
            }
        }

        // Sort by global score
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($results, 0, 20); // Top 20 results
    }

    /**
     * Search specific entity type
     */
    protected static function searchEntityType(string $entityType, string $query, array $context): \Illuminate\Support\Collection
    {
        $modelClass = static::$entityModels[$entityType] ?? null;
        if (! $modelClass) {
            return collect();
        }

        $searchFields = static::$searchFields[$entityType] ?? ['name'];
        $queryBuilder = $modelClass::query();

        // Apply company scoping
        if (method_exists($modelClass, 'scopeCompany') || in_array('App\Traits\BelongsToCompany', class_uses_recursive($modelClass))) {
            $companyId = $context['company_id'] ?? auth()->user()->company_id;
            $queryBuilder->where('company_id', $companyId);
        }

        // Build search query
        $queryBuilder->where(function ($q) use ($searchFields, $query) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });

        // Add relationships if needed
        if ($entityType === 'ticket') {
            $queryBuilder->with('client');
        } elseif (in_array($entityType, ['invoice', 'quote', 'project'])) {
            $queryBuilder->with('client');
        }

        return $queryBuilder->limit(5)->get();
    }

    /**
     * Calculate global search score
     */
    protected static function calculateGlobalScore(Model $entity, string $query, string $entityType, array $context): float
    {
        $searchFields = static::$searchFields[$entityType] ?? ['name'];

        // Base similarity score
        $maxSimilarity = 0;
        foreach ($searchFields as $field) {
            $fieldValue = $entity->{$field} ?? '';
            if ($fieldValue) {
                $similarity = static::calculateSimilarity($query, $fieldValue);
                $maxSimilarity = max($maxSimilarity, $similarity);
            }
        }

        // Entity type priority (some types more important for general search)
        $typePriority = match ($entityType) {
            'client' => 1.2,
            'ticket' => 1.1,
            'invoice', 'quote' => 1.0,
            'project' => 0.9,
            'asset' => 0.8,
            'user' => 0.7,
            default => 1.0,
        };

        $score = $maxSimilarity * $typePriority;

        // Apply context bonuses
        $score += static::calculateContextBonus($entity, $context);
        $score += static::calculateRecencyBonus($entity);

        return $score;
    }

    /**
     * Get recent entities for suggestions
     */
    public static function getRecentEntities(string $entityType, array $context, int $limit = 5): \Illuminate\Support\Collection
    {
        $modelClass = static::$entityModels[$entityType] ?? null;
        if (! $modelClass) {
            return collect();
        }

        $cacheKey = "recent_entities:{$entityType}:".md5(serialize($context)).":{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($modelClass, $entityType, $context, $limit) {
            $query = $modelClass::query();

            // Apply company scoping
            if (method_exists($modelClass, 'scopeCompany') || in_array('App\Traits\BelongsToCompany', class_uses_recursive($modelClass))) {
                $companyId = $context['company_id'] ?? auth()->user()->company_id;
                $query->where('company_id', $companyId);
            }

            // Filter by context
            if (isset($context['client_id']) && in_array($entityType, ['ticket', 'invoice', 'quote', 'project', 'asset'])) {
                $query->where('client_id', $context['client_id']);
            }

            // Add relationships if needed
            if (in_array($entityType, ['ticket', 'invoice', 'quote', 'project'])) {
                $query->with('client');
            }

            return $query->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Clear entity resolution cache
     */
    public static function clearCache(?string $entityType = null, $identifier = null): void
    {
        if ($entityType && $identifier) {
            // Clear specific entity cache
            $pattern = "entity_resolve:{$entityType}:{$identifier}:*";
            static::clearCachePattern($pattern);
        } elseif ($entityType) {
            // Clear all cache for entity type
            $pattern = "entity_resolve:{$entityType}:*";
            static::clearCachePattern($pattern);
        } else {
            // Clear all entity resolution cache
            static::clearCachePattern('entity_resolve:*');
        }

        // Clear recent entities cache
        if ($entityType) {
            static::clearCachePattern("recent_entities:{$entityType}:*");
        } else {
            static::clearCachePattern('recent_entities:*');
        }
    }

    /**
     * Clear cache entries matching pattern
     */
    protected static function clearCachePattern(string $pattern): void
    {
        // This would need to be implemented based on cache driver
        // For Redis: KEYS command
        // For file cache: scan directory
        // For now, we'll use a simple approach
        try {
            if (config('cache.default') === 'redis') {
                $keys = Cache::getRedis()->keys($pattern);
                if (! empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } else {
                // For other cache drivers, we'll just clear all cache
                // This is not ideal but works as fallback
                Cache::flush();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear entity resolver cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get entity type from model instance
     */
    public static function getEntityType(Model $entity): ?string
    {
        $modelClass = get_class($entity);

        foreach (static::$entityModels as $type => $class) {
            if ($class === $modelClass) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Check if entity type is supported
     */
    public static function isValidEntityType(string $entityType): bool
    {
        return isset(static::$entityModels[$entityType]);
    }

    /**
     * Get all supported entity types
     */
    public static function getSupportedEntityTypes(): array
    {
        return array_keys(static::$entityModels);
    }
}
