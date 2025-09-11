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
        $this->results = [];
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
        if (strlen($value) < 2) {
            $this->results = [];
            return;
        }

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
        $queryLower = strtolower($query);

        // Quick action mappings
        $actionMappings = [
            ['keywords' => ['new ticket', 'create ticket'], 'action' => ['title' => 'Create New Ticket', 'subtitle' => 'Quick Action', 'route_name' => 'tickets.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'action']],
            ['keywords' => ['new client', 'add client'], 'action' => ['title' => 'Add New Client', 'subtitle' => 'Quick Action', 'route_name' => 'clients.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'action']],
            ['keywords' => ['new invoice', 'create invoice'], 'action' => ['title' => 'Create Invoice', 'subtitle' => 'Quick Action', 'route_name' => 'financial.invoices.create', 'route_params' => [], 'icon' => 'plus-circle', 'type' => 'action']],
            ['keywords' => ['email', 'inbox', 'mail'], 'action' => ['title' => 'Email Inbox', 'subtitle' => 'Quick Action', 'route_name' => 'email.inbox.index', 'route_params' => [], 'icon' => 'envelope', 'type' => 'action']],
            ['keywords' => ['compose email', 'send email', 'write email'], 'action' => ['title' => 'Compose Email', 'subtitle' => 'Quick Action', 'route_name' => 'email.compose', 'route_params' => [], 'icon' => 'pencil-square', 'type' => 'action']],
            ['keywords' => ['email accounts', 'email settings'], 'action' => ['title' => 'Email Accounts', 'subtitle' => 'Quick Action', 'route_name' => 'email.accounts.index', 'route_params' => [], 'icon' => 'at-symbol', 'type' => 'action']],
            ['keywords' => ['dashboard'], 'action' => ['title' => 'Go to Dashboard', 'subtitle' => 'Quick Action', 'route_name' => 'dashboard', 'route_params' => [], 'icon' => 'home', 'type' => 'action']],
            ['keywords' => ['settings', 'preferences'], 'action' => ['title' => 'Settings', 'subtitle' => 'Quick Action', 'route_name' => 'settings.index', 'route_params' => [], 'icon' => 'cog', 'type' => 'action']],
            ['keywords' => ['reports'], 'action' => ['title' => 'View Reports', 'subtitle' => 'Quick Action', 'route_name' => 'reports.index', 'route_params' => [], 'icon' => 'chart-bar', 'type' => 'action']],
        ];

        foreach ($actionMappings as $mapping) {
            foreach ($mapping['keywords'] as $keyword) {
                if (str_contains($queryLower, $keyword)) {
                    $actions[] = $mapping['action'];
                    break;
                }
            }
        }

        return $actions;
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
            
            // Don't close modal here - let redirect handle it
            // Use redirectRoute with navigate for SPA-like behavior
            if (isset($result['route_name'])) {
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