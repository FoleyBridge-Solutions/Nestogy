<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    /**
     * Display a listing of all domains (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientDomain::with(['client'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain_name', 'like', "%{$search}%")
                  ->orWhere('registrar', 'like', "%{$search}%")
                  ->orWhere('dns_provider', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply registrar filter
        if ($registrar = $request->get('registrar')) {
            $query->where('registrar', $registrar);
        }

        // Apply TLD filter
        if ($tld = $request->get('tld')) {
            $query->where('tld', $tld);
        }

        // Apply expiry filters
        if ($request->get('expired_only')) {
            $query->expired();
        } elseif ($request->get('expiring_soon')) {
            $query->expiringSoon($request->get('expiring_days', 30));
        }

        // Apply auto-renewal filter
        if ($request->has('auto_renewal')) {
            $query->where('auto_renewal', $request->get('auto_renewal') === '1');
        }

        $domains = $query->orderBy('expires_at', 'asc')
                         ->paginate(20)
                         ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $statuses = ClientDomain::getStatuses();
        $registrars = ClientDomain::getRegistrars();
        
        // Get unique TLDs from existing domains
        $tlds = ClientDomain::where('company_id', auth()->user()->company_id)
                           ->whereNotNull('tld')
                           ->distinct()
                           ->pluck('tld')
                           ->filter()
                           ->sort()
                           ->values();

        return view('clients.domains.index', compact('domains', 'clients', 'statuses', 'registrars', 'tlds'));
    }

    /**
     * Show the form for creating a new domain
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $statuses = ClientDomain::getStatuses();
        $registrars = ClientDomain::getRegistrars();
        $dnsProviders = ClientDomain::getDnsProviders();
        $commonTlds = ClientDomain::getCommonTlds();

        return view('clients.domains.create', compact('clients', 'selectedClientId', 'statuses', 'registrars', 'dnsProviders', 'commonTlds'));
    }

    /**
     * Store a newly created domain
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
            'domain_name' => 'required|string|max:255',
            'tld' => 'required|string|max:20',
            'registrar' => 'nullable|in:' . implode(',', array_keys(ClientDomain::getRegistrars())),
            'registrar_account' => 'nullable|string|max:255',
            'registrar_url' => 'nullable|url',
            'nameservers' => 'nullable|string',
            'dns_provider' => 'nullable|in:' . implode(',', array_keys(ClientDomain::getDnsProviders())),
            'dns_account' => 'nullable|string|max:255',
            'registered_at' => 'nullable|date|before_or_equal:today',
            'expires_at' => 'required|date|after:registered_at',
            'renewal_date' => 'nullable|date|before:expires_at',
            'auto_renewal' => 'boolean',
            'days_before_expiry_alert' => 'nullable|integer|min:1|max:365',
            'status' => 'required|in:' . implode(',', array_keys(ClientDomain::getStatuses())),
            'privacy_protection' => 'boolean',
            'lock_status' => 'boolean',
            'whois_guard' => 'boolean',
            'transfer_lock' => 'boolean',
            'purchase_cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'transfer_auth_code' => 'nullable|string|max:255',
            'dns_records_count' => 'nullable|integer|min:0',
            'subdomains_count' => 'nullable|integer|min:0',
            'email_forwards_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process nameservers
        $nameservers = [];
        if ($request->nameservers) {
            $nameservers = array_map('trim', explode(',', $request->nameservers));
            $nameservers = array_filter($nameservers);
        }

        $domain = new ClientDomain([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'description' => $request->description,
            'domain_name' => strtolower($request->domain_name),
            'tld' => strtolower($request->tld),
            'registrar' => $request->registrar,
            'registrar_account' => $request->registrar_account,
            'registrar_url' => $request->registrar_url,
            'nameservers' => $nameservers,
            'dns_provider' => $request->dns_provider,
            'dns_account' => $request->dns_account,
            'registered_at' => $request->registered_at,
            'expires_at' => $request->expires_at,
            'renewal_date' => $request->renewal_date,
            'auto_renewal' => $request->has('auto_renewal'),
            'days_before_expiry_alert' => $request->days_before_expiry_alert ?: 30,
            'status' => $request->status,
            'privacy_protection' => $request->has('privacy_protection'),
            'lock_status' => $request->has('lock_status'),
            'whois_guard' => $request->has('whois_guard'),
            'transfer_lock' => $request->has('transfer_lock'),
            'purchase_cost' => $request->purchase_cost,
            'renewal_cost' => $request->renewal_cost,
            'transfer_auth_code' => $request->transfer_auth_code,
            'dns_records_count' => $request->dns_records_count ?: 0,
            'subdomains_count' => $request->subdomains_count ?: 0,
            'email_forwards_count' => $request->email_forwards_count ?: 0,
            'notes' => $request->notes,
        ]);
        
        $domain->company_id = auth()->user()->company_id;
        $domain->save();

        return redirect()->route('clients.domains.standalone.index')
                        ->with('success', 'Domain created successfully.');
    }

    /**
     * Display the specified domain
     */
    public function show(ClientDomain $domain)
    {
        $this->authorize('view', $domain);

        $domain->load('client');
        
        // Update access timestamp
        $domain->update(['accessed_at' => now()]);

        return view('clients.domains.show', compact('domain'));
    }

    /**
     * Show the form for editing the specified domain
     */
    public function edit(ClientDomain $domain)
    {
        $this->authorize('update', $domain);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $statuses = ClientDomain::getStatuses();
        $registrars = ClientDomain::getRegistrars();
        $dnsProviders = ClientDomain::getDnsProviders();
        $commonTlds = ClientDomain::getCommonTlds();

        return view('clients.domains.edit', compact('domain', 'clients', 'statuses', 'registrars', 'dnsProviders', 'commonTlds'));
    }

    /**
     * Update the specified domain
     */
    public function update(Request $request, ClientDomain $domain)
    {
        $this->authorize('update', $domain);

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
            'domain_name' => 'required|string|max:255',
            'tld' => 'required|string|max:20',
            'registrar' => 'nullable|in:' . implode(',', array_keys(ClientDomain::getRegistrars())),
            'registrar_account' => 'nullable|string|max:255',
            'registrar_url' => 'nullable|url',
            'nameservers' => 'nullable|string',
            'dns_provider' => 'nullable|in:' . implode(',', array_keys(ClientDomain::getDnsProviders())),
            'dns_account' => 'nullable|string|max:255',
            'registered_at' => 'nullable|date|before_or_equal:today',
            'expires_at' => 'required|date|after:registered_at',
            'renewal_date' => 'nullable|date|before:expires_at',
            'auto_renewal' => 'boolean',
            'days_before_expiry_alert' => 'nullable|integer|min:1|max:365',
            'status' => 'required|in:' . implode(',', array_keys(ClientDomain::getStatuses())),
            'privacy_protection' => 'boolean',
            'lock_status' => 'boolean',
            'whois_guard' => 'boolean',
            'transfer_lock' => 'boolean',
            'purchase_cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'transfer_auth_code' => 'nullable|string|max:255',
            'dns_records_count' => 'nullable|integer|min:0',
            'subdomains_count' => 'nullable|integer|min:0',
            'email_forwards_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process nameservers
        $nameservers = [];
        if ($request->nameservers) {
            $nameservers = array_map('trim', explode(',', $request->nameservers));
            $nameservers = array_filter($nameservers);
        }

        $domain->fill([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'description' => $request->description,
            'domain_name' => strtolower($request->domain_name),
            'tld' => strtolower($request->tld),
            'registrar' => $request->registrar,
            'registrar_account' => $request->registrar_account,
            'registrar_url' => $request->registrar_url,
            'nameservers' => $nameservers,
            'dns_provider' => $request->dns_provider,
            'dns_account' => $request->dns_account,
            'registered_at' => $request->registered_at,
            'expires_at' => $request->expires_at,
            'renewal_date' => $request->renewal_date,
            'auto_renewal' => $request->has('auto_renewal'),
            'days_before_expiry_alert' => $request->days_before_expiry_alert ?: 30,
            'status' => $request->status,
            'privacy_protection' => $request->has('privacy_protection'),
            'lock_status' => $request->has('lock_status'),
            'whois_guard' => $request->has('whois_guard'),
            'transfer_lock' => $request->has('transfer_lock'),
            'purchase_cost' => $request->purchase_cost,
            'renewal_cost' => $request->renewal_cost,
            'transfer_auth_code' => $request->transfer_auth_code,
            'dns_records_count' => $request->dns_records_count ?: 0,
            'subdomains_count' => $request->subdomains_count ?: 0,
            'email_forwards_count' => $request->email_forwards_count ?: 0,
            'notes' => $request->notes,
        ]);

        $domain->save();

        return redirect()->route('clients.domains.standalone.index')
                        ->with('success', 'Domain updated successfully.');
    }

    /**
     * Remove the specified domain
     */
    public function destroy(ClientDomain $domain)
    {
        $this->authorize('delete', $domain);

        $domain->delete();

        return redirect()->route('clients.domains.standalone.index')
                        ->with('success', 'Domain deleted successfully.');
    }

    /**
     * Export domains to CSV
     */
    public function export(Request $request)
    {
        $query = ClientDomain::with(['client'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain_name', 'like', "%{$search}%")
                  ->orWhere('registrar', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($registrar = $request->get('registrar')) {
            $query->where('registrar', $registrar);
        }

        if ($tld = $request->get('tld')) {
            $query->where('tld', $tld);
        }

        $domains = $query->orderBy('expires_at', 'asc')->get();

        $filename = 'domains_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($domains) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Domain Name',
                'Full Domain',
                'Client Name',
                'Status',
                'Registrar',
                'DNS Provider',
                'Registered Date',
                'Expiry Date',
                'Days Until Expiry',
                'Auto Renewal',
                'Privacy Protection',
                'Domain Lock',
                'Transfer Lock',
                'Purchase Cost',
                'Renewal Cost',
                'DNS Records',
                'Subdomains',
                'Email Forwards',
                'Created At'
            ]);

            // CSV data
            foreach ($domains as $domain) {
                fputcsv($file, [
                    $domain->name,
                    $domain->full_domain,
                    $domain->client->display_name,
                    $domain->status,
                    $domain->registrar,
                    $domain->dns_provider,
                    $domain->registered_at ? $domain->registered_at->format('Y-m-d') : '',
                    $domain->expires_at ? $domain->expires_at->format('Y-m-d') : '',
                    $domain->days_until_expiry,
                    $domain->auto_renewal ? 'Yes' : 'No',
                    $domain->privacy_protection ? 'Yes' : 'No',
                    $domain->lock_status ? 'Yes' : 'No',
                    $domain->transfer_lock ? 'Yes' : 'No',
                    $domain->purchase_cost,
                    $domain->renewal_cost,
                    $domain->dns_records_count,
                    $domain->subdomains_count,
                    $domain->email_forwards_count,
                    $domain->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}