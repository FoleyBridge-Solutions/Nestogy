# OpenRouter AI Service Usage Guide

## Overview

The OpenRouter AI Service provides AI-powered features to your application using the OpenRouter API. This service is **multi-tenant** - each company configures their own API key and settings stored in the database.

## Configuration

### 1. Company Settings

AI settings are stored per-company in the `companies.ai_settings` JSON column:

```json
{
  "enabled": true,
  "openrouter_api_key": "sk-or-v1-...",
  "default_model": "openai/gpt-3.5-turbo",
  "temperature": 0.7,
  "max_tokens": 1000
}
```

### 2. Configuring via UI

Companies can configure AI settings in **Settings > AI Integration**:

- **Enabled**: Toggle AI features on/off
- **OpenRouter API Key**: Your API key from https://openrouter.ai/keys
- **Default Model**: Which AI model to use (GPT-4, Claude, etc.)
- **Temperature**: Creativity level (0-2, lower = more focused)
- **Max Tokens**: Maximum response length

### 3. Configuring Programmatically

```php
use App\Domains\Company\Models\Company;

$company = Company::find(1);
$company->ai_settings = [
    'enabled' => true,
    'openrouter_api_key' => 'sk-or-v1-...',
    'default_model' => 'openai/gpt-3.5-turbo',
    'temperature' => 0.7,
    'max_tokens' => 1000,
];
$company->save();
```

## Usage Examples

### Basic Setup

```php
use App\Domains\Core\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Auth;

// Create service instance for current user's company
$company = Auth::user()->company;
$aiService = new OpenRouterService($company);

// Check if AI is configured
if (!$aiService->isConfigured()) {
    return 'AI service is not configured. Please add your API key in Settings.';
}
```

### 1. Simple Text Completion

```php
$prompt = "Write a professional email thanking a client for their business.";
$response = $aiService->complete($prompt);

echo $response;
// "Dear [Client Name], I wanted to take a moment to express..."
```

### 2. Text Analysis

```php
$ticket = Ticket::find(123);

// Analyze sentiment
$analysis = $aiService->analyze($ticket->description, 'sentiment');
/*
[
    'type' => 'sentiment',
    'content' => 'Overall sentiment: Negative. Customer is frustrated...',
    'model' => 'openai/gpt-3.5-turbo',
    'tokens_used' => 245
]
*/

// Analyze ticket priority
$analysis = $aiService->analyze($ticket->description, 'ticket');
// Returns analysis with urgency, main issue, sentiment, and priority
```

### 3. Text Summarization

```php
$longText = $ticket->description . "\n\n" . $ticket->comments->pluck('body')->implode("\n\n");

$summary = $aiService->summarize($longText, 200);
// Returns a concise ~200 character summary
```

### 4. Generate Suggestions

```php
$context = "Customer's laptop won't turn on. Battery is charged.";
$suggestions = $aiService->suggest($context, 'troubleshooting', 5);
/*
[
    'Check if the power adapter is properly connected',
    'Try a different power outlet',
    'Remove battery and try AC power only',
    'Check for POST beep codes',
    'Test with external monitor to rule out display issues'
]
*/
```

### 5. Text Classification

```php
$categories = ['Bug', 'Feature Request', 'Question', 'Complaint'];
$result = $aiService->classify($ticket->subject, $categories);
/*
[
    'category' => 'Bug',
    'confidence' => 85,
    'raw_response' => 'Bug 85'
]
*/
```

### 6. Extract Structured Data

```php
$email = "Hi, I'm John Doe from Acme Corp. My email is john@acme.com and phone is 555-1234.";
$fields = ['name', 'company', 'email', 'phone'];

$extracted = $aiService->extract($email, $fields);
/*
[
    'name' => 'John Doe',
    'company' => 'Acme Corp',
    'email' => 'john@acme.com',
    'phone' => '555-1234'
]
*/
```

### 7. Generate Content

```php
$data = [
    'recipient_name' => 'John Smith',
    'ticket_number' => '#12345',
    'issue' => 'Email configuration',
    'resolution' => 'Updated SMTP settings',
];

$email = $aiService->generate('email', $data);
// Returns a professionally formatted email using the provided data
```

### 8. Chat with History

```php
$history = [
    ['role' => 'system', 'content' => 'You are a helpful IT support assistant.'],
    ['role' => 'user', 'content' => 'My computer is running slow'],
    ['role' => 'assistant', 'content' => 'Let me help you troubleshoot...'],
];

$response = $aiService->chat($history, 'I also see a blue screen sometimes');
// Returns AI response considering conversation history
```

## Advanced Usage

### Custom Models

Override the default model per request:

```php
// Use GPT-4 for complex analysis
$analysis = $aiService->complete(
    $prompt,
    $systemPrompt,
    'openai/gpt-4-turbo-preview'
);
```

### Custom Parameters

Pass additional options:

```php
$response = $aiService->sendRequest($messages, null, [
    'temperature' => 0.9,  // More creative
    'max_tokens' => 2000,  // Longer response
    'top_p' => 0.95,
]);
```

### Available Models

Get list of available models from OpenRouter:

```php
$models = $aiService->getAvailableModels();
// Returns array of model objects from OpenRouter API
```

## Real-World Examples

### Auto-Prioritize Tickets

```php
use App\Jobs\AutoPrioritizeTicket;

class AutoPrioritizeTicket implements ShouldQueue
{
    public function handle(Ticket $ticket)
    {
        $aiService = new OpenRouterService($ticket->company);
        
        if (!$aiService->isConfigured()) {
            return;
        }
        
        $analysis = $aiService->analyze($ticket->description, 'ticket');
        
        // Parse AI suggestions and update ticket
        $ticket->update([
            'priority' => $this->extractPriority($analysis['content']),
            'ai_analysis' => $analysis['content'],
        ]);
    }
}
```

### Smart Ticket Categorization

```php
$categories = ['Hardware', 'Software', 'Network', 'Email', 'Password Reset'];
$result = $aiService->classify($ticket->description, $categories);

$ticket->category = $result['category'];
$ticket->save();
```

### Generate Knowledge Base Articles

```php
$tickets = Ticket::where('subject', 'LIKE', '%password reset%')
    ->where('status', 'resolved')
    ->limit(10)
    ->get();

$context = $tickets->map(fn($t) => $t->description . "\n" . $t->resolution)->implode("\n\n");

$article = $aiService->generate('documentation', [
    'title' => 'How to Reset Your Password',
    'common_issues' => $context,
]);

KnowledgeBaseArticle::create([
    'title' => 'How to Reset Your Password',
    'content' => $article,
    'company_id' => $company->id,
]);
```

## Error Handling

```php
use Exception;

try {
    $response = $aiService->complete($prompt);
} catch (Exception $e) {
    if (str_contains($e->getMessage(), 'not configured')) {
        // AI service not set up
        return redirect()->route('settings.ai')
            ->with('error', 'Please configure AI settings first.');
    }
    
    if (str_contains($e->getMessage(), 'API request failed')) {
        // API key invalid or rate limited
        Log::error('OpenRouter API error', ['error' => $e->getMessage()]);
        return 'AI service temporarily unavailable.';
    }
    
    throw $e;
}
```

## Best Practices

1. **Always check if configured**: Use `$aiService->isConfigured()` before making requests
2. **Handle errors gracefully**: AI services can fail - have fallbacks
3. **Use appropriate models**: GPT-3.5 for speed/cost, GPT-4 for complex tasks
4. **Cache results**: AI calls are expensive - cache when possible
5. **Limit token usage**: Set appropriate `max_tokens` to control costs
6. **Use system prompts**: Guide the AI with clear instructions
7. **Queue AI jobs**: Don't block user requests - use jobs for AI processing

## Cost Management

- **GPT-3.5 Turbo**: ~$0.001 per 1K tokens (cheapest)
- **GPT-4 Turbo**: ~$0.01 per 1K tokens (most capable)
- **Claude 3 Sonnet**: ~$0.003 per 1K tokens (balanced)

Track usage per company:

```php
// Log token usage after each request
$response = $aiService->sendRequest($messages);
$tokensUsed = $response['usage']['total_tokens'] ?? 0;

AIUsageLog::create([
    'company_id' => $company->id,
    'tokens_used' => $tokensUsed,
    'model' => $response['model'],
    'cost' => $this->calculateCost($tokensUsed, $response['model']),
]);
```

## Testing

```php
use App\Domains\Core\Services\AI\OpenRouterService;
use App\Domains\Company\Models\Company;

class OpenRouterServiceTest extends TestCase
{
    public function test_service_requires_configuration()
    {
        $company = Company::factory()->create([
            'ai_settings' => ['enabled' => false]
        ]);
        
        $service = new OpenRouterService($company);
        
        $this->assertFalse($service->isConfigured());
        
        $this->expectException(Exception::class);
        $service->complete('test prompt');
    }
    
    public function test_service_makes_api_request()
    {
        $company = Company::factory()->create([
            'ai_settings' => [
                'enabled' => true,
                'openrouter_api_key' => 'test-key',
                'default_model' => 'openai/gpt-3.5-turbo',
            ]
        ]);
        
        $service = new OpenRouterService($company);
        
        // Mock HTTP client
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Test response']]
                ]
            ], 200)
        ]);
        
        $response = $service->complete('test');
        
        $this->assertEquals('Test response', $response);
    }
}
```

## Troubleshooting

**Service not configured**:
- Check that `ai_settings.enabled` is `true`
- Verify API key is set in company settings
- Ensure API key starts with `sk-or-v1-`

**API requests failing**:
- Verify API key is valid at https://openrouter.ai/keys
- Check account has sufficient credits
- Review OpenRouter API status

**High costs**:
- Use GPT-3.5 instead of GPT-4 for simple tasks
- Reduce `max_tokens` setting
- Cache AI responses where possible
- Implement rate limiting per company

## See Also

- [OpenRouter Documentation](https://openrouter.ai/docs)
- [OpenRouter Models](https://openrouter.ai/models)
- [OpenRouter Pricing](https://openrouter.ai/docs#models)
