<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CommandPaletteService
{
    /**
     * Command patterns and their handlers
     */
    protected static $commandPatterns = [
        // Navigation commands
        '/^go to (.+)$/i' => 'navigateTo',
        '/^open (.+)$/i' => 'navigateTo',
        '/^show (.+)$/i' => 'showItems',
        
        // Action commands
        '/^create (.+)$/i' => 'createItem',
        '/^new (.+)$/i' => 'createItem',
        '/^add (.+)$/i' => 'createItem',
        
        // Search commands
        '/^find (.+)$/i' => 'searchFor',
        '/^search (.+)$/i' => 'searchFor',
        '/^lookup (.+)$/i' => 'searchFor',
        
        // Filter commands
        '/^filter by (.+)$/i' => 'filterBy',
        '/^show only (.+)$/i' => 'filterBy',
        
        // Workflow commands
        '/^start (.+) workflow$/i' => 'startWorkflow',
        '/^switch to (.+) mode$/i' => 'switchMode',
        
        // Quick actions
        '/^urgent$/i' => 'showUrgent',
        '/^today$/i' => 'showToday',
        '/^overdue$/i' => 'showOverdue',
    ];

    /**
     * Process a command and return results
     */
    public static function processCommand(string $command, array $context = []): array
    {
        $command = trim($command);
        
        // Check for exact command matches first
        if ($result = static::checkExactCommands($command, $context)) {
            return $result;
        }
        
        // Check pattern-based commands
        foreach (static::$commandPatterns as $pattern => $handler) {
            if (preg_match($pattern, $command, $matches)) {
                $method = 'handle' . ucfirst($handler);
                if (method_exists(static::class, $method)) {
                    return static::$method($matches, $context);
                }
            }
        }
        
        // Fall back to natural language search
        return static::naturalLanguageSearch($command, $context);
    }

    /**
     * Get command suggestions based on partial input
     */
    public static function getSuggestions(?string $partial, array $context = []): array
    {
        $suggestions = [];
        $partial = $partial ? strtolower(trim($partial)) : '';
        
        if (empty($partial)) {
            // Return default suggestions
            return static::getDefaultSuggestions($context);
        }
        
        // Command suggestions
        $commands = [
            // Create commands
            'create ticket' => ['icon' => '🎫', 'description' => 'Create a new support ticket'],
            'create invoice' => ['icon' => '💰', 'description' => 'Create a new invoice'],
            'create quote' => ['icon' => '📝', 'description' => 'Create a new quote'],
            'create client' => ['icon' => '👥', 'description' => 'Add a new client'],
            'create project' => ['icon' => '📊', 'description' => 'Create a new project'],
            'create asset' => ['icon' => '🖥️', 'description' => 'Add a new asset'],
            'create contract' => ['icon' => '📄', 'description' => 'Create a new contract'],
            'create expense' => ['icon' => '💸', 'description' => 'Add a new expense'],
            'create payment' => ['icon' => '💳', 'description' => 'Record a payment'],
            'create user' => ['icon' => '👤', 'description' => 'Add a new user'],
            'create article' => ['icon' => '📚', 'description' => 'Create knowledge base article'],
            
            // Show commands
            'show urgent' => ['icon' => '🔥', 'description' => 'View urgent items'],
            'show today' => ['icon' => '📅', 'description' => "Today's schedule"],
            'show tickets' => ['icon' => '🎫', 'description' => 'View all tickets'],
            'show invoices' => ['icon' => '💰', 'description' => 'View all invoices'],
            'show quotes' => ['icon' => '📝', 'description' => 'View all quotes'],
            'show projects' => ['icon' => '📊', 'description' => 'View all projects'],
            'show assets' => ['icon' => '🖥️', 'description' => 'View all assets'],
            'show contracts' => ['icon' => '📄', 'description' => 'View all contracts'],
            'show expenses' => ['icon' => '💸', 'description' => 'View all expenses'],
            'show payments' => ['icon' => '💳', 'description' => 'View all payments'],
            'show overdue invoices' => ['icon' => '⚠️', 'description' => 'View overdue invoices'],
            'show pending quotes' => ['icon' => '⏳', 'description' => 'View pending quotes'],
            'show active projects' => ['icon' => '🚀', 'description' => 'View active projects'],
            
            // Navigation commands
            'go to dashboard' => ['icon' => '🏠', 'description' => 'Navigate to dashboard'],
            'go to clients' => ['icon' => '👥', 'description' => 'Navigate to clients'],
            'go to tickets' => ['icon' => '🎫', 'description' => 'Navigate to tickets'],
            'go to billing' => ['icon' => '💰', 'description' => 'Navigate to billing'],
            'go to assets' => ['icon' => '🖥️', 'description' => 'Navigate to assets'],
            'go to projects' => ['icon' => '📊', 'description' => 'Navigate to projects'],
            'go to reports' => ['icon' => '📈', 'description' => 'Navigate to reports'],
            'go to settings' => ['icon' => '⚙️', 'description' => 'Navigate to settings'],
            'go to knowledge base' => ['icon' => '📚', 'description' => 'Navigate to knowledge base'],
            
            // Workflow commands
            'start morning workflow' => ['icon' => '☀️', 'description' => 'Begin morning routine'],
            'start billing workflow' => ['icon' => '💰', 'description' => 'Begin billing tasks'],
            'start maintenance window' => ['icon' => '🔧', 'description' => 'Start maintenance mode'],
            
            // Search commands
            'find' => ['icon' => '🔍', 'description' => 'Search for anything'],
            'search tickets' => ['icon' => '🔍', 'description' => 'Search tickets'],
            'search clients' => ['icon' => '🔍', 'description' => 'Search clients'],
            'search invoices' => ['icon' => '🔍', 'description' => 'Search invoices'],
        ];
        
        // Filter commands that match the partial
        foreach ($commands as $cmd => $info) {
            if (str_starts_with($cmd, $partial) || str_contains($cmd, $partial)) {
                $suggestions[] = [
                    'command' => $cmd,
                    'icon' => $info['icon'],
                    'description' => $info['description'],
                    'type' => 'command',
                ];
            }
        }
        
        // Add recent items if applicable
        if (str_starts_with('recent', $partial) || str_starts_with($partial, 'recent')) {
            $suggestions = array_merge($suggestions, static::getRecentItems($context));
        }
        
        // Add client suggestions if searching for clients
        if (str_contains($partial, 'client')) {
            $suggestions = array_merge($suggestions, static::getClientSuggestions($partial, $context));
        }
        
        return array_slice($suggestions, 0, 10); // Limit to 10 suggestions
    }

    /**
     * Handle navigation commands
     */
    protected static function handleNavigateTo($matches, $context): array
    {
        $destination = strtolower(trim($matches[1]));
        
        $routes = [
            'dashboard' => ['route' => 'dashboard', 'name' => 'Dashboard'],
            'clients' => ['route' => 'clients.index', 'name' => 'Clients'],
            'tickets' => ['route' => 'tickets.index', 'name' => 'Tickets'],
            'invoices' => ['route' => 'financial.invoices.index', 'name' => 'Invoices'],
            'quotes' => ['route' => 'financial.quotes.index', 'name' => 'Quotes'],
            'billing' => ['route' => 'financial.invoices.index', 'name' => 'Billing'],
            'assets' => ['route' => 'assets.index', 'name' => 'Assets'],
            'projects' => ['route' => 'projects.index', 'name' => 'Projects'],
            'contracts' => ['route' => 'financial.contracts.index', 'name' => 'Contracts'],
            'expenses' => ['route' => 'financial.expenses.index', 'name' => 'Expenses'],
            'payments' => ['route' => 'financial.payments.index', 'name' => 'Payments'],
            'reports' => ['route' => 'reports.index', 'name' => 'Reports'],
            'settings' => ['route' => 'settings.index', 'name' => 'Settings'],
            'knowledge' => ['route' => 'knowledge.index', 'name' => 'Knowledge Base'],
            'integrations' => ['route' => 'integrations.index', 'name' => 'Integrations'],
            'users' => ['route' => 'users.index', 'name' => 'Users'],
        ];
        
        foreach ($routes as $key => $route) {
            if (str_contains($destination, $key)) {
                return [
                    'action' => 'navigate',
                    'url' => route($route['route']),
                    'message' => "Navigating to {$route['name']}",
                ];
            }
        }
        
        return [
            'action' => 'error',
            'message' => "Cannot find destination: {$destination}",
        ];
    }

    /**
     * Handle create commands
     */
    protected static function handleCreateItem($matches, $context): array
    {
        $item = strtolower(trim($matches[1]));
        
        $createRoutes = [
            'ticket' => ['route' => 'tickets.create', 'name' => 'New Ticket'],
            'client' => ['route' => 'clients.create', 'name' => 'New Client'],
            'invoice' => ['route' => 'financial.invoices.create', 'name' => 'New Invoice'],
            'quote' => ['route' => 'financial.quotes.create', 'name' => 'New Quote'],
            'project' => ['route' => 'projects.create', 'name' => 'New Project'],
            'asset' => ['route' => 'assets.create', 'name' => 'New Asset'],
            'contract' => ['route' => 'financial.contracts.create', 'name' => 'New Contract'],
            'expense' => ['route' => 'financial.expenses.create', 'name' => 'New Expense'],
            'payment' => ['route' => 'financial.payments.create', 'name' => 'New Payment'],
            'user' => ['route' => 'users.create', 'name' => 'New User'],
            'article' => ['route' => 'knowledge.articles.create', 'name' => 'New Article'],
            'contact' => ['route' => 'clients.contacts.create', 'name' => 'New Contact'],
            'documentation' => ['route' => 'clients.it-documentation.create', 'name' => 'New IT Documentation'],
        ];
        
        foreach ($createRoutes as $key => $route) {
            if (str_contains($item, $key)) {
                $params = [];
                
                // Add client context if available
                if (isset($context['client_id']) && in_array($key, ['ticket', 'invoice', 'project', 'quote'])) {
                    $params['client_id'] = $context['client_id'];
                }
                
                return [
                    'action' => 'navigate',
                    'url' => route($route['route'], $params),
                    'message' => "Creating {$route['name']}",
                ];
            }
        }
        
        return [
            'action' => 'error',
            'message' => "Cannot create: {$item}",
        ];
    }

    /**
     * Handle show commands
     */
    protected static function handleShowItems($matches, $context): array
    {
        $item = strtolower(trim($matches[1]));
        
        // Handle special show commands
        if (str_contains($item, 'urgent')) {
            return static::handleShowUrgent($matches, $context);
        }
        
        if (str_contains($item, 'overdue')) {
            return static::showOverdueItems($context);
        }
        
        if (str_contains($item, 'today')) {
            return static::handleShowToday($matches, $context);
        }
        
        // Handle entity-based show commands
        $showRoutes = [
            'tickets' => ['route' => 'tickets.index', 'name' => 'Tickets'],
            'clients' => ['route' => 'clients.index', 'name' => 'Clients'],
            'invoices' => ['route' => 'financial.invoices.index', 'name' => 'Invoices'],
            'quotes' => ['route' => 'financial.quotes.index', 'name' => 'Quotes'],
            'assets' => ['route' => 'assets.index', 'name' => 'Assets'],
            'projects' => ['route' => 'projects.index', 'name' => 'Projects'],
            'contracts' => ['route' => 'financial.contracts.index', 'name' => 'Contracts'],
            'expenses' => ['route' => 'financial.expenses.index', 'name' => 'Expenses'],
            'payments' => ['route' => 'financial.payments.index', 'name' => 'Payments'],
            'users' => ['route' => 'users.index', 'name' => 'Users'],
            'articles' => ['route' => 'knowledge.articles.index', 'name' => 'Knowledge Base Articles'],
        ];
        
        foreach ($showRoutes as $key => $route) {
            if (str_contains($item, $key)) {
                $params = [];
                
                // Add filters based on the command
                if (str_contains($item, 'open')) {
                    $params['status'] = 'open';
                }
                if (str_contains($item, 'closed')) {
                    $params['status'] = 'closed';
                }
                if (str_contains($item, 'my')) {
                    $params['assignee'] = auth()->id();
                }
                
                return [
                    'action' => 'navigate',
                    'url' => route($route['route'], $params),
                    'message' => "Showing {$route['name']}",
                ];
            }
        }
        
        return [
            'action' => 'search',
            'query' => $item,
            'message' => "Searching for: {$item}",
        ];
    }

    /**
     * Handle urgent items
     */
    protected static function handleShowUrgent($matches, $context): array
    {
        return [
            'action' => 'navigate',
            'url' => route('dashboard', ['view' => 'urgent']),
            'message' => 'Showing urgent items',
            'workflow' => 'urgent',
        ];
    }

    /**
     * Handle today's items
     */
    protected static function handleShowToday($matches, $context): array
    {
        return [
            'action' => 'navigate',
            'url' => route('dashboard', ['view' => 'today']),
            'message' => "Showing today's work",
            'workflow' => 'today',
        ];
    }

    /**
     * Show overdue items
     */
    protected static function showOverdueItems($context): array
    {
        return [
            'action' => 'navigate',
            'url' => route('financial.invoices.index', ['status' => 'overdue']),
            'message' => 'Showing overdue items',
        ];
    }

    /**
     * Handle workflow commands
     */
    protected static function handleStartWorkflow($matches, $context): array
    {
        $workflow = strtolower(trim($matches[1]));
        
        $workflows = [
            'morning' => ['name' => 'Morning Routine', 'workflow' => 'morning_routine'],
            'billing' => ['name' => 'Billing Day', 'workflow' => 'billing_day'],
            'maintenance' => ['name' => 'Maintenance Window', 'workflow' => 'maintenance_window'],
            'urgent' => ['name' => 'Urgent Response', 'workflow' => 'urgent_response'],
        ];
        
        foreach ($workflows as $key => $wf) {
            if (str_contains($workflow, $key)) {
                NavigationService::setWorkflowContext($wf['workflow']);
                
                return [
                    'action' => 'workflow',
                    'workflow' => $wf['workflow'],
                    'url' => route('dashboard', ['workflow' => $wf['workflow']]),
                    'message' => "Starting {$wf['name']} workflow",
                ];
            }
        }
        
        return [
            'action' => 'error',
            'message' => "Unknown workflow: {$workflow}",
        ];
    }

    /**
     * Natural language search fallback
     */
    protected static function naturalLanguageSearch($command, $context): array
    {
        // Extract potential entities from the command
        $entities = static::extractEntities($command);
        
        // Build search query
        $searchParams = [
            'q' => $command,
            'entities' => $entities,
        ];
        
        if (isset($context['client_id'])) {
            $searchParams['client_id'] = $context['client_id'];
        }
        
        return [
            'action' => 'search',
            'query' => $command,
            'params' => $searchParams,
            'message' => "Searching for: {$command}",
        ];
    }

    /**
     * Extract entities from natural language
     */
    protected static function extractEntities($text): array
    {
        $entities = [];
        
        // Check for entity keywords
        $entityKeywords = [
            'ticket' => ['ticket', 'issue', 'problem', 'support'],
            'client' => ['client', 'customer', 'company'],
            'invoice' => ['invoice', 'bill'],
            'quote' => ['quote', 'quotation', 'estimate', 'proposal'],
            'asset' => ['asset', 'device', 'equipment', 'hardware'],
            'project' => ['project', 'task', 'milestone'],
            'contract' => ['contract', 'agreement', 'sla'],
            'expense' => ['expense', 'cost', 'purchase'],
            'payment' => ['payment', 'transaction', 'receipt'],
            'user' => ['user', 'staff', 'employee', 'technician'],
            'article' => ['article', 'documentation', 'kb', 'knowledge'],
        ];
        
        foreach ($entityKeywords as $entity => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($text), $keyword)) {
                    $entities[] = $entity;
                    break;
                }
            }
        }
        
        return array_unique($entities);
    }

    /**
     * Get default suggestions when no input
     */
    protected static function getDefaultSuggestions($context): array
    {
        $suggestions = [];
        
        // Quick actions - most commonly used
        $suggestions[] = [
            'command' => 'show urgent',
            'icon' => '🔥',
            'description' => 'View urgent items',
            'type' => 'quick',
            'shortcut' => 'Alt+U',
        ];
        
        $suggestions[] = [
            'command' => 'show today',
            'icon' => '📅',
            'description' => "Today's schedule",
            'type' => 'quick',
            'shortcut' => 'Alt+T',
        ];
        
        $suggestions[] = [
            'command' => 'create ticket',
            'icon' => '🎫',
            'description' => 'Create new ticket',
            'type' => 'quick',
        ];
        
        $suggestions[] = [
            'command' => 'create quote',
            'icon' => '📝',
            'description' => 'Create new quote',
            'type' => 'quick',
        ];
        
        $suggestions[] = [
            'command' => 'create invoice',
            'icon' => '💰',
            'description' => 'Create new invoice',
            'type' => 'quick',
        ];
        
        // Recent items
        $recentItems = static::getRecentItems($context);
        $suggestions = array_merge($suggestions, array_slice($recentItems, 0, 3));
        
        // Contextual suggestions based on selected client
        if (isset($context['client_id'])) {
            $suggestions[] = [
                'command' => 'show client tickets',
                'icon' => '🎫',
                'description' => 'View this client\'s tickets',
                'type' => 'context',
            ];
            
            $suggestions[] = [
                'command' => 'create invoice for client',
                'icon' => '💰',
                'description' => 'Create invoice for current client',
                'type' => 'context',
            ];
            
            $suggestions[] = [
                'command' => 'create quote for client',
                'icon' => '📝',
                'description' => 'Create quote for current client',
                'type' => 'context',
            ];
        }
        
        return array_slice($suggestions, 0, 10);
    }

    /**
     * Get recent items for suggestions
     */
    protected static function getRecentItems($context): array
    {
        $items = [];
        
        // This would normally query recent activity from database
        // For now, return mock data
        $items[] = [
            'command' => 'open ticket #1234',
            'icon' => '🎫',
            'description' => 'Server Down - Acme Corp',
            'type' => 'recent',
        ];
        
        $items[] = [
            'command' => 'open invoice #5678',
            'icon' => '💰',
            'description' => 'Monthly Service - TechCo',
            'type' => 'recent',
        ];
        
        return $items;
    }

    /**
     * Get client suggestions
     */
    protected static function getClientSuggestions($partial, $context): array
    {
        $suggestions = [];
        
        try {
            $clients = \App\Models\Client::where('company_id', auth()->user()->company_id)
                ->where(function($query) use ($partial) {
                    $query->where('name', 'like', "%{$partial}%")
                          ->orWhere('email', 'like', "%{$partial}%");
                })
                ->limit(5)
                ->get();
            
            foreach ($clients as $client) {
                $suggestions[] = [
                    'command' => "go to client {$client->name}",
                    'icon' => '👥',
                    'description' => $client->email,
                    'type' => 'client',
                    'data' => ['client_id' => $client->id],
                ];
            }
        } catch (\Exception $e) {
            // Silent fail
        }
        
        return $suggestions;
    }

    /**
     * Check for exact command matches
     */
    protected static function checkExactCommands($command, $context): ?array
    {
        $exactCommands = [
            'help' => [
                'action' => 'help',
                'message' => 'Available commands: create [item], show [items], go to [place], find [query]',
            ],
            'clear' => [
                'action' => 'clear',
                'message' => 'Cleared',
            ],
            'logout' => [
                'action' => 'logout',
                'url' => route('logout'),
                'message' => 'Logging out...',
            ],
        ];
        
        $lowerCommand = strtolower($command);
        
        if (isset($exactCommands[$lowerCommand])) {
            return $exactCommands[$lowerCommand];
        }
        
        return null;
    }
}