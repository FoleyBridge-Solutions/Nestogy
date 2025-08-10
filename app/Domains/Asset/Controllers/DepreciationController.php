<?php

namespace App\Domains\Asset\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Asset\Models\AssetDepreciation;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepreciationController extends Controller
{
    /**
     * Display a listing of all depreciation records (standalone view)
     */
    public function index(Request $request)
    {
        $query = AssetDepreciation::with(['asset.client'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply search filters
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('method', 'like', "%{$search}%")
                  ->orWhereHas('asset', function($assetQuery) use ($search) {
                      $assetQuery->where('name', 'like', "%{$search}%")
                                 ->orWhere('serial', 'like', "%{$search}%");
                  });
            });
        }

        // Apply method filters
        if ($method = $request->get('method')) {
            $query->where('method', $method);
        }

        // Apply asset filter
        if ($assetId = $request->get('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $depreciations = $query->orderBy('created_at', 'desc')
                              ->paginate(20)
                              ->appends($request->query());

        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        return view('assets.depreciations.index', compact('depreciations', 'assets'));
    }

    /**
     * Show the form for creating a new depreciation record
     */
    public function create(Request $request)
    {
        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        $selectedAssetId = $request->get('asset_id');

        return view('assets.depreciations.create', compact('assets', 'selectedAssetId'));
    }

    /**
     * Store a newly created depreciation record
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
            'original_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'method' => 'required|in:straight_line,declining_balance,double_declining,sum_of_years,units_of_production',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $depreciation = new AssetDepreciation($request->all());
        $depreciation->company_id = auth()->user()->company_id;
        
        // Calculate annual depreciation based on method
        $depreciation->calculateAnnualDepreciation();
        
        $depreciation->save();

        return redirect()->route('assets.depreciations.index')
                        ->with('success', 'Depreciation record created successfully.');
    }

    /**
     * Display the specified depreciation record
     */
    public function show(AssetDepreciation $depreciation)
    {
        $this->authorize('view', $depreciation);

        $depreciation->load(['asset.client']);

        // Get depreciation schedule
        $schedule = $depreciation->getDepreciationSchedule();

        return view('assets.depreciations.show', compact('depreciation', 'schedule'));
    }

    /**
     * Show the form for editing the specified depreciation record
     */
    public function edit(AssetDepreciation $depreciation)
    {
        $this->authorize('update', $depreciation);

        $assets = Asset::where('company_id', auth()->user()->company_id)
                      ->orderBy('name')
                      ->get();

        return view('assets.depreciations.edit', compact('depreciation', 'assets'));
    }

    /**
     * Update the specified depreciation record
     */
    public function update(Request $request, AssetDepreciation $depreciation)
    {
        $this->authorize('update', $depreciation);

        $validator = Validator::make($request->all(), [
            'asset_id' => [
                'required',
                'exists:assets,id',
                Rule::exists('assets', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'original_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1',
            'method' => 'required|in:straight_line,declining_balance,double_declining,sum_of_years,units_of_production',
            'depreciation_rate' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $depreciation->fill($request->all());
        
        // Recalculate annual depreciation
        $depreciation->calculateAnnualDepreciation();
        
        $depreciation->save();

        return redirect()->route('assets.depreciations.index')
                        ->with('success', 'Depreciation record updated successfully.');
    }

    /**
     * Remove the specified depreciation record
     */
    public function destroy(AssetDepreciation $depreciation)
    {
        $this->authorize('delete', $depreciation);

        $depreciation->delete();

        return redirect()->route('assets.depreciations.index')
                        ->with('success', 'Depreciation record deleted successfully.');
    }

    /**
     * Export depreciation records to CSV
     */
    public function export(Request $request)
    {
        $query = AssetDepreciation::with(['asset.client'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('method', 'like', "%{$search}%");
            });
        }

        if ($method = $request->get('method')) {
            $query->where('method', $method);
        }

        if ($assetId = $request->get('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $depreciations = $query->orderBy('created_at', 'desc')->get();

        $filename = 'asset_depreciations_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($depreciations) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Asset Name',
                'Asset Serial',
                'Client',
                'Original Cost',
                'Salvage Value',
                'Useful Life (Years)',
                'Method',
                'Annual Depreciation',
                'Accumulated Depreciation',
                'Book Value',
                'Start Date',
                'Notes'
            ]);

            // CSV data
            foreach ($depreciations as $depreciation) {
                fputcsv($file, [
                    $depreciation->asset->name,
                    $depreciation->asset->serial,
                    $depreciation->asset->client->name ?? '',
                    '$' . number_format($depreciation->original_cost, 2),
                    $depreciation->salvage_value ? '$' . number_format($depreciation->salvage_value, 2) : '',
                    $depreciation->useful_life_years,
                    ucfirst(str_replace('_', ' ', $depreciation->method)),
                    '$' . number_format($depreciation->annual_depreciation, 2),
                    '$' . number_format($depreciation->accumulated_depreciation, 2),
                    '$' . number_format($depreciation->current_book_value, 2),
                    $depreciation->start_date?->format('Y-m-d'),
                    $depreciation->notes,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate depreciation report
     */
    public function report(Request $request)
    {
        $query = AssetDepreciation::with(['asset.client'])
            ->whereHas('asset', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        $year = $request->get('year', date('Y'));
        
        // Get depreciation data for the specified year
        $depreciations = $query->get();
        
        $reportData = [
            'total_original_cost' => $depreciations->sum('original_cost'),
            'total_accumulated_depreciation' => $depreciations->sum('accumulated_depreciation'),
            'total_book_value' => $depreciations->sum('current_book_value'),
            'annual_depreciation' => $depreciations->sum('annual_depreciation'),
            'by_method' => $depreciations->groupBy('method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total_cost' => $group->sum('original_cost'),
                    'total_depreciation' => $group->sum('accumulated_depreciation'),
                ];
            }),
        ];

        return view('assets.depreciations.report', compact('depreciations', 'reportData', 'year'));
    }

    /**
     * Recalculate depreciation for an asset
     */
    public function recalculate(AssetDepreciation $depreciation)
    {
        $this->authorize('update', $depreciation);

        $depreciation->calculateAnnualDepreciation();
        $depreciation->save();

        return redirect()->back()
                        ->with('success', 'Depreciation recalculated successfully.');
    }
}