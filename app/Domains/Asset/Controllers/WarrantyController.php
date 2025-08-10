<?php

namespace App\Domains\Asset\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Asset\Models\AssetWarranty;
use App\Models\Asset;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WarrantyController extends Controller
{
    /**
     * Display a listing of all warranty records (standalone view)
     */
    public function index(Request $request)
    {
        $query = AssetWarranty::with(['asset.client', 'vendor'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('warranty_provider', 'like', "%{$search}%")
                  ->orWhere('warranty_type', 'like', "%{$search}%")
                  ->orWhere('terms', 'like', "%{$search}%")
                  ->orWhereHas('asset', function($assetQuery) use ($search) {
                      $assetQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('serial', 'like', "%{$search}%");
                  });
            });
        }

        // Apply type filters
        if ($type = $request->get('warranty_type')) {
            $query->where('warranty_type', $type);
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply asset filter
        if ($assetId = $request->get('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        // Apply expiry filters
        if ($request->get('expiry_filter') === 'expired') {
            $query->where('warranty_end_date', '<', now());
        } elseif ($request->get('expiry_filter') === 'expiring_soon') {
            $query->whereBetween('warranty_end_date', [now(), now()->addDays(30)]);
        } elseif ($request->get('expiry_filter') === 'active') {
            $query->where('warranty_end_date', '>', now());
        }

        $warranties = $query->orderBy('warranty_end_date', 'desc')
                           ->paginate(20)
                           ->appends($request->query());

        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $vendors = Vendor::where('company_id', auth()->user()->company_id)
                         ->orderBy('name')
                         ->get();

        return view('assets.warranties.index', compact('warranties', 'assets', 'vendors'));
    }

    /**
     * Show the form for creating a new warranty record
     */
    public function create(Request $request)
    {
        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $vendors = Vendor::where('company_id', auth()->user()->company_id)
                         ->orderBy('name')
                         ->get();

        $selectedAssetId = $request->get('asset_id');

        return view('assets.warranties.create', compact('assets', 'vendors', 'selectedAssetId'));
    }

    /**
     * Store a newly created warranty record
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => [
                'required',
                'exists:assets,id',
                Rule::exists('assets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'warranty_start_date' => 'required|date',
            'warranty_end_date' => 'required|date|after:warranty_start_date',
            'warranty_provider' => 'required|string|max:255',
            'warranty_type' => 'required|in:manufacturer,extended,third_party,service_contract',
            'terms' => 'nullable|string',
            'coverage_details' => 'nullable|string',
            'vendor_id' => [
                'nullable',
                'exists:vendors,id',
                Rule::exists('vendors', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'auto_renewal' => 'boolean',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,expired,cancelled,pending',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $warranty = new AssetWarranty($request->all());
        $warranty->company_id = auth()->user()->company_id;
        $warranty->save();

        return redirect()->route('assets.warranties.index')
                        ->with('success', 'Warranty record created successfully.');
    }

    /**
     * Display the specified warranty record
     */
    public function show(AssetWarranty $warranty)
    {
        $this->authorize('view', $warranty);

        $warranty->load(['asset.client', 'vendor']);

        return view('assets.warranties.show', compact('warranty'));
    }

    /**
     * Show the form for editing the specified warranty record
     */
    public function edit(AssetWarranty $warranty)
    {
        $this->authorize('update', $warranty);

        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $vendors = Vendor::where('company_id', auth()->user()->company_id)
                         ->orderBy('name')
                         ->get();

        return view('assets.warranties.edit', compact('warranty', 'assets', 'vendors'));
    }

    /**
     * Update the specified warranty record
     */
    public function update(Request $request, AssetWarranty $warranty)
    {
        $this->authorize('update', $warranty);

        $validator = Validator::make($request->all(), [
            'asset_id' => [
                'required',
                'exists:assets,id',
                Rule::exists('assets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'warranty_start_date' => 'required|date',
            'warranty_end_date' => 'required|date|after:warranty_start_date',
            'warranty_provider' => 'required|string|max:255',
            'warranty_type' => 'required|in:manufacturer,extended,third_party,service_contract',
            'terms' => 'nullable|string',
            'coverage_details' => 'nullable|string',
            'vendor_id' => [
                'nullable',
                'exists:vendors,id',
                Rule::exists('vendors', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'cost' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0',
            'auto_renewal' => 'boolean',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,expired,cancelled,pending',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $warranty->fill($request->all());
        $warranty->save();

        return redirect()->route('assets.warranties.index')
                        ->with('success', 'Warranty record updated successfully.');
    }

    /**
     * Remove the specified warranty record
     */
    public function destroy(AssetWarranty $warranty)
    {
        $this->authorize('delete', $warranty);

        $warranty->delete();

        return redirect()->route('assets.warranties.index')
                        ->with('success', 'Warranty record deleted successfully.');
    }

    /**
     * Export warranty records to CSV
     */
    public function export(Request $request)
    {
        $query = AssetWarranty::with(['asset.client', 'vendor'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('warranty_provider', 'like', "%{$search}%")
                  ->orWhere('warranty_type', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('warranty_type')) {
            $query->where('warranty_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($assetId = $request->get('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $warranties = $query->orderBy('warranty_end_date', 'desc')->get();

        $filename = 'asset_warranties_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($warranties) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Asset Name',
                'Asset Serial',
                'Client',
                'Warranty Provider',
                'Warranty Type',
                'Start Date',
                'End Date',
                'Status',
                'Cost',
                'Renewal Cost',
                'Auto Renewal',
                'Contact Email',
                'Contact Phone',
                'Reference Number',
                'Coverage Details',
                'Notes'
            ]);

            // CSV data
            foreach ($warranties as $warranty) {
                fputcsv($file, [
                    $warranty->asset->name,
                    $warranty->asset->serial,
                    $warranty->asset->client->name ?? '',
                    $warranty->warranty_provider,
                    ucfirst(str_replace('_', ' ', $warranty->warranty_type)),
                    $warranty->warranty_start_date?->format('Y-m-d'),
                    $warranty->warranty_end_date?->format('Y-m-d'),
                    ucfirst($warranty->status),
                    $warranty->cost ? '$' . number_format($warranty->cost, 2) : '',
                    $warranty->renewal_cost ? '$' . number_format($warranty->renewal_cost, 2) : '',
                    $warranty->auto_renewal ? 'Yes' : 'No',
                    $warranty->contact_email,
                    $warranty->contact_phone,
                    $warranty->reference_number,
                    $warranty->coverage_details,
                    $warranty->notes,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get warranty expiry report
     */
    public function expiryReport(Request $request)
    {
        $expiredQuery = AssetWarranty::with(['asset.client'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->where('warranty_end_date', '<', now())
            ->where('status', '!=', 'expired');

        $expiringSoonQuery = AssetWarranty::with(['asset.client'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            })
            ->whereBetween('warranty_end_date', [now(), now()->addDays(30)])
            ->where('status', 'active');

        $expired = $expiredQuery->orderBy('warranty_end_date', 'desc')->get();
        $expiringSoon = $expiringSoonQuery->orderBy('warranty_end_date')->get();

        return view('assets.warranties.expiry-report', compact('expired', 'expiringSoon'));
    }

    /**
     * Renew warranty
     */
    public function renew(Request $request, AssetWarranty $warranty)
    {
        $this->authorize('update', $warranty);

        $request->validate([
            'new_end_date' => 'required|date|after:warranty_end_date',
            'renewal_cost' => 'nullable|numeric|min:0',
        ]);

        // Update current warranty
        $warranty->update([
            'warranty_end_date' => $request->new_end_date,
            'cost' => $request->renewal_cost ?? $warranty->renewal_cost ?? $warranty->cost,
            'status' => 'active',
        ]);

        return redirect()->back()
                        ->with('success', 'Warranty renewed successfully.');
    }

    /**
     * Mark warranty as expired
     */
    public function markExpired(AssetWarranty $warranty)
    {
        $this->authorize('update', $warranty);

        $warranty->update(['status' => 'expired']);

        return redirect()->back()
                        ->with('success', 'Warranty marked as expired.');
    }
}