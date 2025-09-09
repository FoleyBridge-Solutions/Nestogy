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
        
        // Action commands (create/add)
        '/^create (.+)$/i' => 'createItem',
        '/^new (.+)$/i' => 'createItem',
        '/^add (.+)$/i' => 'createItem',
        '/^make (.+)$/i' => 'createItem',
        '/^build (.+)$/i' => 'createItem',
        '/^generate (.+)$/i' => 'createItem',
        
        // MSP Action commands
        '/^assign (.+)$/i' => 'mspAction',
        '/^escalate (.+)$/i' => 'mspAction',
        '/^resolve (.+)$/i' => 'mspAction', 
        '/^close (.+)$/i' => 'mspAction',
        '/^schedule (.+)$/i' => 'mspAction',
        
        // System commands
        '/^monitor (.+)$/i' => 'systemAction',
        '/^restart (.+)$/i' => 'systemAction',
        '/^backup (.+)$/i' => 'systemAction',
        '/^restore (.+)$/i' => 'systemAction',
        '/^patch (.+)$/i' => 'systemAction',
        
        // Communication commands
        '/^send (.+)$/i' => 'commAction',
        '/^notify (.+)$/i' => 'commAction',
        '/^alert (.+)$/i' => 'commAction',
        '/^email (.+)$/i' => 'commAction',
        '/^message (.+)$/i' => 'commAction',
        
        // Data commands
        '/^export (.+)$/i' => 'dataAction',
        '/^import (.+)$/i' => 'dataAction',
        '/^sync (.+)$/i' => 'dataAction',
        '/^archive (.+)$/i' => 'dataAction',
        
        // State commands  
        '/^enable (.+)$/i' => 'stateAction',
        '/^disable (.+)$/i' => 'stateAction',
        '/^activate (.+)$/i' => 'stateAction',
        '/^deactivate (.+)$/i' => 'stateAction',
        '/^start (.+)$/i' => 'stateAction',
        '/^stop (.+)$/i' => 'stateAction',
        
        // Configuration commands
        '/^configure (.+)$/i' => 'configAction',
        '/^deploy (.+)$/i' => 'configAction',
        '/^update (.+)$/i' => 'configAction',
        '/^install (.+)$/i' => 'configAction',
        '/^upgrade (.+)$/i' => 'configAction',
        
        // Phase 3: Analysis & Reporting commands
        '/^analyze (.+)$/i' => 'analysisAction',
        '/^report (.+)$/i' => 'analysisAction',
        '/^audit (.+)$/i' => 'analysisAction',
        '/^investigate (.+)$/i' => 'analysisAction',
        '/^troubleshoot (.+)$/i' => 'analysisAction',
        
        // Workflow & Automation commands
        '/^trigger (.+)$/i' => 'workflowAction',
        '/^execute (.+)$/i' => 'workflowAction',
        '/^automate (.+)$/i' => 'workflowAction',
        '/^validate (.+)$/i' => 'workflowAction',
        '/^test (.+)$/i' => 'workflowAction',
        
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
        
        // Base command templates (NLP will expand these automatically)
        $baseCommands = [
            // Create commands
            'ticket' => ['icon' => '🎫', 'description' => 'Create a new support ticket', 'type' => 'create', 'route' => 'tickets.create'],
            'invoice' => ['icon' => '💰', 'description' => 'Create a new invoice', 'type' => 'create', 'route' => 'financial.invoices.create'],
            'quote' => ['icon' => '📝', 'description' => 'Create a new quote', 'type' => 'create', 'route' => 'financial.quotes.create'],
            'client' => ['icon' => '👥', 'description' => 'Add a new client', 'type' => 'create', 'route' => 'clients.create'],
            'project' => ['icon' => '📊', 'description' => 'Create a new project', 'type' => 'create', 'route' => 'projects.create'],
            'asset' => ['icon' => '🖥️', 'description' => 'Add a new asset', 'type' => 'create', 'route' => 'assets.create'],
            'contract' => ['icon' => '📄', 'description' => 'Create a new contract', 'type' => 'create', 'route' => 'financial.contracts.create'],
            'expense' => ['icon' => '💸', 'description' => 'Add a new expense', 'type' => 'create', 'route' => 'financial.expenses.create'],
            'payment' => ['icon' => '💳', 'description' => 'Record a payment', 'type' => 'create', 'route' => 'financial.payments.create'],
            'user' => ['icon' => '👤', 'description' => 'Add a new user', 'type' => 'create', 'route' => 'users.create'],
            'article' => ['icon' => '📚', 'description' => 'Create knowledge base article', 'type' => 'create', 'route' => 'knowledge.articles.create'],
            'product' => ['icon' => '📦', 'description' => 'Add a new product', 'type' => 'create', 'route' => 'products.create'],
            'service' => ['icon' => '🔧', 'description' => 'Add a new service', 'type' => 'create', 'route' => 'services.create'],
            'bundle' => ['icon' => '🎁', 'description' => 'Create product bundle', 'type' => 'create', 'route' => 'bundles.create'],
            'pricing rule' => ['icon' => '💲', 'description' => 'Create pricing rule', 'type' => 'create', 'route' => 'pricing-rules.create'],
        ];
        
        // Generate intelligent suggestions using NLP (with deduplication)
        $commands = static::generateSmartSuggestions($baseCommands, $partial);
        
        // Add show/navigation commands
        $staticCommands = [
            // Show commands
            'show urgent' => ['icon' => '🔥', 'description' => 'View urgent items', 'route' => 'dashboard.urgent'],
            'show today' => ['icon' => '📅', 'description' => "Today's schedule", 'route' => 'dashboard.today'],
            'show tickets' => ['icon' => '🎫', 'description' => 'View all tickets', 'route' => 'tickets.index'],
            'show invoices' => ['icon' => '💰', 'description' => 'View all invoices', 'route' => 'financial.invoices.index'],
            'show quotes' => ['icon' => '📝', 'description' => 'View all quotes', 'route' => 'financial.quotes.index'],
            'show projects' => ['icon' => '📊', 'description' => 'View all projects', 'route' => 'projects.index'],
            'show assets' => ['icon' => '🖥️', 'description' => 'View all assets', 'route' => 'assets.index'],
            'show contracts' => ['icon' => '📄', 'description' => 'View all contracts', 'route' => 'financial.contracts.index'],
            'show expenses' => ['icon' => '💸', 'description' => 'View all expenses', 'route' => 'financial.expenses.index'],
            'show payments' => ['icon' => '💳', 'description' => 'View all payments', 'route' => 'financial.payments.index'],
            'show overdue invoices' => ['icon' => '⚠️', 'description' => 'View overdue invoices', 'route' => 'financial.invoices.overdue'],
            'show pending quotes' => ['icon' => '⏳', 'description' => 'View pending quotes', 'route' => 'financial.quotes.pending'],
            'show active projects' => ['icon' => '🚀', 'description' => 'View active projects', 'route' => 'projects.active'],
            'show products' => ['icon' => '📦', 'description' => 'View all products', 'route' => 'products.index'],
            'show services' => ['icon' => '🔧', 'description' => 'View all services', 'route' => 'services.index'],
            'show bundles' => ['icon' => '🎁', 'description' => 'View product bundles', 'route' => 'bundles.index'],
            'show pricing rules' => ['icon' => '💲', 'description' => 'View pricing rules', 'route' => 'pricing-rules.index'],
            
            // Navigation commands
            'go to dashboard' => ['icon' => '🏠', 'description' => 'Navigate to dashboard', 'route' => 'dashboard'],
            'go to clients' => ['icon' => '👥', 'description' => 'Navigate to clients', 'route' => 'clients.index'],
            'go to tickets' => ['icon' => '🎫', 'description' => 'Navigate to tickets', 'route' => 'tickets.index'],
            'go to billing' => ['icon' => '💰', 'description' => 'Navigate to billing', 'route' => 'financial.invoices.index'],
            'go to assets' => ['icon' => '🖥️', 'description' => 'Navigate to assets', 'route' => 'assets.index'],
            'go to projects' => ['icon' => '📊', 'description' => 'Navigate to projects', 'route' => 'projects.index'],
            'go to reports' => ['icon' => '📈', 'description' => 'Navigate to reports', 'route' => 'reports.index'],
            'go to settings' => ['icon' => '⚙️', 'description' => 'Navigate to settings', 'route' => 'settings.index'],
            'go to knowledge base' => ['icon' => '📚', 'description' => 'Navigate to knowledge base', 'route' => 'knowledge.index'],
            'go to products' => ['icon' => '📦', 'description' => 'Navigate to products', 'route' => 'products.index'],
            'go to services' => ['icon' => '🔧', 'description' => 'Navigate to services', 'route' => 'services.index'],
            'go to bundles' => ['icon' => '🎁', 'description' => 'Navigate to bundles', 'route' => 'bundles.index'],
            'go to pricing rules' => ['icon' => '💲', 'description' => 'Navigate to pricing rules', 'route' => 'pricing-rules.index'],
            
            // Workflow commands
            'start morning workflow' => ['icon' => '☀️', 'description' => 'Begin morning routine', 'route' => 'workflow.morning'],
            'start billing workflow' => ['icon' => '💰', 'description' => 'Begin billing tasks', 'route' => 'workflow.billing'],
            'start maintenance window' => ['icon' => '🔧', 'description' => 'Start maintenance mode', 'route' => 'workflow.maintenance'],
            
            // Search commands
            'find' => ['icon' => '🔍', 'description' => 'Search for anything', 'route' => 'search'],
            'search tickets' => ['icon' => '🔍', 'description' => 'Search tickets', 'route' => 'search.tickets'],
            'search clients' => ['icon' => '🔍', 'description' => 'Search clients', 'route' => 'search.clients'],
            'search invoices' => ['icon' => '🔍', 'description' => 'Search invoices', 'route' => 'search.invoices'],
        ];
        
        // Merge commands and deduplicate by route
        $commands = array_merge($commands, $staticCommands);
        
        // Filter and deduplicate commands that match the partial
        $seenRoutes = [];
        $filteredCommands = [];
        
        foreach ($commands as $cmd => $info) {
            if (str_starts_with($cmd, $partial) || str_contains($cmd, $partial)) {
                $route = $info['route'] ?? null;
                
                // If we have a route, use it for deduplication
                if ($route && !isset($seenRoutes[$route])) {
                    $filteredCommands[] = [
                        'command' => $cmd,
                        'icon' => $info['icon'],
                        'description' => $info['description'],
                        'type' => 'command',
                    ];
                    $seenRoutes[$route] = true;
                } elseif (!$route) {
                    // Commands without routes (like special actions) are always included
                    $filteredCommands[] = [
                        'command' => $cmd,
                        'icon' => $info['icon'],
                        'description' => $info['description'],
                        'type' => 'command',
                    ];
                }
            }
        }
        
        // Sort filtered commands by relevance (exact match first, then starts with, then contains)
        usort($filteredCommands, function($a, $b) use ($partial) {
            $cmdA = strtolower($a['command']);
            $cmdB = strtolower($b['command']);
            
            // Exact matches first
            if ($cmdA === $partial) return -1;
            if ($cmdB === $partial) return 1;
            
            // Then starts with
            $aStarts = str_starts_with($cmdA, $partial);
            $bStarts = str_starts_with($cmdB, $partial);
            if ($aStarts && !$bStarts) return -1;
            if (!$aStarts && $bStarts) return 1;
            
            // Then by length (shorter commands first)
            return strlen($cmdA) - strlen($cmdB);
        });
        
        $suggestions = $filteredCommands;
        
        // Add recent items if applicable
        if (str_starts_with('recent', $partial) || str_starts_with($partial, 'recent')) {
            $suggestions = array_merge($suggestions, static::getRecentItems($context));
        }
        
        // Add client suggestions if searching for clients
        if (str_contains($partial, 'client')) {
            $suggestions = array_merge($suggestions, static::getClientSuggestions($partial, $context));
        }
        
        return array_slice($suggestions, 0, 8); // Limit to 8 suggestions
    }

    /**
     * Generate smart command suggestions using NLP patterns
     */
    protected static function generateSmartSuggestions(array $baseCommands, string $partial): array
    {
        $suggestions = [];
        $partial = strtolower(trim($partial));
        
        // Define preferred verbs for each action type (first one is the preferred)
        $preferredVerbs = [
            'create' => 'create',
            'show' => 'show',
            'navigate' => 'go to',
        ];
        
        // MSP-specific verb categories  
        $createVerbs = ['create', 'new', 'add', 'make', 'build', 'generate'];
        $showVerbs = ['show', 'list', 'view', 'display', 'see'];
        $navVerbs = ['go', 'open', 'visit', 'navigate'];
        
        // Generate suggestions based on entity and partial match
        foreach ($baseCommands as $entity => $config) {
            // Check if the partial matches the entity name
            $entityMatches = empty($partial) || 
                           str_starts_with($entity, $partial) || 
                           str_contains($entity, $partial);
            
            // Check if any create verb matches the partial
            $verbMatches = false;
            $matchedVerb = null;
            
            foreach ($createVerbs as $verb) {
                $command = "{$verb} {$entity}";
                if (str_starts_with($command, $partial) || str_contains($command, $partial)) {
                    $verbMatches = true;
                    $matchedVerb = $verb;
                    break;
                }
            }
            
            // Add suggestion if there's a match
            if ($entityMatches || $verbMatches) {
                // Use the matched verb or the preferred verb
                $verb = $matchedVerb ?: $preferredVerbs['create'];
                $command = "{$verb} {$entity}";
                
                // Only add if this command matches the partial (or partial is empty)
                if (empty($partial) || str_starts_with($command, $partial) || str_contains($command, $partial)) {
                    $suggestions[$command] = [
                        'icon' => $config['icon'],
                        'description' => $config['description'],
                        'route' => $config['route'] ?? null,
                    ];
                }
            }
            
            // Special handling for typing just the verb
            if (!empty($partial)) {
                foreach ($createVerbs as $verb) {
                    if (str_starts_with($verb, $partial) && strlen($partial) <= strlen($verb)) {
                        $command = "{$verb} {$entity}";
                        $suggestions[$command] = [
                            'icon' => $config['icon'],
                            'description' => $config['description'],
                            'route' => $config['route'] ?? null,
                        ];
                    }
                }
            }
        }
        
        return $suggestions;
    }

    /**
     * Generate contextual descriptions for verb-entity combinations
     */
    protected static function generateVerbDescription(string $verb, string $entity, string $defaultDescription): string
    {
        // MSP-specific verb descriptions
        $verbDescriptions = [
            // Action verbs
            'assign' => [
                'ticket' => 'Assign ticket to technician',
                'project' => 'Assign project to team member',
                'default' => "Assign {$entity}",
            ],
            'escalate' => [
                'ticket' => 'Escalate ticket to higher tier',
                'default' => "Escalate {$entity}",
            ],
            'resolve' => [
                'ticket' => 'Mark ticket as resolved',
                'default' => "Resolve {$entity}",
            ],
            'close' => [
                'ticket' => 'Close completed ticket',
                'project' => 'Close completed project',
                'default' => "Close {$entity}",
            ],
            'schedule' => [
                'ticket' => 'Schedule ticket maintenance',
                'project' => 'Schedule project tasks',
                'default' => "Schedule {$entity}",
            ],
            
            // System verbs
            'monitor' => [
                'asset' => 'Monitor asset performance',
                'default' => "Monitor {$entity}",
            ],
            'restart' => [
                'asset' => 'Restart system/service',
                'default' => "Restart {$entity}",
            ],
            'backup' => [
                'asset' => 'Create system backup',
                'default' => "Backup {$entity}",
            ],
            'restore' => [
                'asset' => 'Restore from backup',
                'default' => "Restore {$entity}",
            ],
            'patch' => [
                'asset' => 'Apply system patches',
                'default' => "Patch {$entity}",
            ],
            
            // Communication verbs
            'send' => [
                'invoice' => 'Send invoice to client',
                'ticket' => 'Send ticket update',
                'default' => "Send {$entity}",
            ],
            'notify' => [
                'client' => 'Send notification to client',
                'user' => 'Send notification to user',
                'default' => "Notify about {$entity}",
            ],
            'alert' => [
                'ticket' => 'Send alert about ticket',
                'default' => "Send alert about {$entity}",
            ],
            'email' => [
                'invoice' => 'Email invoice to client',
                'client' => 'Send email to client',
                'default' => "Email {$entity}",
            ],
            'message' => [
                'client' => 'Send message to client',
                'user' => 'Send message to user',
                'default' => "Message about {$entity}",
            ],
            
            // Data verbs
            'export' => [
                'invoice' => 'Export invoice data',
                'project' => 'Export project data',
                'default' => "Export {$entity} data",
            ],
            'import' => [
                'invoice' => 'Import invoice data',
                'default' => "Import {$entity} data",
            ],
            'sync' => [
                'invoice' => 'Sync invoice data',
                'default' => "Sync {$entity} data",
            ],
            'archive' => [
                'project' => 'Archive completed project',
                'default' => "Archive {$entity}",
            ],
            
            // State verbs
            'enable' => [
                'user' => 'Enable user account',
                'client' => 'Enable client account',
                'asset' => 'Enable asset/service',
                'default' => "Enable {$entity}",
            ],
            'disable' => [
                'user' => 'Disable user account',
                'client' => 'Disable client account',
                'asset' => 'Disable asset/service',
                'default' => "Disable {$entity}",
            ],
            'activate' => [
                'client' => 'Activate client account',
                'user' => 'Activate user account',
                'default' => "Activate {$entity}",
            ],
            'deactivate' => [
                'client' => 'Deactivate client account',
                'user' => 'Deactivate user account',
                'default' => "Deactivate {$entity}",
            ],
            
            // Configuration verbs
            'configure' => [
                'client' => 'Configure client settings',
                'asset' => 'Configure asset parameters',
                'user' => 'Configure user settings',
                'default' => "Configure {$entity}",
            ],
            'deploy' => [
                'asset' => 'Deploy to asset/system',
                'default' => "Deploy {$entity}",
            ],
            'update' => [
                'asset' => 'Update system/software',
                'default' => "Update {$entity}",
            ],
            'install' => [
                'asset' => 'Install software/component',
                'default' => "Install {$entity}",
            ],
            'upgrade' => [
                'asset' => 'Upgrade system/software',
                'default' => "Upgrade {$entity}",
            ],
            
            // Additional state verbs
            'start' => [
                'asset' => 'Start service/system',
                'default' => "Start {$entity}",
            ],
            'stop' => [
                'asset' => 'Stop service/system',
                'default' => "Stop {$entity}",
            ],
            
            // Phase 3: Analysis verbs
            'analyze' => [
                'client' => 'Analyze client data and patterns',
                'ticket' => 'Analyze ticket trends and issues',
                'invoice' => 'Analyze financial performance',
                'asset' => 'Analyze asset performance and health',
                'default' => "Analyze {$entity} data",
            ],
            'report' => [
                'client' => 'Generate client reports',
                'ticket' => 'Generate ticket reports',
                'invoice' => 'Generate financial reports',
                'asset' => 'Generate asset reports',
                'default' => "Generate {$entity} report",
            ],
            'audit' => [
                'client' => 'Audit client compliance and security',
                'invoice' => 'Audit financial records',
                'asset' => 'Audit asset configurations',
                'default' => "Audit {$entity}",
            ],
            'investigate' => [
                'ticket' => 'Investigate ticket issues and patterns',
                'asset' => 'Investigate asset problems',
                'default' => "Investigate {$entity} issues",
            ],
            'troubleshoot' => [
                'ticket' => 'Troubleshoot technical issues',
                'asset' => 'Troubleshoot system problems',
                'default' => "Troubleshoot {$entity}",
            ],
            
            // Workflow automation verbs
            'trigger' => [
                'asset' => 'Trigger automated workflows',
                'default' => "Trigger {$entity} workflows",
            ],
            'execute' => [
                'asset' => 'Execute scripts and commands',
                'default' => "Execute {$entity} operations",
            ],
            'automate' => [
                'ticket' => 'Automate ticket workflows',
                'asset' => 'Automate system tasks',
                'default' => "Automate {$entity} processes",
            ],
            'validate' => [
                'ticket' => 'Validate ticket information',
                'invoice' => 'Validate billing data',
                'asset' => 'Validate system configurations',
                'default' => "Validate {$entity} data",
            ],
            'test' => [
                'ticket' => 'Test ticket resolution procedures',
                'asset' => 'Test system functionality',
                'default' => "Test {$entity} operations",
            ],
        ];
        
        // Get specific description or fall back to default
        if (isset($verbDescriptions[$verb])) {
            return $verbDescriptions[$verb][$entity] ?? $verbDescriptions[$verb]['default'];
        }
        
        // Fall back to original description for create/show verbs
        return $defaultDescription;
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
            'products' => ['route' => 'products.index', 'name' => 'Products'],
            'services' => ['route' => 'services.index', 'name' => 'Services'],
            'bundles' => ['route' => 'bundles.index', 'name' => 'Product Bundles'],
            'pricing rules' => ['route' => 'pricing-rules.index', 'name' => 'Pricing Rules'],
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
        
        // Order matters! More specific matches should come first
        // Check for compound phrases before single words
        $createRoutes = [
            'invoice for client' => ['route' => 'financial.invoices.create', 'name' => 'New Invoice', 'use_client_context' => true],
            'quote for client' => ['route' => 'financial.quotes.create', 'name' => 'New Quote', 'use_client_context' => true],
            'ticket for client' => ['route' => 'tickets.create', 'name' => 'New Ticket', 'use_client_context' => true],
            'project for client' => ['route' => 'projects.create', 'name' => 'New Project', 'use_client_context' => true],
            'documentation' => ['route' => 'clients.it-documentation.create', 'name' => 'New IT Documentation'],
            'contact' => ['route' => 'clients.contacts.create', 'name' => 'New Contact'],
            'article' => ['route' => 'knowledge.articles.create', 'name' => 'New Article'],
            'invoice' => ['route' => 'financial.invoices.create', 'name' => 'New Invoice'],
            'quote' => ['route' => 'financial.quotes.create', 'name' => 'New Quote'],
            'ticket' => ['route' => 'tickets.create', 'name' => 'New Ticket'],
            'project' => ['route' => 'projects.create', 'name' => 'New Project'],
            'asset' => ['route' => 'assets.create', 'name' => 'New Asset'],
            'contract' => ['route' => 'financial.contracts.create', 'name' => 'New Contract'],
            'expense' => ['route' => 'financial.expenses.create', 'name' => 'New Expense'],
            'payment' => ['route' => 'financial.payments.create', 'name' => 'New Payment'],
            'user' => ['route' => 'users.create', 'name' => 'New User'],
            'client' => ['route' => 'clients.create', 'name' => 'New Client'],
            'product' => ['route' => 'products.create', 'name' => 'New Product'],
            'service' => ['route' => 'services.create', 'name' => 'New Service'],
            'bundle' => ['route' => 'bundles.create', 'name' => 'New Bundle'],
            'pricing rule' => ['route' => 'pricing-rules.create', 'name' => 'New Pricing Rule'],
        ];
        
        foreach ($createRoutes as $key => $route) {
            if (str_contains($item, $key)) {
                $params = [];
                
                // Add client context if available and route uses it
                if (isset($context['client_id']) && 
                    (isset($route['use_client_context']) || in_array($key, ['ticket', 'invoice', 'project', 'quote']))) {
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
            'products' => ['route' => 'products.index', 'name' => 'Products'],
            'services' => ['route' => 'services.index', 'name' => 'Services'],
            'bundles' => ['route' => 'bundles.index', 'name' => 'Product Bundles'],
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
        // Show only one suggestion per command type
        $quickActions = [
            [
                'command' => 'show urgent',
                'icon' => '🔥',
                'description' => 'View urgent items requiring attention',
                'type' => 'quick_action',
            ],
            [
                'command' => 'create ticket',
                'icon' => '🎫',
                'description' => 'Create new support ticket',
                'type' => 'quick_action',
                'shortcut' => 'Ctrl+Shift+T',
            ],
            [
                'command' => 'show today',
                'icon' => '📅',
                'description' => "View today's scheduled items",
                'type' => 'quick_action',
            ],
            [
                'command' => 'create invoice',
                'icon' => '💰',
                'description' => 'Create new invoice',
                'type' => 'quick_action',
                'shortcut' => 'Ctrl+Shift+I',
            ],
            [
                'command' => 'go to dashboard',
                'icon' => '🏠',
                'description' => 'Navigate to dashboard',
                'type' => 'quick_action',
                'shortcut' => 'Ctrl+0',
            ],
        ];
        
        return $quickActions;
    }

    /**
     * Get recent items for suggestions
     */
    protected static function getRecentItems($context): array
    {
        $items = [];
        
        try {
            $companyId = auth()->user()->company_id;
            
            // Get recent tickets (last 3)
            $recentTickets = \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($recentTickets as $ticket) {
                $items[] = [
                    'command' => "open ticket #{$ticket->id}",
                    'icon' => '🎫',
                    'description' => $ticket->subject . ($ticket->client ? ' - ' . $ticket->client->name : ''),
                    'type' => 'recent',
                ];
            }
            
            // Get upcoming scheduled tickets
            $upcomingTickets = \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>', now())
                ->where('scheduled_at', '<=', now()->addDays(7))
                ->with('client')
                ->orderBy('scheduled_at', 'asc')
                ->limit(2)
                ->get();
            
            foreach ($upcomingTickets as $ticket) {
                $items[] = [
                    'command' => "open ticket #{$ticket->id}",
                    'icon' => '📅',
                    'description' => 'Scheduled: ' . $ticket->scheduled_at->format('M j') . ' - ' . $ticket->subject,
                    'type' => 'upcoming',
                ];
            }
            
            // Get recent invoices (last 2)
            $recentInvoices = \App\Models\Invoice::where('company_id', $companyId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->limit(2)
                ->get();
            
            foreach ($recentInvoices as $invoice) {
                $invoiceNumber = $invoice->prefix ? $invoice->prefix . $invoice->number : $invoice->number;
                $items[] = [
                    'command' => "open invoice #{$invoiceNumber}",
                    'icon' => '💰',
                    'description' => ($invoice->client ? $invoice->client->name . ' - ' : '') . '$' . number_format($invoice->amount, 2),
                    'type' => 'recent',
                ];
            }
            
            // Get recent quotes (last 1)
            $recentQuotes = \App\Models\Quote::where('company_id', $companyId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->limit(1)
                ->get();
            
            foreach ($recentQuotes as $quote) {
                $quoteNumber = $quote->prefix ? $quote->prefix . $quote->number : $quote->number;
                $items[] = [
                    'command' => "open quote #{$quoteNumber}",
                    'icon' => '📝',
                    'description' => ($quote->client ? $quote->client->name . ' - ' : '') . 'Status: ' . $quote->status,
                    'type' => 'recent',
                ];
            }
            
        } catch (\Exception $e) {
            // If there's an error querying, return empty array instead of mock data
            \Log::error('Error fetching recent items: ' . $e->getMessage());
        }
        
        return array_slice($items, 0, 5); // Limit to 5 recent items
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
                'message' => static::getHelpMessage(),
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

    /**
     * Handle MSP-specific actions (assign, escalate, resolve, close, schedule)
     */
    protected static function handleMspAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0])); // Full command
        $item = strtolower(trim($matches[1])); // Entity part
        
        $verb = explode(' ', $command)[0]; // Extract the verb
        
        // For now, provide feedback - actual implementation will depend on specific MSP workflows
        return [
            'action' => 'info',
            'message' => "MSP Action: {$verb} {$item}",
            'suggestion' => "This will be implemented based on your MSP workflow requirements",
        ];
    }
    
    /**
     * Handle system actions (monitor, restart, backup, restore, patch)
     */
    protected static function handleSystemAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        return [
            'action' => 'info',
            'message' => "System Action: {$verb} {$item}",
            'suggestion' => "System operations require specific asset/service selection",
        ];
    }
    
    /**
     * Handle communication actions (send, notify, alert, email, message)
     */
    protected static function handleCommAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        return [
            'action' => 'info',
            'message' => "Communication Action: {$verb} {$item}",
            'suggestion' => "Communication actions will integrate with your notification system",
        ];
    }
    
    /**
     * Handle data actions (export, import, sync, archive)
     */
    protected static function handleDataAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        // For export commands, we can actually implement basic functionality
        if ($verb === 'export') {
            return static::handleExport($item, $context);
        }
        
        return [
            'action' => 'info',
            'message' => "Data Action: {$verb} {$item}",
            'suggestion' => "Data operations will be configured based on your business requirements",
        ];
    }
    
    /**
     * Handle state actions (enable, disable, activate, deactivate, start, stop)
     */
    protected static function handleStateAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        return [
            'action' => 'info',
            'message' => "State Action: {$verb} {$item}",
            'suggestion' => "State changes require specific entity selection and permissions",
        ];
    }
    
    /**
     * Handle configuration actions (configure, deploy, update, install, upgrade)
     */
    protected static function handleConfigAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        // Handle specific configuration actions
        switch ($verb) {
            case 'configure':
                return static::handleConfigure($item, $context);
            case 'deploy':
                return static::handleDeploy($item, $context);
            case 'update':
            case 'upgrade':
                return static::handleUpdate($item, $context);
            case 'install':
                return static::handleInstall($item, $context);
            default:
                return [
                    'action' => 'info',
                    'message' => "Configuration Action: {$verb} {$item}",
                    'suggestion' => "Configuration actions require appropriate permissions and context",
                ];
        }
    }
    
    /**
     * Handle configure commands
     */
    protected static function handleConfigure($item, $context): array
    {
        $configRoutes = [
            'client' => 'clients.show', // Navigate to client settings
            'asset' => 'assets.show', // Navigate to asset configuration
            'user' => 'users.show', // Navigate to user settings
        ];
        
        foreach ($configRoutes as $entity => $route) {
            if (str_contains($item, $entity)) {
                return [
                    'action' => 'info',
                    'message' => "Configure {$entity}",
                    'suggestion' => "Configuration options will be available in the {$entity} details page",
                ];
            }
        }
        
        return [
            'action' => 'info',
            'message' => "Configure: {$item}",
            'suggestion' => "Configuration interface will be implemented for this entity",
        ];
    }
    
    /**
     * Handle deploy commands
     */
    protected static function handleDeploy($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Deploy: {$item}",
            'suggestion' => "Deployment actions will be integrated with your deployment pipeline",
        ];
    }
    
    /**
     * Handle update/upgrade commands
     */
    protected static function handleUpdate($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Update: {$item}",
            'suggestion' => "Update operations will be coordinated with your change management process",
        ];
    }
    
    /**
     * Handle install commands
     */
    protected static function handleInstall($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Install: {$item}",
            'suggestion' => "Installation procedures will follow your approved software catalog",
        ];
    }

    /**
     * Handle analysis actions (analyze, report, audit, investigate, troubleshoot)
     */
    protected static function handleAnalysisAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        // Handle specific analysis actions
        switch ($verb) {
            case 'analyze':
                return static::handleAnalyze($item, $context);
            case 'report':
                return static::handleReport($item, $context);
            case 'audit':
                return static::handleAudit($item, $context);
            case 'investigate':
                return static::handleInvestigate($item, $context);
            case 'troubleshoot':
                return static::handleTroubleshoot($item, $context);
            default:
                return [
                    'action' => 'info',
                    'message' => "Analysis Action: {$verb} {$item}",
                    'suggestion' => "Analysis tools will provide insights based on your data",
                ];
        }
    }
    
    /**
     * Handle workflow actions (trigger, execute, automate, validate, test)
     */
    protected static function handleWorkflowAction($matches, $context): array
    {
        $command = strtolower(trim($matches[0]));
        $item = strtolower(trim($matches[1]));
        
        $verb = explode(' ', $command)[0];
        
        // Handle specific workflow actions
        switch ($verb) {
            case 'trigger':
                return static::handleTrigger($item, $context);
            case 'execute':
                return static::handleExecute($item, $context);
            case 'automate':
                return static::handleAutomate($item, $context);
            case 'validate':
                return static::handleValidate($item, $context);
            case 'test':
                return static::handleTest($item, $context);
            default:
                return [
                    'action' => 'info',
                    'message' => "Workflow Action: {$verb} {$item}",
                    'suggestion' => "Workflow automation will streamline your processes",
                ];
        }
    }
    
    /**
     * Handle analyze commands
     */
    protected static function handleAnalyze($item, $context): array
    {
        $analysisRoutes = [
            'client' => 'reports.client-analysis',
            'ticket' => 'reports.ticket-analysis', 
            'invoice' => 'reports.financial-analysis',
            'asset' => 'reports.asset-analysis',
        ];
        
        foreach ($analysisRoutes as $entity => $route) {
            if (str_contains($item, $entity)) {
                return [
                    'action' => 'info',
                    'message' => "Analyze {$entity} data",
                    'suggestion' => "Analysis reports will be available in the Reports section",
                ];
            }
        }
        
        return [
            'action' => 'navigate',
            'url' => route('reports.index'),
            'message' => "Opening Analysis Dashboard",
        ];
    }
    
    /**
     * Handle report commands
     */
    protected static function handleReport($item, $context): array
    {
        return [
            'action' => 'navigate',
            'url' => route('reports.index'),
            'message' => "Generating report for: {$item}",
        ];
    }
    
    /**
     * Handle audit commands
     */
    protected static function handleAudit($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Audit: {$item}",
            'suggestion' => "Audit trails will show all changes and access patterns",
        ];
    }
    
    /**
     * Handle investigate commands  
     */
    protected static function handleInvestigate($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Investigate: {$item}",
            'suggestion' => "Investigation tools will help trace issues and patterns",
        ];
    }
    
    /**
     * Handle troubleshoot commands
     */
    protected static function handleTroubleshoot($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Troubleshoot: {$item}",
            'suggestion' => "Diagnostic tools will help identify and resolve issues",
        ];
    }
    
    /**
     * Handle trigger commands
     */
    protected static function handleTrigger($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Trigger: {$item}",
            'suggestion' => "Workflow triggers will automate responses to events",
        ];
    }
    
    /**
     * Handle execute commands
     */
    protected static function handleExecute($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Execute: {$item}",
            'suggestion' => "Execution commands will run predefined scripts and workflows",
        ];
    }
    
    /**
     * Handle automate commands
     */
    protected static function handleAutomate($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Automate: {$item}",
            'suggestion' => "Automation will handle repetitive tasks and workflows",
        ];
    }
    
    /**
     * Handle validate commands
     */
    protected static function handleValidate($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Validate: {$item}",
            'suggestion' => "Validation will check data integrity and business rules",
        ];
    }
    
    /**
     * Handle test commands
     */
    protected static function handleTest($item, $context): array
    {
        return [
            'action' => 'info',
            'message' => "Test: {$item}",
            'suggestion' => "Testing tools will verify system functionality and performance",
        ];
    }

    /**
     * Handle export functionality (partial implementation)
     */
    protected static function handleExport($item, $context): array
    {
        $exportRoutes = [
            'invoices' => 'financial.invoices.export',
            'clients' => 'clients.export', 
            'tickets' => 'tickets.export',
            'assets' => 'assets.export',
            'projects' => 'projects.export',
        ];
        
        // Try to find exact matches first
        foreach ($exportRoutes as $entity => $route) {
            if (str_contains($item, $entity)) {
                // Check if route exists
                if (Route::has($route)) {
                    return [
                        'action' => 'navigate',
                        'url' => route($route),
                        'message' => "Exporting {$entity}",
                    ];
                }
            }
        }
        
        return [
            'action' => 'info',
            'message' => "Export: {$item}",
            'suggestion' => 'Export functionality will be implemented for this entity type',
        ];
    }

    /**
     * Get comprehensive help message with keyboard shortcuts
     */
    protected static function getHelpMessage(): string
    {
        // Delegate to the centralized shortcut service
        return \App\Services\ShortcutService::getHelpMessage();
    }
}