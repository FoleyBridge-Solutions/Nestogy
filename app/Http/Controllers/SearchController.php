<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Asset;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Project;

class SearchController extends Controller
{
    public function global(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);
        $companyId = Auth::user()->company_id;
        
        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'query' => $query
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
                'url' => route('clients.show', $client->id)
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
                'url' => route('tickets.show', $ticket->id)
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
                'url' => route('assets.show', $asset->id)
            ];
        }

        return response()->json([
            'results' => $results,
            'query' => $query,
            'total' => count($results)
        ]);
    }

    public function query(Request $request)
    {
        return $this->global($request);
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
                    'type' => 'client'
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
                    'type' => 'ticket'
                ];
            }
        }

        return response()->json([
            'suggestions' => $suggestions
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
        if (!empty($query)) {
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
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query . '%']) // Prioritize exact matches
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
        if (empty($name)) return '?';
        
        $words = explode(' ', $name);
        $initials = '';
        
        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
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