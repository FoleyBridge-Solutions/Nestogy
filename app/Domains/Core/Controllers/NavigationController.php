<?php

namespace App\Domains\Core\Controllers;

use App\Http\Controllers\Controller;

use App\Domains\Core\Services\NavigationService;
use App\Domains\Core\Services\CommandPaletteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class NavigationController extends Controller
{
    /**
     * Get navigation tree for current user
     */
    public function getNavigationTree(Request $request): JsonResponse
    {
        $domain = $request->get('domain', 'dashboard');
        $user = auth()->user();
        
        $navigation = NavigationService::getFilteredNavigationItems($domain);
        $badges = NavigationService::getBadgeCounts($domain);
        
        return response()->json([
            'domain' => $domain,
            'items' => $navigation,
            'badges' => $badges,
            'client' => NavigationService::getSelectedClient(),
            'workflow' => NavigationService::getWorkflowContext(),
        ]);
    }

    /**
     * Get badge counts for navigation items
     */
    public function getBadgeCounts(Request $request): JsonResponse
    {
        $domain = $request->get('domain');
        $badges = NavigationService::getBadgeCounts($domain);
        
        return response()->json($badges);
    }

    /**
     * Get command palette suggestions
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $context = [
            'client_id' => session('selected_client_id'),
            'domain' => NavigationService::getActiveDomain(),
            'workflow' => NavigationService::getWorkflowContext(),
        ];
        
        $suggestions = CommandPaletteService::getSuggestions($query, $context);
        
        return response()->json($suggestions);
    }

    /**
     * Execute command from command palette
     */
    public function executeCommand(Request $request): JsonResponse
    {
        $request->validate([
            'command' => 'required|string|max:500',
        ]);
        
        $context = [
            'client_id' => session('selected_client_id'),
            'domain' => NavigationService::getActiveDomain(),
            'workflow' => NavigationService::getWorkflowContext(),
            'user_id' => auth()->id(),
        ];
        
        $result = CommandPaletteService::processCommand($request->command, $context);
        
        // Log command for analytics
        $this->logCommand($request->command, $result);
        
        return response()->json($result);
    }

    /**
     * Set workflow context
     */
    public function setWorkflow(Request $request): JsonResponse
    {
        $request->validate([
            'workflow' => 'required|string|in:urgent,today,scheduled,financial,reports,morning_routine,billing_day,maintenance_window',
        ]);
        
        NavigationService::setWorkflowContext($request->workflow);
        
        $highlights = NavigationService::getWorkflowNavigationHighlights($request->workflow);
        
        return response()->json([
            'success' => true,
            'workflow' => $request->workflow,
            'highlights' => $highlights,
        ]);
    }

    /**
     * Get workflow highlights
     */
    public function getWorkflowHighlights(Request $request): JsonResponse
    {
        $workflow = $request->get('workflow', NavigationService::getWorkflowContext());
        $highlights = NavigationService::getWorkflowNavigationHighlights($workflow);
        
        return response()->json($highlights);
    }

    /**
     * Get recent navigation items
     */
    public function getRecentItems(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $recentItems = [];
        
        try {
            $companyId = auth()->user()->company_id;
            
            // Get recent tickets
            $recentTickets = \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->limit(4)
                ->get();
            
            foreach ($recentTickets as $ticket) {
                $recentItems[] = [
                    'type' => 'ticket',
                    'id' => $ticket->id,
                    'title' => "#{$ticket->id} - {$ticket->subject}",
                    'subtitle' => $ticket->client ? $ticket->client->name : 'No Client',
                    'url' => route('tickets.show', $ticket->id),
                    'icon' => '🎫',
                    'timestamp' => $ticket->updated_at,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                ];
            }
            
            // Get upcoming scheduled tickets
            $upcomingTickets = \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>', now())
                ->where('scheduled_at', '<=', now()->addDays(7))
                ->with('client')
                ->orderBy('scheduled_at', 'asc')
                ->limit(3)
                ->get();
            
            foreach ($upcomingTickets as $ticket) {
                $recentItems[] = [
                    'type' => 'ticket',
                    'id' => $ticket->id,
                    'title' => "#{$ticket->id} - {$ticket->subject}",
                    'subtitle' => 'Scheduled: ' . $ticket->scheduled_at->format('M j, g:i A'),
                    'url' => route('tickets.show', $ticket->id),
                    'icon' => '📅',
                    'timestamp' => $ticket->scheduled_at,
                    'status' => 'scheduled',
                    'priority' => $ticket->priority,
                ];
            }
            
            // Get recent invoices
            $recentInvoices = \App\Models\Invoice::where('company_id', $companyId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($recentInvoices as $invoice) {
                $invoiceNumber = $invoice->prefix ? $invoice->prefix . $invoice->number : $invoice->number;
                $recentItems[] = [
                    'type' => 'invoice',
                    'id' => $invoice->id,
                    'title' => "Invoice #{$invoiceNumber}",
                    'subtitle' => ($invoice->client ? $invoice->client->name . ' - ' : '') . '$' . number_format($invoice->amount, 2),
                    'url' => route('financial.invoices.show', $invoice->id),
                    'icon' => '💰',
                    'timestamp' => $invoice->updated_at,
                    'status' => $invoice->status,
                ];
            }
            
            // Get recent quotes
            $recentQuotes = \App\Models\Quote::where('company_id', $companyId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->limit(2)
                ->get();
            
            foreach ($recentQuotes as $quote) {
                $quoteNumber = $quote->prefix ? $quote->prefix . $quote->number : $quote->number;
                $recentItems[] = [
                    'type' => 'quote',
                    'id' => $quote->id,
                    'title' => "Quote #{$quoteNumber}",
                    'subtitle' => ($quote->client ? $quote->client->name . ' - ' : '') . '$' . number_format($quote->total, 2),
                    'url' => route('financial.quotes.show', $quote->id),
                    'icon' => '📝',
                    'timestamp' => $quote->updated_at,
                    'status' => $quote->status,
                ];
            }
            
            // Get recently accessed clients
            $recentClients = \App\Models\Client::where('company_id', $companyId)
                ->orderBy('updated_at', 'desc')
                ->limit(2)
                ->get();
            
            foreach ($recentClients as $client) {
                $recentItems[] = [
                    'type' => 'client',
                    'id' => $client->id,
                    'title' => $client->name,
                    'subtitle' => $client->email ?: $client->phone ?: 'No contact info',
                    'url' => route('clients.show', $client->id),
                    'icon' => '👥',
                    'timestamp' => $client->updated_at,
                    'status' => $client->status ?? 'active',
                ];
            }
            
            // Sort by timestamp (most recent first)
            usort($recentItems, function($a, $b) {
                return $b['timestamp']->timestamp - $a['timestamp']->timestamp;
            });
            
        } catch (\Exception $e) {
            \Log::error('Error fetching recent items: ' . $e->getMessage());
            // Return empty array on error
            $recentItems = [];
        }
        
        return response()->json(array_slice($recentItems, 0, $limit));
    }

    /**
     * Search across all domains
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('query', '');
        $context = $request->get('context', 'global');
        $clientId = $request->get('client_id');
        $domain = $request->get('domain', 'all');
        
        if (empty($query)) {
            return response()->json(['results' => []]);
        }
        
        $results = [];
        
        try {
            // Handle smart search patterns first
            $smartResult = $this->handleSmartSearchPatterns($query);
            if ($smartResult) {
                return response()->json(['results' => $smartResult]);
            }
            // Search tickets
            if ($domain === 'all' || $domain === 'tickets') {
                $tickets = \App\Domains\Ticket\Models\Ticket::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhere('id', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $tickets->where('client_id', $clientId);
                }
                
                $tickets = $tickets->limit(5)->get();
                
                foreach ($tickets as $ticket) {
                    $results[] = [
                        'type' => 'ticket',
                        'icon' => '🎫',
                        'title' => "#{$ticket->id} - {$ticket->title}",
                        'subtitle' => $ticket->client->name ?? 'No Client',
                        'url' => route('tickets.show', $ticket->id),
                        'meta' => [
                            'status' => $ticket->status,
                            'priority' => $ticket->priority,
                        ],
                    ];
                }
            }
            
            // Search clients
            if ($domain === 'all' || $domain === 'clients') {
                $clients = \App\Models\Client::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%")
                          ->orWhere('phone', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();
                
                foreach ($clients as $client) {
                    $results[] = [
                        'type' => 'client',
                        'icon' => '👥',
                        'title' => $client->name,
                        'subtitle' => $client->email,
                        'url' => route('clients.index'),
                        'meta' => [
                            'status' => $client->status,
                        ],
                    ];
                }
            }
            
            // Search invoices
            if ($domain === 'all' || $domain === 'financial') {
                $invoices = \App\Models\Invoice::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('invoice_number', 'like', "%{$query}%")
                          ->orWhere('id', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $invoices->where('client_id', $clientId);
                }
                
                $invoices = $invoices->limit(5)->get();
                
                foreach ($invoices as $invoice) {
                    $results[] = [
                        'type' => 'invoice',
                        'icon' => '💰',
                        'title' => "Invoice #{$invoice->invoice_number}",
                        'subtitle' => $invoice->client->name ?? 'No Client',
                        'url' => route('financial.invoices.show', $invoice->id),
                        'meta' => [
                            'status' => $invoice->status,
                            'amount' => '$' . number_format($invoice->total, 2),
                        ],
                    ];
                }
            }
            
            // Search quotes
            if ($domain === 'all' || $domain === 'financial') {
                $quotes = \App\Models\Quote::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('quote_number', 'like', "%{$query}%")
                          ->orWhere('id', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $quotes->where('client_id', $clientId);
                }
                
                $quotes = $quotes->limit(5)->get();
                
                foreach ($quotes as $quote) {
                    $results[] = [
                        'type' => 'quote',
                        'icon' => '📝',
                        'title' => "Quote #{$quote->quote_number}",
                        'subtitle' => $quote->client->name ?? 'No Client',
                        'url' => route('financial.quotes.show', $quote->id),
                        'meta' => [
                            'status' => $quote->status,
                            'amount' => '$' . number_format($quote->total, 2),
                        ],
                    ];
                }
            }
            
            // Search assets
            if ($domain === 'all' || $domain === 'assets') {
                $assets = \App\Models\Asset::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('asset_tag', 'like', "%{$query}%")
                          ->orWhere('serial_number', 'like', "%{$query}%")
                          ->orWhere('model', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $assets->where('client_id', $clientId);
                }
                
                $assets = $assets->limit(5)->get();
                
                foreach ($assets as $asset) {
                    $results[] = [
                        'type' => 'asset',
                        'icon' => '🖥️',
                        'title' => $asset->name,
                        'subtitle' => "Tag: {$asset->asset_tag} | {$asset->model}",
                        'url' => route('assets.show', $asset->id),
                        'meta' => [
                            'status' => $asset->status,
                        ],
                    ];
                }
            }
            
            // Search projects
            if ($domain === 'all' || $domain === 'projects') {
                $projects = \App\Models\Project::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhere('project_code', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $projects->where('client_id', $clientId);
                }
                
                $projects = $projects->limit(5)->get();
                
                foreach ($projects as $project) {
                    $results[] = [
                        'type' => 'project',
                        'icon' => '📊',
                        'title' => $project->name,
                        'subtitle' => $project->client->name ?? 'Internal Project',
                        'url' => route('projects.show', $project->id),
                        'meta' => [
                            'status' => $project->status,
                        ],
                    ];
                }
            }
            
            // Search contracts
            if ($domain === 'all' || $domain === 'financial') {
                $contracts = \App\Domains\Contract\Models\Contract::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('contract_number', 'like', "%{$query}%")
                          ->orWhere('title', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $contracts->where('client_id', $clientId);
                }
                
                $contracts = $contracts->limit(5)->get();
                
                foreach ($contracts as $contract) {
                    $results[] = [
                        'type' => 'contract',
                        'icon' => '📄',
                        'title' => $contract->title ?? "Contract #{$contract->contract_number}",
                        'subtitle' => $contract->client->name ?? 'No Client',
                        'url' => route('financial.contracts.show', $contract->id),
                        'meta' => [
                            'status' => $contract->status,
                        ],
                    ];
                }
            }
            
            // Search expenses
            if ($domain === 'all' || $domain === 'financial') {
                $expenses = \App\Models\Expense::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('description', 'like', "%{$query}%")
                          ->orWhere('vendor', 'like', "%{$query}%")
                          ->orWhere('reference', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();
                
                foreach ($expenses as $expense) {
                    $results[] = [
                        'type' => 'expense',
                        'icon' => '💸',
                        'title' => $expense->description,
                        'subtitle' => $expense->vendor ?? 'No Vendor',
                        'url' => route('financial.expenses.show', $expense->id),
                        'meta' => [
                            'status' => $expense->status,
                            'amount' => '$' . number_format($expense->amount, 2),
                        ],
                    ];
                }
            }
            
            // Search payments
            if ($domain === 'all' || $domain === 'financial') {
                $payments = \App\Models\Payment::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('reference', 'like', "%{$query}%")
                          ->orWhere('notes', 'like', "%{$query}%");
                    });
                
                if ($clientId) {
                    $payments->where('client_id', $clientId);
                }
                
                $payments = $payments->limit(5)->get();
                
                foreach ($payments as $payment) {
                    $results[] = [
                        'type' => 'payment',
                        'icon' => '💳',
                        'title' => "Payment #{$payment->id}",
                        'subtitle' => $payment->client->name ?? 'No Client',
                        'url' => route('financial.payments.show', $payment->id),
                        'meta' => [
                            'status' => $payment->status,
                            'amount' => '$' . number_format($payment->amount, 2),
                        ],
                    ];
                }
            }
            
            // Search users
            if ($domain === 'all' || $domain === 'users') {
                $users = \App\Models\User::where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();
                
                foreach ($users as $user) {
                    $results[] = [
                        'type' => 'user',
                        'icon' => '👤',
                        'title' => $user->name,
                        'subtitle' => $user->email,
                        'url' => route('users.show', $user->id),
                        'meta' => [
                            'status' => $user->is_active ? 'active' : 'inactive',
                        ],
                    ];
                }
            }
            
            // Search products
            if ($domain === 'all' || $domain === 'products') {
                $products = \App\Models\Product::products()
                    ->where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhere('sku', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();
                
                foreach ($products as $product) {
                    $results[] = [
                        'type' => 'product',
                        'icon' => '📦',
                        'title' => $product->name,
                        'subtitle' => $product->description ?: 'Product - ' . $product->getFormattedPrice(),
                        'url' => route('products.show', $product->id),
                        'meta' => [
                            'status' => $product->is_active ? 'active' : 'inactive',
                            'price' => $product->getFormattedPrice(),
                        ],
                    ];
                }
            }
            
            // Search services
            if ($domain === 'all' || $domain === 'services') {
                $services = \App\Models\Product::services()
                    ->where('company_id', auth()->user()->company_id)
                    ->where(function($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhere('sku', 'like', "%{$query}%");
                    })
                    ->limit(5)
                    ->get();
                
                foreach ($services as $service) {
                    $results[] = [
                        'type' => 'service',
                        'icon' => '🔧',
                        'title' => $service->name,
                        'subtitle' => $service->description ?: 'Service - ' . $service->getFormattedPrice(),
                        'url' => route('services.show', $service->id),
                        'meta' => [
                            'status' => $service->is_active ? 'active' : 'inactive',
                            'price' => $service->getFormattedPrice(),
                        ],
                    ];
                }
            }
            
            // Search knowledge base articles (if exists)
            if ($domain === 'all' || $domain === 'knowledge') {
                try {
                    $articles = \App\Models\KbArticle::where('company_id', auth()->user()->company_id)
                        ->where(function($q) use ($query) {
                            $q->where('title', 'like', "%{$query}%")
                              ->orWhere('content', 'like', "%{$query}%")
                              ->orWhere('tags', 'like', "%{$query}%");
                        })
                        ->limit(5)
                        ->get();
                    
                    foreach ($articles as $article) {
                        $results[] = [
                            'type' => 'article',
                            'icon' => '📚',
                            'title' => $article->title,
                            'subtitle' => Str::limit($article->content, 100),
                            'url' => route('knowledge.articles.show', $article->id),
                            'meta' => [
                                'status' => $article->status,
                            ],
                        ];
                    }
                } catch (\Exception $e) {
                    // Model might not exist
                }
            }
            
            // Search IT Documentation
            if ($domain === 'all' || $domain === 'clients') {
                try {
                    $docs = \App\Domains\Client\Models\ClientITDocumentation::whereHas('client', function($q) {
                            $q->where('company_id', auth()->user()->company_id);
                        })
                        ->where(function($q) use ($query) {
                            $q->where('network_diagram', 'like', "%{$query}%")
                              ->orWhere('server_info', 'like', "%{$query}%")
                              ->orWhere('network_equipment', 'like', "%{$query}%");
                        });
                    
                    if ($clientId) {
                        $docs->where('client_id', $clientId);
                    }
                    
                    $docs = $docs->limit(5)->get();
                    
                    foreach ($docs as $doc) {
                        $results[] = [
                            'type' => 'it-doc',
                            'icon' => '🔧',
                            'title' => "IT Documentation - {$doc->client->name}",
                            'subtitle' => 'Network & Infrastructure Documentation',
                            'url' => route('clients.it-documentation.show', [$doc->client_id, $doc->id]),
                            'meta' => [],
                        ];
                    }
                } catch (\Exception $e) {
                    // Model might not exist
                }
            }
            
            // Search client contacts
            if (($domain === 'all' || $domain === 'clients') && $clientId) {
                try {
                    $contacts = \App\Domains\Client\Models\ClientContact::where('client_id', $clientId)
                        ->where(function($q) use ($query) {
                            $q->where('name', 'like', "%{$query}%")
                              ->orWhere('email', 'like', "%{$query}%")
                              ->orWhere('phone', 'like', "%{$query}%");
                        })
                        ->limit(5)
                        ->get();
                    
                    foreach ($contacts as $contact) {
                        $results[] = [
                            'type' => 'contact',
                            'icon' => '📧',
                            'title' => $contact->name,
                            'subtitle' => "{$contact->email} | {$contact->phone}",
                            'url' => route('clients.contacts.show', [$contact->client_id, $contact->id]),
                            'meta' => [],
                        ];
                    }
                } catch (\Exception $e) {
                    // Model might not exist
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Search error: ' . $e->getMessage());
        }
        
        return response()->json(['results' => $results]);
    }

    /**
     * Handle smart search patterns like #123, @client, $invoice, %project
     */
    protected function handleSmartSearchPatterns(string $query): ?array
    {
        $query = trim($query);
        $companyId = auth()->user()->company_id;
        
        // Pattern: #123 - Find ticket by ID
        if (preg_match('/^#(\d+)$/', $query, $matches)) {
            $ticketId = $matches[1];
            $ticket = \App\Domains\Ticket\Models\Ticket::where('company_id', $companyId)
                ->where('id', $ticketId)
                ->with('client')
                ->first();
                
            if ($ticket) {
                return [[
                    'type' => 'ticket',
                    'icon' => '🎫',
                    'title' => "#{$ticket->id} - {$ticket->title}",
                    'subtitle' => $ticket->client->name ?? 'No Client',
                    'url' => route('tickets.show', $ticket->id),
                    'meta' => [
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                    ],
                ]];
            }
        }
        
        // Pattern: @ClientName - Find client by name
        if (preg_match('/^@(.+)$/', $query, $matches)) {
            $clientName = trim($matches[1]);
            $clients = \App\Models\Client::where('company_id', $companyId)
                ->where('name', 'like', "%{$clientName}%")
                ->limit(3)
                ->get();
                
            $results = [];
            foreach ($clients as $client) {
                $results[] = [
                    'type' => 'client',
                    'icon' => '👥',
                    'title' => $client->name,
                    'subtitle' => $client->email,
                    'url' => route('clients.show', $client->id),
                    'meta' => [
                        'status' => $client->status,
                    ],
                ];
            }
            return $results;
        }
        
        // Pattern: $INV-123 - Find invoice by number
        if (preg_match('/^\$(.+)$/', $query, $matches)) {
            $invoiceNumber = trim($matches[1]);
            $invoice = \App\Models\Invoice::where('company_id', $companyId)
                ->where('invoice_number', 'like', "%{$invoiceNumber}%")
                ->with('client')
                ->first();
                
            if ($invoice) {
                return [[
                    'type' => 'invoice',
                    'icon' => '💰',
                    'title' => "Invoice #{$invoice->invoice_number}",
                    'subtitle' => $invoice->client->name ?? 'No Client',
                    'url' => route('financial.invoices.show', $invoice->id),
                    'meta' => [
                        'status' => $invoice->status,
                        'amount' => '$' . number_format($invoice->total, 2),
                    ],
                ]];
            }
        }
        
        // Pattern: %ProjectName - Find project by name
        if (preg_match('/^%(.+)$/', $query, $matches)) {
            $projectName = trim($matches[1]);
            $projects = \App\Models\Project::where('company_id', $companyId)
                ->where('name', 'like', "%{$projectName}%")
                ->with('client')
                ->limit(3)
                ->get();
                
            $results = [];
            foreach ($projects as $project) {
                $results[] = [
                    'type' => 'project',
                    'icon' => '📊',
                    'title' => $project->name,
                    'subtitle' => $project->client->name ?? 'Internal Project',
                    'url' => route('projects.show', $project->id),
                    'meta' => [
                        'status' => $project->status,
                    ],
                ];
            }
            return $results;
        }
        
        // Pattern: ^AssetTag - Find asset by tag
        if (preg_match('/^\^(.+)$/', $query, $matches)) {
            $assetTag = trim($matches[1]);
            $asset = \App\Models\Asset::where('company_id', $companyId)
                ->where('asset_tag', 'like', "%{$assetTag}%")
                ->with('client')
                ->first();
                
            if ($asset) {
                return [[
                    'type' => 'asset',
                    'icon' => '🖥️',
                    'title' => $asset->name,
                    'subtitle' => "Tag: {$asset->asset_tag} | {$asset->model}",
                    'url' => route('assets.show', $asset->id),
                    'meta' => [
                        'status' => $asset->status,
                    ],
                ]];
            }
        }
        
        return null; // No smart pattern matched
    }

    /**
     * Log command execution for analytics
     */
    private function logCommand($command, $result)
    {
        try {
            // Log to database for analytics
            // This would normally save to a commands_log table
            \Log::info('Command executed', [
                'user_id' => auth()->id(),
                'command' => $command,
                'result' => $result['action'] ?? 'unknown',
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail for logging
        }
    }
}