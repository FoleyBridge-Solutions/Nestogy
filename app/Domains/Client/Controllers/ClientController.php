<?php

namespace App\Domains\Client\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Asset;
use App\Models\Tag;
use App\Domains\Client\Requests\StoreClientRequest;
use App\Domains\Client\Requests\UpdateClientRequest;
use App\Domains\Client\Services\ClientService;
use App\Imports\ClientsImport;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends BaseController
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        parent::__construct();
        $this->clientService = $clientService;
    }

    protected function initializeController(): void
    {
        $this->modelClass = Client::class;
        $this->serviceClass = ClientService::class;
        $this->resourceName = 'clients';
        $this->viewPrefix = 'clients';
        $this->eagerLoadRelations = ['primaryContact', 'primaryLocation'];
    }

    protected function getFilters(Request $request): array
    {
        return $request->only(['search', 'type', 'status']);
    }

    protected function applyCustomFilters($query, Request $request)
    {
        // Only show customers, not leads
        $query->where('lead', false);

        return $query;
    }

    protected function prepareStoreData(array $data): array
    {
        $data = parent::prepareStoreData($data);
        
        // Automatically select the newly created client
        if (isset($data['client_id'])) {
            \App\Services\NavigationService::setSelectedClient($data['client_id']);
        }
        
        return $data;
    }

    /**
     * Display a listing of clients (custom implementation for DataTables)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check if this is a DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getClientsDataTable($request);
        }

        $query = Client::with(['primaryContact', 'primaryLocation'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', false); // Only show customers, not leads

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('website', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $clients = $query->orderBy('accessed_at', 'desc')
                        ->orderBy('name')
                        ->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($clients);
        }

        return view('clients.index-simple', compact('clients'));
    }

    /**
     * Handle DataTables AJAX request for clients data
     */
    private function getClientsDataTable(Request $request)
    {
        $user = Auth::user();
        
        // Get DataTables parameters
        $draw = $request->get('draw', 1);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir = $request->get('order')[0]['dir'] ?? 'desc';

        // Build query
        $query = Client::with(['primaryContact', 'primaryLocation'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', false);

        // Apply search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('primaryContact', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('primaryLocation', function ($q) use ($search) {
                      $q->where('address', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                  });
            });
        }

        // Get total count
        $totalData = Client::where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', false)
            ->count();

        // Get filtered count
        $totalFiltered = $query->count();

        // Apply ordering
        $columns = ['accessed_at', 'name', 'type', 'created_at'];
        $orderColumnName = $columns[$orderColumn] ?? 'accessed_at';
        $query->orderBy($orderColumnName, $orderDir);

        // Apply pagination
        $clients = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $clients->map(function ($client) {
            $contact = $client->primaryContact;
            $location = $client->primaryLocation;
            
            return [
                'DT_RowId' => 'row_' . $client->id,
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email ?? ($contact->email ?? ''),
                'phone' => $contact ? $this->formatPhoneNumber($contact->phone) : '',
                'type' => $client->type,
                'is_active' => $client->status === 'active',
                'tags' => [], // $client->tags->pluck('name')->toArray(),
                'location' => $location ? "{$location->address}, {$location->city}, {$location->state} {$location->zip}" : '',
                'contact_name' => $contact->name ?? '',
                'contact_phone' => $contact ? $this->formatPhoneNumber($contact->phone) : '',
                'contact_email' => $contact->email ?? '',
                'created_at' => $client->created_at->format('Y-m-d'),
                'accessed_at' => $client->accessed_at ? $client->accessed_at->format('Y-m-d H:i:s') : '',
                'balance' => $client->getBalance(),
                'monthly_recurring' => $client->getMonthlyRecurring(),
                'lead' => $client->lead,
                'actions' => view('clients.partials.actions', compact('client'))->render()
            ];
        });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new client
     */
    public function create()
    {
        return view('clients.create');
    }

    /**
     * Store a newly created client
     */
    public function store(StoreClientRequest $request)
    {
        try {
            $clientData = $this->clientService->createClient($request->validated());
            
            // Automatically select the newly created client
            \App\Services\NavigationService::setSelectedClient($clientData['client_id']);
            
            Log::info('Client created and selected', [
                'client_id' => $clientData['client_id'],
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client created successfully',
                    'client' => $clientData
                ], 201);
            }

            return redirect()
                ->route('clients.show', $clientData['client_id'])
                ->with('success', "Client <strong>{$clientData['name']}</strong> created and selected successfully");

        } catch (\Exception $e) {
            Log::error('Client creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create client'
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create client');
        }
    }

    /**
     * Display the specified client
     */
    public function show(Request $request, Client $client)
    {
        $this->authorize('view', $client);

        // Update client access timestamp
        $this->clientService->updateClientAccess($client);

        $client->load([
            'contacts' => function ($query) {
                $query->whereNull('archived_at')->orderBy('primary', 'desc')->orderBy('name');
            },
            'locations' => function ($query) {
                $query->whereNull('archived_at')->orderBy('primary', 'desc')->orderBy('name');
            },
            'assets' => function ($query) {
                $query->whereNull('archived_at')->orderBy('name');
            },
            'tickets' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'invoices' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'projects' => function ($query) {
                $query->whereNull('archived_at')->orderBy('created_at', 'desc')->limit(5);
            },
            'recurringInvoices' => function ($query) {
                $query->where('status', true)->orderBy('next_date');
            }
        ]);

        // Get client statistics
        $stats = $this->clientService->getClientStats($client);

        // Get recent activity
        $recentActivity = $this->clientService->getClientActivity($client, 20);

        // Get upcoming renewals - commented out until domains and certificates models are created
        $upcomingRenewals = [
            'domains' => collect(), // $client->domains()->where('expire', '<=', now()->addDays(30))->get(),
            'certificates' => collect(), // $client->certificates()->where('expire', '<=', now()->addDays(30))->get(),
        ];

        if ($request->wantsJson()) {
            // For API requests, return simplified client data for the search component
            return response()->json([
                'id' => $client->id,
                'name' => $client->name,
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'stats' => $stats,
                'recentActivity' => $recentActivity,
                'upcomingRenewals' => $upcomingRenewals
            ]);
        }

        return view('clients.show', compact('client', 'stats', 'recentActivity', 'upcomingRenewals'));
    }

    /**
     * Handle DataTables AJAX request
     */
    public function data(Request $request)
    {
        return $this->getClientsDataTable($request);
    }

    /**
     * Show the form for editing the specified client
     */
    public function edit(Client $client)
    {
        $this->authorize('update', $client);
        
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified client
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);

        try {
            $updatedClient = $this->clientService->updateClient($client, $request->validated());
            
            Log::info('Client updated', [
                'client_id' => $client->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client updated successfully',
                    'client' => $updatedClient
                ]);
            }

            return redirect()
                ->route('clients.show', $client)
                ->with('success', "Client <strong>{$updatedClient->name}</strong> updated successfully");

        } catch (\Exception $e) {
            Log::error('Client update failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update client'
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to update client');
        }
    }

    /**
     * Archive the specified client
     */
    public function archive(Request $request, Client $client)
    {
        $this->authorize('delete', $client);

        try {
            $this->clientService->archiveClient($client);
            
            Log::info('Client archived', [
                'client_id' => $client->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client archived successfully'
                ]);
            }

            return redirect()
                ->route('clients.index')
                ->with('success', "Client <strong>{$client->name}</strong> archived successfully");

        } catch (\Exception $e) {
            Log::error('Client archive failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to archive client'
                ], 500);
            }

            return back()->with('error', 'Failed to archive client');
        }
    }

    /**
     * Restore archived client
     */
    public function restore(Request $request, $id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        $this->authorize('restore', $client);

        try {
            $this->clientService->restoreClient($client);
            
            Log::info('Client restored', [
                'client_id' => $client->id,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client restored successfully'
                ]);
            }

            return redirect()
                ->route('clients.show', $client)
                ->with('success', "Client <strong>{$client->name}</strong> restored successfully");

        } catch (\Exception $e) {
            Log::error('Client restore failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('error', 'Failed to restore client');
        }
    }

    /**
     * Permanently delete the specified client
     */
    public function destroy(Request $request, $id)
    {
        $client = Client::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $client);

        try {
            $clientName = $client->name;
            $this->clientService->deleteClient($client);
            
            Log::warning('Client permanently deleted', [
                'client_id' => $client->id,
                'client_name' => $clientName,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client permanently deleted'
                ]);
            }

            return redirect()
                ->route('clients.index')
                ->with('success', "Client <strong>{$clientName}</strong> permanently deleted");

        } catch (\Exception $e) {
            Log::error('Client deletion failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete client'
                ], 500);
            }

            return back()->with('error', 'Failed to delete client');
        }
    }

    /**
     * Export clients to CSV
     */
    public function exportCsv(Request $request)
    {
        $user = Auth::user();
        
        $clients = Client::with(['primaryContact', 'primaryLocation'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $filename = 'clients-' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($clients) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Client Name',
                'Industry',
                'Referral',
                'Website',
                'Primary Address',
                'Contact Name',
                'Contact Phone',
                'Extension',
                'Contact Mobile',
                'Contact Email',
                'Creation Date'
            ]);

            // CSV data
            foreach ($clients as $client) {
                $contact = $client->primaryContact;
                $location = $client->primaryLocation;
                
                fputcsv($file, [
                    $client->name,
                    $client->type,
                    $client->referral,
                    $client->website,
                    $location ? "{$location->address} {$location->city} {$location->state} {$location->zip}" : '',
                    $contact->name ?? '',
                    $contact ? $this->formatPhoneNumber($contact->phone) : '',
                    $contact->extension ?? '',
                    $contact ? $this->formatPhoneNumber($contact->mobile) : '',
                    $contact->email ?? '',
                    $client->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        Log::info('Clients exported to CSV', [
            'count' => $clients->count(),
            'user_id' => Auth::id()
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update client notes via AJAX
     */
    public function updateNotes(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $request->validate([
            'notes' => 'nullable|string'
        ]);

        try {
            $client->update(['notes' => $request->get('notes')]);
            
            Log::info('Client notes updated', [
                'client_id' => $client->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notes updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Client notes update failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update notes'
            ], 500);
        }
    }

    /**
     * Get active clients for dropdowns
     */
    public function getActiveClients(Request $request)
    {
        $user = Auth::user();
        
        $query = Client::where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('status', 'active')
            ->where('lead', false);

        // Apply search if provided
        if ($request->filled('q')) {
            $search = $request->get('q');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('company_name', 'ilike', "%{$search}%");
            });
        }

        $clients = $query->orderBy('accessed_at', 'desc')
                        ->orderBy('name')
                        ->limit(50)
                        ->get(['id', 'name', 'company_name']);

        return response()->json($clients);
    }

    /**
     * Display leads
     */
    public function leads(Request $request)
    {
        $user = Auth::user();
        
        $query = Client::with(['primaryContact'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', true);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('referral', 'like', "%{$search}%");
            });
        }

        $leads = $query->orderBy('created_at', 'desc')->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($leads);
        }

        return view('clients.index-simple', [
            'clients' => $leads,
            'isLeadsView' => true
        ]);
    }

    /**
     * Convert lead to customer
     */
    public function convertLead(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        if (!$client->isLead()) {
            return back()->with('error', 'This client is already a customer');
        }

        try {
            $client->convertToCustomer();
            
            // Automatically select the converted client
            \App\Services\NavigationService::setSelectedClient($client->id);
            
            Log::info('Lead converted to customer and selected', [
                'client_id' => $client->id,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lead converted to customer successfully'
                ]);
            }

            return redirect()
                ->route('clients.show', $client)
                ->with('success', "Lead <strong>{$client->name}</strong> converted to customer and selected successfully");

        } catch (\Exception $e) {
            Log::error('Lead conversion failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to convert lead'
                ], 500);
            }

            return back()->with('error', 'Failed to convert lead');
        }
    }

    /**
     * Import clients from CSV
     */
    public function import(Request $request)
    {
        $this->authorize('create', Client::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        try {
            $import = new ClientsImport();
            Excel::import($import, $request->file('file'));

            $rowCount = $import->getRowCount();

            Log::info('Clients imported from CSV', [
                'count' => $rowCount,
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$rowCount} clients imported successfully"
                ]);
            }

            return redirect()
                ->route('clients.index')
                ->with('success', "{$rowCount} clients imported successfully");

        } catch (\Exception $e) {
            Log::error('Client import failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to import clients: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to import clients: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        $this->authorize('create', Client::class);
        
        return view('clients.import');
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="clients-import-template.csv"',
        ];

        $columns = [
            'name',
            'company_name',
            'type',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'zip_code',
            'country',
            'website',
            'referral',
            'rate',
            'currency_code',
            'net_terms',
            'tax_id_number',
            'notes',
            'contact_name',
            'contact_email',
            'contact_phone',
            'contact_mobile',
            'location_name',
            'location_address',
            'location_city',
            'location_state',
            'location_zip',
            'location_phone'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add sample row
            fputcsv($file, [
                'Acme Corporation',
                'Acme Corp',
                'Business',
                'contact@acmecorp.com',
                '555-1234',
                '123 Main St',
                'New York',
                'NY',
                '10001',
                'US',
                'https://acmecorp.com',
                'Website',
                '150',
                'USD',
                '30',
                '12-3456789',
                'Sample client',
                'John Doe',
                'john@acmecorp.com',
                '555-1234',
                '555-5678',
                'Main Office',
                '123 Main St',
                'New York',
                'NY',
                '10001',
                '555-1234'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Manage client tags
     */
    public function tags(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        if ($request->isMethod('post')) {
            $request->validate([
                'tags' => 'array',
                'tags.*' => 'exists:tags,id'
            ]);

            $client->syncTags($request->get('tags', []));

            Log::info('Client tags updated', [
                'client_id' => $client->id,
                'tags' => $request->get('tags', []),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tags updated successfully'
                ]);
            }

            return back()->with('success', 'Tags updated successfully');
        }

        $allTags = Tag::where('company_id', Auth::user()->company_id)
                     ->clientTags()
                     ->orderBy('name')
                     ->get();

        return view('clients.tags', compact('client', 'allTags'));
    }

    /**
     * Select client for session
     */
    public function selectClient(Request $request, Client $client)
    {
        $this->authorize('view', $client);
        
        try {
            \App\Services\NavigationService::setSelectedClient($client->id);
            
            Log::info('Client selected for session', [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client selected successfully',
                    'client' => $client
                ]);
            }

            // Handle return_to parameter for route preservation
            $returnTo = $request->input('return_to');
            if ($returnTo && $this->isValidReturnUrl($returnTo)) {
                // Update client ID in URL if it's a client-specific route
                $updatedUrl = $this->updateClientIdInUrl($returnTo, $client->id);
                return redirect($updatedUrl)->with('success', "Now working with <strong>{$client->name}</strong>");
            }

            return redirect()
                ->route('clients.show', $client)
                ->with('success', "Now working with <strong>{$client->name}</strong>");

        } catch (\Exception $e) {
            Log::error('Client selection failed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to select client'
                ], 500);
            }

            return back()->with('error', 'Failed to select client');
        }
    }

    /**
     * Clear client selection
     */
    public function clearSelection(Request $request)
    {
        try {
            \App\Services\NavigationService::clearSelectedClient();
            
            Log::info('Client selection cleared', [
                'user_id' => Auth::id(),
                'ip' => $request->ip()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Client selection cleared'
                ]);
            }

            return redirect()
                ->route('clients.index')
                ->with('success', 'Client selection cleared');

        } catch (\Exception $e) {
            Log::error('Failed to clear client selection', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to clear client selection'
                ], 500);
            }

            return back()->with('error', 'Failed to clear client selection');
        }
    }

    /**
     * Validate that the return URL is safe and from our application
     */
    private function isValidReturnUrl($url)
    {
        // Parse the URL to get the path
        $parsedUrl = parse_url($url);
        
        // Must have a valid path
        if (!isset($parsedUrl['path'])) {
            return false;
        }
        
        // Must be from the same host (if host is specified)
        if (isset($parsedUrl['host'])) {
            $currentHost = request()->getHost();
            if ($parsedUrl['host'] !== $currentHost) {
                return false;
            }
        }
        
        // Basic security check - no external redirects
        $path = $parsedUrl['path'];
        if (str_starts_with($path, '//') || str_contains($path, '://')) {
            return false;
        }
        
        return true;
    }

    /**
     * Update client ID in URL if it's a client-specific route
     */
    private function updateClientIdInUrl($url, $newClientId)
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // Pattern for client-specific routes: /clients/{id}/something
        $pattern = '/\/clients\/(\d+)(\/.*)?$/';
        
        if (preg_match($pattern, $path, $matches)) {
            $oldClientId = $matches[1];
            $subPath = $matches[2] ?? '';
            
            // Replace with new client ID
            $newPath = "/clients/{$newClientId}{$subPath}";
            
            // Rebuild the URL
            $newUrl = $newPath;
            if (isset($parsedUrl['query'])) {
                $newUrl .= '?' . $parsedUrl['query'];
            }
            if (isset($parsedUrl['fragment'])) {
                $newUrl .= '#' . $parsedUrl['fragment'];
            }
            
            return $newUrl;
        }
        
        // If it's not a client-specific route, return as-is
        return $url;
    }

    /**
     * Mark a client as recently accessed
     */
    public function markAsAccessed(Request $request, Client $client)
    {
        $this->authorize('view', $client);
        
        try {
            $client->markAsAccessed();
            
            return response()->json([
                'success' => true,
                'message' => 'Client marked as accessed'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark client as accessed', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark client as accessed'
            ], 500);
        }
    }

    /**
     * Validate that multiple clients exist and belong to the current company
     */
    public function validateBatch(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);
        
        $ids = $request->input('ids', []);
        $companyId = Auth::user()->company_id;
        
        // Get valid client IDs that exist and belong to this company
        $validIds = Client::where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->pluck('id')
            ->toArray();
        
        Log::info('Batch client validation', [
            'requested' => count($ids),
            'valid' => count($validIds),
            'user_id' => Auth::id()
        ]);
        
        return response()->json($validIds);
    }

    /**
     * Legacy client switch page - redirects to clients list
     */
    public function switch(Request $request)
    {
        // Legacy route - redirect to clients list with a message
        return redirect()
            ->route('clients.index')
            ->with('info', 'Client switching is now available in the navigation bar dropdown');
    }

    /**
     * Format phone number
     */
    private function formatPhoneNumber($phone)
    {
        if (!$phone) return '';
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 4)
            );
        }
        
        return $phone;
    }


    /**
     * Show the leads import form
     */
    public function leadsImportForm(Request $request)
    {
        $this->authorize('create', Client::class);
        
        return view('clients.leads-import');
    }

    /**
     * Import leads from CSV file
     */
    public function leadsImport(Request $request)
    {
        $this->authorize('create', Client::class);
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'default_status' => 'required|string|in:active,inactive',
            'default_type' => 'nullable|string|in:prospect,customer,partner',
            'skip_duplicates' => 'boolean',
            'import_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $file = $request->file('csv_file');
            $handle = fopen($file->getPathname(), 'r');
            
            if (!$handle) {
                throw new \Exception('Could not open CSV file');
            }

            // Read header row
            $headers = fgetcsv($handle);
            if (!$headers) {
                throw new \Exception('Invalid CSV file - no headers found');
            }

            // Create column mapping
            $columnMap = $this->createLeadColumnMapping($headers);
            
            $imported = 0;
            $skipped = 0;
            $errors = [];
            $details = [];

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Map CSV data to lead data
                    $leadData = $this->mapCsvRowToLeadData($row, $headers, $columnMap);
                    
                    // Add default values
                    $leadData['lead'] = true;
                    $leadData['status'] = $request->input('default_status', 'active');
                    $leadData['type'] = $request->input('default_type', 'prospect');
                    $leadData['company_id'] = auth()->user()->company_id;
                    
                    if ($request->filled('import_notes')) {
                        $leadData['notes'] = $request->input('import_notes');
                    }

                    // Check for duplicates if requested
                    if ($request->boolean('skip_duplicates') && !empty($leadData['email'])) {
                        $existing = Client::where('company_id', auth()->user()->company_id)
                            ->where('email', $leadData['email'])
                            ->first();
                        
                        if ($existing) {
                            $skipped++;
                            $details[] = "Skipped: {$leadData['email']} (already exists)";
                            continue;
                        }
                    }

                    // Create the lead as a client
                    $client = Client::create($leadData);
                    $imported++;
                    $details[] = "Imported: {$client->name} ({$client->email})";

                } catch (\Exception $e) {
                    $errors[] = "Row error: " . $e->getMessage();
                    $details[] = "Error processing row: " . implode(', ', array_slice($row, 0, 3));
                }
            }

            fclose($handle);

            // Prepare summary message
            $message = "Import completed: {$imported} leads imported";
            if ($skipped > 0) {
                $message .= ", {$skipped} duplicates skipped";
            }
            if (count($errors) > 0) {
                $message .= ", " . count($errors) . " errors";
            }

            return redirect()
                ->route('clients.leads')
                ->with('success', $message)
                ->with('import_details', $details);

        } catch (\Exception $e) {
            Log::error('Lead CSV import failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV template for lead import
     */
    public function leadsImportTemplate()
    {
        $filename = 'leads_import_template.csv';
        
        $headers = [
            'Last', 'First', 'Middle', 'Company Name',
            'Company Address Line 1', 'Company Address Line 2',
            'City', 'State', 'ZIP', 'Email', 'Website', 'Phone'
        ];
        
        $sampleData = [
            'Smith', 'Jane', 'A', 'Tech Solutions LLC',
            '456 Innovation Ave', 'Floor 2', 'Austin', 'TX', '78701',
            'jane.smith@techsolutions.com', 'https://techsolutions.com', '(555) 987-6543'
        ];

        return response()->streamDownload(function () use ($headers, $sampleData) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            fputcsv($output, $sampleData);
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Create column mapping for lead CSV import
     */
    private function createLeadColumnMapping(array $headers): array
    {
        $mapping = [];
        
        foreach ($headers as $index => $header) {
            $normalizedHeader = strtolower(trim($header));
            
            // Map various column name variations to our expected fields
            if (in_array($normalizedHeader, ['last', 'last name', 'lastname', 'surname'])) {
                $mapping['last_name'] = $index;
            } elseif (in_array($normalizedHeader, ['first', 'first name', 'firstname', 'given name'])) {
                $mapping['first_name'] = $index;
            } elseif (in_array($normalizedHeader, ['middle', 'middle name', 'middlename', 'middle initial'])) {
                $mapping['middle_name'] = $index;
            } elseif (in_array($normalizedHeader, ['company', 'company name', 'organization', 'business name'])) {
                $mapping['company_name'] = $index;
            } elseif (in_array($normalizedHeader, ['address', 'address line 1', 'address1', 'company address line 1'])) {
                $mapping['address_line_1'] = $index;
            } elseif (in_array($normalizedHeader, ['address line 2', 'address2', 'company address line 2'])) {
                $mapping['address_line_2'] = $index;
            } elseif (in_array($normalizedHeader, ['city', 'town'])) {
                $mapping['city'] = $index;
            } elseif (in_array($normalizedHeader, ['state', 'province', 'region'])) {
                $mapping['state'] = $index;
            } elseif (in_array($normalizedHeader, ['zip', 'postal code', 'zipcode', 'postcode'])) {
                $mapping['postal_code'] = $index;
            } elseif (in_array($normalizedHeader, ['email', 'email address', 'e-mail'])) {
                $mapping['email'] = $index;
            } elseif (in_array($normalizedHeader, ['website', 'url', 'web site', 'homepage'])) {
                $mapping['website'] = $index;
            } elseif (in_array($normalizedHeader, ['phone', 'phone number', 'telephone', 'mobile', 'cell'])) {
                $mapping['phone'] = $index;
            }
        }
        
        return $mapping;
    }

    /**
     * Map CSV row data to lead data array
     */
    private function mapCsvRowToLeadData(array $row, array $headers, array $columnMap): array
    {
        $data = [];
        
        // Build name from parts
        $nameParts = [];
        if (isset($columnMap['first_name']) && !empty($row[$columnMap['first_name']])) {
            $nameParts[] = trim($row[$columnMap['first_name']]);
        }
        if (isset($columnMap['middle_name']) && !empty($row[$columnMap['middle_name']])) {
            $nameParts[] = trim($row[$columnMap['middle_name']]);
        }
        if (isset($columnMap['last_name']) && !empty($row[$columnMap['last_name']])) {
            $nameParts[] = trim($row[$columnMap['last_name']]);
        }
        
        if (empty($nameParts)) {
            throw new \Exception('Name is required (First/Last name columns)');
        }
        
        $data['name'] = implode(' ', $nameParts);
        
        // Map other fields
        if (isset($columnMap['company_name']) && !empty($row[$columnMap['company_name']])) {
            $data['company_name'] = trim($row[$columnMap['company_name']]);
        }
        
        if (isset($columnMap['email']) && !empty($row[$columnMap['email']])) {
            $email = trim($row[$columnMap['email']]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Invalid email format: {$email}");
            }
            $data['email'] = $email;
        }
        
        if (isset($columnMap['phone']) && !empty($row[$columnMap['phone']])) {
            $data['phone'] = trim($row[$columnMap['phone']]);
        }
        
        if (isset($columnMap['website']) && !empty($row[$columnMap['website']])) {
            $data['website'] = trim($row[$columnMap['website']]);
        }
        
        // Build address
        $addressParts = [];
        if (isset($columnMap['address_line_1']) && !empty($row[$columnMap['address_line_1']])) {
            $addressParts[] = trim($row[$columnMap['address_line_1']]);
        }
        if (isset($columnMap['address_line_2']) && !empty($row[$columnMap['address_line_2']])) {
            $addressParts[] = trim($row[$columnMap['address_line_2']]);
        }
        if (!empty($addressParts)) {
            $data['address'] = implode(', ', $addressParts);
        }
        
        if (isset($columnMap['city']) && !empty($row[$columnMap['city']])) {
            $data['city'] = trim($row[$columnMap['city']]);
        }
        
        if (isset($columnMap['state']) && !empty($row[$columnMap['state']])) {
            $data['state'] = trim($row[$columnMap['state']]);
        }
        
        if (isset($columnMap['postal_code']) && !empty($row[$columnMap['postal_code']])) {
            $data['postal_code'] = trim($row[$columnMap['postal_code']]);
        }
        
        return $data;
    }
}