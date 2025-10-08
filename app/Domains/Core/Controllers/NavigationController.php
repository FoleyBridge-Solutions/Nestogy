<?php

namespace App\Domains\Core\Controllers;

use App\Domains\Core\Services\CommandPaletteService;
use App\Domains\Core\Services\NavigationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                    'subtitle' => 'Scheduled: '.$ticket->scheduled_at->format('M j, g:i A'),
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
                $invoiceNumber = $invoice->prefix ? $invoice->prefix.$invoice->number : $invoice->number;
                $recentItems[] = [
                    'type' => 'invoice',
                    'id' => $invoice->id,
                    'title' => "Invoice #{$invoiceNumber}",
                    'subtitle' => ($invoice->client ? $invoice->client->name.' - ' : '').'$'.number_format($invoice->amount, 2),
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
                $quoteNumber = $quote->prefix ? $quote->prefix.$quote->number : $quote->number;
                $recentItems[] = [
                    'type' => 'quote',
                    'id' => $quote->id,
                    'title' => "Quote #{$quoteNumber}",
                    'subtitle' => ($quote->client ? $quote->client->name.' - ' : '').'$'.number_format($quote->total, 2),
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
            usort($recentItems, function ($a, $b) {
                return $b['timestamp']->timestamp - $a['timestamp']->timestamp;
            });

        } catch (\Exception $e) {
            \Log::error('Error fetching recent items: '.$e->getMessage());
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

        $smartResult = $this->handleSmartSearchPatterns($query);
        if ($smartResult) {
            return response()->json(['results' => $smartResult]);
        }

        $results = $this->performDomainSearch($query, $domain, $clientId);

        return response()->json(['results' => $results]);
    }

    protected function performDomainSearch(string $query, string $domain, $clientId): array
    {
        $results = [];

        try {
            $searchMethods = [
                'tickets' => 'searchTickets',
                'clients' => 'searchClients',
                'financial' => ['searchInvoices', 'searchQuotes', 'searchContracts', 'searchExpenses', 'searchPayments'],
                'assets' => 'searchAssets',
                'projects' => 'searchProjects',
                'users' => 'searchUsers',
                'products' => 'searchProducts',
                'services' => 'searchServices',
                'knowledge' => 'searchKnowledgeArticles',
            ];

            foreach ($searchMethods as $searchDomain => $methods) {
                if ($domain !== 'all' && $domain !== $searchDomain) {
                    continue;
                }

                $methods = is_array($methods) ? $methods : [$methods];
                foreach ($methods as $method) {
                    $results = array_merge($results, $this->$method($query, $clientId));
                }
            }

            if ($domain === 'all' || $domain === 'clients') {
                $results = array_merge(
                    $results,
                    $this->searchITDocumentation($query, $clientId),
                    $this->searchClientContacts($query, $clientId)
                );
            }
        } catch (\Exception $e) {
            \Log::error('Search error: '.$e->getMessage());
        }

        return $results;
    }

    protected function searchTickets(string $query, $clientId): array
    {
        $tickets = \App\Domains\Ticket\Models\Ticket::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $tickets->map(fn($ticket) => [
            'type' => 'ticket',
            'icon' => '🎫',
            'title' => "#{$ticket->id} - {$ticket->title}",
            'subtitle' => $ticket->client->name ?? 'No Client',
            'url' => route('tickets.show', $ticket->id),
            'meta' => [
                'status' => $ticket->status,
                'priority' => $ticket->priority,
            ],
        ])->toArray();
    }

    protected function searchClients(string $query, $clientId): array
    {
        $clients = \App\Models\Client::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        return $clients->map(fn($client) => [
            'type' => 'client',
            'icon' => '👥',
            'title' => $client->name,
            'subtitle' => $client->email,
            'url' => route('clients.index'),
            'meta' => ['status' => $client->status],
        ])->toArray();
    }

    protected function searchInvoices(string $query, $clientId): array
    {
        $invoices = \App\Models\Invoice::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('invoice_number', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $invoices->map(fn($invoice) => [
            'type' => 'invoice',
            'icon' => '💰',
            'title' => "Invoice #{$invoice->invoice_number}",
            'subtitle' => $invoice->client->name ?? 'No Client',
            'url' => route('financial.invoices.show', $invoice->id),
            'meta' => [
                'status' => $invoice->status,
                'amount' => '$'.number_format($invoice->total, 2),
            ],
        ])->toArray();
    }

    protected function searchQuotes(string $query, $clientId): array
    {
        $quotes = \App\Models\Quote::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('quote_number', 'like', "%{$query}%")
                    ->orWhere('id', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $quotes->map(fn($quote) => [
            'type' => 'quote',
            'icon' => '📝',
            'title' => "Quote #{$quote->quote_number}",
            'subtitle' => $quote->client->name ?? 'No Client',
            'url' => route('financial.quotes.show', $quote->id),
            'meta' => [
                'status' => $quote->status,
                'amount' => '$'.number_format($quote->total, 2),
            ],
        ])->toArray();
    }

    protected function searchAssets(string $query, $clientId): array
    {
        $assets = \App\Models\Asset::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('asset_tag', 'like', "%{$query}%")
                    ->orWhere('serial_number', 'like', "%{$query}%")
                    ->orWhere('model', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $assets->map(fn($asset) => [
            'type' => 'asset',
            'icon' => '🖥️',
            'title' => $asset->name,
            'subtitle' => "Tag: {$asset->asset_tag} | {$asset->model}",
            'url' => route('assets.show', $asset->id),
            'meta' => ['status' => $asset->status],
        ])->toArray();
    }

    protected function searchProjects(string $query, $clientId): array
    {
        $projects = \App\Models\Project::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('project_code', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $projects->map(fn($project) => [
            'type' => 'project',
            'icon' => '📊',
            'title' => $project->name,
            'subtitle' => $project->client->name ?? 'Internal Project',
            'url' => route('projects.show', $project->id),
            'meta' => ['status' => $project->status],
        ])->toArray();
    }

    protected function searchContracts(string $query, $clientId): array
    {
        $contracts = \App\Domains\Contract\Models\Contract::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('contract_number', 'like', "%{$query}%")
                    ->orWhere('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $contracts->map(fn($contract) => [
            'type' => 'contract',
            'icon' => '📄',
            'title' => $contract->title ?? "Contract #{$contract->contract_number}",
            'subtitle' => $contract->client->name ?? 'No Client',
            'url' => route('financial.contracts.show', $contract->id),
            'meta' => ['status' => $contract->status],
        ])->toArray();
    }

    protected function searchExpenses(string $query, $clientId): array
    {
        $expenses = \App\Models\Expense::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                    ->orWhere('vendor', 'like', "%{$query}%")
                    ->orWhere('reference', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        return $expenses->map(fn($expense) => [
            'type' => 'expense',
            'icon' => '💸',
            'title' => $expense->description,
            'subtitle' => $expense->vendor ?? 'No Vendor',
            'url' => route('financial.expenses.show', $expense->id),
            'meta' => [
                'status' => $expense->status,
                'amount' => '$'.number_format($expense->amount, 2),
            ],
        ])->toArray();
    }

    protected function searchPayments(string $query, $clientId): array
    {
        $payments = \App\Models\Payment::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('reference', 'like', "%{$query}%")
                    ->orWhere('notes', 'like', "%{$query}%");
            })
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->limit(5)
            ->get();

        return $payments->map(fn($payment) => [
            'type' => 'payment',
            'icon' => '💳',
            'title' => "Payment #{$payment->id}",
            'subtitle' => $payment->client->name ?? 'No Client',
            'url' => route('financial.payments.show', $payment->id),
            'meta' => [
                'status' => $payment->status,
                'amount' => '$'.number_format($payment->amount, 2),
            ],
        ])->toArray();
    }

    protected function searchUsers(string $query, $clientId): array
    {
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        return $users->map(fn($user) => [
            'type' => 'user',
            'icon' => '👤',
            'title' => $user->name,
            'subtitle' => $user->email,
            'url' => route('users.show', $user->id),
            'meta' => ['status' => $user->is_active ? 'active' : 'inactive'],
        ])->toArray();
    }

    protected function searchProducts(string $query, $clientId): array
    {
        $products = \App\Models\Product::products()
            ->where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        return $products->map(fn($product) => [
            'type' => 'product',
            'icon' => '📦',
            'title' => $product->name,
            'subtitle' => $product->description ?: 'Product - '.$product->getFormattedPrice(),
            'url' => route('products.show', $product->id),
            'meta' => [
                'status' => $product->is_active ? 'active' : 'inactive',
                'price' => $product->getFormattedPrice(),
            ],
        ])->toArray();
    }

    protected function searchServices(string $query, $clientId): array
    {
        $services = \App\Models\Product::services()
            ->where('company_id', auth()->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        return $services->map(fn($service) => [
            'type' => 'service',
            'icon' => '🔧',
            'title' => $service->name,
            'subtitle' => $service->description ?: 'Service - '.$service->getFormattedPrice(),
            'url' => route('services.show', $service->id),
            'meta' => [
                'status' => $service->is_active ? 'active' : 'inactive',
                'price' => $service->getFormattedPrice(),
            ],
        ])->toArray();
    }

    protected function searchKnowledgeArticles(string $query, $clientId): array
    {
        try {
            $articles = \App\Models\KbArticle::where('company_id', auth()->user()->company_id)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%")
                        ->orWhere('tags', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get();

            return $articles->map(fn($article) => [
                'type' => 'article',
                'icon' => '📚',
                'title' => $article->title,
                'subtitle' => Str::limit($article->content, 100),
                'url' => route('knowledge.articles.show', $article->id),
                'meta' => ['status' => $article->status],
            ])->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function searchITDocumentation(string $query, $clientId): array
    {
        try {
            $docs = \App\Domains\Client\Models\ClientITDocumentation::whereHas('client', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
                ->where(function ($q) use ($query) {
                    $q->where('network_diagram', 'like', "%{$query}%")
                        ->orWhere('server_info', 'like', "%{$query}%")
                        ->orWhere('network_equipment', 'like', "%{$query}%");
                })
                ->when($clientId, fn($q) => $q->where('client_id', $clientId))
                ->limit(5)
                ->get();

            return $docs->map(fn($doc) => [
                'type' => 'it-doc',
                'icon' => '🔧',
                'title' => "IT Documentation - {$doc->client->name}",
                'subtitle' => 'Network & Infrastructure Documentation',
                'url' => route('clients.it-documentation.show', [$doc->client_id, $doc->id]),
                'meta' => [],
            ])->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function searchClientContacts(string $query, $clientId): array
    {
        if (!$clientId) {
            return [];
        }

        try {
            $contacts = \App\Domains\Client\Models\ClientContact::where('client_id', $clientId)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get();

            return $contacts->map(fn($contact) => [
                'type' => 'contact',
                'icon' => '📧',
                'title' => $contact->name,
                'subtitle' => "{$contact->email} | {$contact->phone}",
                'url' => route('clients.contacts.show', [$contact->client_id, $contact->id]),
                'meta' => [],
            ])->toArray();
        } catch (\Exception $e) {
            return [];
        }
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
                        'amount' => '$'.number_format($invoice->total, 2),
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
