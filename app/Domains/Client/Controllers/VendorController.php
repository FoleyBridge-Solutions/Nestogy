<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientVendor;
use App\Traits\UsesSelectedClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    use UsesSelectedClient;

    /**
     * Display a listing of vendors for the selected client
     */
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (! $client) {
            return redirect()->route('clients.select-screen');
        }

        $query = ClientVendor::with('client')
            ->where('client_id', $client->id);

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply type filter
        if ($type = $request->get('vendor_type')) {
            $query->where('vendor_type', $type);
        }

        // Apply category filter
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            switch ($status) {
                case 'preferred':
                    $query->preferred();
                    break;
                case 'approved':
                    $query->approved();
                    break;
                case 'active':
                    $query->active();
                    break;
                case 'high_rated':
                    $query->highRated();
                    break;
                case 'needs_review':
                    $query->needingReview();
                    break;
                case 'contracts_expiring':
                    $query->contractsExpiringSoon();
                    break;
            }
        }

        // Apply rating filter
        if ($minRating = $request->get('min_rating')) {
            $query->where('overall_rating', '>=', $minRating);
        }

        $vendors = $query->orderBy('vendor_name')
            ->paginate(20)
            ->appends($request->query());

        $vendorTypes = ClientVendor::getVendorTypes();
        $vendorCategories = ClientVendor::getVendorCategories();

        return view('clients.vendors.index', compact('vendors', 'client', 'vendorTypes', 'vendorCategories'));
    }

    /**
     * Show the form for creating a new vendor
     */
    public function create(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (! $client) {
            return redirect()->route('clients.select-screen');
        }

        $vendorTypes = ClientVendor::getVendorTypes();
        $vendorCategories = ClientVendor::getVendorCategories();
        $relationshipStatuses = ClientVendor::getRelationshipStatuses();
        $paymentTerms = ClientVendor::getPaymentTerms();
        $paymentMethods = ClientVendor::getPaymentMethods();
        $billingFrequencies = ClientVendor::getBillingFrequencies();

        return view('clients.vendors.create', compact(
            'client',
            'vendorTypes',
            'vendorCategories',
            'relationshipStatuses',
            'paymentTerms',
            'paymentMethods',
            'billingFrequencies'
        ));
    }

    /**
     * Store a newly created vendor
     */
    public function store(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (! $client) {
            return redirect()->route('clients.select-screen');
        }

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                Rule::in([$client->id]), // Must match selected client
            ],
            'vendor_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vendor_type' => 'required|in:'.implode(',', array_keys(ClientVendor::getVendorTypes())),
            'category' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getVendorCategories())),
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getPaymentTerms())),
            'preferred_payment_method' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getPaymentMethods())),
            'relationship_status' => 'required|in:'.implode(',', array_keys(ClientVendor::getRelationshipStatuses())),
            'start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:start_date',
            'contract_value' => 'nullable|numeric|min:0|max:99999999.99',
            'currency' => 'nullable|string|size:3',
            'billing_frequency' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getBillingFrequencies())),
            'last_order_date' => 'nullable|date|before_or_equal:today',
            'total_spent' => 'nullable|numeric|min:0|max:99999999.99',
            'average_response_time' => 'nullable|string|max:100',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'reliability_rating' => 'nullable|integer|min:1|max:5',
            'cost_rating' => 'nullable|integer|min:1|max:5',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'is_preferred' => 'boolean',
            'is_approved' => 'boolean',
            'requires_approval' => 'boolean',
            'approval_limit' => 'nullable|numeric|min:0|max:99999999.99',
            'certifications' => 'nullable|string',
            'insurance_info' => 'nullable|string',
            'backup_contacts' => 'nullable|string',
            'service_areas' => 'nullable|string',
            'specializations' => 'nullable|string',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
            'last_review_date' => 'nullable|date|before_or_equal:today',
            'next_review_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $vendorData = $request->all();

        // Process array fields
        if ($request->certifications) {
            $vendorData['certifications'] = array_map('trim', explode(',', $request->certifications));
        }

        if ($request->insurance_info) {
            $vendorData['insurance_info'] = json_decode($request->insurance_info, true) ?: [];
        }

        if ($request->backup_contacts) {
            $vendorData['backup_contacts'] = array_map('trim', explode("\n", $request->backup_contacts));
        }

        if ($request->service_areas) {
            $vendorData['service_areas'] = array_map('trim', explode(',', $request->service_areas));
        }

        if ($request->specializations) {
            $vendorData['specializations'] = array_map('trim', explode(',', $request->specializations));
        }

        if ($request->tags) {
            $vendorData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $vendor = new ClientVendor($vendorData);
        $vendor->company_id = auth()->user()->company_id;
        $vendor->save();

        return redirect()->route('clients.vendors.index', ['client' => $client->id])
            ->with('success', 'Vendor created successfully.');
    }

    /**
     * Display the specified vendor
     */
    public function show(ClientVendor $vendor)
    {
        $this->authorize('view', $vendor);

        $vendor->load('client');

        return view('clients.vendors.show', compact('vendor'));
    }

    /**
     * Show the form for editing the specified vendor
     */
    public function edit(ClientVendor $vendor)
    {
        $this->authorize('update', $vendor);

        $clients = Client::where('company_id', auth()->user()->company_id)
            ->orderBy('name')
            ->get();

        $vendorTypes = ClientVendor::getVendorTypes();
        $vendorCategories = ClientVendor::getVendorCategories();
        $relationshipStatuses = ClientVendor::getRelationshipStatuses();
        $paymentTerms = ClientVendor::getPaymentTerms();
        $paymentMethods = ClientVendor::getPaymentMethods();
        $billingFrequencies = ClientVendor::getBillingFrequencies();

        return view('clients.vendors.edit', compact(
            'vendor',
            'clients',
            'vendorTypes',
            'vendorCategories',
            'relationshipStatuses',
            'paymentTerms',
            'paymentMethods',
            'billingFrequencies'
        ));
    }

    /**
     * Update the specified vendor
     */
    public function update(Request $request, ClientVendor $vendor)
    {
        $this->authorize('update', $vendor);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'vendor_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vendor_type' => 'required|in:'.implode(',', array_keys(ClientVendor::getVendorTypes())),
            'category' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getVendorCategories())),
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getPaymentTerms())),
            'preferred_payment_method' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getPaymentMethods())),
            'relationship_status' => 'required|in:'.implode(',', array_keys(ClientVendor::getRelationshipStatuses())),
            'start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:start_date',
            'contract_value' => 'nullable|numeric|min:0|max:99999999.99',
            'currency' => 'nullable|string|size:3',
            'billing_frequency' => 'nullable|in:'.implode(',', array_keys(ClientVendor::getBillingFrequencies())),
            'last_order_date' => 'nullable|date|before_or_equal:today',
            'total_spent' => 'nullable|numeric|min:0|max:99999999.99',
            'average_response_time' => 'nullable|string|max:100',
            'performance_rating' => 'nullable|integer|min:1|max:5',
            'reliability_rating' => 'nullable|integer|min:1|max:5',
            'cost_rating' => 'nullable|integer|min:1|max:5',
            'overall_rating' => 'nullable|integer|min:1|max:5',
            'is_preferred' => 'boolean',
            'is_approved' => 'boolean',
            'requires_approval' => 'boolean',
            'approval_limit' => 'nullable|numeric|min:0|max:99999999.99',
            'certifications' => 'nullable|string',
            'insurance_info' => 'nullable|string',
            'backup_contacts' => 'nullable|string',
            'service_areas' => 'nullable|string',
            'specializations' => 'nullable|string',
            'notes' => 'nullable|string',
            'tags' => 'nullable|string',
            'last_review_date' => 'nullable|date|before_or_equal:today',
            'next_review_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $vendorData = $request->all();

        // Process array fields
        if ($request->certifications) {
            $vendorData['certifications'] = array_map('trim', explode(',', $request->certifications));
        }

        if ($request->insurance_info) {
            $vendorData['insurance_info'] = json_decode($request->insurance_info, true) ?: [];
        }

        if ($request->backup_contacts) {
            $vendorData['backup_contacts'] = array_map('trim', explode("\n", $request->backup_contacts));
        }

        if ($request->service_areas) {
            $vendorData['service_areas'] = array_map('trim', explode(',', $request->service_areas));
        }

        if ($request->specializations) {
            $vendorData['specializations'] = array_map('trim', explode(',', $request->specializations));
        }

        if ($request->tags) {
            $vendorData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $vendor->fill($vendorData);
        $vendor->save();

        return redirect()->route('clients.vendors.index', ['client' => $vendor->client_id])
            ->with('success', 'Vendor updated successfully.');
    }

    /**
     * Remove the specified vendor
     */
    public function destroy(ClientVendor $vendor)
    {
        $this->authorize('delete', $vendor);

        $vendor->delete();

        return redirect()->route('clients.vendors.index', ['client' => $vendor->client_id])
            ->with('success', 'Vendor deleted successfully.');
    }

    /**
     * Export vendors to CSV
     */
    public function export(Request $request)
    {
        $query = ClientVendor::with('client')
            ->whereHas('client', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('vendor_type')) {
            $query->where('vendor_type', $type);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->get('status')) {
            switch ($status) {
                case 'preferred':
                    $query->preferred();
                    break;
                case 'approved':
                    $query->approved();
                    break;
                case 'active':
                    $query->active();
                    break;
                case 'high_rated':
                    $query->highRated();
                    break;
            }
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $vendors = $query->orderBy('vendor_name')->get();

        $filename = 'vendors_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($vendors) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Vendor Name',
                'Client Name',
                'Type',
                'Category',
                'Contact Person',
                'Email',
                'Phone',
                'City',
                'State',
                'Status',
                'Preferred',
                'Approved',
                'Overall Rating',
                'Total Spent',
                'Last Order Date',
                'Contract End Date',
            ]);

            // CSV data
            foreach ($vendors as $vendor) {
                fputcsv($file, [
                    $vendor->vendor_name,
                    $vendor->client->display_name,
                    $vendor->vendor_type,
                    $vendor->category,
                    $vendor->contact_person,
                    $vendor->email,
                    $vendor->phone,
                    $vendor->city,
                    $vendor->state,
                    $vendor->status_label,
                    $vendor->is_preferred ? 'Yes' : 'No',
                    $vendor->is_approved ? 'Yes' : 'No',
                    $vendor->overall_rating,
                    $vendor->total_spent,
                    $vendor->last_order_date ? $vendor->last_order_date->format('Y-m-d') : '',
                    $vendor->contract_end_date ? $vendor->contract_end_date->format('Y-m-d') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
