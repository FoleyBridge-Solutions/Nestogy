<?php

namespace App\Http\Controllers;

use App\Models\ServiceTaxRate;
use App\Models\TaxProfile;
use App\Models\TaxJurisdiction;
use App\Models\TaxCategory;
use App\Services\TaxEngine\TaxEngineRouter;
use App\Services\TaxEngine\TaxProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Tax Administration Controller
 * 
 * Provides administrative interface for managing tax rates, profiles,
 * and system settings for the comprehensive tax system.
 */
class TaxAdminController extends Controller
{
    protected TaxEngineRouter $taxEngine;
    protected TaxProfileService $profileService;

    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        
        // Initialize services with company context
        $companyId = auth()->user()->company_id ?? 1;
        $this->taxEngine = new TaxEngineRouter($companyId);
        $this->profileService = new TaxProfileService($companyId);
    }

    /**
     * Display tax administration dashboard
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;
        
        // Get system statistics
        $stats = [
            'tax_profiles' => TaxProfile::where('company_id', $companyId)->count(),
            'tax_rates' => ServiceTaxRate::where('company_id', $companyId)->active()->count(),
            'jurisdictions' => TaxJurisdiction::where('company_id', $companyId)->count(),
            'categories' => TaxCategory::where('company_id', $companyId)->count(),
        ];
        
        // Get recent tax calculations for monitoring
        $recentCalculations = DB::table('tax_calculations')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get performance metrics
        $performanceStats = $this->taxEngine->getCacheStatistics();
        
        return view('admin.tax.index', compact(
            'stats',
            'recentCalculations', 
            'performanceStats'
        ));
    }

    /**
     * Display tax profiles management
     */
    public function profiles()
    {
        $companyId = auth()->user()->company_id;
        
        $profiles = TaxProfile::where('company_id', $companyId)
            ->with(['category', 'taxCategory'])
            ->ordered()
            ->paginate(20);
        
        $availableCategories = \App\Models\Category::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        $availableTaxCategories = TaxCategory::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        return view('admin.tax.profiles', compact(
            'profiles',
            'availableCategories',
            'availableTaxCategories'
        ));
    }

    /**
     * Display tax rates management
     */
    public function rates(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        $query = ServiceTaxRate::where('company_id', $companyId)
            ->with(['jurisdiction', 'category']);
        
        // Apply filters
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        
        if ($request->filled('tax_type')) {
            $query->where('tax_type', $request->tax_type);
        }
        
        if ($request->filled('jurisdiction_id')) {
            $query->where('tax_jurisdiction_id', $request->jurisdiction_id);
        }
        
        if ($request->filled('active')) {
            if ($request->active === '1') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }
        
        $rates = $query->orderBy('priority')
            ->orderBy('service_type')
            ->paginate(25);
        
        // Get filter options
        $serviceTypes = ServiceTaxRate::getServiceTypes();
        $taxTypes = ServiceTaxRate::getTaxTypes();
        $jurisdictions = TaxJurisdiction::where('company_id', $companyId)
            ->orderBy('name')
            ->get();
        
        return view('admin.tax.rates', compact(
            'rates',
            'serviceTypes',
            'taxTypes',
            'jurisdictions'
        ));
    }

    /**
     * Display tax jurisdictions management
     */
    public function jurisdictions()
    {
        $companyId = auth()->user()->company_id;
        
        $jurisdictions = TaxJurisdiction::where('company_id', $companyId)
            ->withCount('taxRates')
            ->orderBy('state')
            ->orderBy('name')
            ->paginate(20);
        
        return view('admin.tax.jurisdictions', compact('jurisdictions'));
    }

    /**
     * Display system performance and monitoring
     */
    public function performance()
    {
        $companyId = auth()->user()->company_id;
        
        // Get cache statistics
        $cacheStats = $this->taxEngine->getCacheStatistics();
        
        // Get recent calculation performance
        $performanceData = DB::table('tax_calculations')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('
                created_at::date as date,
                COUNT(*) as calculations_count,
                AVG(calculation_time_ms) as avg_time,
                MAX(calculation_time_ms) as max_time,
                MIN(calculation_time_ms) as min_time
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get error rates
        $errorData = DB::table('tax_calculations')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('
                created_at::date as date,
                COUNT(*) as total_calculations,
                SUM(CASE WHEN status = "error" THEN 1 ELSE 0 END) as error_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get most used engines
        $engineUsage = DB::table('tax_calculations')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('engine_used, COUNT(*) as usage_count')
            ->groupBy('engine_used')
            ->orderBy('usage_count', 'desc')
            ->get();
        
        return view('admin.tax.performance', compact(
            'cacheStats',
            'performanceData',
            'errorData',
            'engineUsage'
        ));
    }

    /**
     * Bulk operations for tax rates
     */
    public function bulkOperations(Request $request)
    {
        $validated = $request->validate([
            'operation' => 'required|in:activate,deactivate,delete,recalculate',
            'rate_ids' => 'required|array',
            'rate_ids.*' => 'exists:service_tax_rates,id',
        ]);
        
        $companyId = auth()->user()->company_id;
        $rateIds = $validated['rate_ids'];
        
        // Verify all rates belong to current company
        $rates = ServiceTaxRate::whereIn('id', $rateIds)
            ->where('company_id', $companyId)
            ->get();
        
        if ($rates->count() !== count($rateIds)) {
            return redirect()->back()->withErrors(['bulk' => 'Some rates were not found or do not belong to your company.']);
        }
        
        DB::transaction(function () use ($validated, $rates) {
            switch ($validated['operation']) {
                case 'activate':
                    ServiceTaxRate::whereIn('id', $rates->pluck('id'))->update(['is_active' => true]);
                    break;
                    
                case 'deactivate':
                    ServiceTaxRate::whereIn('id', $rates->pluck('id'))->update(['is_active' => false]);
                    break;
                    
                case 'delete':
                    ServiceTaxRate::whereIn('id', $rates->pluck('id'))->delete();
                    break;
                    
                case 'recalculate':
                    // Clear caches to force recalculation
                    $this->taxEngine->clearTaxCaches();
                    break;
            }
        });
        
        // Clear relevant caches
        $this->taxEngine->clearTaxCaches();
        
        $message = match($validated['operation']) {
            'activate' => 'Tax rates activated successfully.',
            'deactivate' => 'Tax rates deactivated successfully.',
            'delete' => 'Tax rates deleted successfully.',
            'recalculate' => 'Tax calculation caches cleared successfully.',
        };
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Clear all tax caches
     */
    public function clearCaches(Request $request)
    {
        try {
            $this->taxEngine->clearTaxCaches();
            
            Log::info('Tax caches cleared via admin interface', [
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id,
            ]);
            
            return redirect()->back()->with('success', 'All tax caches cleared successfully.');
            
        } catch (\Exception $e) {
            Log::error('Failed to clear tax caches', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return redirect()->back()->withErrors(['cache' => 'Failed to clear caches: ' . $e->getMessage()]);
        }
    }

    /**
     * Warm up tax caches
     */
    public function warmCaches(Request $request)
    {
        try {
            $categoryIds = $request->input('category_ids', []);
            $this->taxEngine->warmUpCaches($categoryIds);
            
            Log::info('Tax caches warmed up via admin interface', [
                'user_id' => auth()->id(),
                'company_id' => auth()->user()->company_id,
                'categories' => count($categoryIds),
            ]);
            
            return redirect()->back()->with('success', 'Tax caches warmed up successfully.');
            
        } catch (\Exception $e) {
            Log::error('Failed to warm up tax caches', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            
            return redirect()->back()->withErrors(['cache' => 'Failed to warm up caches: ' . $e->getMessage()]);
        }
    }

    /**
     * Export tax configuration
     */
    public function exportConfig(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        $config = [
            'profiles' => TaxProfile::where('company_id', $companyId)->get(),
            'rates' => ServiceTaxRate::where('company_id', $companyId)->active()->get(),
            'jurisdictions' => TaxJurisdiction::where('company_id', $companyId)->get(),
            'categories' => TaxCategory::where('company_id', $companyId)->get(),
            'exported_at' => now()->toISOString(),
            'company_id' => $companyId,
        ];
        
        $filename = 'tax-config-' . $companyId . '-' . now()->format('Y-m-d') . '.json';
        
        return response()->json($config)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Test tax calculation system
     */
    public function testCalculation(Request $request)
    {
        $validated = $request->validate([
            'test_data' => 'required|array',
            'test_data.base_price' => 'required|numeric|min:0.01',
            'test_data.quantity' => 'nullable|integer|min:1',
            'test_data.category_id' => 'nullable|exists:categories,id',
            'test_data.customer_id' => 'nullable|exists:clients,id',
            'test_data.tax_data' => 'nullable|array',
        ]);
        
        try {
            $result = $this->taxEngine->calculateTaxes($validated['test_data']);
            
            return response()->json([
                'success' => true,
                'result' => $result,
                'test_data' => $validated['test_data'],
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'test_data' => $validated['test_data'],
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }
}