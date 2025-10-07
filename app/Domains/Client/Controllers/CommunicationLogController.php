<?php

namespace App\Domains\Client\Controllers;

use App\Domains\Core\Services\NavigationService;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CommunicationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class CommunicationLogController extends Controller
{
    protected NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Display a listing of communications for the selected client.
     */
    public function index(Request $request)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Please select a client to view communications.');
        }

        // Check if user wants to include automatic sources
        $includeAutomatic = $request->get('include_automatic', true);

        if ($includeAutomatic) {
            // Get unified communication timeline from multiple sources
            $communications = $this->getUnifiedCommunications($client, $request);
        } else {
            // Get only manual communication logs
            $query = CommunicationLog::where('client_id', $client->id)
                ->with(['user', 'contact'])
                ->orderBy('created_at', 'desc');

            $communications = $this->applyFilters($query, $request)->paginate(20)->withQueryString();
        }

        // Get filter options
        $types = CommunicationLog::TYPES;
        $channels = CommunicationLog::CHANNELS;

        // Navigation context for sidebar
        $activeDomain = 'clients';
        $activeSection = 'communications';

        return view('clients.communications.index', compact(
            'client',
            'communications',
            'types',
            'channels',
            'activeDomain',
            'activeSection'
        ));
    }

    /**
     * Show the form for creating a new communication log entry.
     */
    public function create()
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Please select a client to add a communication.');
        }

        $contacts = $client->contacts()->orderBy('name')->get();
        $types = CommunicationLog::TYPES;
        $channels = CommunicationLog::CHANNELS;

        // Navigation context for sidebar
        $activeDomain = 'clients';
        $activeSection = 'communications';

        return view('clients.communications.create', compact(
            'client',
            'contacts',
            'types',
            'channels',
            'activeDomain',
            'activeSection'
        ));
    }

    /**
     * Store a newly created communication log entry.
     */
    public function store(Request $request)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Please select a client to add a communication.');
        }

        $validated = $request->validate([
            'type' => 'required|string|in:'.implode(',', array_keys(CommunicationLog::TYPES)),
            'channel' => 'required|string|in:'.implode(',', array_keys(CommunicationLog::CHANNELS)),
            'contact_id' => 'nullable|exists:contacts,id',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'notes' => 'required|string',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:today',
        ]);

        CommunicationLog::create([
            'client_id' => $client->id,
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'channel' => $validated['channel'],
            'contact_id' => $validated['contact_id'],
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'subject' => $validated['subject'],
            'notes' => $validated['notes'],
            'follow_up_required' => $validated['follow_up_required'] ?? false,
            'follow_up_date' => $validated['follow_up_date'],
        ]);

        return redirect()->route('clients.communications.index')
            ->with('success', 'Communication log entry created successfully.');
    }

    /**
     * Display the specified communication log entry.
     */
    public function show(CommunicationLog $communication)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client || $communication->client_id !== $client->id) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Communication not found or access denied.');
        }

        $communication->load(['user', 'contact']);

        // Navigation context for sidebar
        $activeDomain = 'clients';
        $activeSection = 'communications';

        return view('clients.communications.show', compact(
            'client',
            'communication',
            'activeDomain',
            'activeSection'
        ));
    }

    /**
     * Show the form for editing the specified communication log entry.
     */
    public function edit(CommunicationLog $communication)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client || $communication->client_id !== $client->id) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Communication not found or access denied.');
        }

        $contacts = $client->contacts()->orderBy('name')->get();
        $types = CommunicationLog::TYPES;
        $channels = CommunicationLog::CHANNELS;

        // Navigation context for sidebar
        $activeDomain = 'clients';
        $activeSection = 'communications';

        return view('clients.communications.edit', compact(
            'client',
            'communication',
            'contacts',
            'types',
            'channels',
            'activeDomain',
            'activeSection'
        ));
    }

    /**
     * Update the specified communication log entry.
     */
    public function update(Request $request, CommunicationLog $communication)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client || $communication->client_id !== $client->id) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Communication not found or access denied.');
        }

        $validated = $request->validate([
            'type' => 'required|string|in:'.implode(',', array_keys(CommunicationLog::TYPES)),
            'channel' => 'required|string|in:'.implode(',', array_keys(CommunicationLog::CHANNELS)),
            'contact_id' => 'nullable|exists:contacts,id',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'notes' => 'required|string',
            'follow_up_required' => 'boolean',
            'follow_up_date' => 'nullable|date|after:today',
        ]);

        $communication->update($validated);

        return redirect()->route('clients.communications.index')
            ->with('success', 'Communication log entry updated successfully.');
    }

    /**
     * Remove the specified communication log entry.
     */
    public function destroy(CommunicationLog $communication)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client || $communication->client_id !== $client->id) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Communication not found or access denied.');
        }

        $communication->delete();

        return redirect()->route('clients.communications.index')
            ->with('success', 'Communication log entry deleted successfully.');
    }

    /**
     * Export communications for the selected client.
     */
    public function export(Request $request)
    {
        $client = $this->navigationService->getSelectedClient();

        if (! $client) {
            return redirect()->route('clients.select-screen')
                ->with('error', 'Please select a client to export communications.');
        }

        $query = CommunicationLog::where('client_id', $client->id)
            ->with(['user', 'contact'])
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $communications = $query->get();

        $csvData = [];
        $csvData[] = [
            'Date',
            'Type',
            'Channel',
            'Contact',
            'Subject',
            'Notes',
            'User',
            'Follow Up Required',
            'Follow Up Date',
        ];

        foreach ($communications as $communication) {
            $csvData[] = [
                $communication->created_at->format('Y-m-d H:i:s'),
                CommunicationLog::TYPES[$communication->type] ?? $communication->type,
                CommunicationLog::CHANNELS[$communication->channel] ?? $communication->channel,
                $communication->contact_name ?: ($communication->contact ? $communication->contact->name : 'N/A'),
                $communication->subject,
                strip_tags($communication->notes),
                $communication->user ? $communication->user->name : 'N/A',
                $communication->follow_up_required ? 'Yes' : 'No',
                $communication->follow_up_date ? $communication->follow_up_date->format('Y-m-d') : '',
            ];
        }

        $filename = "communications_{$client->name}_".date('Y-m-d').'.csv';

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get unified communications from multiple sources.
     */
    protected function getUnifiedCommunications($client, $request)
    {
        $communications = collect();

        $communications = $communications->concat($this->getManualCommunications($client));
        $communications = $communications->concat($this->getTicketCommunications($client));
        $communications = $communications->concat($this->getInvoiceCommunications($client));

        $communications = $this->applyFiltersToCollection($communications, $request);
        $communications = $communications->sortByDesc('created_at');

        return $this->paginateCollection($communications, $request);
    }

    /**
     * Get manual communication logs.
     */
    protected function getManualCommunications($client)
    {
        return CommunicationLog::where('client_id', $client->id)
            ->with(['user', 'contact'])
            ->get()
            ->map(function ($log) {
                return (object) [
                    'id' => $log->id,
                    'type' => 'manual',
                    'source' => 'Communication Log',
                    'communication_type' => $log->type,
                    'channel' => $log->channel,
                    'subject' => $log->subject,
                    'notes' => $log->notes,
                    'contact_name' => $log->contact_display_name,
                    'contact_email' => $log->contact_email,
                    'contact_phone' => $log->contact_phone,
                    'user_name' => $log->user ? $log->user->name : 'System',
                    'follow_up_required' => $log->follow_up_required,
                    'follow_up_date' => $log->follow_up_date,
                    'created_at' => $log->created_at,
                    'route' => route('clients.communications.show', $log),
                    'raw_data' => $log,
                ];
            });
    }

    /**
     * Get ticket communications.
     */
    protected function getTicketCommunications($client)
    {
        if (!class_exists('App\Domains\Ticket\Models\Ticket')) {
            return collect();
        }

        try {
            return \App\Domains\Ticket\Models\Ticket::where('client_id', $client->id)
                ->with(['creator', 'assignee', 'contact'])
                ->get()
                ->map(fn($ticket) => $this->mapTicketToCommunication($ticket));
        } catch (\Exception $e) {
            \Log::warning('Could not load tickets for communication log: '.$e->getMessage());
            return collect();
        }
    }

    /**
     * Map a ticket to a communication object.
     */
    protected function mapTicketToCommunication($ticket)
    {
        return (object) [
            'id' => 'ticket_'.$ticket->id,
            'type' => 'automatic',
            'source' => 'Support Ticket',
            'communication_type' => 'support',
            'channel' => 'ticket_system',
            'subject' => $ticket->title ?? $ticket->subject ?? 'Ticket #'.($ticket->id ?? 'Unknown'),
            'notes' => $ticket->description ?? $ticket->notes ?? 'Support ticket created',
            'contact_name' => $ticket->contact ? $ticket->contact->name : 'N/A',
            'contact_email' => $ticket->contact ? $ticket->contact->email : null,
            'contact_phone' => $ticket->contact ? $ticket->contact->phone : null,
            'user_name' => $ticket->creator ? $ticket->creator->name : ($ticket->assignee ? $ticket->assignee->name : 'System'),
            'follow_up_required' => in_array($ticket->status ?? '', ['open', 'in-progress']),
            'follow_up_date' => null,
            'created_at' => $ticket->created_at,
            'route' => route('tickets.show', $ticket),
            'raw_data' => $ticket,
        ];
    }

    /**
     * Get invoice communications.
     */
    protected function getInvoiceCommunications($client)
    {
        if (!class_exists('App\Models\Invoice')) {
            return collect();
        }

        try {
            return \App\Models\Invoice::where('client_id', $client->id)
                ->whereIn('status', ['sent', 'paid', 'overdue'])
                ->with(['client'])
                ->get()
                ->map(fn($invoice) => $this->mapInvoiceToCommunication($invoice));
        } catch (\Exception $e) {
            \Log::warning('Could not load invoices for communication log: '.$e->getMessage());
            return collect();
        }
    }

    /**
     * Map an invoice to a communication object.
     */
    protected function mapInvoiceToCommunication($invoice)
    {
        return (object) [
            'id' => 'invoice_'.$invoice->id,
            'type' => 'automatic',
            'source' => 'Invoice',
            'communication_type' => 'billing',
            'channel' => 'email',
            'subject' => 'Invoice #'.($invoice->number ?? $invoice->id).' sent',
            'notes' => 'Invoice for $'.number_format($invoice->amount ?? 0, 2).' sent to client',
            'contact_name' => $invoice->client->name ?? 'Billing Contact',
            'contact_email' => $invoice->client->email ?? null,
            'contact_phone' => null,
            'user_name' => 'System',
            'follow_up_required' => in_array($invoice->status, ['sent', 'overdue']),
            'follow_up_date' => $invoice->due_date ?? null,
            'created_at' => $invoice->created_at,
            'route' => route('financial.invoices.show', $invoice),
            'raw_data' => $invoice,
        ];
    }

    /**
     * Paginate a collection.
     */
    protected function paginateCollection($communications, $request)
    {
        $page = $request->get('page', 1);
        $perPage = 20;
        $total = $communications->count();
        $items = $communications->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );
    }

    /**
     * Apply filters to a query builder.
     */
    protected function applyFilters($query, $request)
    {
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        return $query;
    }

    /**
     * Apply filters to a collection.
     */
    protected function applyFiltersToCollection($communications, $request)
    {
        if ($request->filled('search')) {
            $search = strtolower($request->get('search'));
            $communications = $communications->filter(function ($comm) use ($search) {
                return str_contains(strtolower($comm->subject), $search) ||
                       str_contains(strtolower($comm->notes), $search) ||
                       str_contains(strtolower($comm->contact_name), $search);
            });
        }

        if ($request->filled('type')) {
            $communications = $communications->filter(function ($comm) use ($request) {
                return $comm->communication_type === $request->get('type');
            });
        }

        if ($request->filled('channel')) {
            $communications = $communications->filter(function ($comm) use ($request) {
                return $comm->channel === $request->get('channel');
            });
        }

        if ($request->filled('date_from')) {
            $date = \Carbon\Carbon::parse($request->get('date_from'));
            $communications = $communications->filter(function ($comm) use ($date) {
                return $comm->created_at >= $date;
            });
        }

        if ($request->filled('date_to')) {
            $date = \Carbon\Carbon::parse($request->get('date_to'));
            $communications = $communications->filter(function ($comm) use ($date) {
                return $comm->created_at <= $date;
            });
        }

        return $communications;
    }
}
