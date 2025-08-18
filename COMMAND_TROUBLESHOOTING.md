# 🔧 Command System Troubleshooting Guide

## Common Issues & Solutions

### Command Palette Issues

#### Command Palette Not Opening
**Symptoms**: Pressing `Ctrl+/` or `/` doesn't open the command palette

**Causes & Solutions**:
1. **Input Field Focus**: You're typing in a text input
   - **Solution**: Click outside the input field first, then try the shortcut
   
2. **Browser Extension Conflict**: Extensions intercepting keyboard shortcuts
   - **Solution**: Try `Shift+Ctrl+/` or use the search button in navigation
   
3. **JavaScript Errors**: Console errors preventing initialization
   - **Solution**: Open browser console (F12), look for errors, refresh page
   
4. **Ad Blockers**: Blocking JavaScript execution
   - **Solution**: Whitelist your Nestogy domain

#### Command Palette Opens But Shows No Suggestions
**Symptoms**: Empty suggestion list when palette opens

**Causes & Solutions**:
1. **API Connection Issues**: Network or server problems
   - **Solution**: Check network tab in browser dev tools
   - **Check**: `/api/navigation/suggestions` endpoint responds
   
2. **Session Expired**: User authentication lost
   - **Solution**: Refresh page to re-authenticate
   
3. **Database Connection**: Backend can't query recent items
   - **Solution**: Check application logs for database errors

### Command Processing Issues

#### Commands Not Executing
**Symptoms**: Command appears to be processed but nothing happens

**Debug Steps**:
1. **Open Browser Console** (F12)
2. **Enter Command** and look for JavaScript errors
3. **Check Network Tab** for failed API requests
4. **Look for Error Messages** in response

**Common Causes**:
```javascript
// Permission errors
{
  "action": "error", 
  "message": "Insufficient permissions to create tickets"
}

// Route errors
{
  "action": "error",
  "message": "Route [tickets.create] not defined"
}

// Context errors
{
  "action": "error", 
  "message": "Client context required but not provided"
}
```

#### Wrong Command Interpretation
**Symptoms**: Command does something different than expected

**Debug Commands**: Add `?debug=commands` to URL for detailed parsing info

**Example Debug Output**:
```
Input: "create invoice for client"
Parsed Intent: CREATE
Extracted Entity: CLIENT (should be INVOICE)
Context Applied: client_id=null
Route: clients.create (incorrect)
```

**Common Causes**:
1. **Entity Matching Order**: More general entities matched before specific ones
   - "create invoice for client" matches "client" before "invoice"
   - **Solution**: Use more specific commands like "invoice for acme"

2. **Regex Pattern Conflicts**: Multiple patterns matching same input
   - **Solution**: Check `CommandPaletteService::$commandPatterns` order

3. **Context Misapplication**: Wrong context being applied
   - **Solution**: Check context passing in `processCommand()`

### Search & Suggestion Issues

#### Slow Autocomplete
**Symptoms**: Suggestions take 3+ seconds to appear

**Performance Debugging**:
```bash
# Check query performance
tail -f storage/logs/laravel.log | grep "suggestions"

# Profile database queries
?debug=queries
```

**Solutions**:
1. **Database Indexes Missing**:
   ```sql
   -- Add indexes for common search fields
   CREATE INDEX idx_clients_name ON clients(name);
   CREATE INDEX idx_tickets_subject ON tickets(subject);
   CREATE INDEX idx_invoices_number ON invoices(invoice_number);
   ```

2. **Cache Not Working**:
   ```bash
   # Clear and rebuild cache
   php artisan cache:clear
   php artisan config:cache
   ```

3. **Too Many Results**:
   ```php
   // Limit query results in CommandPaletteService
   ->limit(10) // Add to queries
   ```

#### Incorrect Search Results
**Symptoms**: Search returns wrong or irrelevant items

**Debug Steps**:
1. **Check Entity Keywords**: Verify entity mapping in `extractEntities()`
2. **Test Search Query**: Run database queries manually
3. **Check Permissions**: Ensure user can see returned items

**Common Issues**:
```php
// Entity keywords too broad
'invoice' => ['invoice', 'bill', 'payment'] // 'payment' conflicts

// Search query issues
$query->where('name', 'like', "%{$search}%")
      ->orWhere('email', 'like', "%{$search}%"); // Too permissive

// Permission filtering missing
->where('company_id', auth()->user()->company_id) // Always required
```

### Context & Permission Issues

#### Commands Ignore Selected Client
**Symptoms**: Creating items doesn't associate with selected client

**Debug Steps**:
1. **Check Client Selection**: Verify client is actually selected
   ```javascript
   // In browser console
   console.log(window.selectedClientId);
   ```

2. **Check Context Passing**:
   ```php
   // In NavigationController::executeCommand()
   $context = [
       'client_id' => session('selected_client_id'), // Verify this exists
   ];
   ```

3. **Check Handler Logic**:
   ```php
   // In CommandPaletteService::handleCreateItem()
   if (isset($context['client_id']) && /* conditions */) {
       $params['client_id'] = $context['client_id']; // Verify this executes
   }
   ```

#### Permission Denied Errors
**Symptoms**: Commands fail with "insufficient permissions"

**Debug Steps**:
1. **Check User Permissions**:
   ```php
   // In tinker
   $user = auth()->user();
   $user->getAllPermissions()->pluck('name');
   ```

2. **Check Policy Implementation**:
   ```php
   // Test specific policy
   Gate::allows('create', \App\Models\Ticket::class);
   ```

3. **Check Middleware**:
   ```php
   // In routes/web.php
   Route::middleware(['auth', 'permission:create-tickets'])
   ```

### Entity Resolution Issues

#### Entities Not Found
**Symptoms**: "Cannot find client 'acme'" when client exists

**Common Causes**:
1. **Case Sensitivity**: Search is case-sensitive
   ```php
   // Fix: Make search case-insensitive
   ->where('name', 'ilike', "%{$search}%") // PostgreSQL
   ->where('name', 'like', "%{$search}%") // MySQL with proper collation
   ```

2. **Company Scoping**: Searching across all companies
   ```php
   // Always add company filter
   ->where('company_id', auth()->user()->company_id)
   ```

3. **Fuzzy Matching Too Strict**: Exact match required
   ```php
   // Implement fuzzy matching
   $similarity = similar_text($input, $entity->name);
   if ($similarity > 60) { /* match */ }
   ```

#### Wrong Entity Selected
**Symptoms**: Command opens wrong client/ticket/invoice

**Debug Steps**:
1. **Check ID Resolution**: Verify ID parsing logic
2. **Check Recent Item Priority**: Recent items might override exact matches
3. **Check Fuzzy Matching**: Similar names causing conflicts

### Performance Issues

#### High Memory Usage
**Symptoms**: PHP memory errors during command processing

**Solutions**:
```php
// Use chunking for large datasets
Client::chunk(100, function($clients) {
    // Process in batches
});

// Limit eager loading
->with(['client' => function($query) {
    $query->select('id', 'name'); // Only needed fields
}])

// Clear variables after use
unset($largeArray);
gc_collect_cycles();
```

#### Database Connection Issues
**Symptoms**: "Database connection lost" errors

**Solutions**:
```php
// Add connection retry logic
try {
    return $query->get();
} catch (\Exception $e) {
    DB::reconnect();
    return $query->get();
}

// Check connection pool settings
// In config/database.php
'options' => [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_TIMEOUT => 30,
]
```

## Debug Commands

### Enable Debug Mode
Add these parameters to any URL for debugging information:

```
?debug=commands    # Show command parsing details
?debug=context     # Display current context
?debug=suggestions # Show suggestion generation process
?debug=queries     # Show database queries (Laravel debugbar)
?debug=cache       # Show cache operations
```

### Console Debug Commands
Open browser console and use these JavaScript commands:

```javascript
// Check command palette state
window.commandPalette.isOpen
window.commandPalette.suggestions
window.commandPalette.query

// Test command processing
fetch('/api/navigation/command', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({ command: 'test command' })
}).then(r => r.json()).then(console.log);

// Check suggestions endpoint
fetch('/api/navigation/suggestions?q=create')
    .then(r => r.json())
    .then(console.log);
```

### Laravel Debug Commands
Use these artisan commands for backend debugging:

```bash
# View logs
tail -f storage/logs/laravel.log

# Clear various caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list | grep navigation
php artisan route:list | grep command

# Test in tinker
php artisan tinker
>>> CommandPaletteService::processCommand('create ticket', ['client_id' => 1])
```

## Error Codes & Messages

### Common Error Responses

#### ERR_001: Command Not Recognized
```json
{
    "action": "error",
    "message": "Command not recognized: 'xyz'",
    "suggestions": ["Did you mean 'create'?"]
}
```
**Solution**: Check for typos, use autocomplete suggestions

#### ERR_002: Insufficient Permissions
```json
{
    "action": "error", 
    "message": "You don't have permission to create tickets"
}
```
**Solution**: Contact administrator to grant permissions

#### ERR_003: Entity Not Found
```json
{
    "action": "error",
    "message": "Cannot find client 'acme'",
    "suggestions": ["Acme Corporation", "Acme Tech"]
}
```
**Solution**: Use suggested exact names or IDs

#### ERR_004: Context Required
```json
{
    "action": "error",
    "message": "Client selection required for this command"
}
```
**Solution**: Select a client first, or specify client in command

#### ERR_005: Route Not Found
```json
{
    "action": "error",
    "message": "Route [tickets.create] not defined"
}
```
**Solution**: Check route definitions, ensure module is enabled

## Logging & Monitoring

### Enable Command Logging
```php
// In CommandPaletteService::processCommand()
Log::info('Command executed', [
    'user_id' => auth()->id(),
    'command' => $command,
    'context' => $context,
    'result' => $result,
    'execution_time' => microtime(true) - $start,
]);
```

### Monitor Performance
```php
// Track slow commands
if ($executionTime > 2.0) {
    Log::warning('Slow command execution', [
        'command' => $command,
        'time' => $executionTime,
        'user_id' => auth()->id(),
    ]);
}
```

### New Services Debugging

#### CommandIntentService Issues
```php
// Debug intent parsing
$parsed = CommandIntentService::parseCommand('create ticket acme', $context);
Log::info('Parsed command', [
    'intent' => $parsed->getIntent(),
    'entities' => $parsed->getEntities(),
    'confidence' => $parsed->getConfidence(),
]);
```

#### EntityResolverService Issues
```php
// Debug entity resolution
$entity = EntityResolverService::resolve('client', 'acme', $context);
Log::info('Entity resolution', [
    'found' => $entity ? true : false,
    'entity_id' => $entity?->id,
    'entity_name' => $entity?->name,
]);

// Clear entity cache if needed
EntityResolverService::clearCache('client', 'acme');
```

#### CommandLearningService Issues
```php
// Check user patterns
$patterns = CommandLearningService::getUserPatterns(auth()->id());
Log::info('User patterns', ['count' => count($patterns)]);

// Get personalized suggestions
$suggestions = CommandLearningService::getPersonalizedSuggestions(
    auth()->id(), 
    'create', 
    $context
);

// Clear learning data if corrupted
CommandLearningService::clearUserLearning(auth()->id());
```

## Getting Help

### Internal Resources
- **COMMAND_SYSTEM.md**: Technical documentation
- **NAVIGATION_GUIDE.md**: User guide with examples
- **Application Logs**: `storage/logs/laravel.log`

### Debug Information to Collect
When reporting issues, include:

1. **Command Input**: Exact command that failed
2. **Expected Behavior**: What should have happened
3. **Actual Behavior**: What actually happened
4. **Browser Console Errors**: JavaScript errors
5. **Laravel Logs**: PHP errors and warnings
6. **User Context**: Selected client, current page, user role
7. **Browser/Environment**: Browser version, device type

### Quick Diagnostic Commands
```bash
# System health check
php artisan about
php artisan config:show database
php artisan cache:table

# Permission check
php artisan permission:show {user_id}

# Route verification
php artisan route:list --name=navigation
```

---

For complex issues not covered here, enable debug mode and collect the diagnostic information listed above before seeking assistance.