<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientNetwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class NetworkController extends Controller
{
    /**
     * Display a listing of all networks (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientNetwork::with('client')
            ->whereHas('client', function($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ip_range', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhere('ssid', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply type filter
        if ($type = $request->get('network_type')) {
            $query->where('network_type', $type);
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
                case 'monitored':
                    $query->monitored();
                    break;
                case 'wireless':
                    $query->wireless();
                    break;
                case 'vlan':
                    $query->withVlan();
                    break;
            }
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply provider filter
        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        $networks = $query->orderBy('name')
                         ->paginate(20)
                         ->appends($request->query());

        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
                        ->orderBy('name')
                        ->get();

        // Get unique providers for filter
        $providers = ClientNetwork::whereHas('client', function($q) {
                       $q->where('tenant_id', auth()->user()->tenant_id);
                     })
                     ->whereNotNull('provider')
                     ->distinct()
                     ->orderBy('provider')
                     ->pluck('provider');

        $networkTypes = ClientNetwork::getNetworkTypes();

        return view('clients.networks.index', compact('networks', 'clients', 'providers', 'networkTypes'));
    }

    /**
     * Show the form for creating a new network
     */
    public function create(Request $request)
    {
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $networkTypes = ClientNetwork::getNetworkTypes();
        $securityTypes = ClientNetwork::getSecurityTypes();
        $bandwidthOptions = ClientNetwork::getBandwidthOptions();

        return view('clients.networks.create', compact('clients', 'selectedClientId', 'networkTypes', 'securityTypes', 'bandwidthOptions'));
    }

    /**
     * Store a newly created network
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'network_type' => 'required|in:' . implode(',', array_keys(ClientNetwork::getNetworkTypes())),
            'ip_range' => 'nullable|string|max:100',
            'subnet_mask' => 'nullable|string|max:50',
            'gateway' => 'nullable|ip',
            'dns_servers' => 'nullable|string',
            'dhcp_range_start' => 'nullable|ip',
            'dhcp_range_end' => 'nullable|ip',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'ssid' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'security_type' => 'nullable|in:' . implode(',', array_keys(ClientNetwork::getSecurityTypes())),
            'bandwidth' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:255',
            'circuit_id' => 'nullable|string|max:255',
            'static_routes' => 'nullable|string',
            'firewall_rules' => 'nullable|string',
            'vpn_config' => 'nullable|string',
            'monitoring_enabled' => 'boolean',
            'backup_config' => 'nullable|string',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'equipment' => 'nullable|string',
            'notes' => 'nullable|string',
            'last_audit_date' => 'nullable|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $networkData = $request->all();
        
        // Process array fields
        if ($request->dns_servers) {
            $networkData['dns_servers'] = array_map('trim', explode(',', $request->dns_servers));
        }
        
        if ($request->static_routes) {
            $networkData['static_routes'] = array_map('trim', explode("\n", $request->static_routes));
        }
        
        if ($request->firewall_rules) {
            $networkData['firewall_rules'] = array_map('trim', explode("\n", $request->firewall_rules));
        }
        
        if ($request->vpn_config) {
            $networkData['vpn_config'] = json_decode($request->vpn_config, true) ?: [];
        }
        
        if ($request->equipment) {
            $networkData['equipment'] = array_map('trim', explode("\n", $request->equipment));
        }

        $network = new ClientNetwork($networkData);
        $network->tenant_id = auth()->user()->tenant_id;
        $network->save();

        return redirect()->route('clients.networks.standalone.index')
                        ->with('success', 'Network created successfully.');
    }

    /**
     * Display the specified network
     */
    public function show(ClientNetwork $network)
    {
        $this->authorize('view', $network);

        $network->load('client');

        return view('clients.networks.show', compact('network'));
    }

    /**
     * Show the form for editing the specified network
     */
    public function edit(ClientNetwork $network)
    {
        $this->authorize('update', $network);

        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
                        ->orderBy('name')
                        ->get();

        $networkTypes = ClientNetwork::getNetworkTypes();
        $securityTypes = ClientNetwork::getSecurityTypes();
        $bandwidthOptions = ClientNetwork::getBandwidthOptions();

        return view('clients.networks.edit', compact('network', 'clients', 'networkTypes', 'securityTypes', 'bandwidthOptions'));
    }

    /**
     * Update the specified network
     */
    public function update(Request $request, ClientNetwork $network)
    {
        $this->authorize('update', $network);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('tenant_id', auth()->user()->tenant_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'network_type' => 'required|in:' . implode(',', array_keys(ClientNetwork::getNetworkTypes())),
            'ip_range' => 'nullable|string|max:100',
            'subnet_mask' => 'nullable|string|max:50',
            'gateway' => 'nullable|ip',
            'dns_servers' => 'nullable|string',
            'dhcp_range_start' => 'nullable|ip',
            'dhcp_range_end' => 'nullable|ip',
            'vlan_id' => 'nullable|integer|min:1|max:4094',
            'ssid' => 'nullable|string|max:255',
            'wifi_password' => 'nullable|string|max:255',
            'security_type' => 'nullable|in:' . implode(',', array_keys(ClientNetwork::getSecurityTypes())),
            'bandwidth' => 'nullable|string|max:100',
            'provider' => 'nullable|string|max:255',
            'circuit_id' => 'nullable|string|max:255',
            'static_routes' => 'nullable|string',
            'firewall_rules' => 'nullable|string',
            'vpn_config' => 'nullable|string',
            'monitoring_enabled' => 'boolean',
            'backup_config' => 'nullable|string',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'equipment' => 'nullable|string',
            'notes' => 'nullable|string',
            'last_audit_date' => 'nullable|date|before_or_equal:today',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $networkData = $request->all();
        
        // Process array fields
        if ($request->dns_servers) {
            $networkData['dns_servers'] = array_map('trim', explode(',', $request->dns_servers));
        }
        
        if ($request->static_routes) {
            $networkData['static_routes'] = array_map('trim', explode("\n", $request->static_routes));
        }
        
        if ($request->firewall_rules) {
            $networkData['firewall_rules'] = array_map('trim', explode("\n", $request->firewall_rules));
        }
        
        if ($request->vpn_config) {
            $networkData['vpn_config'] = json_decode($request->vpn_config, true) ?: [];
        }
        
        if ($request->equipment) {
            $networkData['equipment'] = array_map('trim', explode("\n", $request->equipment));
        }

        $network->fill($networkData);
        $network->save();

        return redirect()->route('clients.networks.standalone.index')
                        ->with('success', 'Network updated successfully.');
    }

    /**
     * Remove the specified network
     */
    public function destroy(ClientNetwork $network)
    {
        $this->authorize('delete', $network);

        $network->delete();

        return redirect()->route('clients.networks.standalone.index')
                        ->with('success', 'Network deleted successfully.');
    }

    /**
     * Export networks to CSV
     */
    public function export(Request $request)
    {
        $query = ClientNetwork::with('client')
            ->whereHas('client', function($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('ip_range', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('network_type')) {
            $query->where('network_type', $type);
        }

        if ($status = $request->get('status')) {
            switch ($status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->inactive();
                    break;
                case 'monitored':
                    $query->monitored();
                    break;
            }
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($provider = $request->get('provider')) {
            $query->where('provider', $provider);
        }

        $networks = $query->orderBy('name')->get();

        $filename = 'networks_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($networks) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Network Name',
                'Client Name',
                'Type',
                'IP Range',
                'Subnet Mask',
                'Gateway',
                'SSID',
                'Provider',
                'Bandwidth',
                'Status',
                'Monitoring',
                'Location',
                'Last Audit',
                'Notes'
            ]);

            // CSV data
            foreach ($networks as $network) {
                fputcsv($file, [
                    $network->name,
                    $network->client->display_name,
                    $network->network_type,
                    $network->ip_range,
                    $network->subnet_mask,
                    $network->gateway,
                    $network->ssid,
                    $network->provider,
                    $network->bandwidth,
                    $network->status_label,
                    $network->monitoring_enabled ? 'Yes' : 'No',
                    $network->location,
                    $network->last_audit_date ? $network->last_audit_date->format('Y-m-d') : '',
                    $network->notes,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}