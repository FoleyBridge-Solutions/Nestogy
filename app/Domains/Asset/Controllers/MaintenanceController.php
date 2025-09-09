<?php

namespace App\Domains\Asset\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Asset\Models\AssetMaintenance;
use App\Models\Asset;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of all maintenance records (standalone view)
     */
    public function index(Request $request)
    {
        $query = AssetMaintenance::with(['asset.client', 'technician', 'vendor'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('maintenance_type', 'like', "%{$search}%")
                  ->orWhereHas('asset', function($assetQuery) use ($search) {
                      $assetQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('serial', 'like', "%{$search}%");
                  });
            });
        }

        // Apply type filters
        if ($type = $request->get('maintenance_type')) {
            $query->where('maintenance_type', $type);
        }

        // Apply status filters
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Apply asset filter
        if ($assetId = $request->get('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        // Apply date range filters
        if ($startDate = $request->get('start_date')) {
            $query->where('scheduled_date', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->where('scheduled_date', '<=', $endDate);
        }

        $maintenance = $query->orderBy('scheduled_date', 'desc')
                           ->paginate(20)
                           ->appends($request->query());

        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $vendors = Vendor::where('company_id', auth()->user()->company_id)
                         ->orderBy('name')
                         ->get();

        return view('assets.maintenance.index', compact('maintenance', 'assets', 'vendors'));
    }

    /**
     * Show the form for creating a new maintenance record
     */
    public function create(Request $request)
    {
        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $technicians = User::where('company_id', auth()->user()->company_id)
                          ->where('role', '!=', 'client')
                          ->orderBy('name')
                          ->get();

        $vendors = Vendor::where('company_id', auth()->user()->company_id)
                         ->orderBy('name')
                         ->get();

        $selectedAssetId = $request->get('asset_id');

        return view('assets.maintenance.create', compact('assets', 'technicians', 'vendors', 'selectedAssetId'));
    }

    /**
     * Store a newly created maintenance record
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
            'maintenance_type' => 'required|in:preventive,corrective,emergency,upgrade,inspection',
            'scheduled_date' => 'required|date',
            'completed_date' => 'nullable|date|after_or_equal:scheduled_date',
            'technician_id' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'vendor_id' => [
                'nullable',
                'exists:vendors,id',
                Rule::exists('vendors', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'cost' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'next_maintenance_date' => 'nullable|date|after:scheduled_date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $maintenance = new AssetMaintenance($request->all());
        $maintenance->company_id = auth()->user()->company_id;
        $maintenance->save();

        return redirect()->route('assets.maintenance.index')
                        ->with('success', 'Maintenance record created successfully.');
    }

    /**
     * Display the specified maintenance record
     */
    public function show(AssetMaintenance $maintenance)
    {
        $this->authorize('view', $maintenance);

        $maintenance->load(['asset.client', 'technician', 'vendor']);

        return view('assets.maintenance.show', compact('maintenance'));
    }

    /**
     * Show the form for editing the specified maintenance record
     */
    public function edit(AssetMaintenance $maintenance)
    {
        $this->authorize('update', $maintenance);

        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $technicians = User::where('company_id', auth()->user()->company_id)
                          ->where('role', '!=', 'client')
                          ->orderBy('name')
                          ->get();

        $vendors = Vendor::where('company_id', auth()->user()->company_id)
                         ->orderBy('name')
                         ->get();

        return view('assets.maintenance.edit', compact('maintenance', 'assets', 'technicians', 'vendors'));
    }

    /**
     * Update the specified maintenance record
     */
    public function update(Request $request, AssetMaintenance $maintenance)
    {
        $this->authorize('update', $maintenance);

        $validator = Validator::make($request->all(), [
            'asset_id' => [
                'required',
                'exists:assets,id',
                Rule::exists('assets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'maintenance_type' => 'required|in:preventive,corrective,emergency,upgrade,inspection',
            'scheduled_date' => 'required|date',
            'completed_date' => 'nullable|date|after_or_equal:scheduled_date',
            'technician_id' => [
                'nullable',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'vendor_id' => [
                'nullable',
                'exists:vendors,id',
                Rule::exists('vendors', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'cost' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'next_maintenance_date' => 'nullable|date|after:scheduled_date',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $maintenance->fill($request->all());
        $maintenance->save();

        return redirect()->route('assets.maintenance.index')
                        ->with('success', 'Maintenance record updated successfully.');
    }

    /**
     * Remove the specified maintenance record
     */
    public function destroy(AssetMaintenance $maintenance)
    {
        $this->authorize('delete', $maintenance);

        $maintenance->delete();

        return redirect()->route('assets.maintenance.index')
                        ->with('success', 'Maintenance record deleted successfully.');
    }

    /**
     * Export maintenance records to CSV
     */
    public function export(Request $request)
    {
        $query = AssetMaintenance::with(['asset.client', 'technician', 'vendor'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('maintenance_type', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('maintenance_type')) {
            $query->where('maintenance_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($assetId = $request->get('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $maintenance = $query->orderBy('scheduled_date', 'desc')->get();

        $filename = 'asset_maintenance_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($maintenance) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Asset Name',
                'Asset Serial',
                'Client',
                'Maintenance Type',
                'Scheduled Date',
                'Completed Date',
                'Technician',
                'Vendor',
                'Cost',
                'Status',
                'Description',
                'Next Maintenance'
            ]);

            // CSV data
            foreach ($maintenance as $record) {
                fputcsv($file, [
                    $record->asset->name,
                    $record->asset->serial,
                    $record->asset->client->name ?? '',
                    ucfirst($record->maintenance_type),
                    $record->scheduled_date?->format('Y-m-d'),
                    $record->completed_date?->format('Y-m-d'),
                    $record->technician->name ?? '',
                    $record->vendor->name ?? '',
                    $record->cost ? '$' . number_format($record->cost, 2) : '',
                    ucfirst($record->status),
                    $record->description,
                    $record->next_maintenance_date?->format('Y-m-d'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mark maintenance as completed
     */
    public function markCompleted(AssetMaintenance $maintenance)
    {
        $this->authorize('update', $maintenance);

        $maintenance->update([
            'status' => 'completed',
            'completed_date' => now(),
        ]);

        return redirect()->back()
                        ->with('success', 'Maintenance marked as completed.');
    }

    /**
     * Schedule next maintenance
     */
    public function scheduleNext(Request $request, AssetMaintenance $maintenance)
    {
        $this->authorize('update', $maintenance);

        $request->validate([
            'next_maintenance_date' => 'required|date|after:today',
            'maintenance_type' => 'required|in:preventive,corrective,emergency,upgrade,inspection',
            'description' => 'required|string',
        ]);

        // Create new maintenance record
        AssetMaintenance::create([
            'company_id' => auth()->user()->company_id,
            'asset_id' => $maintenance->asset_id,
            'maintenance_type' => $request->maintenance_type,
            'scheduled_date' => $request->next_maintenance_date,
            'technician_id' => $maintenance->technician_id,
            'vendor_id' => $maintenance->vendor_id,
            'description' => $request->description,
            'status' => 'scheduled',
        ]);

        return redirect()->back()
                        ->with('success', 'Next maintenance scheduled successfully.');
    }
}