<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\Invoice;
use App\Models\Asset;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Services\ClientService;
use App\Imports\ClientsImport;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    /**
     * Display a listing of clients
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check if this is a DataTables AJAX request
        if ($request->ajax() && $request->has('draw')) {
            return $this->getClientsDataTable($request);
        }

        $query = Client::with(['primaryContact', 'primaryLocation', 'tags'])
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

        return view('clients.index', compact('clients'));
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
        $query = Client::with(['primaryContact', 'primaryLocation', 'tags'])
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
                'tags' => $client->tags->pluck('name')->toArray(),
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
            
            Log::info('Client created', [
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
                ->with('success', "Client <strong>{$clientData['name']}</strong> created successfully");

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
            },
            'tags'
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
            return response()->json([
                'client' => $client,
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
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
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
        
        $query = Client::with(['primaryContact', 'tags'])
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

        return view('clients.leads', compact('leads'));
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
            
            Log::info('Lead converted to customer', [
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
                ->with('success', "Lead <strong>{$client->name}</strong> converted to customer successfully");

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
}