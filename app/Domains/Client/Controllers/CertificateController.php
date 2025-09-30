<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientCertificate;
use App\Domains\Core\Services\NavigationService;
use App\Traits\UsesSelectedClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    use UsesSelectedClient;
    /**
     * Display a listing of certificates for the selected client
     */
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (!$client) {
            return redirect()->route('clients.select-screen');
        }

        $query = $client->certificates()->with(['client']);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('issuer', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('domain_names', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Apply status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply vendor filter
        if ($vendor = $request->get('vendor')) {
            $query->where('vendor', $vendor);
        }

        // Apply expiry filters
        if ($request->get('expired_only')) {
            $query->expired();
        } elseif ($request->get('expiring_soon')) {
            $query->expiringSoon($request->get('expiring_days', 30));
        }

        // Apply wildcard filter
        if ($request->get('wildcard_only')) {
            $query->wildcard();
        }

        $certificates = $query->orderBy('expires_at', 'asc')
                             ->paginate(20)
                             ->appends($request->query());

        $types = ClientCertificate::getTypes();
        $statuses = ClientCertificate::getStatuses();
        $vendors = ClientCertificate::getVendors();

        return view('clients.certificates.index', compact('certificates', 'client', 'types', 'statuses', 'vendors'));
    }

    /**
     * Show the form for creating a new certificate
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $types = ClientCertificate::getTypes();
        $statuses = ClientCertificate::getStatuses();
        $keySizes = ClientCertificate::getKeySizes();
        $algorithms = ClientCertificate::getAlgorithms();
        $vendors = ClientCertificate::getVendors();

        return view('clients.certificates.create', compact('clients', 'selectedClientId', 'types', 'statuses', 'keySizes', 'algorithms', 'vendors'));
    }

    /**
     * Store a newly created certificate
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
            'type' => 'required|in:' . implode(',', array_keys(ClientCertificate::getTypes())),
            'issuer' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'key_size' => 'nullable|in:' . implode(',', array_keys(ClientCertificate::getKeySizes())),
            'algorithm' => 'nullable|in:' . implode(',', array_keys(ClientCertificate::getAlgorithms())),
            'fingerprint_sha1' => 'nullable|string|max:255',
            'fingerprint_sha256' => 'nullable|string|max:255',
            'is_wildcard' => 'boolean',
            'domain_names' => 'nullable|string',
            'certificate_path' => 'nullable|string|max:500',
            'private_key_path' => 'nullable|string|max:500',
            'intermediate_path' => 'nullable|string|max:500',
            'root_ca_path' => 'nullable|string|max:500',
            'issued_at' => 'nullable|date|before_or_equal:today',
            'expires_at' => 'required|date|after:issued_at',
            'renewal_date' => 'nullable|date|before:expires_at',
            'auto_renewal' => 'boolean',
            'days_before_expiry_alert' => 'nullable|integer|min:1|max:365',
            'status' => 'required|in:' . implode(',', array_keys(ClientCertificate::getStatuses())),
            'vendor' => 'nullable|in:' . implode(',', array_keys(ClientCertificate::getVendors())),
            'purchase_cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process domain names
        $domainNames = [];
        if ($request->domain_names) {
            $domainNames = array_map('trim', explode(',', $request->domain_names));
            $domainNames = array_filter($domainNames);
        }

        $certificate = new ClientCertificate([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'issuer' => $request->issuer,
            'subject' => $request->subject,
            'serial_number' => $request->serial_number,
            'key_size' => $request->key_size,
            'algorithm' => $request->algorithm,
            'fingerprint_sha1' => $request->fingerprint_sha1,
            'fingerprint_sha256' => $request->fingerprint_sha256,
            'is_wildcard' => $request->has('is_wildcard'),
            'domain_names' => $domainNames,
            'certificate_path' => $request->certificate_path,
            'private_key_path' => $request->private_key_path,
            'intermediate_path' => $request->intermediate_path,
            'root_ca_path' => $request->root_ca_path,
            'issued_at' => $request->issued_at,
            'expires_at' => $request->expires_at,
            'renewal_date' => $request->renewal_date,
            'auto_renewal' => $request->has('auto_renewal'),
            'days_before_expiry_alert' => $request->days_before_expiry_alert ?: 30,
            'status' => $request->status,
            'vendor' => $request->vendor,
            'purchase_cost' => $request->purchase_cost,
            'renewal_cost' => $request->renewal_cost,
            'notes' => $request->notes,
        ]);
        
        $certificate->company_id = auth()->user()->company_id;
        $certificate->save();

        return redirect()->route('clients.certificates.standalone.index')
                        ->with('success', 'Certificate created successfully.');
    }

    /**
     * Display the specified certificate
     */
    public function show(ClientCertificate $certificate)
    {
        $this->authorize('view', $certificate);

        $certificate->load('client');
        
        // Update access timestamp
        $certificate->update(['accessed_at' => now()]);

        return view('clients.certificates.show', compact('certificate'));
    }

    /**
     * Show the form for editing the specified certificate
     */
    public function edit(ClientCertificate $certificate)
    {
        $this->authorize('update', $certificate);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $types = ClientCertificate::getTypes();
        $statuses = ClientCertificate::getStatuses();
        $keySizes = ClientCertificate::getKeySizes();
        $algorithms = ClientCertificate::getAlgorithms();
        $vendors = ClientCertificate::getVendors();

        return view('clients.certificates.edit', compact('certificate', 'clients', 'types', 'statuses', 'keySizes', 'algorithms', 'vendors'));
    }

    /**
     * Update the specified certificate
     */
    public function update(Request $request, ClientCertificate $certificate)
    {
        $this->authorize('update', $certificate);

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
            'type' => 'required|in:' . implode(',', array_keys(ClientCertificate::getTypes())),
            'issuer' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'key_size' => 'nullable|in:' . implode(',', array_keys(ClientCertificate::getKeySizes())),
            'algorithm' => 'nullable|in:' . implode(',', array_keys(ClientCertificate::getAlgorithms())),
            'fingerprint_sha1' => 'nullable|string|max:255',
            'fingerprint_sha256' => 'nullable|string|max:255',
            'is_wildcard' => 'boolean',
            'domain_names' => 'nullable|string',
            'certificate_path' => 'nullable|string|max:500',
            'private_key_path' => 'nullable|string|max:500',
            'intermediate_path' => 'nullable|string|max:500',
            'root_ca_path' => 'nullable|string|max:500',
            'issued_at' => 'nullable|date|before_or_equal:today',
            'expires_at' => 'required|date|after:issued_at',
            'renewal_date' => 'nullable|date|before:expires_at',
            'auto_renewal' => 'boolean',
            'days_before_expiry_alert' => 'nullable|integer|min:1|max:365',
            'status' => 'required|in:' . implode(',', array_keys(ClientCertificate::getStatuses())),
            'vendor' => 'nullable|in:' . implode(',', array_keys(ClientCertificate::getVendors())),
            'purchase_cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process domain names
        $domainNames = [];
        if ($request->domain_names) {
            $domainNames = array_map('trim', explode(',', $request->domain_names));
            $domainNames = array_filter($domainNames);
        }

        $certificate->fill([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'issuer' => $request->issuer,
            'subject' => $request->subject,
            'serial_number' => $request->serial_number,
            'key_size' => $request->key_size,
            'algorithm' => $request->algorithm,
            'fingerprint_sha1' => $request->fingerprint_sha1,
            'fingerprint_sha256' => $request->fingerprint_sha256,
            'is_wildcard' => $request->has('is_wildcard'),
            'domain_names' => $domainNames,
            'certificate_path' => $request->certificate_path,
            'private_key_path' => $request->private_key_path,
            'intermediate_path' => $request->intermediate_path,
            'root_ca_path' => $request->root_ca_path,
            'issued_at' => $request->issued_at,
            'expires_at' => $request->expires_at,
            'renewal_date' => $request->renewal_date,
            'auto_renewal' => $request->has('auto_renewal'),
            'days_before_expiry_alert' => $request->days_before_expiry_alert ?: 30,
            'status' => $request->status,
            'vendor' => $request->vendor,
            'purchase_cost' => $request->purchase_cost,
            'renewal_cost' => $request->renewal_cost,
            'notes' => $request->notes,
        ]);

        $certificate->save();

        return redirect()->route('clients.certificates.standalone.index')
                        ->with('success', 'Certificate updated successfully.');
    }

    /**
     * Remove the specified certificate
     */
    public function destroy(ClientCertificate $certificate)
    {
        $this->authorize('delete', $certificate);

        $certificate->delete();

        return redirect()->route('clients.certificates.standalone.index')
                        ->with('success', 'Certificate deleted successfully.');
    }

    /**
     * Export certificates to CSV
     */
    public function export(Request $request)
    {
        $query = ClientCertificate::with(['client'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('issuer', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($vendor = $request->get('vendor')) {
            $query->where('vendor', $vendor);
        }

        $certificates = $query->orderBy('expires_at', 'asc')->get();

        $filename = 'certificates_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($certificates) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Certificate Name',
                'Type',
                'Client Name',
                'Primary Domain',
                'Issuer',
                'Status',
                'Key Size',
                'Algorithm',
                'Is Wildcard',
                'Issued Date',
                'Expiry Date',
                'Days Until Expiry',
                'Vendor',
                'Purchase Cost',
                'Renewal Cost',
                'Auto Renewal',
                'Created At'
            ]);

            // CSV data
            foreach ($certificates as $certificate) {
                fputcsv($file, [
                    $certificate->name,
                    $certificate->type,
                    $certificate->client->display_name,
                    $certificate->primary_domain ?: 'N/A',
                    $certificate->issuer,
                    $certificate->status,
                    $certificate->key_size,
                    $certificate->algorithm_display,
                    $certificate->is_wildcard ? 'Yes' : 'No',
                    $certificate->issued_at ? $certificate->issued_at->format('Y-m-d') : '',
                    $certificate->expires_at ? $certificate->expires_at->format('Y-m-d') : '',
                    $certificate->days_until_expiry,
                    $certificate->vendor,
                    $certificate->purchase_cost,
                    $certificate->renewal_cost,
                    $certificate->auto_renewal ? 'Yes' : 'No',
                    $certificate->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}