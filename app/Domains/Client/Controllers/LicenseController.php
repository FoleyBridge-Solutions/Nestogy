<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientLicense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LicenseController extends Controller
{
    /**
     * Display a listing of all licenses (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientLicense::with('client')
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_key', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('version', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply type filter
        if ($type = $request->get('license_type')) {
            $query->where('license_type', $type);
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

        // Apply vendor filter
        if ($vendor = $request->get('vendor')) {
            $query->where('vendor', $vendor);
        }

        $licenses = $query->orderBy('expiry_date')
                         ->paginate(20)
                         ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        // Get unique vendors for filter
        $vendors = ClientLicense::whereHas('client', function($q) {
                     $q->where('company_id', auth()->user()->company_id);
                   })
                   ->whereNotNull('vendor')
                   ->distinct()
                   ->orderBy('vendor')
                   ->pluck('vendor');

        $licenseTypes = ClientLicense::getLicenseTypes();

        return view('clients.licenses.index', compact('licenses', 'clients', 'vendors', 'licenseTypes'));
    }

    /**
     * Show the form for creating a new license
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $licenseTypes = ClientLicense::getLicenseTypes();
        $supportLevels = ClientLicense::getSupportLevels();

        return view('clients.licenses.create', compact('clients', 'selectedClientId', 'licenseTypes', 'supportLevels'));
    }

    /**
     * Store a newly created license
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
            'license_type' => 'required|in:' . implode(',', array_keys(ClientLicense::getLicenseTypes())),
            'license_key' => 'nullable|string|max:500',
            'vendor' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:100',
            'seats' => 'nullable|integer|min:1|max:999999',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'renewal_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0|max:9999999.99',
            'renewal_cost' => 'nullable|numeric|min:0|max:9999999.99',
            'is_active' => 'boolean',
            'auto_renewal' => 'boolean',
            'support_level' => 'nullable|in:' . implode(',', array_keys(ClientLicense::getSupportLevels())),
            'license_terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $license = new ClientLicense($request->all());
        $license->company_id = auth()->user()->company_id;
        $license->save();

        return redirect()->route('clients.licenses.standalone.index')
                        ->with('success', 'License created successfully.');
    }

    /**
     * Display the specified license
     */
    public function show(ClientLicense $license)
    {
        $this->authorize('view', $license);

        $license->load('client');

        return view('clients.licenses.show', compact('license'));
    }

    /**
     * Show the form for editing the specified license
     */
    public function edit(ClientLicense $license)
    {
        $this->authorize('update', $license);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $licenseTypes = ClientLicense::getLicenseTypes();
        $supportLevels = ClientLicense::getSupportLevels();

        return view('clients.licenses.edit', compact('license', 'clients', 'licenseTypes', 'supportLevels'));
    }

    /**
     * Update the specified license
     */
    public function update(Request $request, ClientLicense $license)
    {
        $this->authorize('update', $license);

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
            'license_type' => 'required|in:' . implode(',', array_keys(ClientLicense::getLicenseTypes())),
            'license_key' => 'nullable|string|max:500',
            'vendor' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:100',
            'seats' => 'nullable|integer|min:1|max:999999',
            'purchase_date' => 'nullable|date|before_or_equal:today',
            'renewal_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0|max:9999999.99',
            'renewal_cost' => 'nullable|numeric|min:0|max:9999999.99',
            'is_active' => 'boolean',
            'auto_renewal' => 'boolean',
            'support_level' => 'nullable|in:' . implode(',', array_keys(ClientLicense::getSupportLevels())),
            'license_terms' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $license->fill($request->all());
        $license->save();

        return redirect()->route('clients.licenses.standalone.index')
                        ->with('success', 'License updated successfully.');
    }

    /**
     * Remove the specified license
     */
    public function destroy(ClientLicense $license)
    {
        $this->authorize('delete', $license);

        $license->delete();

        return redirect()->route('clients.licenses.standalone.index')
                        ->with('success', 'License deleted successfully.');
    }

    /**
     * Export licenses to CSV
     */
    public function export(Request $request)
    {
        $query = ClientLicense::with('client')
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('license_key', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('license_type')) {
            $query->where('license_type', $type);
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

        if ($vendor = $request->get('vendor')) {
            $query->where('vendor', $vendor);
        }

        $licenses = $query->orderBy('expiry_date')->get();

        $filename = 'licenses_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($licenses) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'License Name',
                'Client Name',
                'License Type',
                'License Key',
                'Vendor',
                'Version',
                'Seats',
                'Purchase Date',
                'Renewal Date',
                'Expiry Date',
                'Purchase Cost',
                'Renewal Cost',
                'Status',
                'Auto Renewal',
                'Support Level',
                'Notes'
            ]);

            // CSV data
            foreach ($licenses as $license) {
                fputcsv($file, [
                    $license->name,
                    $license->client->display_name,
                    $license->license_type,
                    $license->license_key,
                    $license->vendor,
                    $license->version,
                    $license->seats,
                    $license->purchase_date ? $license->purchase_date->format('Y-m-d') : '',
                    $license->renewal_date ? $license->renewal_date->format('Y-m-d') : '',
                    $license->expiry_date ? $license->expiry_date->format('Y-m-d') : '',
                    $license->purchase_cost,
                    $license->renewal_cost,
                    $license->status_label,
                    $license->auto_renewal ? 'Yes' : 'No',
                    $license->support_level,
                    $license->notes,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}