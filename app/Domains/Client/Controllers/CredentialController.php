<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CredentialController extends Controller
{
    /**
     * Display a listing of all credentials (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientCredential::with(['client', 'creator'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply type filter
        if ($type = $request->get('credential_type')) {
            $query->where('credential_type', $type);
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            switch ($status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->inactive();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'expiring_soon':
                    $query->expiringSoon();
                    break;
            }
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply environment filter
        if ($environment = $request->get('environment')) {
            $query->where('environment', $environment);
        }

        // Apply shared filter
        if ($request->has('is_shared')) {
            $query->where('is_shared', $request->get('is_shared') === '1');
        }

        $credentials = $query->orderBy('name')
                            ->paginate(20)
                            ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $credentialTypes = ClientCredential::getCredentialTypes();
        $environments = ClientCredential::getEnvironments();

        return view('clients.credentials.index', compact('credentials', 'clients', 'credentialTypes', 'environments'));
    }

    /**
     * Show the form for creating a new credential
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $credentialTypes = ClientCredential::getCredentialTypes();
        $environments = ClientCredential::getEnvironments();
        $accessLevels = ClientCredential::getAccessLevels();

        return view('clients.credentials.create', compact('clients', 'selectedClientId', 'credentialTypes', 'environments', 'accessLevels'));
    }

    /**
     * Store a newly created credential
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credential_type' => 'required|in:' . implode(',', array_keys(ClientCredential::getCredentialTypes())),
            'service_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'url' => 'nullable|url|max:500',
            'port' => 'nullable|integer|min:1|max:65535',
            'database_name' => 'nullable|string|max:255',
            'connection_string' => 'nullable|string|max:1000',
            'api_key' => 'nullable|string|max:1000',
            'secret_key' => 'nullable|string|max:1000',
            'certificate' => 'nullable|string',
            'private_key' => 'nullable|string',
            'public_key' => 'nullable|string',
            'token' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'boolean',
            'is_shared' => 'boolean',
            'environment' => 'nullable|in:' . implode(',', array_keys(ClientCredential::getEnvironments())),
            'access_level' => 'nullable|in:' . implode(',', array_keys(ClientCredential::getAccessLevels())),
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $credential = new ClientCredential($request->all());
        $credential->company_id = auth()->user()->company_id;
        $credential->created_by = auth()->id();
        $credential->save();

        return redirect()->route('clients.credentials.standalone.index')
                        ->with('success', 'Credential created successfully.');
    }

    /**
     * Display the specified credential
     */
    public function show(ClientCredential $credential)
    {
        $this->authorize('view', $credential);

        $credential->load('client', 'creator');

        return view('clients.credentials.show', compact('credential'));
    }

    /**
     * Show the form for editing the specified credential
     */
    public function edit(ClientCredential $credential)
    {
        $this->authorize('update', $credential);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $credentialTypes = ClientCredential::getCredentialTypes();
        $environments = ClientCredential::getEnvironments();
        $accessLevels = ClientCredential::getAccessLevels();

        return view('clients.credentials.edit', compact('credential', 'clients', 'credentialTypes', 'environments', 'accessLevels'));
    }

    /**
     * Update the specified credential
     */
    public function update(Request $request, ClientCredential $credential)
    {
        $this->authorize('update', $credential);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credential_type' => 'required|in:' . implode(',', array_keys(ClientCredential::getCredentialTypes())),
            'service_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:1000',
            'email' => 'nullable|email|max:255',
            'url' => 'nullable|url|max:500',
            'port' => 'nullable|integer|min:1|max:65535',
            'database_name' => 'nullable|string|max:255',
            'connection_string' => 'nullable|string|max:1000',
            'api_key' => 'nullable|string|max:1000',
            'secret_key' => 'nullable|string|max:1000',
            'certificate' => 'nullable|string',
            'private_key' => 'nullable|string',
            'public_key' => 'nullable|string',
            'token' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'boolean',
            'is_shared' => 'boolean',
            'environment' => 'nullable|in:' . implode(',', array_keys(ClientCredential::getEnvironments())),
            'access_level' => 'nullable|in:' . implode(',', array_keys(ClientCredential::getAccessLevels())),
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $credential->fill($request->all());
        $credential->save();

        return redirect()->route('clients.credentials.standalone.index')
                        ->with('success', 'Credential updated successfully.');
    }

    /**
     * Remove the specified credential
     */
    public function destroy(ClientCredential $credential)
    {
        $this->authorize('delete', $credential);

        $credential->delete();

        return redirect()->route('clients.credentials.standalone.index')
                        ->with('success', 'Credential deleted successfully.');
    }

    /**
     * Show decrypted credential details (AJAX)
     */
    public function showDecrypted(ClientCredential $credential)
    {
        $this->authorize('view', $credential);

        // Update last accessed timestamp
        $credential->updateLastAccessed();

        return response()->json([
            'password' => $credential->decrypted_password,
            'api_key' => $credential->decrypted_api_key,
            'secret_key' => $credential->decrypted_secret_key,
        ]);
    }

    /**
     * Export credentials to CSV
     */
    public function export(Request $request)
    {
        $query = ClientCredential::with(['client', 'creator'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index (excluding sensitive fields)
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('credential_type')) {
            $query->where('credential_type', $type);
        }

        if ($status = $request->get('status')) {
            switch ($status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->inactive();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'expiring_soon':
                    $query->expiringSoon();
                    break;
            }
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($environment = $request->get('environment')) {
            $query->where('environment', $environment);
        }

        $credentials = $query->orderBy('name')->get();

        $filename = 'credentials_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($credentials) {
            $file = fopen('php://output', 'w');
            
            // CSV headers (excluding sensitive data)
            fputcsv($file, [
                'Credential Name',
                'Client Name',
                'Type',
                'Service Name',
                'Username',
                'Email',
                'URL',
                'Environment',
                'Status',
                'Shared',
                'Access Level',
                'Expires At',
                'Last Accessed',
                'Created By'
            ]);

            // CSV data
            foreach ($credentials as $credential) {
                fputcsv($file, [
                    $credential->name,
                    $credential->client->display_name,
                    $credential->credential_type,
                    $credential->service_name,
                    $credential->username,
                    $credential->email,
                    $credential->url,
                    $credential->environment,
                    $credential->status_label,
                    $credential->is_shared ? 'Yes' : 'No',
                    $credential->access_level,
                    $credential->expires_at ? $credential->expires_at->format('Y-m-d') : '',
                    $credential->last_accessed_at ? $credential->last_accessed_at->format('Y-m-d H:i:s') : '',
                    $credential->creator ? $credential->creator->name : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}