<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Command Learning Service
 * 
 * Provides machine learning capabilities for command system including:
 * - User pattern recognition
 * - Command success tracking  
 * - Adaptive suggestions
 * - Performance optimization
 */
class CommandLearningService
{
    /**
     * Cache durations (in seconds)
     */
    const CACHE_USER_PATTERNS = 3600;      // 1 hour
    const CACHE_POPULAR_COMMANDS = 1800;   // 30 minutes
    const CACHE_ENTITY_POPULARITY = 600;   // 10 minutes
    const CACHE_SUGGESTIONS = 300;         // 5 minutes

    /**
     * Learning thresholds
     */
    const MIN_USAGE_FOR_PATTERN = 3;
    const MAX_USER_PATTERNS = 100;
    const MAX_POPULAR_COMMANDS = 50;

    /**
     * Record successful command execution
     */
    public static function recordSuccess(string $command, ParsedCommand $parsed, array $context = []): void
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        // Record user pattern
        static::updateUserPattern($userId, $parsed, true);

        // Record global command popularity
        static::updateCommandPopularity($command, $parsed, true);

        // Record entity access patterns
        static::updateEntityPopularity($parsed, $context, true);

        // Record contextual success
        static::updateContextualSuccess($parsed, $context);

        // Log for analytics
        static::logCommandExecution($command, $parsed, $context, true);
    }

    /**
     * Record failed command execution
     */
    public static function recordFailure(string $command, ?ParsedCommand $parsed, array $context = [], string $reason = ''): void
    {
        $userId = auth()->id();
        if (!$userId) {
            return;
        }

        // Record user pattern with negative weight
        if ($parsed) {
            static::updateUserPattern($userId, $parsed, false);
            static::updateCommandPopularity($command, $parsed, false);
        }

        // Record failure reason for improvement
        static::recordFailureReason($command, $reason, $context);

        // Log for analytics
        static::logCommandExecution($command, $parsed, $context, false, $reason);
    }

    /**
     * Update user command patterns
     */
    protected static function updateUserPattern(int $userId, ParsedCommand $parsed, bool $success): void
    {
        $cacheKey = "user_patterns:{$userId}";
        $patterns = Cache::get($cacheKey, []);

        $patternKey = static::generatePatternKey($parsed);
        
        if (!isset($patterns[$patternKey])) {
            $patterns[$patternKey] = [
                'intent' => $parsed->getIntent(),
                'entities' => $parsed->getEntities(),
                'modifiers' => $parsed->getModifiers(),
                'success_count' => 0,
                'failure_count' => 0,
                'last_used' => now()->timestamp,
                'created_at' => now()->timestamp,
            ];
        }

        // Update counters
        if ($success) {
            $patterns[$patternKey]['success_count']++;
        } else {
            $patterns[$patternKey]['failure_count']++;
        }
        
        $patterns[$patternKey]['last_used'] = now()->timestamp;

        // Keep only top patterns to prevent memory bloat
        if (count($patterns) > static::MAX_USER_PATTERNS) {
            $patterns = static::prunePatterns($patterns);
        }

        Cache::put($cacheKey, $patterns, static::CACHE_USER_PATTERNS);
    }

    /**
     * Update global command popularity
     */
    protected static function updateCommandPopularity(string $command, ParsedCommand $parsed, bool $success): void
    {
        $cacheKey = 'popular_commands';
        $commands = Cache::get($cacheKey, []);

        $commandKey = md5($command);
        
        if (!isset($commands[$commandKey])) {
            $commands[$commandKey] = [
                'command' => $command,
                'intent' => $parsed->getIntent(),
                'entities' => $parsed->getEntities(),
                'usage_count' => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'last_used' => now()->timestamp,
            ];
        }

        $commands[$commandKey]['usage_count']++;
        if ($success) {
            $commands[$commandKey]['success_count']++;
        } else {
            $commands[$commandKey]['failure_count']++;
        }
        $commands[$commandKey]['last_used'] = now()->timestamp;

        // Sort by usage and keep top commands
        uasort($commands, function ($a, $b) {
            return $b['usage_count'] <=> $a['usage_count'];
        });

        if (count($commands) > static::MAX_POPULAR_COMMANDS) {
            $commands = array_slice($commands, 0, static::MAX_POPULAR_COMMANDS, true);
        }

        Cache::put($cacheKey, $commands, static::CACHE_POPULAR_COMMANDS);
    }

    /**
     * Update entity access patterns
     */
    protected static function updateEntityPopularity(ParsedCommand $parsed, array $context, bool $success): void
    {
        $companyId = $context['company_id'] ?? auth()->user()->company_id;
        $cacheKey = "entity_popularity:{$companyId}";
        
        $popularity = Cache::get($cacheKey, []);

        foreach ($parsed->getEntities() as $entity) {
            if (!isset($popularity[$entity])) {
                $popularity[$entity] = [
                    'access_count' => 0,
                    'success_count' => 0,
                    'last_accessed' => now()->timestamp,
                ];
            }

            $popularity[$entity]['access_count']++;
            if ($success) {
                $popularity[$entity]['success_count']++;
            }
            $popularity[$entity]['last_accessed'] = now()->timestamp;
        }

        Cache::put($cacheKey, $popularity, static::CACHE_ENTITY_POPULARITY);
    }

    /**
     * Update contextual success patterns
     */
    protected static function updateContextualSuccess(ParsedCommand $parsed, array $context): void
    {
        $userId = auth()->id();
        $cacheKey = "contextual_success:{$userId}";
        
        $contextSuccess = Cache::get($cacheKey, []);
        
        // Track context combinations that work well
        $contextKey = static::generateContextKey($context);
        $patternKey = static::generatePatternKey($parsed);
        $combinationKey = $contextKey . ':' . $patternKey;

        if (!isset($contextSuccess[$combinationKey])) {
            $contextSuccess[$combinationKey] = [
                'context' => $context,
                'pattern' => [
                    'intent' => $parsed->getIntent(),
                    'entities' => $parsed->getEntities(),
                ],
                'success_count' => 0,
                'last_used' => now()->timestamp,
            ];
        }

        $contextSuccess[$combinationKey]['success_count']++;
        $contextSuccess[$combinationKey]['last_used'] = now()->timestamp;

        // Prune old entries
        $contextSuccess = static::pruneContextualPatterns($contextSuccess);

        Cache::put($cacheKey, $contextSuccess, static::CACHE_USER_PATTERNS);
    }

    /**
     * Record failure reasons for analysis
     */
    protected static function recordFailureReason(string $command, string $reason, array $context): void
    {
        $cacheKey = 'command_failures';
        $failures = Cache::get($cacheKey, []);

        $failureKey = md5($command . $reason);
        
        if (!isset($failures[$failureKey])) {
            $failures[$failureKey] = [
                'command' => $command,
                'reason' => $reason,
                'count' => 0,
                'context_patterns' => [],
                'first_seen' => now()->timestamp,
                'last_seen' => now()->timestamp,
            ];
        }

        $failures[$failureKey]['count']++;
        $failures[$failureKey]['last_seen'] = now()->timestamp;

        // Track context patterns for failures
        $contextPattern = static::generateContextKey($context);
        if (!isset($failures[$failureKey]['context_patterns'][$contextPattern])) {
            $failures[$failureKey]['context_patterns'][$contextPattern] = 0;
        }
        $failures[$failureKey]['context_patterns'][$contextPattern]++;

        // Keep only recent failures (last 7 days)
        $failures = array_filter($failures, function ($failure) {
            return $failure['last_seen'] > (now()->timestamp - (7 * 24 * 3600));
        });

        Cache::put($cacheKey, $failures, 86400); // 24 hours
    }

    /**
     * Get personalized suggestions for user
     */
    public static function getPersonalizedSuggestions(int $userId, string $partial = '', array $context = []): array
    {
        $cacheKey = "personalized_suggestions:{$userId}:" . md5($partial . serialize($context));
        
        return Cache::remember($cacheKey, static::CACHE_SUGGESTIONS, function () use ($userId, $partial, $context) {
            $suggestions = [];

            // Get user patterns
            $userPatterns = static::getUserPatterns($userId);
            $suggestions = array_merge($suggestions, static::suggestionsFromUserPatterns($userPatterns, $partial));

            // Get contextual suggestions
            $contextual = static::getContextualSuggestions($userId, $context, $partial);
            $suggestions = array_merge($suggestions, $contextual);

            // Get popular commands
            $popular = static::getPopularCommandSuggestions($partial, $context);
            $suggestions = array_merge($suggestions, $popular);

            // Rank by relevance
            return static::rankPersonalizedSuggestions($suggestions, $partial, $context);
        });
    }

    /**
     * Get user command patterns
     */
    protected static function getUserPatterns(int $userId): array
    {
        $cacheKey = "user_patterns:{$userId}";
        $patterns = Cache::get($cacheKey, []);

        // Filter successful patterns
        return array_filter($patterns, function ($pattern) {
            $successRate = $pattern['success_count'] / max(1, $pattern['success_count'] + $pattern['failure_count']);
            return $pattern['success_count'] >= static::MIN_USAGE_FOR_PATTERN && $successRate > 0.5;
        });
    }

    /**
     * Generate suggestions from user patterns
     */
    protected static function suggestionsFromUserPatterns(array $patterns, string $partial): array
    {
        $suggestions = [];

        foreach ($patterns as $pattern) {
            // Generate command suggestion from pattern
            $command = static::generateCommandFromPattern($pattern);
            
            if (empty($partial) || str_contains(strtolower($command), strtolower($partial))) {
                $suggestions[] = [
                    'text' => $command,
                    'type' => 'personal',
                    'description' => 'Based on your usage patterns',
                    'confidence' => static::calculatePatternConfidence($pattern),
                    'usage_count' => $pattern['success_count'],
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Get contextual suggestions based on learned patterns
     */
    protected static function getContextualSuggestions(int $userId, array $context, string $partial): array
    {
        $cacheKey = "contextual_success:{$userId}";
        $contextSuccess = Cache::get($cacheKey, []);

        $suggestions = [];
        $currentContext = static::generateContextKey($context);

        foreach ($contextSuccess as $combinationKey => $data) {
            // Check if context matches
            if (str_starts_with($combinationKey, $currentContext)) {
                $command = static::generateCommandFromPattern($data['pattern']);
                
                if (empty($partial) || str_contains(strtolower($command), strtolower($partial))) {
                    $suggestions[] = [
                        'text' => $command,
                        'type' => 'contextual',
                        'description' => 'Works well in this context',
                        'confidence' => min(0.9, $data['success_count'] / 10),
                        'usage_count' => $data['success_count'],
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Get popular command suggestions
     */
    protected static function getPopularCommandSuggestions(string $partial, array $context): array
    {
        $cacheKey = 'popular_commands';
        $commands = Cache::get($cacheKey, []);

        $suggestions = [];

        foreach ($commands as $commandData) {
            $command = $commandData['command'];
            
            if (empty($partial) || str_contains(strtolower($command), strtolower($partial))) {
                $successRate = $commandData['success_count'] / max(1, $commandData['usage_count']);
                
                if ($successRate > 0.6) { // Only suggest commands with good success rate
                    $suggestions[] = [
                        'text' => $command,
                        'type' => 'popular',
                        'description' => 'Popular command',
                        'confidence' => $successRate * 0.7,
                        'usage_count' => $commandData['usage_count'],
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Rank personalized suggestions
     */
    protected static function rankPersonalizedSuggestions(array $suggestions, string $partial, array $context): array
    {
        // Calculate final scores
        foreach ($suggestions as &$suggestion) {
            $score = $suggestion['confidence'];
            
            // Boost exact matches
            if (!empty($partial) && str_starts_with(strtolower($suggestion['text']), strtolower($partial))) {
                $score += 0.3;
            }
            
            // Boost based on usage frequency
            $score += min(0.2, ($suggestion['usage_count'] ?? 0) / 50);
            
            // Type-based adjustments
            switch ($suggestion['type']) {
                case 'personal':
                    $score += 0.2; // Prefer personal patterns
                    break;
                case 'contextual':
                    $score += 0.15;
                    break;
                case 'popular':
                    $score += 0.05;
                    break;
            }
            
            $suggestion['final_score'] = $score;
        }

        // Sort by final score
        usort($suggestions, function ($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });

        return array_slice($suggestions, 0, 10);
    }

    /**
     * Generate pattern key from parsed command
     */
    protected static function generatePatternKey(ParsedCommand $parsed): string
    {
        $entities = $parsed->getEntities();
        sort($entities);
        
        $modifiers = $parsed->getModifiers();
        sort($modifiers);
        
        return $parsed->getIntent() . ':' . implode(',', $entities) . ':' . implode(',', $modifiers);
    }

    /**
     * Generate context key from context array
     */
    protected static function generateContextKey(array $context): string
    {
        $key = [];
        
        if (isset($context['client_id'])) {
            $key[] = 'client:' . $context['client_id'];
        }
        
        if (isset($context['domain'])) {
            $key[] = 'domain:' . $context['domain'];
        }
        
        if (isset($context['workflow'])) {
            $key[] = 'workflow:' . $context['workflow'];
        }
        
        return implode('|', $key);
    }

    /**
     * Generate command text from pattern
     */
    protected static function generateCommandFromPattern(array $pattern): string
    {
        $intent = strtolower($pattern['intent']);
        $entities = $pattern['entities'] ?? [];
        
        $intentVerbs = [
            'CREATE' => 'create',
            'SHOW' => 'show',
            'GO' => 'go to',
            'FIND' => 'find',
            'ACTION' => 'action',
        ];
        
        $verb = $intentVerbs[$pattern['intent']] ?? $intent;
        
        if (!empty($entities)) {
            return $verb . ' ' . implode(' ', $entities);
        }
        
        return $verb;
    }

    /**
     * Calculate confidence for pattern
     */
    protected static function calculatePatternConfidence(array $pattern): float
    {
        $total = $pattern['success_count'] + $pattern['failure_count'];
        $successRate = $pattern['success_count'] / max(1, $total);
        
        // Factor in usage frequency
        $frequency = min(1.0, $pattern['success_count'] / 10);
        
        // Factor in recency
        $daysSinceUse = (now()->timestamp - $pattern['last_used']) / 86400;
        $recency = max(0.1, 1.0 - ($daysSinceUse / 30)); // Decay over 30 days
        
        return $successRate * 0.6 + $frequency * 0.3 + $recency * 0.1;
    }

    /**
     * Prune old patterns to prevent memory bloat
     */
    protected static function prunePatterns(array $patterns): array
    {
        // Sort by success count and recency
        uasort($patterns, function ($a, $b) {
            $scoreA = $a['success_count'] + ($a['last_used'] > (now()->timestamp - 86400) ? 5 : 0);
            $scoreB = $b['success_count'] + ($b['last_used'] > (now()->timestamp - 86400) ? 5 : 0);
            
            return $scoreB <=> $scoreA;
        });

        return array_slice($patterns, 0, static::MAX_USER_PATTERNS, true);
    }

    /**
     * Prune contextual patterns
     */
    protected static function pruneContextualPatterns(array $patterns): array
    {
        // Remove patterns older than 30 days
        $cutoff = now()->timestamp - (30 * 24 * 3600);
        
        return array_filter($patterns, function ($pattern) use ($cutoff) {
            return $pattern['last_used'] > $cutoff;
        });
    }

    /**
     * Log command execution for analytics
     */
    protected static function logCommandExecution(
        string $command, 
        ?ParsedCommand $parsed, 
        array $context, 
        bool $success, 
        string $reason = ''
    ): void {
        if (config('app.debug') || config('command.analytics', false)) {
            Log::info('Command execution logged', [
                'command' => $command,
                'intent' => $parsed?->getIntent(),
                'entities' => $parsed?->getEntities(),
                'success' => $success,
                'reason' => $reason,
                'context' => $context,
                'user_id' => auth()->id(),
                'company_id' => auth()->user()?->company_id,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Get command analytics for admin dashboard
     */
    public static function getAnalytics(int $days = 7): array
    {
        // This would typically query a dedicated analytics table
        // For now, return summary from cache
        
        $popularCommands = Cache::get('popular_commands', []);
        $failures = Cache::get('command_failures', []);
        
        return [
            'popular_commands' => array_slice($popularCommands, 0, 10),
            'common_failures' => array_slice($failures, 0, 10),
            'total_users_with_patterns' => static::countUsersWithPatterns(),
            'cache_status' => static::getCacheStatus(),
        ];
    }

    /**
     * Count users with learned patterns
     */
    protected static function countUsersWithPatterns(): int
    {
        // This is a simplified version - in production you'd query cache keys
        return 0; // Placeholder
    }

    /**
     * Get cache status information
     */
    protected static function getCacheStatus(): array
    {
        return [
            'driver' => config('cache.default'),
            'popular_commands_cached' => Cache::has('popular_commands'),
            'cache_hits' => 0, // Would need cache driver support
            'cache_misses' => 0, // Would need cache driver support
        ];
    }

    /**
     * Clear learning data for user
     */
    public static function clearUserLearning(int $userId): void
    {
        Cache::forget("user_patterns:{$userId}");
        Cache::forget("contextual_success:{$userId}");
        Cache::forget("personalized_suggestions:{$userId}");
    }

    /**
     * Clear all learning data
     */
    public static function clearAllLearning(): void
    {
        $patterns = [
            'user_patterns:*',
            'contextual_success:*',
            'personalized_suggestions:*',
            'popular_commands',
            'command_failures',
            'entity_popularity:*',
        ];

        foreach ($patterns as $pattern) {
            static::clearCacheByPattern($pattern);
        }
    }

    /**
     * Clear cache by pattern (simplified implementation)
     */
    protected static function clearCacheByPattern(string $pattern): void
    {
        try {
            if (config('cache.default') === 'redis') {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear learning cache', ['pattern' => $pattern, 'error' => $e->getMessage()]);
        }
    }
}