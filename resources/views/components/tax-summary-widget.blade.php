@props([
    'period' => '30_days',
    'showDetails' => true,
])

@php
    use App\Models\TaxCalculation;
    use Carbon\Carbon;
    
    $companyId = auth()->user()->company_id;
    
    // Get date range
    $dateRange = match($period) {
        '7_days' => [now()->subDays(7), now()],
        '30_days' => [now()->subDays(30), now()],
        '90_days' => [now()->subDays(90), now()],
        default => [now()->subDays(30), now()]
    };
    
    // Get tax summary data
    $taxSummary = TaxCalculation::where('company_id', $companyId)
        ->whereBetween('created_at', $dateRange)
        ->selectRaw('
            COUNT(*) as total_calculations,
            SUM(CASE WHEN status = "completed" THEN total_amount ELSE 0 END) as total_tax,
            AVG(CASE WHEN status = "completed" THEN calculation_time_ms ELSE NULL END) as avg_time,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful_calculations
        ')
        ->first();
    
    $successRate = $taxSummary->total_calculations > 0 
        ? ($taxSummary->successful_calculations / $taxSummary->total_calculations) * 100 
        : 0;
    
    // Get recent calculations
    $recentCalculations = TaxCalculation::where('company_id', $companyId)
        ->whereBetween('created_at', $dateRange)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
@endphp

<div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-calculator text-blue-600 mr-2"></i>
                Tax Calculations
            </h3>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-gray-500">Last {{ $period === '7_days' ? '7' : ($period === '30_days' ? '30' : '90') }} days</span>
                <a href="{{ route('reports.tax.index') }}" class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                    View all
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="p-6">
        <div class="grid grid-cols-2 gap-4 mb-6">
            <!-- Total Calculations -->
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">{{ number_format($taxSummary->total_calculations ?? 0) }}</div>
                <div class="text-sm text-gray-500">Total Calculations</div>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $successRate >= 95 ? 'bg-green-100 text-green-800' : ($successRate >= 90 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ number_format($successRate, 1) }}% success
                    </span>
                </div>
            </div>

            <!-- Total Tax -->
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">${{ number_format($taxSummary->total_tax ?? 0, 2) }}</div>
                <div class="text-sm text-gray-500">Tax Calculated</div>
                <div class="mt-1">
                    <span class="text-xs text-gray-500">
                        Avg: {{ number_format($taxSummary->avg_time ?? 0, 1) }}ms
                    </span>
                </div>
            </div>
        </div>

        @if($showDetails && $recentCalculations->count() > 0)
        <!-- Recent Calculations -->
        <div class="border-t border-gray-200 pt-4">
            <h4 class="text-sm font-medium text-gray-900 mb-6">Recent Calculations</h4>
            <div class="space-y-3">
                @foreach($recentCalculations as $calc)
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full text-xs font-medium
                                {{ $calc->status === 'completed' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                                <i class="fas {{ $calc->status === 'completed' ? 'fa-check' : 'fa-times' }}"></i>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-gray-900 font-medium">
                                {{ ucfirst($calc->calculation_type) }}
                            </div>
                            <div class="text-gray-500 text-xs">
                                {{ $calc->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-gray-900 font-medium">
                            ${{ number_format($calc->total_amount ?? 0, 2) }}
                        </div>
                        <div class="text-gray-500 text-xs">
                            {{ number_format($calc->calculation_time_ms ?? 0, 1) }}ms
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($taxSummary->total_calculations == 0)
        <!-- Empty State -->
        <div class="text-center py-6">
            <div class="text-gray-400">
                <i class="fas fa-calculator fa-2x mb-6"></i>
                <p class="text-sm">No tax calculations in the last {{ $period === '7_days' ? '7' : ($period === '30_days' ? '30' : '90') }} days</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="px-6 py-6 bg-gray-50 border-t border-gray-200">
        <div class="flex items-center justify-between text-sm">
            <a href="{{ route('reports.tax.summary') }}" class="text-blue-600 hover:text-blue-500 font-medium">
                <i class="fas fa-chart-pie mr-1"></i>
                View detailed report
            </a>
            <a href="{{ route('admin.tax.index') }}" class="text-gray-600 hover:text-gray-500">
                <i class="fas fa-cog mr-1"></i>
                Manage settings
            </a>
        </div>
    </div>
</div>
