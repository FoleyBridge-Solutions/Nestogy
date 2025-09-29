<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Domains\Ticket\Models\Ticket;
use App\Models\Asset;
use App\Domains\Contract\Models\Contract;
use App\Models\Invoice;
use App\Domains\Project\Models\Project;
use App\Domains\Lead\Models\Lead;
use App\Domains\Knowledge\Models\KbArticle;
use App\Services\QuickActionService;
use App\Models\CustomQuickAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CommandPalette extends Component
{
    public $isOpen = false;
    public $search = '';
    public $results = [];
    public $selectedIndex = 0;

    protected $listeners = ['openCommandPalette' => 'open'];

    public function open()
    {
        $this->isOpen = true;
        $this->search = '';
        $this->results = $this->getPopularCommands();
        $this->selectedIndex = 0;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->search = '';
        $this->results = [];
        $this->selectedIndex = 0;
    }

    public function updatedSearch($value)
    {
        if (strlen($value) < 1) {
            // Show popular navigation commands when empty
            $this->results = $this->getPopularCommands();
            return;
        }

        // Perform search even for single character
        $this->performSearch($value);
        $this->selectedIndex = 0;
    }

    private function performSearch($query)
    {
        $results = [];
        $limit = 5;
        $user = Auth::user();

        try {
            // Search Clients - bypass global scope and filter manually
            $clients = Client::withoutGlobalScope('company')
                ->when($user && $user->company_id, function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('company_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get();

            foreach ($clients as $client) {
                $results[] = [
                    'type' => 'client',
                    'id' => $client->id,
                    'title' => $client->name,
                    'subtitle' => "Client" . ($client->company_name ? " • {$client->company_name}" : ''),
                    'route_name' => 'clients.show',
                    'route_params' => ['client' => $client->id],
                    'icon' => 'building-office'
                ];
            }

            // Search Tickets
            $ticketQuery = method_exists(Ticket::class, 'withoutGlobalScope') 
                ? Ticket::withoutGlobalScope('company') 
                : Ticket::query();
                
            $tickets = $ticketQuery
                ->when($user && $user->company_id, function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
                ->where(function($q) use ($query) {
                    $q->where('subject', 'like', "%{$query}%")
                      ->orWhere('number', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get();

            foreach ($tickets as $ticket) {
                $results[] = [
                    'type' => 'ticket',
                    'id' => $ticket->id,
                    'title' => $ticket->subject,
                    'subtitle' => "Ticket #{$ticket->number} • {$ticket->status}",
                    'route_name' => 'tickets.show',
                    'route_params' => ['ticket' => $ticket->id],
                    'icon' => 'ticket'
                ];
            }

            // Search Assets
            $assetQuery = method_exists(Asset::class, 'withoutGlobalScope') 
                ? Asset::withoutGlobalScope('company') 
                : Asset::query();
                
            $assets = $assetQuery
                ->when($user && $user->company_id, function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('serial', 'like', "%{$query}%")
                      ->orWhere('make', 'like', "%{$query}%")
                      ->orWhere('model', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get();

            foreach ($assets as $asset) {
                $results[] = [
                    'type' => 'asset',
                    'id' => $asset->id,
                    'title' => $asset->name,
                    'subtitle' => "Asset • {$asset->type}",
                    'route_name' => 'assets.show',
                    'route_params' => ['asset' => $asset->id],
                    'icon' => 'computer-desktop'
                ];
            }

            // Search Contracts
            $contractQuery = method_exists(Contract::class, 'withoutGlobalScope') 
                ? Contract::withoutGlobalScope('company') 
                : Contract::query();
                
            $contracts = $contractQuery
                ->when($user && $user->company_id, function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('contract_number', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get();

            foreach ($contracts as $contract) {
                $results[] = [
                    'type' => 'contract',
                    'id' => $contract->id,
                    'title' => $contract->title ?: "Contract #{$contract->contract_number}",
                    'subtitle' => "Contract • {$contract->contract_type}",
                    'route_name' => 'contracts.show',
                    'route_params' => ['contract' => $contract->id],
                    'icon' => 'document-text'
                ];
            }

            // Search Invoices
            $invoiceQuery = method_exists(Invoice::class, 'withoutGlobalScope') 
                ? Invoice::withoutGlobalScope('company') 
                : Invoice::query();
                
            $invoices = $invoiceQuery
                ->when($user && $user->company_id, function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
                ->where('number', 'like', "%{$query}%")
                ->limit($limit)
                ->get();

            foreach ($invoices as $invoice) {
                $results[] = [
                    'type' => 'invoice',
                    'id' => $invoice->id,
                    'title' => "Invoice #{$invoice->number}",
                    'subtitle' => "Invoice • \${$invoice->amount}",
                    'route_name' => 'financial.invoices.show',
                    'route_params' => ['invoice' => $invoice->id],
                    'icon' => 'currency-dollar'
                ];
            }

            // Search Projects
            $projectQuery = method_exists(Project::class, 'withoutGlobalScope') 
                ? Project::withoutGlobalScope('company') 
                : Project::query();
                
            $projects = $projectQuery
                ->when($user && $user->company_id, function($q) use ($user) {
                    return $q->where('company_id', $user->company_id);
                })
                ->where('name', 'like', "%{$query}%")
                ->limit($limit)
                ->get();

            foreach ($projects as $project) {
                $results[] = [
                    'type' => 'project',
                    'id' => $project->id,
                    'title' => $project->name,
                    'subtitle' => "Project • {$project->status}",
                    'route_name' => 'projects.show',
                    'route_params' => ['project' => $project->id],
                    'icon' => 'briefcase'
                ];
            }

            // Search Knowledge Articles - Commented out until routes are implemented
            // $articles = KbArticle::where('title', 'like', "%{$query}%")
            //     ->orWhere('content', 'like', "%{$query}%")
            //     ->limit($limit)
            //     ->get();

            // foreach ($articles as $article) {
            //     $results[] = [
            //         'type' => 'article',
            //         'id' => $article->id,
            //         'title' => $article->title,
            //         'subtitle' => "Knowledge Article",
            //         'url' => route('knowledge.articles.show', $article),
            //         'icon' => 'book-open'
            //     ];
            // }

            // Add quick actions
            $quickActions = $this->getQuickActions($query);
            $results = array_merge($quickActions, $results);

            // Limit total results
            $this->results = array_slice($results, 0, 15);
        } catch (\Exception $e) {
            // Log the error but don't crash the search
            \Log::error('Command palette search error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
            $this->results = [];
        }
    }

    private function getQuickActions($query)
    {
        $actions = [];
        $user = Auth::user();
        
        if (!$user) {
            return $actions;
        }
        
        // Use QuickActionService to search for quick actions
        $quickActions = QuickActionService::searchActions($query, $user);
        
        foreach ($quickActions as $action) {
            $formattedAction = [
                'type' => 'quick_action',
                'id' => $action['id'] ?? null,
                'title' => $action['title'],
                'subtitle' => 'Quick Action • ' . ($action['description'] ?? ''),
                'icon' => $action['icon'] ?? 'bolt',
                'action_data' => $action,
            ];
            
            // Add route information if available
            if (isset($action['route'])) {
                $formattedAction['route_name'] = $action['route'];
                $formattedAction['route_params'] = $action['parameters'] ?? [];
            }
            
            // Add custom action ID if it's a custom action
            if (isset($action['custom_id'])) {
                $formattedAction['custom_id'] = $action['custom_id'];
            }
            
            // Add action key for system actions
            if (isset($action['action'])) {
                $formattedAction['action_key'] = $action['action'];
            }
            
            $actions[] = $formattedAction;
        }

        // Get all navigation items from sidebar configurations  
        $navigationActions = $this->getAllNavigationCommands();
        $queryLower = strtolower($query);

        // Filter navigation items based on query
        foreach ($navigationActions as $action) {
            // Check if title or keywords match the query
            if (str_contains(strtolower($action['title']), $queryLower) ||
                (isset($action['keywords']) && $this->matchesKeywords($action['keywords'], $queryLower))) {
                $actions[] = $action;
            }
        }

        // Quick action mappings for create/new actions (these are hardcoded for common actions)
        $quickActionMappings = [
            ['keywords' => ['new ticket', 'create ticket'], 'action' => ['title' => 'Create New Ticket', 'subtitle' => 'Quick Action', 'route_name' => 'tickets.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'quick_action']],
            ['keywords' => ['new client', 'add client'], 'action' => ['title' => 'Add New Client', 'subtitle' => 'Quick Action', 'route_name' => 'clients.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'quick_action']],
            ['keywords' => ['new invoice', 'create invoice'], 'action' => ['title' => 'Create Invoice', 'subtitle' => 'Quick Action', 'route_name' => 'financial.invoices.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'quick_action']],
            ['keywords' => ['new project', 'create project'], 'action' => ['title' => 'Create New Project', 'subtitle' => 'Quick Action', 'route_name' => 'projects.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'quick_action']],
            ['keywords' => ['new asset', 'add asset'], 'action' => ['title' => 'Add New Asset', 'subtitle' => 'Quick Action', 'route_name' => 'assets.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'quick_action']],
            ['keywords' => ['compose email', 'send email', 'write email'], 'action' => ['title' => 'Compose Email', 'subtitle' => 'Quick Action', 'route_name' => 'email.compose.index', 'route_params' => [], 'icon' => 'pencil-square', 'type' => 'quick_action']],
        ];

        foreach ($quickActionMappings as $mapping) {
            foreach ($mapping['keywords'] as $keyword) {
                if (str_contains($queryLower, $keyword)) {
                    // Check if not already added
                    $exists = false;
                    foreach ($actions as $existingAction) {
                        if (isset($existingAction['route_name']) && 
                            isset($mapping['action']['route_name']) &&
                            $existingAction['route_name'] === $mapping['action']['route_name']) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $actions[] = $mapping['action'];
                    }
                    break;
                }
            }
        }

        return $actions;
    }

    /**
     * Get all navigation items from sidebar configurations as commands
     */
    private function getAllNavigationCommands()
    {
        $commands = [];
        $sidebarProvider = app(\App\Services\SidebarConfigProvider::class);

        // Define all contexts to load
        $contexts = ['clients', 'tickets', 'email', 'assets', 'financial', 'projects', 'reports', 'settings'];

        foreach ($contexts as $context) {
            $config = $sidebarProvider->getConfiguration($context);

            if (empty($config['sections'])) {
                continue;
            }

            // Extract navigation items from each section
            foreach ($config['sections'] as $section) {
                if (!isset($section['items'])) {
                    continue;
                }

                foreach ($section['items'] as $item) {
                    // Skip if no route defined
                    if (!isset($item['route'])) {
                        continue;
                    }

                    // Process route parameters to handle special values like 'current'
                    $routeParams = $item['params'] ?? $item['route_params'] ?? [];
                    $processedParams = $this->processRouteParameters($routeParams);

                    // Build command from navigation item
                    $command = [
                        'type' => 'navigation',
                        'title' => $item['name'],
                        'subtitle' => 'Navigation • ' . ucfirst($context),
                        'route_name' => $item['route'],
                        'route_params' => $processedParams,
                        'icon' => $item['icon'] ?? 'arrow-right',
                        'keywords' => $this->generateKeywords($item['name'], $context)
                    ];

                    // Add description if available
                    if (isset($item['description'])) {
                        $command['subtitle'] .= ' • ' . $item['description'];
                    }

                    $commands[] = $command;
                }
            }
        }

        // Add main dashboard
        $commands[] = [
            'type' => 'navigation',
            'title' => 'Dashboard',
            'subtitle' => 'Navigation • Main',
            'route_name' => 'dashboard',
            'route_params' => [],
            'icon' => 'home',
            'keywords' => ['dashboard', 'home', 'main', 'overview']
        ];

        return $commands;
    }

    /**
     * Process route parameters to handle special values
     */
    private function processRouteParameters($params)
    {
        if (empty($params)) {
            return [];
        }

        $processed = [];
        $selectedClient = \App\Services\NavigationService::getSelectedClient();

        foreach ($params as $key => $value) {
            // Handle 'current' client parameter
            if ($value === 'current' && in_array($key, ['client', 'client_id'])) {
                if ($selectedClient) {
                    $processed[$key] = $selectedClient->id;
                }
                // Skip this parameter if no client is selected
                continue;
            }

            // Keep other parameters as-is
            $processed[$key] = $value;
        }

        return $processed;
    }

    /**
     * Generate searchable keywords for a navigation item
     */
    private function generateKeywords($name, $context)
    {
        $keywords = [];

        // Add the name itself
        $keywords[] = strtolower($name);

        // Add context
        $keywords[] = strtolower($context);

        // Add common variations
        $nameWords = explode(' ', strtolower($name));
        foreach ($nameWords as $word) {
            if (strlen($word) > 2) { // Skip short words
                $keywords[] = $word;
            }
        }

        // Add specific keywords for common items
        $specificKeywords = [
            'client details' => ['customer', 'account'],
            'open tickets' => ['issues', 'problems', 'support'],
            'contacts' => ['people', 'users'],
            'locations' => ['addresses', 'sites'],
            'invoices' => ['bills', 'billing'],
            'quotes' => ['estimates', 'proposals'],
            'contracts' => ['agreements', 'sla'],
            'assets' => ['equipment', 'hardware', 'devices'],
            'projects' => ['tasks', 'work'],
            'email' => ['mail', 'messages'],
            'settings' => ['config', 'configuration', 'preferences'],
            'reports' => ['analytics', 'stats', 'statistics'],
            'security' => ['permissions', 'access', 'auth'],
            'users' => ['staff', 'employees', 'team'],
        ];

        $nameLower = strtolower($name);
        foreach ($specificKeywords as $item => $itemKeywords) {
            if (str_contains($nameLower, $item)) {
                $keywords = array_merge($keywords, $itemKeywords);
            }
        }

        return array_unique($keywords);
    }

    /**
     * Check if any keyword matches the query
     */
    private function matchesKeywords($keywords, $query)
    {
        foreach ($keywords as $keyword) {
            if (str_contains(strtolower($keyword), $query)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get popular/frequently used commands to show when palette opens
     */
    private function getPopularCommands()
    {
        $user = Auth::user();
        $commands = [];
        
        if ($user) {
            // Get popular quick actions from the service
            $popularActions = QuickActionService::getPopularActions($user);
            
            foreach ($popularActions as $action) {
                $command = [
                    'type' => 'quick_action',
                    'id' => $action['id'] ?? null,
                    'title' => $action['title'],
                    'subtitle' => ($action['is_favorite'] ?? false) ? '⭐ Favorite Quick Action • ' . ($action['description'] ?? '') : 'Quick Action • ' . ($action['description'] ?? ''),
                    'icon' => $action['icon'] ?? 'bolt',
                    'action_data' => $action,
                ];
                
                // Add route information if available
                if (isset($action['route'])) {
                    $command['route_name'] = $action['route'];
                    $command['route_params'] = $action['parameters'] ?? [];
                }
                
                // Add custom action ID if it's a custom action
                if (isset($action['custom_id'])) {
                    $command['custom_id'] = $action['custom_id'];
                }
                
                // Add action key for system actions
                if (isset($action['action'])) {
                    $command['action_key'] = $action['action'];
                }
                
                $commands[] = $command;
            }
        }
        
        // Add standard navigation commands
        $navigationCommands = [
            [
                'type' => 'navigation',
                'title' => 'Dashboard',
                'subtitle' => 'Navigation • Main',
                'route_name' => 'dashboard',
                'route_params' => [],
                'icon' => 'home'
            ],
            [
                'type' => 'navigation',
                'title' => 'Clients',
                'subtitle' => 'Navigation • View all clients',
                'route_name' => 'clients.index',
                'route_params' => [],
                'icon' => 'user-group'
            ],
            [
                'type' => 'navigation',
                'title' => 'Tickets',
                'subtitle' => 'Navigation • Support tickets',
                'route_name' => 'tickets.index',
                'route_params' => [],
                'icon' => 'ticket'
            ],
            [
                'type' => 'navigation',
                'title' => 'Email Inbox',
                'subtitle' => 'Navigation • Email',
                'route_name' => 'email.inbox.index',
                'route_params' => [],
                'icon' => 'envelope'
            ],
            [
                'type' => 'navigation',
                'title' => 'Invoices',
                'subtitle' => 'Navigation • Financial',
                'route_name' => 'financial.invoices.index',
                'route_params' => [],
                'icon' => 'document-text'
            ],
            [
                'type' => 'navigation',
                'title' => 'Projects',
                'subtitle' => 'Navigation • Project management',
                'route_name' => 'projects.index',
                'route_params' => [],
                'icon' => 'folder'
            ],
            [
                'type' => 'navigation',
                'title' => 'Assets',
                'subtitle' => 'Navigation • Equipment & inventory',
                'route_name' => 'assets.index',
                'route_params' => [],
                'icon' => 'computer-desktop'
            ],
            [
                'type' => 'navigation',
                'title' => 'Reports',
                'subtitle' => 'Navigation • Analytics',
                'route_name' => 'reports.index',
                'route_params' => [],
                'icon' => 'chart-bar'
            ],
            [
                'type' => 'navigation',
                'title' => 'Settings',
                'subtitle' => 'Navigation • System configuration',
                'route_name' => 'settings.index',
                'route_params' => [],
                'icon' => 'cog-6-tooth'
            ]
        ];
        
        // Merge commands, limiting total to reasonable number
        return array_merge($commands, $navigationCommands);
    }

    public function selectNext()
    {
        if ($this->selectedIndex < count($this->results) - 1) {
            $this->selectedIndex++;
        }
    }

    public function selectPrevious()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    public function selectResult($index = null)
    {
        $index = $index ?? $this->selectedIndex;

        if (isset($this->results[$index])) {
            $result = $this->results[$index];

            // Close the modal first
            $this->close();

            // Handle quick actions
            if ($result['type'] === 'quick_action' && isset($result['action_data'])) {
                $action = $result['action_data'];
                
                // Handle custom actions
                if (isset($action['custom_id'])) {
                    $customAction = CustomQuickAction::find($action['custom_id']);
                    
                    if ($customAction && $customAction->canBeExecutedBy(Auth::user())) {
                        $customAction->recordUsage();
                        
                        if ($customAction->type === 'route') {
                            return $this->redirectRoute(
                                $customAction->target,
                                $customAction->parameters ?? [],
                                navigate: true
                            );
                        } elseif ($customAction->type === 'url') {
                            $url = $customAction->target;
                            if (!empty($customAction->parameters)) {
                                $url .= '?' . http_build_query($customAction->parameters);
                            }
                            
                            if ($customAction->open_in === 'new_tab') {
                                $this->dispatch('open-url', ['url' => $url, 'target' => '_blank']);
                                return;
                            } else {
                                return $this->redirect($url, navigate: true);
                            }
                        }
                    }
                }
                // Handle system actions with special dispatch events
                elseif (isset($action['action'])) {
                    switch ($action['action']) {
                        case 'remoteAccess':
                            $this->dispatch('open-remote-access');
                            break;
                        case 'clientPortal':
                            $this->dispatch('open-client-portal');
                            break;
                        default:
                            $this->dispatch('quick-action-executed', ['action' => $action['action']]);
                            break;
                    }
                    return;
                }
                // Handle route-based system actions
                elseif (isset($action['route'])) {
                    return $this->redirectRoute(
                        $action['route'],
                        $action['parameters'] ?? [],
                        navigate: true
                    );
                }
            }

            // Use redirectRoute with navigate for SPA-like behavior
            if (isset($result['route_name'])) {
                // Just navigate - let middleware handle any client requirements
                return $this->redirectRoute(
                    $result['route_name'],
                    $result['route_params'] ?? [],
                    navigate: true
                );
            }

            // Fallback for any results that still have URL
            if (isset($result['url'])) {
                return $this->redirect($result['url'], navigate: true);
            }
        }
    }
    
    public function navigateTo($url)
    {
        // Don't close modal here - let redirect handle it
        // Use Livewire's redirect method with navigate for SPA-like behavior
        return $this->redirect($url, navigate: true);
    }
    
    public function navigateToRoute($routeName, $params = [])
    {
        // Don't close modal here - let redirect handle it
        return $this->redirectRoute($routeName, $params, navigate: true);
    }

    public function render()
    {
        return view('livewire.command-palette');
    }
}