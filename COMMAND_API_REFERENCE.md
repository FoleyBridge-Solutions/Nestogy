# 🚀 Command System API Reference

## Overview

This document provides comprehensive API reference for developers integrating with or extending the Nestogy Command System. All services are designed to work together to provide intelligent command processing.

## Core Service APIs

### CommandIntentService

#### parseCommand()
```php
public static function parseCommand(string $input, array $context = []): ParsedCommand
```

**Purpose**: Parse user input into structured intent  
**Parameters**:
- `$input` (string): Raw user command
- `$context` (array): Current application context

**Returns**: ParsedCommand object with structured data

**Example**:
```php
$parsed = CommandIntentService::parseCommand('create invoice acme', [
    'client_id' => 123,
    'user_id' => 456,
    'company_id' => 789
]);

echo $parsed->getIntent();        // 'CREATE'
echo $parsed->getPrimaryEntity(); // 'invoice'
echo $parsed->getConfidence();    // 0.85
```

#### getSuggestions()
```php
public static function getSuggestions(string $partial, array $context = []): array
```

**Purpose**: Get autocomplete suggestions for partial input  
**Parameters**:
- `$partial` (string): Partial user input
- `$context` (array): Current application context

**Returns**: Array of suggestion objects

**Example**:
```php
$suggestions = CommandIntentService::getSuggestions('cre', $context);
// Returns:
// [
//   ['text' => 'create', 'type' => 'intent', 'confidence' => 0.9],
//   ['text' => 'create ticket', 'type' => 'command', 'confidence' => 0.8],
// ]
```

### EntityResolverService

#### resolve()
```php
public static function resolve(string $entityType, $identifier, array $context = []): ?Model
```

**Purpose**: Resolve entity reference to actual database entity  
**Parameters**:
- `$entityType` (string): Type of entity ('client', 'ticket', etc.)
- `$identifier` (mixed): Entity identifier (ID, name, etc.)
- `$context` (array): Current application context

**Returns**: Model instance or null if not found

**Example**:
```php
// Resolve by ID
$client = EntityResolverService::resolve('client', 123, $context);

// Resolve by name with fuzzy matching
$client = EntityResolverService::resolve('client', 'acme corp', $context);

// Resolve special formats
$invoice = EntityResolverService::resolve('invoice', 'INV-456', $context);
```

#### searchGlobal()
```php
public static function searchGlobal(string $query, array $context = [], array $entityTypes = null): array
```

**Purpose**: Search across multiple entity types  
**Parameters**:
- `$query` (string): Search query
- `$context` (array): Current application context
- `$entityTypes` (array|null): Limit to specific entity types

**Returns**: Array of search results with scores

**Example**:
```php
$results = EntityResolverService::searchGlobal('server down', $context);
// Returns scored results across tickets, assets, documentation, etc.
```

#### getRecentEntities()
```php
public static function getRecentEntities(string $entityType, array $context, int $limit = 5): Collection
```

**Purpose**: Get recently accessed entities of specific type  

**Example**:
```php
$recentTickets = EntityResolverService::getRecentEntities('ticket', $context, 10);
```

### CommandLearningService

#### recordSuccess()
```php
public static function recordSuccess(string $command, ParsedCommand $parsed, array $context = []): void
```

**Purpose**: Record successful command execution for learning

**Example**:
```php
CommandLearningService::recordSuccess($userInput, $parsedCommand, $context);
```

#### recordFailure()
```php
public static function recordFailure(string $command, ?ParsedCommand $parsed, array $context = [], string $reason = ''): void
```

**Purpose**: Record failed command execution for improvement

**Example**:
```php
CommandLearningService::recordFailure($userInput, $parsedCommand, $context, 'Entity not found');
```

#### getPersonalizedSuggestions()
```php
public static function getPersonalizedSuggestions(int $userId, string $partial = '', array $context = []): array
```

**Purpose**: Get AI-powered personalized suggestions for user

**Example**:
```php
$suggestions = CommandLearningService::getPersonalizedSuggestions(
    auth()->id(), 
    'show', 
    $context
);
```

### ParsedCommand

#### Core Methods
```php
public function getIntent(): string              // Primary intent (CREATE, SHOW, etc.)
public function getEntities(): array             // Extracted entities
public function getPrimaryEntity(): ?string     // First/main entity
public function hasEntity(string $entity): bool // Check if entity present
public function getModifiers(): array           // Intent modifiers
public function hasModifier(string $modifier): bool
public function getEntityReference(): ?array    // Entity ID/reference info
public function hasEntityReference(): bool
public function getContext(): array             // Full context
public function getContextValue(string $key, $default = null)
public function getConfidence(): float          // Parsing confidence (0-1)
public function isShortcut(): bool             // Was parsed from shortcut
```

## REST API Endpoints

### Navigation Controller

#### GET /api/navigation/suggestions
Get autocomplete suggestions for command palette

**Parameters**:
- `q` (string): Partial query
- `context` (string): JSON-encoded context

**Response**:
```json
[
  {
    "command": "create ticket",
    "icon": "🎫",
    "description": "Create new support ticket",
    "type": "command",
    "confidence": 0.9
  }
]
```

#### POST /api/navigation/command
Execute command from command palette

**Request Body**:
```json
{
  "command": "create ticket acme urgent"
}
```

**Response**:
```json
{
  "action": "navigate",
  "url": "/tickets/create?client_id=123",
  "message": "Creating New Ticket",
  "workflow": null
}
```

#### GET /api/navigation/recent-items
Get recent items for user

**Parameters**:
- `limit` (int): Max items to return (default: 10)

**Response**:
```json
[
  {
    "type": "ticket",
    "id": 123,
    "title": "#123 - Server Down",
    "url": "/tickets/123",
    "icon": "🎫",
    "timestamp": "2025-01-10T15:30:00Z"
  }
]
```

## Integration Examples

### Adding New Commands

#### 1. Register Intent Pattern
```php
// In CommandIntentService::$intentVerbs
'APPROVE' => ['approve', 'accept', 'confirm'],
```

#### 2. Handle New Intent
```php
// In CommandPaletteService
protected static function handleApproveItem($matches, $context): array
{
    $item = trim($matches[1]);
    
    if (str_contains($item, 'quote')) {
        return [
            'action' => 'navigate',
            'url' => route('quotes.approve', $context['quote_id']),
            'message' => 'Approving quote',
        ];
    }
    
    return ['action' => 'error', 'message' => 'Cannot approve: ' . $item];
}
```

#### 3. Add Suggestions
```php
// In CommandIntentService suggestions
'approve quote' => ['icon' => '✅', 'description' => 'Approve pending quote'],
```

### Adding New Entity Types

#### 1. Register Entity
```php
// In CommandIntentService::$entityTypes
'document' => ['document', 'documents', 'doc', 'file'],
```

#### 2. Add Model Mapping
```php
// In EntityResolverService::$entityModels
'document' => Document::class,
```

#### 3. Add Search Fields
```php
// In EntityResolverService::$searchFields
'document' => ['title', 'filename', 'description'],
```

#### 4. Update Search Controller
```php
// In NavigationController::search()
if ($domain === 'all' || $domain === 'documents') {
    $documents = Document::where('company_id', auth()->user()->company_id)
        ->where('title', 'like', "%{$query}%")
        ->limit(5)->get();
    
    foreach ($documents as $doc) {
        $results[] = [
            'type' => 'document',
            'icon' => '📄',
            'title' => $doc->title,
            'url' => route('documents.show', $doc->id),
        ];
    }
}
```

### Custom Learning Integration

```php
class CustomCommandLearning
{
    public static function trackSpecialCommand(string $command, bool $success)
    {
        // Custom learning logic
        $patterns = Cache::get('special_patterns', []);
        $patterns[$command] = [
            'success_rate' => $success ? 1.0 : 0.0,
            'usage_count' => 1,
            'last_used' => now(),
        ];
        Cache::put('special_patterns', $patterns, 3600);
    }
    
    public static function getSpecialSuggestions(string $partial): array
    {
        $patterns = Cache::get('special_patterns', []);
        $suggestions = [];
        
        foreach ($patterns as $command => $data) {
            if (str_contains($command, $partial) && $data['success_rate'] > 0.7) {
                $suggestions[] = [
                    'text' => $command,
                    'type' => 'special',
                    'confidence' => $data['success_rate'],
                ];
            }
        }
        
        return $suggestions;
    }
}
```

## Error Handling

### Standard Error Responses

```php
// Success response
[
    'action' => 'navigate',
    'url' => '/destination',
    'message' => 'Success message'
]

// Error response
[
    'action' => 'error',
    'message' => 'Error description',
    'suggestions' => ['Did you mean...?']
]

// Search response
[
    'action' => 'search',
    'query' => 'search terms',
    'message' => 'Searching for...'
]
```

### Exception Handling

```php
try {
    $result = CommandIntentService::parseCommand($input, $context);
} catch (CommandParsingException $e) {
    Log::error('Command parsing failed', [
        'input' => $input,
        'error' => $e->getMessage(),
    ]);
    
    return [
        'action' => 'error',
        'message' => 'Could not understand command',
        'suggestions' => ['Try: create ticket', 'show invoices']
    ];
}
```

## Performance Considerations

### Caching Strategy
```php
// Cache suggestion results
$suggestions = Cache::remember("suggestions:{$partial}:{$userId}", 300, function() {
    return CommandIntentService::getSuggestions($partial, $context);
});

// Cache entity lookups
$entity = Cache::remember("entity:{$type}:{$id}", 600, function() {
    return EntityResolverService::resolve($type, $id, $context);
});
```

### Database Optimization
```php
// Add indexes for common searches
Schema::table('clients', function (Blueprint $table) {
    $table->index(['company_id', 'name']);
    $table->index(['company_id', 'updated_at']);
});

// Limit query results
$query->limit(10); // Prevent large result sets
```

### Background Processing
```php
// Queue learning updates for heavy operations
dispatch(new UpdateLearningPatternsJob($userId, $command, $success));

// Batch entity cache warming
dispatch(new WarmEntityCacheJob($entityType))->onQueue('low');
```

## Testing Examples

### Unit Tests
```php
class CommandIntentServiceTest extends TestCase
{
    /** @test */
    public function it_parses_create_commands()
    {
        $parsed = CommandIntentService::parseCommand('create ticket urgent');
        
        $this->assertEquals('CREATE', $parsed->getIntent());
        $this->assertContains('ticket', $parsed->getEntities());
        $this->assertContains('URGENT', $parsed->getModifiers());
        $this->assertGreaterThan(0.5, $parsed->getConfidence());
    }
    
    /** @test */
    public function it_handles_shortcuts()
    {
        $parsed = CommandIntentService::parseCommand('#123');
        
        $this->assertEquals('GO', $parsed->getIntent());
        $this->assertTrue($parsed->isShortcut());
        $this->assertEquals('ticket', $parsed->getPrimaryEntity());
    }
}
```

### Integration Tests
```php
class CommandSystemIntegrationTest extends TestCase
{
    /** @test */
    public function it_resolves_entities_with_context()
    {
        $client = Client::factory()->create(['name' => 'Acme Corp']);
        
        $entity = EntityResolverService::resolve('client', 'acme', [
            'company_id' => $client->company_id
        ]);
        
        $this->assertNotNull($entity);
        $this->assertEquals($client->id, $entity->id);
    }
}
```

---

This API reference provides the foundation for extending and integrating with the Nestogy Command System. All services are designed to be extensible and maintainable while providing powerful functionality.