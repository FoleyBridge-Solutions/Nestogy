<?php

namespace App\Domains\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Asset\Models\Asset;
use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Invoice;
use App\Domains\Project\Models\Project;
use App\Domains\Ticket\Models\Ticket;
use App\Domains\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    private const CLIENT_RELATION_FIELDS = 'client:id,name';

    public function global(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);
        $companyId = Auth::user()->company_id;

        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'query' => $query,
            ]);
        }

        $results = [];

        // Search Clients
        $clients = Client::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        foreach ($clients as $client) {
            $results[] = [
                'type' => 'client',
                'id' => $client->id,
                'title' => $client->name,
                'subtitle' => $client->email,
                'url' => route('clients.show', $client->id),
            ];
        }

        // Search Tickets
        $tickets = Ticket::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('ticket_number', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'ticket_number', 'subject', 'status']);

        foreach ($tickets as $ticket) {
            $results[] = [
                'type' => 'ticket',
                'id' => $ticket->id,
                'title' => "#{$ticket->ticket_number} - {$ticket->subject}",
                'subtitle' => ucfirst($ticket->status),
                'url' => route('tickets.show', $ticket->id),
            ];
        }

        // Search Assets
        $assets = Asset::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('serial_number', 'like', "%{$query}%")
                    ->orWhere('asset_tag', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'serial_number', 'type']);

        foreach ($assets as $asset) {
            $results[] = [
                'type' => 'asset',
                'id' => $asset->id,
                'title' => $asset->name,
                'subtitle' => "{$asset->type} - {$asset->serial_number}",
                'url' => route('assets.show', $asset->id),
            ];
        }

        return response()->json([
            'results' => $results,
            'query' => $query,
            'total' => count($results),
        ]);
    }

    public function query(Request $request)
    {
        return $this->global($request);
    }

    /**
     * Command palette search endpoint
     */
    public function commandPalette(Request $request)
    {
        $query = $request->input('query', '');
        $companyId = Auth::user() ? Auth::user()->company_id : null;

        if (! $companyId || empty($query)) {
            return response()->json([
                'suggestions' => $this->getDefaultCommands(),
                'results' => [],
            ]);
        }

        $suggestions = $this->filterMatchingCommands($query);
        $results = $this->searchAllEntities($query, $companyId);

        return response()->json([
            'suggestions' => $suggestions,
            'results' => $results,
            'query' => $query,
        ]);
    }

    private function filterMatchingCommands(string $query): array
    {
        $commands = $this->getDefaultCommands();
        $suggestions = [];

        foreach ($commands as $command) {
            if (stripos($command['command'], $query) !== false ||
                stripos($command['description'], $query) !== false) {
                $suggestions[] = $command;
            }
        }

        return array_slice($suggestions, 0, 3);
    }

    private function searchAllEntities(string $query, int $companyId): array
    {
        return array_merge(
            $this->searchClients($query, $companyId),
            $this->searchTickets($query, $companyId),
            $this->searchAssets($query, $companyId),
            $this->searchInvoices($query, $companyId),
            $this->searchProjects($query, $companyId)
        );
    }

    private function searchClients(string $query, int $companyId): array
    {
        $clients = Client::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'name', 'email', 'company_name']);

        $results = [];
        foreach ($clients as $client) {
            $results[] = [
                'type' => 'client',
                'id' => $client->id,
                'title' => $client->name,
                'subtitle' => $client->company_name ?: $client->email,
                'icon' => 'user',
                'url' => route('clients.show', $client->id),
            ];
        }

        return $results;
    }

    private function searchTickets(string $query, int $companyId): array
    {
        $tickets = Ticket::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('ticket_number', 'like', "%{$query}%");
            })
            ->with(self::CLIENT_RELATION_FIELDS)
            ->limit(5)
            ->get(['id', 'ticket_number', 'subject', 'status', 'priority', 'client_id']);

        $results = [];
        foreach ($tickets as $ticket) {
            $results[] = [
                'type' => 'ticket',
                'id' => $ticket->id,
                'title' => "#{$ticket->ticket_number}: {$ticket->subject}",
                'subtitle' => $ticket->client ? $ticket->client->name : 'No client',
                'icon' => 'ticket',
                'url' => route('tickets.show', $ticket->id),
                'meta' => [
                    'status' => ucfirst($ticket->status),
                    'priority' => $ticket->priority,
                ],
            ];
        }

        return $results;
    }

    private function searchAssets(string $query, int $companyId): array
    {
        $assets = Asset::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('serial_number', 'like', "%{$query}%")
                    ->orWhere('asset_tag', 'like', "%{$query}%");
            })
            ->with(self::CLIENT_RELATION_FIELDS)
            ->limit(5)
            ->get(['id', 'name', 'serial_number', 'type', 'client_id']);

        $results = [];
        foreach ($assets as $asset) {
            $results[] = [
                'type' => 'asset',
                'id' => $asset->id,
                'title' => $asset->name,
                'subtitle' => $asset->client ? $asset->client->name : $asset->type,
                'icon' => 'computer-desktop',
                'url' => route('assets.show', $asset->id),
            ];
        }

        return $results;
    }

    private function searchInvoices(string $query, int $companyId): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%");
            })
            ->with(self::CLIENT_RELATION_FIELDS)
            ->limit(3)
            ->get(['id', 'number', 'total', 'status', 'client_id']);

        $results = [];
        foreach ($invoices as $invoice) {
            $results[] = [
                'type' => 'invoice',
                'id' => $invoice->id,
                'title' => "Invoice #{$invoice->number}",
                'subtitle' => $invoice->client ? $invoice->client->name : 'No client',
                'icon' => 'document-text',
                'url' => route('invoices.show', $invoice->id),
                'meta' => [
                    'status' => ucfirst($invoice->status),
                    'amount' => '$'.number_format($invoice->total, 2),
                ],
            ];
        }

        return $results;
    }

    private function searchProjects(string $query, int $companyId): array
    {
        $projects = Project::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->with(self::CLIENT_RELATION_FIELDS)
            ->limit(3)
            ->get(['id', 'name', 'status', 'client_id']);

        $results = [];
        foreach ($projects as $project) {
            $results[] = [
                'type' => 'project',
                'id' => $project->id,
                'title' => $project->name,
                'subtitle' => $project->client ? $project->client->name : 'Internal Project',
                'icon' => 'briefcase',
                'url' => route('projects.show', $project->id),
                'meta' => [
                    'status' => ucfirst($project->status),
                ],
            ];
        }

        return $results;
    }

    /**
     * Get default command suggestions
     */
    private function getDefaultCommands()
    {
        return [
            [
                'command' => 'Dashboard',
                'description' => 'Go to dashboard',
                'icon' => 'home',
                'shortcut' => '⌘D',
                'url' => '/dashboard',
            ],
            [
                'command' => 'Clients',
                'description' => 'View all clients',
                'icon' => 'user-group',
                'shortcut' => '⌘⇧C',
                'url' => '/clients',
            ],
            [
                'command' => 'New Client',
                'description' => 'Create a new client',
                'icon' => 'user-plus',
                'url' => '/clients/create',
            ],
            [
                'command' => 'Tickets',
                'description' => 'View support tickets',
                'icon' => 'ticket',
                'shortcut' => '⌘T',
                'url' => '/tickets',
            ],
            [
                'command' => 'New Ticket',
                'description' => 'Create support ticket',
                'icon' => 'plus-circle',
                'url' => '/tickets/create',
            ],
            [
                'command' => 'Assets',
                'description' => 'Manage assets',
                'icon' => 'computer-desktop',
                'shortcut' => '⌘A',
                'url' => '/assets',
            ],
            [
                'command' => 'Invoices',
                'description' => 'View invoices',
                'icon' => 'document-text',
                'shortcut' => '⌘I',
                'url' => '/financial/invoices',
            ],
            [
                'command' => 'Projects',
                'description' => 'View projects',
                'icon' => 'briefcase',
                'shortcut' => '⌘P',
                'url' => '/projects',
            ],
            [
                'command' => 'Reports',
                'description' => 'View reports',
                'icon' => 'chart-bar',
                'url' => '/reports',
            ],
            [
                'command' => 'Settings',
                'description' => 'Application settings',
                'icon' => 'cog-6-tooth',
                'shortcut' => '⌘,',
                'url' => '/settings',
            ],
            [
                'command' => 'Profile',
                'description' => 'Your profile',
                'icon' => 'user',
                'url' => '/users/profile',
            ],
            [
                'command' => 'Help',
                'description' => 'Help and documentation',
                'icon' => 'question-mark-circle',
                'shortcut' => '⌘?',
                'url' => '/help',
            ],
        ];
    }

    public function suggestions(Request $request)
    {
        $type = $request->get('type', 'all');
        $query = $request->get('q', '');
        $limit = 5;
        $companyId = Auth::user()->company_id;

        $suggestions = [];

        if ($type === 'all' || $type === 'client') {
            $clients = Client::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('name', 'like', "{$query}%")
                ->limit($limit)
                ->pluck('name');

            foreach ($clients as $name) {
                $suggestions[] = [
                    'text' => $name,
                    'type' => 'client',
                ];
            }
        }

        if ($type === 'all' || $type === 'ticket') {
            $tickets = Ticket::where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('subject', 'like', "{$query}%")
                ->limit($limit)
                ->pluck('subject');

            foreach ($tickets as $subject) {
                $suggestions[] = [
                    'text' => $subject,
                    'type' => 'ticket',
                ];
            }
        }

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    public function clients(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 20);
        $companyId = Auth::user()->company_id;

        // Build query
        $clientsQuery = Client::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where('lead', false); // Only show customers, not leads

        // Apply search filter if provided
        if (! empty($query)) {
            $clientsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('company_name', 'like', "%{$query}%");
            });
        }

        // Get clients with only necessary fields for performance
        $clients = $clientsQuery
            ->select('id', 'name', 'company_name', 'email', 'phone', 'status', 'accessed_at')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query.'%']) // Prioritize exact matches
            ->orderBy('name')
            ->limit($limit)
            ->get();

        // Format response for frontend
        $formattedClients = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'initials' => $this->getInitials($client->name),
            ];
        });

        return response()->json($formattedClients);
    }

    /**
     * Get initials from a name
     */
    private function getInitials($name)
    {
        if (empty($name)) {
            return '?';
        }

        $words = explode(' ', $name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            if (! empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }

        return $initials ?: '?';
    }

    public function tickets(Request $request)
    {
        $query = $request->get('q', '');
        $companyId = Auth::user()->company_id;

        $tickets = Ticket::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('ticket_number', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json($tickets);
    }

    public function assets(Request $request)
    {
        $query = $request->get('q', '');
        $companyId = Auth::user()->company_id;

        $assets = Asset::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('serial_number', 'like', "%{$query}%")
                    ->orWhere('asset_tag', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json($assets);
    }

    public function invoices(Request $request)
    {
        $query = $request->get('q', '');
        $companyId = Auth::user()->company_id;

        $invoices = Invoice::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('number', 'like', "%{$query}%")
                    ->orWhereHas('client', function ($c) use ($query) {
                        $c->where('name', 'like', "%{$query}%");
                    });
            })
            ->limit(20)
            ->with('client')
            ->get();

        return response()->json($invoices);
    }

    public function users(Request $request)
    {
        $query = $request->get('q', '');
        $companyId = Auth::user()->company_id;

        $users = User::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json($users);
    }

    public function projects(Request $request)
    {
        $query = $request->get('q', '');
        $companyId = Auth::user()->company_id;

        $projects = Project::where('company_id', $companyId)
            ->whereNull('archived_at')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        return response()->json($projects);
    }
}
