# 🚀 Nestogy Command System Developer Guide

## Overview

The Nestogy Command System is a sophisticated intent-based natural language interface that allows users to interact with the MSP platform using intuitive commands. This document provides comprehensive technical details for developers working on or extending the command system.

## Architecture

### Core Services

#### 1. CommandPaletteService (Main Orchestrator)
- **Location**: `app/Services/CommandPaletteService.php`
- **Purpose**: Primary entry point for all command processing
- **Key Methods**:
  - `processCommand(string $command, array $context)` - Main command processor
  - `getSuggestions(string $partial, array $context)` - Real-time autocomplete
  - `getDefaultSuggestions(array $context)` - Context-aware quick actions

#### 2. CommandIntentService (Intelligence Layer)
- **Location**: `app/Services/CommandIntentService.php`
- **Purpose**: Natural language processing and intent recognition
- **Responsibilities**:
  - Parse and normalize user input with preprocessing
  - Classify intent types (CREATE, SHOW, GO, FIND, ACTION)
  - Extract entities and modifiers from natural language
  - Handle shortcuts and abbreviations
  - Provide intelligent autocomplete suggestions
  - Calculate confidence scores for parsed commands

#### 3. EntityResolverService (Smart Entity Matching)
- **Location**: `app/Services/EntityResolverService.php`
- **Purpose**: Resolve entity references with advanced matching
- **Capabilities**:
  - ID-based lookups (`#123`, `INV-456`, `@acme`)
  - Fuzzy name matching with typo tolerance
  - Context-aware entity prioritization
  - Recent item preference and scoring
  - Multi-entity global search
  - Performance-optimized caching

#### 4. CommandLearningService (Adaptive Intelligence)
- **Location**: `app/Services/CommandLearningService.php`
- **Purpose**: Machine learning and user adaptation
- **Features**:
  - User pattern recognition and learning
  - Command success/failure tracking
  - Personalized suggestion generation
  - Contextual pattern analysis
  - Popular command identification
  - Analytics and performance insights

#### 5. ParsedCommand (Data Structure)
- **Location**: `app/Services/ParsedCommand.php`
- **Purpose**: Immutable value object for structured commands
- **Contains**: Intent, entities, modifiers, context, confidence score

## Intent Classification System

### Intent Hierarchy

```
┌─ DIRECT COMMANDS
│  ├─ help, logout, clear
│  └─ Exact string matches
│
├─ STRUCTURED COMMANDS  
│  ├─ create [entity]
│  ├─ show [items]
│  ├─ go to [place]
│  └─ Pattern-based extraction
│
├─ ENTITY REFERENCES
│  ├─ ticket #123
│  ├─ invoice INV-456  
│  ├─ client @acme
│  └─ ID/shortcut resolution
│
└─ NATURAL LANGUAGE
   ├─ "show me overdue invoices"
   ├─ "what tickets need attention"
   └─ Full NLP processing
```

### Intent Types

#### Primary Intents
- **CREATE**: Create new entities (`create`, `new`, `add`, `+`)
- **SHOW**: Display/filter content (`show`, `display`, `list`, `view`)
- **GO**: Navigation (`go`, `open`, `visit`, `navigate`)
- **FIND**: Search operations (`find`, `search`, `lookup`, `/`)
- **ACTION**: Entity actions (`send`, `email`, `export`, `print`)

#### Intent Modifiers
- **FOR_CLIENT**: Commands scoped to selected client
- **URGENT**: Priority-based filtering (`urgent`, `critical`, `!`)
- **OVERDUE**: Time-based filtering (`overdue`, `late`)
- **SCHEDULED**: Future items (`scheduled`, `upcoming`)
- **MY**: User-scoped (`my`, `assigned to me`)

### Entity Types

```php
const ENTITIES = [
    'ticket' => ['ticket', 'tickets', 'issue', 'support', '#'],
    'client' => ['client', 'clients', 'customer', 'company', '@'], 
    'invoice' => ['invoice', 'invoices', 'bill', 'billing', '$'],
    'quote' => ['quote', 'quotes', 'estimate', 'proposal'],
    'project' => ['project', 'projects', 'task'],
    'asset' => ['asset', 'assets', 'device', 'equipment'],
    'user' => ['user', 'users', 'staff', 'team'],
    'contract' => ['contract', 'contracts', 'agreement'],
    'expense' => ['expense', 'expenses', 'cost'],
    'payment' => ['payment', 'payments', 'transaction'],
    'article' => ['article', 'articles', 'kb', 'knowledge'],
];
```

## Command Processing Flow

### Enhanced Processing Pipeline

The new intelligent command system uses a sophisticated multi-stage processing pipeline:

```php
// Stage 1: Intent Service Processing
$parsedCommand = CommandIntentService::parseCommand($input, $context);

// Stage 2: Entity Resolution (if needed)
if ($parsedCommand->hasEntityReference()) {
    $entity = EntityResolverService::resolve(
        $parsedCommand->getPrimaryEntity(),
        $parsedCommand->getEntityReference()['value'],
        $context
    );
}

// Stage 3: Learning Integration
CommandLearningService::recordSuccess($input, $parsedCommand, $context);

// Stage 4: Action Execution
$result = $this->executeCommand($parsedCommand, $entity, $context);
```

### 1. Input Preprocessing (CommandIntentService)
```php
public static function preprocessInput(string $input): string
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

    return preg_replace('/\s+/', ' ', $input);
}
```

### 2. Intelligent Intent Recognition
```php
public static function parseCommand(string $input, array $context = []): ParsedCommand
{
    // Check shortcuts first (#123, @client, $invoice)
    if ($shortcut = static::parseShortcuts($input, $context)) {
        return $shortcut;
    }

    // Extract components using NLP
    $intent = static::extractIntent($input);
    $entities = static::extractEntities($input);
    $modifiers = static::extractModifiers($input, $context);
    $entityReference = static::extractEntityReference($input);

    return new ParsedCommand([
        'intent' => $intent,
        'entities' => $entities,
        'modifiers' => $modifiers,
        'confidence' => static::calculateConfidence($intent, $entities, $modifiers),
        // ... additional data
    ]);
}
```

### 3. Smart Entity Resolution
```php
public static function resolve(string $entityType, $identifier, array $context = []): ?Model
{
    // Try direct ID lookup with caching
    if (is_numeric($identifier)) {
        return static::findById($modelClass, $identifier, $context);
    }

    // Handle special formats (INV-123, QUOTE-456)
    if ($entity = static::findBySpecialId($modelClass, $entityType, $identifier, $context)) {
        return $entity;
    }

    // Fuzzy matching with scoring
    return static::findByName($modelClass, $entityType, $identifier, $context);
}
```

### 4. Machine Learning Integration
```php
// Record successful command for learning
CommandLearningService::recordSuccess($command, $parsedCommand, $context);

// Get personalized suggestions
$suggestions = CommandLearningService::getPersonalizedSuggestions(
    auth()->id(), 
    $partial, 
    $context
);
```

## Natural Language Processing

### Pattern Recognition

#### Create Commands
```
Input: "create ticket for acme corp"
Parse: Intent=CREATE, Entity=TICKET, Context=CLIENT(acme corp)
Result: Route to tickets.create with client_id resolved

Input: "new invoice for current client"  
Parse: Intent=CREATE, Entity=INVOICE, Context=CURRENT_CLIENT
Result: Route to invoices.create with context client_id
```

#### Show Commands
```
Input: "show me overdue invoices"
Parse: Intent=SHOW, Entity=INVOICE, Filter=OVERDUE
Result: Route to invoices.index with status=overdue

Input: "what tickets are urgent"
Parse: Intent=SHOW, Entity=TICKET, Filter=URGENT
Result: Route to tickets.index with priority=urgent
```

#### Find Commands
```
Input: "find server issues"
Parse: Intent=FIND, Query="server issues", Context=ALL
Result: Search across all entities for "server issues"

Input: "search acme invoices"
Parse: Intent=FIND, Entity=INVOICE, Context=CLIENT(acme)
Result: Search invoices for client matching "acme"
```

### Entity Resolution

#### Fuzzy Matching Algorithm
```php
public function resolveEntity(string $type, string $identifier, array $context): ?object
{
    // Direct ID match
    if (is_numeric($identifier)) {
        return $this->findById($type, $identifier);
    }
    
    // Fuzzy name matching
    $results = $this->searchByName($type, $identifier);
    
    // Apply context-based scoring
    $results = $this->scoreByContext($results, $context);
    
    // Prioritize recent items
    $results = $this->prioritizeRecent($results, auth()->id());
    
    return $results[0] ?? null;
}
```

#### Context Scoring
- **Selected Client**: +10 points
- **Recent Activity**: +5 points per recent interaction
- **User Assignment**: +3 points if assigned to user
- **Exact Match**: +20 points
- **Partial Match**: +1-10 points based on similarity

## Command Handlers

### Handler Registration
```php
protected static $commandHandlers = [
    'CREATE' => CreateCommandHandler::class,
    'SHOW' => ShowCommandHandler::class, 
    'GO' => NavigationCommandHandler::class,
    'FIND' => SearchCommandHandler::class,
    'ACTION' => ActionCommandHandler::class,
];
```

### Handler Interface
```php
interface CommandHandlerInterface
{
    public function handle(ParsedCommand $command, array $context): CommandResult;
    public function canHandle(ParsedCommand $command): bool;
    public function getSuggestions(string $partial, array $context): array;
}
```

### Example Handler Implementation
```php
class CreateCommandHandler implements CommandHandlerInterface
{
    public function handle(ParsedCommand $command, array $context): CommandResult
    {
        $entity = $command->getEntity();
        $route = $this->getCreateRoute($entity);
        
        $params = [];
        if ($command->hasContext('client_id')) {
            $params['client_id'] = $command->getContext('client_id');
        }
        
        return new CommandResult([
            'action' => 'navigate',
            'url' => route($route, $params),
            'message' => "Creating new {$entity}",
        ]);
    }
}
```

## Suggestion Engine

### Real-time Autocomplete
The suggestion engine provides intelligent autocomplete as users type:

```php
public function getSuggestions(string $partial, array $context): array
{
    $suggestions = [];
    
    // Quick actions for empty input
    if (empty($partial)) {
        return $this->getQuickActions($context);
    }
    
    // Command completion
    $suggestions = array_merge($suggestions, $this->getCommandSuggestions($partial));
    
    // Entity suggestions
    $suggestions = array_merge($suggestions, $this->getEntitySuggestions($partial, $context));
    
    // Recent items
    if ($this->shouldShowRecent($partial)) {
        $suggestions = array_merge($suggestions, $this->getRecentItems($context));
    }
    
    return $this->rankSuggestions($suggestions, $partial, $context);
}
```

### Suggestion Ranking
1. **Exact prefix matches**: Highest priority
2. **Context relevance**: Selected client, current page
3. **User patterns**: Frequently used commands
4. **Recent activity**: Recently accessed items
5. **Fuzzy matches**: Typo tolerance

## Error Handling & Recovery

### Command Validation
```php
public function validateCommand(ParsedCommand $command, array $context): ValidationResult
{
    // Check permissions
    if (!$this->userCanExecute($command, auth()->user())) {
        return ValidationResult::forbidden('Insufficient permissions');
    }
    
    // Validate entity exists
    if ($command->hasEntityReference() && !$this->entityExists($command)) {
        return ValidationResult::notFound('Entity not found');
    }
    
    // Check business rules
    if (!$this->businessRulesAllow($command, $context)) {
        return ValidationResult::businessRule('Action not allowed');
    }
    
    return ValidationResult::valid();
}
```

### Error Suggestions
When commands fail, provide helpful suggestions:

```php
public function suggestCorrections(string $failedCommand): array
{
    $suggestions = [];
    
    // Typo corrections
    $suggestions[] = $this->findTypoCorrections($failedCommand);
    
    // Similar commands
    $suggestions[] = $this->findSimilarCommands($failedCommand);
    
    // Context-aware alternatives
    $suggestions[] = $this->getContextAlternatives($failedCommand);
    
    return array_filter($suggestions);
}
```

## Performance Optimization

### Caching Strategy
```php
// Command execution results
Cache::remember("command:suggestions:{$partial}", 300, function() use ($partial, $context) {
    return $this->generateSuggestions($partial, $context);
});

// Entity lookups
Cache::remember("entity:client:{$identifier}", 600, function() use ($identifier) {
    return $this->resolveClient($identifier);
});

// User command patterns  
Cache::remember("user:patterns:{$userId}", 3600, function() use ($userId) {
    return $this->getUserCommandPatterns($userId);
});
```

### Database Optimization
- Index frequently searched entity fields (name, email, number)
- Use database views for complex entity queries
- Implement pagination for large result sets
- Cache frequently accessed entities

## Testing

### Unit Tests
```php
class CommandPaletteServiceTest extends TestCase
{
    /** @test */
    public function it_creates_ticket_with_client_context()
    {
        $result = CommandPaletteService::processCommand(
            'create ticket',
            ['client_id' => 1]
        );
        
        $this->assertEquals('navigate', $result['action']);
        $this->assertStringContains('client_id=1', $result['url']);
    }
    
    /** @test */
    public function it_handles_natural_language()
    {
        $result = CommandPaletteService::processCommand(
            'show me overdue invoices',
            []
        );
        
        $this->assertEquals('navigate', $result['action']);
        $this->assertStringContains('status=overdue', $result['url']);
    }
}
```

### Integration Tests
- Test command execution end-to-end
- Validate context application
- Test permission enforcement
- Verify error handling

## Extending the System

### Adding New Commands
1. **Define Intent Pattern**:
   ```php
   protected static $commandPatterns = [
       '/^archive (.+)$/i' => 'archiveItem',
   ];
   ```

2. **Create Handler Method**:
   ```php
   protected static function handleArchiveItem($matches, $context): array
   {
       // Implementation
   }
   ```

3. **Add Suggestions**:
   ```php
   'archive ticket' => ['icon' => '📦', 'description' => 'Archive completed ticket'],
   ```

### Adding New Entities
1. **Register Entity**:
   ```php
   const ENTITIES = [
       'document' => ['document', 'documents', 'doc', 'file'],
   ];
   ```

2. **Add Routes**:
   ```php
   'document' => ['route' => 'documents.create', 'name' => 'New Document'],
   ```

3. **Implement Search**:
   ```php
   // Add to NavigationController search method
   if ($domain === 'all' || $domain === 'documents') {
       // Search implementation
   }
   ```

## Best Practices

### Command Design
- Use consistent verb patterns (create, show, go, find)
- Support multiple aliases for the same action
- Make commands discoverable through autocomplete
- Provide clear error messages and suggestions

### Performance
- Cache frequently accessed data
- Use database indexes for entity lookups
- Implement pagination for large result sets
- Monitor command execution times

### User Experience
- Show progress indicators for long operations
- Provide keyboard shortcuts for power users
- Learn from user patterns and adapt suggestions
- Handle edge cases gracefully

### Security
- Always validate user permissions
- Sanitize user input
- Audit sensitive command executions
- Implement rate limiting for command processing

## Troubleshooting

### Common Issues
1. **Commands not matching**: Check regex patterns and entity keywords
2. **Context not applying**: Verify context passing through call chain
3. **Slow autocomplete**: Review caching strategy and database indexes
4. **Permission errors**: Check policy implementations and middleware

### Debug Commands
- `?debug=commands` - Show command parsing details
- `?debug=context` - Display current context
- `?debug=suggestions` - Show suggestion generation process

## API Reference

### Main Methods

#### CommandPaletteService::processCommand()
```php
public static function processCommand(string $command, array $context = []): array
```
- **Purpose**: Process user command and return action
- **Parameters**: 
  - `$command`: User input string
  - `$context`: Current context (client_id, user_id, etc.)
- **Returns**: Action array with type, URL, and message

#### CommandPaletteService::getSuggestions()
```php
public static function getSuggestions(?string $partial, array $context = []): array
```
- **Purpose**: Get autocomplete suggestions
- **Parameters**:
  - `$partial`: Partial user input
  - `$context`: Current context
- **Returns**: Array of suggestion objects

### Context Structure
```php
$context = [
    'client_id' => 123,           // Selected client ID
    'user_id' => 456,             // Current user ID
    'company_id' => 789,          // User's company ID
    'domain' => 'tickets',        // Current page domain
    'workflow' => 'urgent',       // Active workflow
    'permissions' => [...],       // User permissions
];
```

### Response Structure
```php
$response = [
    'action' => 'navigate|search|error|help',
    'url' => 'https://...',
    'message' => 'Human readable message',
    'workflow' => 'urgent|today|billing',
    'params' => [...],
];
```

---

This documentation provides the foundation for understanding and extending Nestogy's command system. For implementation examples and troubleshooting, see the accompanying guides.