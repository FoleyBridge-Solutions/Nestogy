<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Command Intent Service
 * 
 * Handles natural language processing and intent recognition for the command system.
 * Converts user input into structured intents with entities and modifiers.
 */
class CommandIntentService
{
    /**
     * Primary intent types
     */
    const INTENT_CREATE = 'CREATE';
    const INTENT_SHOW = 'SHOW';
    const INTENT_GO = 'GO';
    const INTENT_FIND = 'FIND';
    const INTENT_ACTION = 'ACTION';

    /**
     * Intent modifiers
     */
    const MODIFIER_FOR_CLIENT = 'FOR_CLIENT';
    const MODIFIER_URGENT = 'URGENT';
    const MODIFIER_OVERDUE = 'OVERDUE';
    const MODIFIER_SCHEDULED = 'SCHEDULED';
    const MODIFIER_MY = 'MY';

    /**
     * Intent verb mappings
     */
    protected static $intentVerbs = [
        self::INTENT_CREATE => ['create', 'new', 'add', 'make', '+'],
        self::INTENT_SHOW => ['show', 'display', 'list', 'view', 'see', 'get'],
        self::INTENT_GO => ['go', 'open', 'visit', 'navigate', 'goto'],
        self::INTENT_FIND => ['find', 'search', 'lookup', 'locate', '/'],
        self::INTENT_ACTION => ['send', 'email', 'export', 'print', 'delete', 'archive'],
    ];

    /**
     * Entity type mappings with synonyms and shortcuts
     */
    protected static $entityTypes = [
        'ticket' => ['ticket', 'tickets', 'issue', 'issues', 'support', 'tix', '#'],
        'client' => ['client', 'clients', 'customer', 'customers', 'company', 'companies', '@'],
        'invoice' => ['invoice', 'invoices', 'bill', 'bills', 'inv', '$'],
        'quote' => ['quote', 'quotes', 'quotation', 'estimate', 'proposal', 'quot'],
        'project' => ['project', 'projects', 'task', 'tasks', 'proj'],
        'asset' => ['asset', 'assets', 'device', 'devices', 'equipment', 'hardware'],
        'user' => ['user', 'users', 'staff', 'team', 'employee', 'technician', 'tech'],
        'contract' => ['contract', 'contracts', 'agreement', 'agreements', 'sla'],
        'expense' => ['expense', 'expenses', 'cost', 'costs', 'purchase'],
        'payment' => ['payment', 'payments', 'transaction', 'transactions', 'receipt'],
        'article' => ['article', 'articles', 'kb', 'knowledge', 'doc', 'docs'],
        'contact' => ['contact', 'contacts', 'person', 'people'],
        'location' => ['location', 'locations', 'site', 'sites', 'office'],
        'vendor' => ['vendor', 'vendors', 'supplier', 'suppliers'],
        'network' => ['network', 'networks', 'net', 'infrastructure'],
    ];

    /**
     * Common abbreviations and expansions
     */
    protected static $abbreviations = [
        'inv' => 'invoice',
        'quot' => 'quote',
        'tix' => 'tickets',
        'cli' => 'client',
        'proj' => 'project',
        'doc' => 'documentation',
        'kb' => 'knowledge',
    ];

    /**
     * Common typos and corrections
     */
    protected static $typoCorrections = [
        'tiket' => 'ticket',
        'clint' => 'client',
        'invoic' => 'invoice',
        'projct' => 'project',
        'assett' => 'asset',
        'expens' => 'expense',
    ];

    /**
     * Parse user input and return structured intent
     */
    public static function parseCommand(string $input, array $context = []): ParsedCommand
    {
        $input = static::preprocessInput($input);
        
        // Check for shortcuts first
        if ($shortcut = static::parseShortcuts($input, $context)) {
            return $shortcut;
        }

        // Extract intent, entities, and modifiers
        $intent = static::extractIntent($input);
        $entities = static::extractEntities($input);
        $modifiers = static::extractModifiers($input, $context);
        $entityReference = static::extractEntityReference($input);

        // Build structured command
        $command = new ParsedCommand([
            'original' => $input,
            'intent' => $intent,
            'entities' => $entities,
            'modifiers' => $modifiers,
            'entity_reference' => $entityReference,
            'context' => $context,
            'confidence' => static::calculateConfidence($intent, $entities, $modifiers),
        ]);

        // Log command parsing for analytics
        static::logCommandParsing($input, $command);

        return $command;
    }

    /**
     * Preprocess input text
     */
    protected static function preprocessInput(string $input): string
    {
        $input = trim(strtolower($input));

        // Expand abbreviations
        foreach (static::$abbreviations as $abbrev => $full) {
            $input = preg_replace('/\b' . preg_quote($abbrev) . '\b/', $full, $input);
        }

        // Fix common typos
        foreach (static::$typoCorrections as $typo => $correction) {
            $input = str_replace($typo, $correction, $input);
        }

        // Normalize whitespace
        $input = preg_replace('/\s+/', ' ', $input);

        return $input;
    }

    /**
     * Parse shortcut commands
     */
    protected static function parseShortcuts(string $input, array $context): ?ParsedCommand
    {
        // Direct entity references: #123, @client, $INV-456
        if (preg_match('/^([#@$])(.+)$/', $input, $matches)) {
            $symbol = $matches[1];
            $identifier = $matches[2];

            $entityMap = [
                '#' => 'ticket',
                '@' => 'client', 
                '$' => 'invoice',
            ];

            if (isset($entityMap[$symbol])) {
                return new ParsedCommand([
                    'original' => $input,
                    'intent' => self::INTENT_GO,
                    'entities' => [$entityMap[$symbol]],
                    'entity_reference' => ['type' => $entityMap[$symbol], 'id' => $identifier],
                    'modifiers' => [],
                    'context' => $context,
                    'confidence' => 0.95,
                    'is_shortcut' => true,
                ]);
            }
        }

        // Quick actions: +ticket, !urgent
        if (preg_match('/^([+!])(.*)$/', $input, $matches)) {
            $symbol = $matches[1];
            $rest = trim($matches[2]);

            if ($symbol === '+') {
                // Creation shortcut
                $entity = static::identifyPrimaryEntity($rest) ?? 'ticket';
                return new ParsedCommand([
                    'original' => $input,
                    'intent' => self::INTENT_CREATE,
                    'entities' => [$entity],
                    'modifiers' => [],
                    'context' => $context,
                    'confidence' => 0.9,
                    'is_shortcut' => true,
                ]);
            }

            if ($symbol === '!' && ($rest === '' || $rest === 'urgent')) {
                // Urgent items
                return new ParsedCommand([
                    'original' => $input,
                    'intent' => self::INTENT_SHOW,
                    'entities' => [],
                    'modifiers' => [self::MODIFIER_URGENT],
                    'context' => $context,
                    'confidence' => 0.95,
                    'is_shortcut' => true,
                ]);
            }
        }

        // Search shortcut: /query
        if (str_starts_with($input, '/') && strlen($input) > 1) {
            return new ParsedCommand([
                'original' => $input,
                'intent' => self::INTENT_FIND,
                'entities' => [],
                'search_query' => substr($input, 1),
                'modifiers' => [],
                'context' => $context,
                'confidence' => 0.9,
                'is_shortcut' => true,
            ]);
        }

        return null;
    }

    /**
     * Extract primary intent from input
     */
    protected static function extractIntent(string $input): string
    {
        $words = explode(' ', $input);
        
        foreach (static::$intentVerbs as $intent => $verbs) {
            foreach ($verbs as $verb) {
                if (in_array($verb, $words) || str_starts_with($input, $verb . ' ')) {
                    return $intent;
                }
            }
        }

        // Natural language patterns
        if (preg_match('/\b(what|which|show me|give me)\b/', $input)) {
            return self::INTENT_SHOW;
        }

        if (preg_match('/\b(where is|how do i find)\b/', $input)) {
            return self::INTENT_FIND;
        }

        // Default to search for unrecognized patterns
        return self::INTENT_FIND;
    }

    /**
     * Extract entities from input
     */
    protected static function extractEntities(string $input): array
    {
        $entities = [];
        $words = explode(' ', $input);

        foreach (static::$entityTypes as $entityType => $synonyms) {
            foreach ($synonyms as $synonym) {
                if ($synonym === '#' || $synonym === '@' || $synonym === '$') {
                    continue; // Skip shortcuts, handled separately
                }
                
                if (in_array($synonym, $words) || str_contains($input, $synonym)) {
                    $entities[] = $entityType;
                    break; // Only add each entity type once
                }
            }
        }

        return array_unique($entities);
    }

    /**
     * Extract modifiers from input
     */
    protected static function extractModifiers(string $input, array $context): array
    {
        $modifiers = [];

        // Urgency indicators
        if (preg_match('/\b(urgent|critical|emergency|asap|!)\b/', $input)) {
            $modifiers[] = self::MODIFIER_URGENT;
        }

        // Time-based modifiers
        if (preg_match('/\b(overdue|late|past due)\b/', $input)) {
            $modifiers[] = self::MODIFIER_OVERDUE;
        }

        if (preg_match('/\b(scheduled|upcoming|future)\b/', $input)) {
            $modifiers[] = self::MODIFIER_SCHEDULED;
        }

        // Personal scope
        if (preg_match('/\b(my|mine|assigned to me|i created)\b/', $input)) {
            $modifiers[] = self::MODIFIER_MY;
        }

        // Client context
        if (preg_match('/\b(for client|client|current client)\b/', $input) || isset($context['client_id'])) {
            $modifiers[] = self::MODIFIER_FOR_CLIENT;
        }

        return $modifiers;
    }

    /**
     * Extract entity reference (ID or name)
     */
    protected static function extractEntityReference(string $input): ?array
    {
        // Look for ID patterns
        if (preg_match('/\b#?(\d+)\b/', $input, $matches)) {
            return ['type' => 'id', 'value' => $matches[1]];
        }

        // Look for invoice numbers
        if (preg_match('/\b(INV-?\d+|QUOTE-?\d+)\b/i', $input, $matches)) {
            $type = str_starts_with(strtolower($matches[1]), 'inv') ? 'invoice' : 'quote';
            return ['type' => $type, 'value' => $matches[1]];
        }

        // Look for potential entity names (capitalized words)
        if (preg_match('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $input, $matches)) {
            return ['type' => 'name', 'value' => $matches[0]];
        }

        return null;
    }

    /**
     * Identify primary entity from text
     */
    protected static function identifyPrimaryEntity(string $text): ?string
    {
        foreach (static::$entityTypes as $entityType => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (str_contains($text, $synonym)) {
                    return $entityType;
                }
            }
        }

        return null;
    }

    /**
     * Calculate confidence score for parsed command
     */
    protected static function calculateConfidence(string $intent, array $entities, array $modifiers): float
    {
        $confidence = 0.5; // Base confidence

        // Intent confidence
        $confidence += 0.2;

        // Entity presence
        if (!empty($entities)) {
            $confidence += 0.2;
        }

        // Modifier presence
        if (!empty($modifiers)) {
            $confidence += 0.1;
        }

        // Multiple entities reduce confidence (ambiguous)
        if (count($entities) > 2) {
            $confidence -= 0.1;
        }

        return min(1.0, max(0.0, $confidence));
    }

    /**
     * Get command suggestions based on partial input
     */
    public static function getSuggestions(string $partial, array $context = []): array
    {
        $cacheKey = 'command_suggestions:' . md5($partial . serialize($context));
        
        return Cache::remember($cacheKey, 300, function () use ($partial, $context) {
            $suggestions = [];
            
            // Intent-based suggestions
            $suggestions = array_merge($suggestions, static::getIntentSuggestions($partial));
            
            // Entity-based suggestions
            $suggestions = array_merge($suggestions, static::getEntitySuggestions($partial));
            
            // Context-aware suggestions
            $suggestions = array_merge($suggestions, static::getContextSuggestions($partial, $context));
            
            // Quick action suggestions
            $suggestions = array_merge($suggestions, static::getQuickActionSuggestions($partial));

            return static::rankSuggestions($suggestions, $partial, $context);
        });
    }

    /**
     * Get intent-based suggestions
     */
    protected static function getIntentSuggestions(string $partial): array
    {
        $suggestions = [];

        foreach (static::$intentVerbs as $intent => $verbs) {
            foreach ($verbs as $verb) {
                if (str_starts_with($verb, $partial) || ($partial && str_contains($verb, $partial))) {
                    $suggestions[] = [
                        'text' => $verb,
                        'type' => 'intent',
                        'intent' => $intent,
                        'description' => static::getIntentDescription($intent),
                        'confidence' => str_starts_with($verb, $partial) ? 0.9 : 0.6,
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Get entity-based suggestions  
     */
    protected static function getEntitySuggestions(string $partial): array
    {
        $suggestions = [];

        foreach (static::$entityTypes as $entityType => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (str_starts_with($synonym, $partial) || ($partial && str_contains($synonym, $partial))) {
                    $suggestions[] = [
                        'text' => $synonym,
                        'type' => 'entity',
                        'entity' => $entityType,
                        'description' => "Work with {$entityType}s",
                        'confidence' => str_starts_with($synonym, $partial) ? 0.8 : 0.5,
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Get context-aware suggestions
     */
    protected static function getContextSuggestions(string $partial, array $context): array
    {
        $suggestions = [];

        // Client context suggestions
        if (isset($context['client_id'])) {
            $clientSuggestions = [
                'create ticket' => 'Create ticket for current client',
                'create invoice' => 'Create invoice for current client', 
                'show tickets' => 'Show tickets for current client',
                'show invoices' => 'Show invoices for current client',
            ];

            foreach ($clientSuggestions as $command => $description) {
                if (str_starts_with($command, $partial)) {
                    $suggestions[] = [
                        'text' => $command,
                        'type' => 'context',
                        'description' => $description,
                        'confidence' => 0.85,
                    ];
                }
            }
        }

        return $suggestions;
    }

    /**
     * Get quick action suggestions
     */
    protected static function getQuickActionSuggestions(string $partial): array
    {
        if (strlen($partial) > 3) {
            return []; // Only show for short inputs
        }

        return [
            [
                'text' => 'help',
                'type' => 'quick',
                'description' => 'Show command help',
                'confidence' => 0.9,
            ],
            [
                'text' => 'urgent',
                'type' => 'quick',
                'description' => 'Show urgent items',
                'confidence' => 0.8,
            ],
        ];
    }

    /**
     * Rank suggestions by relevance
     */
    protected static function rankSuggestions(array $suggestions, string $partial, array $context): array
    {
        usort($suggestions, function ($a, $b) use ($partial) {
            // Exact prefix matches first
            $aPrefix = str_starts_with($a['text'], $partial) ? 1 : 0;
            $bPrefix = str_starts_with($b['text'], $partial) ? 1 : 0;
            
            if ($aPrefix !== $bPrefix) {
                return $bPrefix - $aPrefix;
            }
            
            // Then by confidence
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($suggestions, 0, 10);
    }

    /**
     * Get intent description
     */
    protected static function getIntentDescription(string $intent): string
    {
        return match($intent) {
            self::INTENT_CREATE => 'Create new items',
            self::INTENT_SHOW => 'Display and filter items',
            self::INTENT_GO => 'Navigate to pages',
            self::INTENT_FIND => 'Search for items',
            self::INTENT_ACTION => 'Perform actions',
            default => 'Execute command',
        };
    }

    /**
     * Log command parsing for analytics
     */
    protected static function logCommandParsing(string $input, ParsedCommand $command): void
    {
        if (config('app.debug')) {
            Log::info('Command parsed', [
                'input' => $input,
                'intent' => $command->getIntent(),
                'entities' => $command->getEntities(),
                'modifiers' => $command->getModifiers(),
                'confidence' => $command->getConfidence(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Learn from user command patterns (future enhancement)
     */
    public static function learnFromUsage(string $command, bool $successful, array $context = []): void
    {
        // Store successful command patterns for future suggestion improvements
        $userId = auth()->id();
        $pattern = static::extractPattern($command);
        
        $userPatterns = Cache::get("user_patterns:{$userId}", []);
        
        if ($successful) {
            $userPatterns[$pattern] = ($userPatterns[$pattern] ?? 0) + 1;
        } else {
            $userPatterns[$pattern] = max(0, ($userPatterns[$pattern] ?? 0) - 1);
        }
        
        Cache::put("user_patterns:{$userId}", $userPatterns, 86400); // 24 hours
    }

    /**
     * Extract pattern from command for learning
     */
    protected static function extractPattern(string $command): string
    {
        $parsed = static::parseCommand($command);
        return $parsed->getIntent() . ':' . implode(',', $parsed->getEntities());
    }
}