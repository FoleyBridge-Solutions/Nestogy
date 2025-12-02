# OpenRouter AI Service - Integration Examples

This guide shows you how to integrate the OpenRouter AI service into different parts of your application.

---

## Table of Contents

1. [Basic Usage Patterns](#basic-usage-patterns)
2. [In Controllers](#in-controllers)
3. [In Livewire Components](#in-livewire-components)
4. [In Jobs/Queues](#in-jobsqueues)
5. [In Events/Listeners](#in-eventslisteners)
6. [In Commands](#in-commands)
7. [Helper Functions](#helper-functions)
8. [Common Patterns](#common-patterns)

---

## Basic Usage Patterns

### Getting the Service Instance

```php
use App\Domains\Core\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Auth;

// Get from authenticated user's company
$company = Auth::user()->company;
$aiService = new OpenRouterService($company);

// Get from a specific company
$company = Company::find($companyId);
$aiService = new OpenRouterService($company);

// Get from a model that belongs to a company
$ticket = Ticket::find($id);
$aiService = new OpenRouterService($ticket->company);
```

### Always Check Configuration

```php
if (!$aiService->isConfigured()) {
    // AI not configured for this company
    return; // or show error, etc.
}

// Now safe to use
$result = $aiService->complete('Your prompt here');
```

---

## In Controllers

### Example: Ticket Analysis Controller

```php
<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Core\Services\AI\OpenRouterService;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketAnalysisController extends Controller
{
    public function analyzeSentiment(Request $request, Ticket $ticket)
    {
        // Get AI service for user's company
        $aiService = new OpenRouterService(Auth::user()->company);
        
        if (!$aiService->isConfigured()) {
            return response()->json([
                'error' => 'AI features not configured. Please contact your administrator.'
            ], 503);
        }

        try {
            // Analyze ticket sentiment
            $analysis = $aiService->analyze($ticket->description, 'sentiment');
            
            // Store analysis result
            $ticket->update([
                'ai_sentiment' => $analysis['content'],
                'ai_sentiment_analyzed_at' => now(),
            ]);
            
            return response()->json([
                'success' => true,
                'sentiment' => $analysis['content'],
                'tokens_used' => $analysis['tokens_used'],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('AI sentiment analysis failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Failed to analyze sentiment. Please try again.'
            ], 500);
        }
    }
    
    public function suggestPriority(Request $request, Ticket $ticket)
    {
        $aiService = new OpenRouterService(Auth::user()->company);
        
        if (!$aiService->isConfigured()) {
            return back()->with('error', 'AI features are not enabled.');
        }

        try {
            $analysis = $aiService->analyze($ticket->description, 'ticket');
            
            // Parse AI response to extract priority
            $priority = $this->extractPriority($analysis['content']);
            
            return response()->json([
                'suggested_priority' => $priority,
                'reasoning' => $analysis['content'],
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function extractPriority(string $analysis): string
    {
        if (stripos($analysis, 'critical') !== false || stripos($analysis, 'urgent') !== false) {
            return 'high';
        } elseif (stripos($analysis, 'medium') !== false) {
            return 'medium';
        }
        return 'low';
    }
}
```

### Example: Email Generation Controller

```php
<?php

namespace App\Domains\Ticket\Controllers;

use App\Domains\Core\Services\AI\OpenRouterService;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Http\Request;

class EmailResponseController extends Controller
{
    public function generateResponse(Request $request, Ticket $ticket)
    {
        $request->validate([
            'tone' => 'nullable|in:professional,friendly,concise',
        ]);

        $aiService = new OpenRouterService($ticket->company);
        
        if (!$aiService->isConfigured()) {
            return back()->with('error', 'AI features not available.');
        }

        try {
            $emailContent = $aiService->generate('email', [
                'recipient_name' => $ticket->contact->name ?? 'Valued Customer',
                'ticket_number' => $ticket->number,
                'subject' => $ticket->subject,
                'issue_description' => $ticket->description,
                'resolution' => $ticket->resolution ?? 'in progress',
                'tone' => $request->input('tone', 'professional'),
            ]);
            
            return response()->json([
                'email_content' => $emailContent,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate email'], 500);
        }
    }
}
```

---

## In Livewire Components

### Example: Ticket AI Assistant Component

```php
<?php

namespace App\Livewire\Ticket;

use App\Domains\Core\Services\AI\OpenRouterService;
use App\Domains\Ticket\Models\Ticket;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class TicketAIAssistant extends Component
{
    public Ticket $ticket;
    public string $aiSuggestion = '';
    public bool $loading = false;
    public ?string $error = null;

    public function mount(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function getSuggestions()
    {
        $this->loading = true;
        $this->error = null;

        try {
            $aiService = new OpenRouterService(Auth::user()->company);
            
            if (!$aiService->isConfigured()) {
                $this->error = 'AI features are not configured.';
                return;
            }

            $context = "Ticket: {$this->ticket->subject}\n\n{$this->ticket->description}";
            $suggestions = $aiService->suggest($context, 'troubleshooting', 5);
            
            $this->aiSuggestion = implode("\n", array_map(
                fn($i, $s) => ($i + 1) . ". " . $s,
                array_keys($suggestions),
                $suggestions
            ));
            
        } catch (\Exception $e) {
            $this->error = 'Failed to generate suggestions.';
            \Log::error('AI suggestions failed', ['error' => $e->getMessage()]);
        } finally {
            $this->loading = false;
        }
    }

    public function summarize()
    {
        $this->loading = true;
        $this->error = null;

        try {
            $aiService = new OpenRouterService(Auth::user()->company);
            
            if (!$aiService->isConfigured()) {
                $this->error = 'AI features are not configured.';
                return;
            }

            // Combine ticket description and all comments
            $fullText = $this->ticket->description . "\n\n";
            $fullText .= $this->ticket->comments->pluck('body')->implode("\n\n");
            
            $this->aiSuggestion = $aiService->summarize($fullText, 300);
            
        } catch (\Exception $e) {
            $this->error = 'Failed to summarize ticket.';
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.ticket.ai-assistant');
    }
}
```

**Blade View** (`resources/views/livewire/ticket/ai-assistant.blade.php`):

```blade
<div class="ai-assistant">
    <div class="flex gap-2 mb-4">
        <button 
            wire:click="getSuggestions" 
            wire:loading.attr="disabled"
            class="btn btn-primary"
        >
            <span wire:loading.remove wire:target="getSuggestions">
                Get AI Suggestions
            </span>
            <span wire:loading wire:target="getSuggestions">
                Generating...
            </span>
        </button>

        <button 
            wire:click="summarize" 
            wire:loading.attr="disabled"
            class="btn btn-secondary"
        >
            <span wire:loading.remove wire:target="summarize">
                Summarize Ticket
            </span>
            <span wire:loading wire:target="summarize">
                Summarizing...
            </span>
        </button>
    </div>

    @if($error)
        <div class="alert alert-error">
            {{ $error }}
        </div>
    @endif

    @if($aiSuggestion)
        <div class="ai-suggestion-box">
            <h4>AI Suggestion:</h4>
            <pre>{{ $aiSuggestion }}</pre>
        </div>
    @endif
</div>
```

---

## In Jobs/Queues

### Example: Auto-Analyze Tickets on Creation

```php
<?php

namespace App\Jobs;

use App\Domains\Core\Services\AI\OpenRouterService;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeTicketWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function handle()
    {
        $aiService = new OpenRouterService($this->ticket->company);
        
        // Skip if AI not configured
        if (!$aiService->isConfigured()) {
            \Log::info('AI not configured for company', [
                'company_id' => $this->ticket->company_id,
                'ticket_id' => $this->ticket->id,
            ]);
            return;
        }

        try {
            // Analyze ticket
            $analysis = $aiService->analyze($this->ticket->description, 'ticket');
            
            // Classify ticket category
            $categories = ['Hardware', 'Software', 'Network', 'Email', 'Password Reset', 'Other'];
            $classification = $aiService->classify($this->ticket->description, $categories);
            
            // Update ticket with AI insights
            $this->ticket->update([
                'ai_analysis' => $analysis['content'],
                'ai_category' => $classification['category'],
                'ai_confidence' => $classification['confidence'],
                'ai_priority_suggestion' => $this->extractPriority($analysis['content']),
                'ai_analyzed_at' => now(),
            ]);
            
            \Log::info('Ticket analyzed with AI', [
                'ticket_id' => $this->ticket->id,
                'category' => $classification['category'],
                'confidence' => $classification['confidence'],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('AI ticket analysis failed', [
                'ticket_id' => $this->ticket->id,
                'error' => $e->getMessage(),
            ]);
            
            // Don't fail the job - just log
        }
    }

    private function extractPriority(string $analysis): string
    {
        $analysis = strtolower($analysis);
        
        if (str_contains($analysis, 'critical') || str_contains($analysis, 'urgent')) {
            return 'high';
        } elseif (str_contains($analysis, 'medium') || str_contains($analysis, 'moderate')) {
            return 'medium';
        }
        
        return 'low';
    }
}
```

### Dispatching the Job

```php
// In your TicketController or wherever tickets are created
use App\Jobs\AnalyzeTicketWithAI;

public function store(Request $request)
{
    $ticket = Ticket::create($validated);
    
    // Queue AI analysis
    AnalyzeTicketWithAI::dispatch($ticket);
    
    return redirect()->route('tickets.show', $ticket);
}
```

---

## In Events/Listeners

### Example: Event Listener for Ticket Created

```php
<?php

namespace App\Listeners;

use App\Domains\Core\Services\AI\OpenRouterService;
use App\Events\TicketCreated;

class AutoAnalyzeNewTicket
{
    public function handle(TicketCreated $event)
    {
        $ticket = $event->ticket;
        $aiService = new OpenRouterService($ticket->company);
        
        if (!$aiService->isConfigured()) {
            return;
        }

        try {
            // Quick sentiment analysis
            $sentiment = $aiService->analyze($ticket->description, 'sentiment');
            
            // If negative sentiment detected, flag for priority review
            if (stripos($sentiment['content'], 'negative') !== false || 
                stripos($sentiment['content'], 'angry') !== false) {
                
                $ticket->update([
                    'flagged_for_review' => true,
                    'flag_reason' => 'Negative sentiment detected by AI',
                ]);
                
                // Notify managers
                // ... notification logic
            }
            
        } catch (\Exception $e) {
            // Silently fail - don't break ticket creation
            \Log::error('Auto-analyze ticket failed', ['error' => $e->getMessage()]);
        }
    }
}
```

### Registering the Listener

In `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \App\Events\TicketCreated::class => [
        \App\Listeners\AutoAnalyzeNewTicket::class,
    ],
];
```

---

## In Commands

### Example: Artisan Command for Bulk Analysis

```php
<?php

namespace App\Console\Commands;

use App\Domains\Core\Services\AI\OpenRouterService;
use App\Domains\Ticket\Models\Ticket;
use Illuminate\Console\Command;

class AnalyzeUnprocessedTickets extends Command
{
    protected $signature = 'tickets:analyze-ai {--company=}';
    protected $description = 'Analyze unprocessed tickets with AI';

    public function handle()
    {
        $companyId = $this->option('company');
        
        $tickets = Ticket::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereNull('ai_analyzed_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('No tickets to analyze.');
            return 0;
        }

        $this->info("Analyzing {$tickets->count()} tickets...");
        $bar = $this->output->createProgressBar($tickets->count());

        $analyzed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tickets as $ticket) {
            $aiService = new OpenRouterService($ticket->company);
            
            if (!$aiService->isConfigured()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                $analysis = $aiService->analyze($ticket->description, 'ticket');
                
                $ticket->update([
                    'ai_analysis' => $analysis['content'],
                    'ai_analyzed_at' => now(),
                ]);
                
                $analyzed++;
                
            } catch (\Exception $e) {
                $this->error("\nFailed to analyze ticket {$ticket->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Analysis complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Analyzed', $analyzed],
                ['Skipped (AI not configured)', $skipped],
                ['Failed', $failed],
            ]
        );

        return 0;
    }
}
```

---

## Helper Functions

### Create a Global Helper

Create `app/Helpers/AIHelper.php`:

```php
<?php

namespace App\Helpers;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Services\AI\OpenRouterService;

class AIHelper
{
    /**
     * Get AI service for a company
     */
    public static function forCompany(Company $company): ?OpenRouterService
    {
        $service = new OpenRouterService($company);
        
        return $service->isConfigured() ? $service : null;
    }

    /**
     * Check if AI is available for a company
     */
    public static function isAvailable(Company $company): bool
    {
        $service = new OpenRouterService($company);
        return $service->isConfigured();
    }

    /**
     * Safely execute an AI operation with fallback
     */
    public static function safeExecute(Company $company, callable $callback, $default = null)
    {
        $service = new OpenRouterService($company);
        
        if (!$service->isConfigured()) {
            return $default;
        }

        try {
            return $callback($service);
        } catch (\Exception $e) {
            \Log::error('AI operation failed', ['error' => $e->getMessage()]);
            return $default;
        }
    }
}
```

Register in `composer.json`:

```json
"autoload": {
    "files": [
        "app/Helpers/AIHelper.php"
    ]
}
```

Run `composer dump-autoload`.

### Usage

```php
use App\Helpers\AIHelper;

// Check if AI is available
if (AIHelper::isAvailable($company)) {
    // ...
}

// Get service with null check
$aiService = AIHelper::forCompany($company);
if ($aiService) {
    $result = $aiService->complete('...');
}

// Safe execution with fallback
$summary = AIHelper::safeExecute(
    $company,
    fn($ai) => $ai->summarize($text),
    default: 'Summary not available'
);
```

---

## Common Patterns

### Pattern 1: Try AI, Fall Back to Manual

```php
public function getPriority(Ticket $ticket): string
{
    $aiService = new OpenRouterService($ticket->company);
    
    if ($aiService->isConfigured()) {
        try {
            $analysis = $aiService->analyze($ticket->description, 'ticket');
            return $this->extractPriority($analysis['content']);
        } catch (\Exception $e) {
            \Log::warning('AI priority detection failed, using default');
        }
    }
    
    // Fallback to rule-based logic
    return $this->calculatePriorityManually($ticket);
}
```

### Pattern 2: Cache AI Results

```php
use Illuminate\Support\Facades\Cache;

public function getAISummary(Ticket $ticket): ?string
{
    $cacheKey = "ticket.{$ticket->id}.ai_summary";
    
    return Cache::remember($cacheKey, now()->addHours(24), function() use ($ticket) {
        $aiService = new OpenRouterService($ticket->company);
        
        if (!$aiService->isConfigured()) {
            return null;
        }
        
        try {
            return $aiService->summarize($ticket->description);
        } catch (\Exception $e) {
            return null;
        }
    });
}
```

### Pattern 3: Batch Processing

```php
public function analyzeTicketBatch(Collection $tickets)
{
    $results = [];
    $byCompany = $tickets->groupBy('company_id');
    
    foreach ($byCompany as $companyId => $companyTickets) {
        $company = Company::find($companyId);
        $aiService = new OpenRouterService($company);
        
        if (!$aiService->isConfigured()) {
            continue;
        }
        
        foreach ($companyTickets as $ticket) {
            try {
                $results[$ticket->id] = $aiService->analyze(
                    $ticket->description,
                    'sentiment'
                );
            } catch (\Exception $e) {
                \Log::error("Failed to analyze ticket {$ticket->id}");
            }
        }
    }
    
    return $results;
}
```

### Pattern 4: Service Injection (Optional)

Create a service provider if you want dependency injection:

```php
<?php

namespace App\Providers;

use App\Domains\Core\Services\AI\OpenRouterService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AIServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind as a scoped instance (per-request)
        $this->app->scoped(OpenRouterService::class, function ($app) {
            $user = Auth::user();
            
            if (!$user || !$user->company) {
                throw new \Exception('Cannot instantiate AI service without company context');
            }
            
            return new OpenRouterService($user->company);
        });
    }
}
```

Then register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AIServiceProvider::class,
],
```

Now you can inject it:

```php
public function __construct(
    private OpenRouterService $aiService
) {}

public function analyze(Ticket $ticket)
{
    if (!$this->aiService->isConfigured()) {
        return back()->with('error', 'AI not configured');
    }
    
    $result = $this->aiService->analyze($ticket->description, 'sentiment');
    // ...
}
```

---

## Best Practices

1. **Always check `isConfigured()`** before using the service
2. **Use try-catch** for AI operations - they can fail
3. **Don't block user actions** - queue AI processing when possible
4. **Cache results** - AI calls are expensive
5. **Provide fallbacks** - app should work without AI
6. **Log failures** - but don't expose AI errors to users
7. **Consider rate limits** - implement company-level throttling
8. **Track usage** - monitor token consumption per company
9. **Use appropriate models** - GPT-3.5 for speed, GPT-4 for quality
10. **Test thoroughly** - mock the service in tests

---

## Testing

### Mocking the Service

```php
use App\Domains\Core\Services\AI\OpenRouterService;
use Mockery;

public function test_ticket_analysis_with_ai()
{
    $mockService = Mockery::mock(OpenRouterService::class);
    $mockService->shouldReceive('isConfigured')->andReturn(true);
    $mockService->shouldReceive('analyze')
        ->with(Mockery::any(), 'sentiment')
        ->andReturn([
            'content' => 'Positive sentiment detected',
            'tokens_used' => 50,
        ]);
    
    $this->app->instance(OpenRouterService::class, $mockService);
    
    // Test your code
}
```

---

For more examples and detailed API documentation, see:
- `docs/AI_SERVICE_USAGE.md` - Comprehensive API guide
- `AI_SERVICE_IMPLEMENTATION_COMPLETE.md` - Implementation details
