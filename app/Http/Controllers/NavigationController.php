<?php

namespace App\Http\Controllers;

use App\Services\NavigationService;
use App\Services\CommandPaletteService;
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
        
        // This would normally query from database
        // For now, return mock data
        $recentItems = [
            [
                'type' => 'ticket',
                'id' => 1234,
                'title' => 'Server Down - Acme Corp',
                'url' => route('tickets.show', 1234),
                'icon' => '🎫',
                'timestamp' => now()->subMinutes(5),
            ],
            [
                'type' => 'client',
                'id' => 45,
                'title' => 'Acme Corp',
                'url' => route('clients.show', 45),
                'icon' => '👥',
                'timestamp' => now()->subMinutes(15),
            ],
            [
                'type' => 'invoice',
                'id' => 5678,
                'title' => 'Invoice #5678',
                'url' => route('financial.invoices.show', 5678),
                'icon' => '💰',
                'timestamp' => now()->subHours(1),
            ],
        ];
        
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
                        'url' => route('clients.show', $client->id),
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
                $contracts = \App\Models\Contract::where('company_id', auth()->user()->company_id)
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