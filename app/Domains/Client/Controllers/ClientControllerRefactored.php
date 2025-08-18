<?php

namespace App\Domains\Client\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\BaseController;
use App\Models\Client;
use App\Models\Tag;
use App\Domains\Client\Requests\StoreClientRequest;
use App\Domains\Client\Requests\UpdateClientRequest;
use App\Domains\Client\Services\ClientService;
use App\Imports\ClientsImport;
use Maatwebsite\Excel\Facades\Excel;

class ClientControllerRefactored extends BaseController
{
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
        // Only show customers, not leads by default
        $query->where('lead', false);
        return $query;
    }

    protected function getShowViewData(\Illuminate\Database\Eloquent\Model $model): array
    {
        $client = $model;
        
        // Update client access timestamp
        app($this->serviceClass)->updateClientAccess($client);

        // Load additional relationships for show view
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

        $service = app($this->serviceClass);
        $stats = $service->getClientStats($client);
        $recentActivity = $service->getClientActivity($client, 20);
        
        // Get upcoming renewals
        $upcomingRenewals = [
            'domains' => collect(),
            'certificates' => collect(),
        ];

        return [
            'client' => $client,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'upcomingRenewals' => $upcomingRenewals
        ];
    }

    protected function prepareStoreData(array $data): array
    {
        $data = parent::prepareStoreData($data);
        return $data;
    }

    // Custom methods that don't fit the base CRUD pattern

    /**
     * Handle DataTables AJAX request for clients data
     */
    public function data(Request $request)
    {
        return $this->getClientsDataTable($request);
    }

    /**
     * Display leads
     */
    public function leads(Request $request)
    {
        $filters = $this->getFilters($request);
        $query = $this->buildIndexQuery($request);
        $query->where('lead', true); // Override to show leads

        $leads = $query->paginate(25);

        if ($request->wantsJson()) {
            return response()->json($leads);
        }

        return view('clients.index-simple', ['clients' => $leads]);
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
            
            $this->logActivity($client, 'converted_lead', $request);

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
            $this->logError('lead_conversion', $e, $request, $client);

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
                'Client Name', 'Industry', 'Referral', 'Website', 'Primary Address',
                'Contact Name', 'Contact Phone', 'Extension', 'Contact Mobile',
                'Contact Email', 'Creation Date'
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
     * Select client for session
     */
    public function selectClient(Request $request, Client $client)
    {
        $this->authorize('view', $client);
        
        try {
            \App\Services\NavigationService::setSelectedClient($client->id);
            
            $this->logActivity($client, 'selected', $request);

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
                $updatedUrl = $this->updateClientIdInUrl($returnTo, $client->id);
                return redirect($updatedUrl)->with('success', "Now working with <strong>{$client->name}</strong>");
            }

            return redirect()
                ->route('clients.show', $client)
                ->with('success', "Now working with <strong>{$client->name}</strong>");

        } catch (\Exception $e) {
            $this->logError('client_selection', $e, $request, $client);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to select client'
                ], 500);
            }

            return back()->with('error', 'Failed to select client');
        }
    }

    // Private helper methods remain unchanged
    private function getClientsDataTable(Request $request)
    {
        // Implementation stays the same as original
        // This is DataTables specific and doesn't fit base patterns
        $user = Auth::user();
        
        $draw = $request->get('draw', 1);
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';

        $query = Client::with(['primaryContact', 'primaryLocation'])
            ->where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', false);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $totalData = Client::where('company_id', $user->company_id)
            ->whereNull('archived_at')
            ->where('lead', false)
            ->count();

        $totalFiltered = $query->count();
        $clients = $query->skip($start)->take($length)->get();

        $data = $clients->map(function ($client) {
            return [
                'DT_RowId' => 'row_' . $client->id,
                'id' => $client->id,
                'name' => $client->name,
                'type' => $client->type,
                'status' => $client->status,
                'created_at' => $client->created_at->format('Y-m-d'),
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

    private function isValidReturnUrl($url)
    {
        $parsedUrl = parse_url($url);
        
        if (!isset($parsedUrl['path'])) {
            return false;
        }
        
        if (isset($parsedUrl['host'])) {
            $currentHost = request()->getHost();
            if ($parsedUrl['host'] !== $currentHost) {
                return false;
            }
        }
        
        $path = $parsedUrl['path'];
        if (str_starts_with($path, '//') || str_contains($path, '://')) {
            return false;
        }
        
        return true;
    }

    private function updateClientIdInUrl($url, $newClientId)
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        $pattern = '/\/clients\/(\d+)(\/.*)?$/';
        
        if (preg_match($pattern, $path, $matches)) {
            $subPath = $matches[2] ?? '';
            $newPath = "/clients/{$newClientId}{$subPath}";
            
            $newUrl = $newPath;
            if (isset($parsedUrl['query'])) {
                $newUrl .= '?' . $parsedUrl['query'];
            }
            if (isset($parsedUrl['fragment'])) {
                $newUrl .= '#' . $parsedUrl['fragment'];
            }
            
            return $newUrl;
        }
        
        return $url;
    }
}