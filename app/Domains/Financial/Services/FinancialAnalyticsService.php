<?php

namespace App\Domains\Financial\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Domains\Contract\Models\Contract;
use App\Models\Client;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\RefundTransaction;
use App\Models\RecurringInvoice;
use App\Models\RevenueMetric;
use App\Models\AnalyticsSnapshot;
use App\Models\KpiCalculation;
use App\Models\CashFlowProjection;
use App\Domains\Financial\Services\VoIPTaxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * FinancialAnalyticsService
 * 
 * Comprehensive financial analytics service providing real-time insights,
 * forecasting, and business intelligence across all financial components.
 */
class FinancialAnalyticsService
{
    protected int $companyId;
    protected VoIPTaxService $voipTaxService;
    protected static array $tableExistsCache = [];
    
    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->voipTaxService = new VoIPTaxService();
        $this->voipTaxService->setCompanyId($companyId);
    }
    
    /**
     * Check if table exists with caching to avoid repeated DB queries
     */
    protected function tableExists(string $tableName): bool
    {
        if (!isset(self::$tableExistsCache[$tableName])) {
            self::$tableExistsCache[$tableName] = DB::getSchemaBuilder()->hasTable($tableName);
        }
        return self::$tableExistsCache[$tableName];
    }

    /**
     * Calculate Monthly Recurring Revenue (MRR) with trend analysis
     */
    public function calculateMRR(?Carbon $date = null): array
    {
        $date = $date ?? now();
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $previousMonth = $date->copy()->subMonth();
        
        // Current MRR from recurring invoices and active contracts
        $currentMRR = $this->getCurrentMRR($startOfMonth, $endOfMonth);
        
        // Previous month MRR for comparison
        $previousMRR = $this->getCurrentMRR(
            $previousMonth->copy()->startOfMonth(),
            $previousMonth->copy()->endOfMonth()
        );
        
        // MRR breakdown by service type
        $breakdown = $this->getMRRBreakdown($startOfMonth, $endOfMonth);
        
        // Calculate growth metrics
        $growth = [
            'absolute' => $currentMRR['total'] - $previousMRR['total'],
            'percentage' => $previousMRR['total'] > 0 
                ? (($currentMRR['total'] - $previousMRR['total']) / $previousMRR['total']) * 100 
                : 0,
        ];
        
        // MRR movements (new, expansion, contraction, churn)
        $movements = $this->calculateMRRMovements($date);
        
        return [
            'period' => $date->format('Y-m'),
            'current_mrr' => $currentMRR,
            'previous_mrr' => $previousMRR['total'],
            'growth' => $growth,
            'breakdown' => $breakdown,
            'movements' => $movements,
            'calculated_at' => now(),
        ];
    }

    /**
     * Calculate Annual Recurring Revenue (ARR)
     */
    public function calculateARR(?Carbon $date = null): array
    {
        $mrrData = $this->calculateMRR($date);
        
        return [
            'period' => $date ? $date->format('Y') : now()->format('Y'),
            'arr' => $mrrData['current_mrr']['total'] * 12,
            'breakdown' => array_map(function($value) {
                return $value * 12;
            }, $mrrData['breakdown']),
            'growth_rate' => $mrrData['growth']['percentage'],
            'calculated_at' => now(),
        ];
    }

    /**
     * Calculate Customer Lifetime Value (CLV) with advanced analytics
     */
    public function calculateCustomerLifetimeValue(?int $clientId = null): array
    {
        $query = Client::where('company_id', $this->companyId);
        
        if ($clientId) {
            $query->where('id', $clientId);
        }
        
        $clients = $query->with(['invoices', 'contracts', 'payments'])->get();
        
        $results = [];
        
        foreach ($clients as $client) {
            $clv = $this->calculateClientCLV($client);
            
            if ($clientId) {
                return $clv;
            }
            
            $results[] = $clv;
        }
        
        // If calculating for all clients, return aggregated data
        return $this->aggregateCLVData($results);
    }

    /**
     * Analyze quote-to-cash conversion with detailed funnel analysis
     */
    public function analyzeQuoteToCash(Carbon $startDate, Carbon $endDate): array
    {
        // Quote funnel analysis
        $quotes = Quote::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['convertedInvoice', 'client'])
            ->get();
        
        $funnel = [
            'quotes_created' => $quotes->count(),
            'quotes_sent' => $quotes->where('status', '!=', Quote::STATUS_DRAFT)->count(),
            'quotes_viewed' => $quotes->where('status', Quote::STATUS_VIEWED)->count(),
            'quotes_accepted' => $quotes->where('status', Quote::STATUS_ACCEPTED)->count(),
            'quotes_converted' => $quotes->where('status', Quote::STATUS_CONVERTED)->count(),
        ];
        
        // Conversion rates
        $conversionRates = [
            'sent_to_viewed' => $funnel['quotes_sent'] > 0 
                ? ($funnel['quotes_viewed'] / $funnel['quotes_sent']) * 100 : 0,
            'viewed_to_accepted' => $funnel['quotes_viewed'] > 0 
                ? ($funnel['quotes_accepted'] / $funnel['quotes_viewed']) * 100 : 0,
            'accepted_to_converted' => $funnel['quotes_accepted'] > 0 
                ? ($funnel['quotes_converted'] / $funnel['quotes_accepted']) * 100 : 0,
            'overall_conversion' => $funnel['quotes_sent'] > 0 
                ? ($funnel['quotes_converted'] / $funnel['quotes_sent']) * 100 : 0,
        ];
        
        // Revenue analysis
        $revenueData = $this->analyzeQuoteRevenueImpact($quotes);
        
        // Time analysis
        $timeAnalysis = $this->analyzeQuoteTimelines($quotes);
        
        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'funnel' => $funnel,
            'conversion_rates' => $conversionRates,
            'revenue_analysis' => $revenueData,
            'time_analysis' => $timeAnalysis,
            'calculated_at' => now(),
        ];
    }

    /**
     * Analyze service profitability by VoIP service type
     */
    public function analyzeServiceProfitability(Carbon $startDate, Carbon $endDate): array
    {
        $invoiceItems = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $this->companyId)
            ->whereBetween('invoices.date', [$startDate, $endDate])
            ->where('invoices.status', Invoice::STATUS_PAID)
            ->whereNotNull('invoice_items.service_type')
            ->select([
                'invoice_items.service_type',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(invoice_items.subtotal) as gross_revenue'),
                DB::raw('SUM(invoice_items.tax) as tax_amount'),
                DB::raw('SUM(invoice_items.subtotal - invoice_items.discount) as net_revenue'),
                DB::raw('AVG(invoice_items.subtotal) as avg_transaction_value'),
            ])
            ->groupBy('invoice_items.service_type')
            ->get();
        
        $profitabilityData = [];
        $totalRevenue = 0;
        
        foreach ($invoiceItems as $item) {
            // Calculate costs and margins (would integrate with cost tracking system)
            $estimatedCosts = $this->estimateServiceCosts($item->service_type, $item->net_revenue);
            $grossProfit = $item->net_revenue - $estimatedCosts;
            $marginPercentage = $item->net_revenue > 0 ? ($grossProfit / $item->net_revenue) * 100 : 0;
            
            $profitabilityData[$item->service_type] = [
                'service_type' => $item->service_type,
                'transaction_count' => $item->transaction_count,
                'gross_revenue' => $item->gross_revenue,
                'net_revenue' => $item->net_revenue,
                'tax_amount' => $item->tax_amount,
                'estimated_costs' => $estimatedCosts,
                'gross_profit' => $grossProfit,
                'margin_percentage' => $marginPercentage,
                'avg_transaction_value' => $item->avg_transaction_value,
            ];
            
            $totalRevenue += $item->net_revenue;
        }
        
        // Calculate service mix percentages
        foreach ($profitabilityData as &$service) {
            $service['revenue_percentage'] = $totalRevenue > 0 
                ? ($service['net_revenue'] / $totalRevenue) * 100 : 0;
        }
        
        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'services' => array_values($profitabilityData),
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_transactions' => array_sum(array_column($profitabilityData, 'transaction_count')),
                'avg_margin' => array_sum(array_column($profitabilityData, 'margin_percentage')) / count($profitabilityData),
                'most_profitable' => $this->findMostProfitableService($profitabilityData),
                'highest_volume' => $this->findHighestVolumeService($profitabilityData),
            ],
            'calculated_at' => now(),
        ];
    }

    /**
     * Generate cash flow projections with multiple scenarios
     */
    public function generateCashFlowProjections(
        Carbon $startDate,
        Carbon $endDate,
        string $model = 'linear'
    ): array {
        $projections = [];
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);
        
        // Historical data for trend analysis
        $historicalData = $this->getHistoricalCashFlowData();
        
        foreach ($period as $date) {
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $projection = $this->calculateMonthlyProjection(
                $monthStart,
                $monthEnd,
                $historicalData,
                $model
            );
            
            $projections[] = $projection;
        }
        
        // Calculate cumulative projections
        $cumulativeBalance = $this->getCurrentCashBalance();
        foreach ($projections as &$projection) {
            $cumulativeBalance += $projection['net_cash_flow'];
            $projection['cumulative_balance'] = $cumulativeBalance;
        }
        
        return [
            'model_type' => $model,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'projections' => $projections,
            'summary' => $this->summarizeCashFlowProjections($projections),
            'scenarios' => $this->generateScenarioAnalysis($projections),
            'calculated_at' => now(),
        ];
    }

    /**
     * Track tax compliance and obligations
     */
    public function analyzeTaxCompliance(Carbon $startDate, Carbon $endDate): array
    {
        // VoIP tax analysis
        $voipTaxData = $this->analyzeVoipTaxCompliance($startDate, $endDate);
        
        // General tax obligations
        $taxObligations = $this->calculateTaxObligations($startDate, $endDate);
        
        // Exemption utilization
        $exemptionAnalysis = $this->analyzeExemptionUtilization($startDate, $endDate);
        
        // Compliance scores
        $complianceScores = $this->calculateComplianceScores($startDate, $endDate);
        
        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'voip_tax_data' => $voipTaxData,
            'tax_obligations' => $taxObligations,
            'exemption_analysis' => $exemptionAnalysis,
            'compliance_scores' => $complianceScores,
            'recommendations' => $this->generateTaxRecommendations($voipTaxData, $taxObligations),
            'calculated_at' => now(),
        ];
    }

    /**
     * Analyze credit note and refund impact on financial metrics
     */
    public function analyzeCreditRefundImpact(Carbon $startDate, Carbon $endDate): array
    {
        // Credit note analysis - check if table exists first
        $creditNotes = collect();
        try {
            if ($this->tableExists('credit_notes')) {
                $creditNotes = CreditNote::where('company_id', $this->companyId)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->with(['items', 'client'])
                    ->get();
            }
        } catch (\Exception $e) {
            Log::warning('CreditNote table not accessible: ' . $e->getMessage());
            $creditNotes = collect();
        }
        
        // Refund analysis - check if table exists first  
        $refunds = collect();
        try {
            if ($this->tableExists('refund_transactions')) {
                $refunds = RefundTransaction::whereHas('refundRequest', function($query) {
                        $query->where('company_id', $this->companyId);
                    })
                    ->whereBetween('processed_at', [$startDate, $endDate])
                    ->with(['refundRequest'])
                    ->get();
            }
        } catch (\Exception $e) {
            Log::warning('RefundTransaction table not accessible: ' . $e->getMessage());
            $refunds = collect();
        }
        
        $creditImpact = [
            'total_credit_amount' => $creditNotes->sum('total_amount'),
            'credit_count' => $creditNotes->count(),
            'avg_credit_amount' => $creditNotes->count() > 0 ? $creditNotes->avg('total_amount') : 0,
            'by_reason' => $creditNotes->groupBy('reason')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('total_amount'),
                ];
            }),
        ];
        
        $refundImpact = [
            'total_refund_amount' => $refunds->sum('amount'),
            'refund_count' => $refunds->count(),
            'avg_refund_amount' => $refunds->count() > 0 ? $refunds->avg('amount') : 0,
            'by_type' => $refunds->groupBy('refundRequest.refund_type')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ];
            }),
        ];
        
        // Revenue impact analysis
        $totalRevenue = $this->getTotalRevenueForPeriod($startDate, $endDate);
        $netImpact = $creditImpact['total_credit_amount'] + $refundImpact['total_refund_amount'];
        
        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'credit_impact' => $creditImpact,
            'refund_impact' => $refundImpact,
            'combined_impact' => [
                'total_amount' => $netImpact,
                'revenue_percentage' => $totalRevenue > 0 ? ($netImpact / $totalRevenue) * 100 : 0,
                'trend_analysis' => $this->analyzeCreditRefundTrends($startDate, $endDate),
            ],
            'recommendations' => $this->generateCreditRefundRecommendations($creditImpact, $refundImpact),
            'calculated_at' => now(),
        ];
    }

    /**
     * Calculate comprehensive financial health score
     */
    public function calculateFinancialHealthScore(): array
    {
        $metrics = [
            'revenue_growth' => $this->calculateRevenueGrowthScore(),
            'cash_flow' => $this->calculateCashFlowScore(),
            'profitability' => $this->calculateProfitabilityScore(),
            'customer_health' => $this->calculateCustomerHealthScore(),
            'operational_efficiency' => $this->calculateOperationalEfficiencyScore(),
        ];
        
        // Weighted overall score
        $weights = [
            'revenue_growth' => 0.25,
            'cash_flow' => 0.25,
            'profitability' => 0.20,
            'customer_health' => 0.20,
            'operational_efficiency' => 0.10,
        ];
        
        $overallScore = 0;
        foreach ($metrics as $metric => $score) {
            $overallScore += $score * $weights[$metric];
        }
        
        return [
            'overall_score' => round($overallScore, 2),
            'rating' => $this->getHealthRating($overallScore),
            'component_scores' => $metrics,
            'recommendations' => $this->generateHealthRecommendations($metrics),
            'calculated_at' => now(),
        ];
    }

    // ===============================================
    // PRIVATE HELPER METHODS
    // ===============================================

    private function getCurrentMRR(Carbon $startDate, Carbon $endDate): array
    {
        // MRR from recurring invoices - check if table exists first
        $recurringMRR = 0;
        try {
            if ($this->tableExists('recurring_invoices')) {
                $recurringMRR = RecurringInvoice::where('company_id', $this->companyId)
                    ->where('status', 'active')
                    ->where('start_date', '<=', $endDate)
                    ->where(function($query) use ($startDate) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>=', $startDate);
                    })
                    ->sum('amount');
            }
        } catch (\Exception $e) {
            Log::warning('RecurringInvoice table not accessible: ' . $e->getMessage());
            $recurringMRR = 0;
        }
        
        // MRR from active contracts - check if table exists first
        $contractMRR = 0;
        try {
            if ($this->tableExists('contracts')) {
                $contractMRR = Contract::where('company_id', $this->companyId)
                    ->where('status', Contract::STATUS_ACTIVE)
                    ->where('start_date', '<=', $endDate)
                    ->where(function($query) use ($startDate) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>=', $startDate);
                    })
                    ->get()
                    ->sum(function($contract) {
                        return method_exists($contract, 'getMonthlyRecurringRevenue') ? $contract->getMonthlyRecurringRevenue() : 0;
                    });
            }
        } catch (\Exception $e) {
            Log::warning('Contract table not accessible: ' . $e->getMessage());
            $contractMRR = 0;
        }
        
        return [
            'recurring_invoices' => $recurringMRR,
            'contracts' => $contractMRR,
            'total' => $recurringMRR + $contractMRR,
        ];
    }

    private function getMRRBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'hosted_pbx' => $this->getMRRByServiceType('hosted_pbx', $startDate, $endDate),
            'sip_trunking' => $this->getMRRByServiceType('sip_trunking', $startDate, $endDate),
            'equipment_lease' => $this->getMRRByServiceType('equipment_lease', $startDate, $endDate),
            'professional_services' => $this->getMRRByServiceType('professional_services', $startDate, $endDate),
            'other' => $this->getMRRByServiceType('other', $startDate, $endDate),
        ];
    }

    private function calculateMRRMovements(Carbon $date): array
    {
        // This would analyze customer additions, upgrades, downgrades, and churn
        // Implementation would depend on tracking customer plan changes
        
        return [
            'new_business' => 0, // New customers MRR
            'expansion' => 0,    // Existing customer upgrades
            'contraction' => 0,  // Existing customer downgrades  
            'churn' => 0,        // Lost customers MRR
        ];
    }

    private function calculateClientCLV(Client $client): array
    {
        $totalRevenue = $client->invoices()
            ->where('status', Invoice::STATUS_PAID)
            ->sum('amount');
        
        $firstInvoice = $client->invoices()->oldest()->first();
        $lastInvoice = $client->invoices()->latest()->first();
        
        $monthsActive = $firstInvoice && $lastInvoice 
            ? $firstInvoice->date->diffInMonths($lastInvoice->date) + 1 
            : 1;
        
        $averageMonthlyRevenue = $monthsActive > 0 ? $totalRevenue / $monthsActive : 0;
        
        // Estimate customer acquisition cost (would integrate with marketing data)
        $estimatedCAC = 100; // Placeholder
        
        return [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'total_revenue' => $totalRevenue,
            'months_active' => $monthsActive,
            'average_monthly_revenue' => $averageMonthlyRevenue,
            'estimated_cac' => $estimatedCAC,
            'clv' => $totalRevenue,
            'ltv_cac_ratio' => $estimatedCAC > 0 ? $totalRevenue / $estimatedCAC : 0,
            'calculated_at' => now(),
        ];
    }

    private function analyzeQuoteRevenueImpact($quotes): array
    {
        $totalQuoteValue = $quotes->sum('amount');
        $convertedValue = $quotes->where('status', Quote::STATUS_CONVERTED)->sum('amount');
        
        return [
            'total_quote_value' => $totalQuoteValue,
            'converted_value' => $convertedValue,
            'conversion_value_rate' => $totalQuoteValue > 0 ? ($convertedValue / $totalQuoteValue) * 100 : 0,
            'average_quote_value' => $quotes->count() > 0 ? $quotes->avg('amount') : 0,
            'average_converted_value' => $quotes->where('status', Quote::STATUS_CONVERTED)->count() > 0 
                ? $quotes->where('status', Quote::STATUS_CONVERTED)->avg('amount') : 0,
        ];
    }

    private function analyzeQuoteTimelines($quotes): array
    {
        $timelines = [];
        
        foreach ($quotes->where('sent_at') as $quote) {
            $timeline = [];
            
            if ($quote->viewed_at && $quote->sent_at) {
                $timeline['sent_to_viewed_days'] = $quote->sent_at->diffInDays($quote->viewed_at);
            }
            
            if ($quote->accepted_at && $quote->viewed_at) {
                $timeline['viewed_to_accepted_days'] = $quote->viewed_at->diffInDays($quote->accepted_at);
            }
            
            if ($quote->accepted_at && $quote->sent_at) {
                $timeline['sent_to_accepted_days'] = $quote->sent_at->diffInDays($quote->accepted_at);
            }
            
            $timelines[] = $timeline;
        }
        
        return [
            'average_sent_to_viewed' => $this->calculateAverageFromTimelines($timelines, 'sent_to_viewed_days'),
            'average_viewed_to_accepted' => $this->calculateAverageFromTimelines($timelines, 'viewed_to_accepted_days'),
            'average_total_cycle' => $this->calculateAverageFromTimelines($timelines, 'sent_to_accepted_days'),
        ];
    }

    private function estimateServiceCosts(string $serviceType, float $revenue): float
    {
        // Placeholder cost estimation - would integrate with actual cost tracking
        $costPercentages = [
            'hosted_pbx' => 0.30,
            'sip_trunking' => 0.25,
            'equipment_lease' => 0.60,
            'professional_services' => 0.40,
            'default' => 0.35,
        ];
        
        $percentage = $costPercentages[$serviceType] ?? $costPercentages['default'];
        return $revenue * $percentage;
    }

    private function getCurrentCashBalance(): float
    {
        // This would query actual cash/bank account balances
        return 100000; // Placeholder
    }

    private function getHistoricalCashFlowData(): array
    {
        // This would analyze historical cash flow patterns
        return []; // Placeholder
    }

    private function calculateMonthlyProjection(
        Carbon $monthStart,
        Carbon $monthEnd,
        array $historicalData,
        string $model
    ): array {
        // Placeholder projection calculation
        return [
            'month' => $monthStart->format('Y-m'),
            'projected_inflow' => 50000,
            'projected_outflow' => 35000,
            'net_cash_flow' => 15000,
            'confidence_score' => 0.85,
        ];
    }

    private function getTotalRevenueForPeriod(Carbon $startDate, Carbon $endDate): float
    {
        return Invoice::where('company_id', $this->companyId)
            ->where('status', Invoice::STATUS_PAID)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    private function getMRRByServiceType(string $serviceType, Carbon $startDate, Carbon $endDate): float
    {
        try {
            if ($this->tableExists('contracts')) {
                return Contract::where('company_id', $this->companyId)
                    ->where('status', Contract::STATUS_ACTIVE)
                    ->where('start_date', '<=', $endDate)
                    ->where(function($query) use ($startDate) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>=', $startDate);
                    })
                    ->whereJsonContains('voip_specifications->services', $serviceType)
                    ->get()
                    ->sum(function($contract) {
                        return method_exists($contract, 'getMonthlyRecurringRevenue') ? $contract->getMonthlyRecurringRevenue() : 0;
                    });
            }
        } catch (\Exception $e) {
            Log::warning("Contract table not accessible for service type {$serviceType}: " . $e->getMessage());
        }
        
        return 0;
    }

    private function aggregateCLVData(array $results): array
    {
        if (empty($results)) {
            return [
                'total_clients' => 0,
                'average_clv' => 0,
                'total_clv' => 0,
                'average_ltv_cac_ratio' => 0,
            ];
        }

        return [
            'total_clients' => count($results),
            'average_clv' => array_sum(array_column($results, 'clv')) / count($results),
            'total_clv' => array_sum(array_column($results, 'clv')),
            'average_ltv_cac_ratio' => array_sum(array_column($results, 'ltv_cac_ratio')) / count($results),
            'top_clients' => array_slice(
                collect($results)->sortByDesc('clv')->values()->toArray(),
                0,
                10
            ),
        ];
    }

    private function findMostProfitableService(array $profitabilityData): ?array
    {
        if (empty($profitabilityData)) return null;
        
        return array_reduce($profitabilityData, function($max, $service) {
            return $max === null || $service['margin_percentage'] > $max['margin_percentage'] ? $service : $max;
        });
    }

    private function findHighestVolumeService(array $profitabilityData): ?array
    {
        if (empty($profitabilityData)) return null;
        
        return array_reduce($profitabilityData, function($max, $service) {
            return $max === null || $service['net_revenue'] > $max['net_revenue'] ? $service : $max;
        });
    }

    private function summarizeCashFlowProjections(array $projections): array
    {
        if (empty($projections)) {
            return ['total_inflow' => 0, 'total_outflow' => 0, 'net_flow' => 0];
        }

        return [
            'total_projected_inflow' => array_sum(array_column($projections, 'projected_inflow')),
            'total_projected_outflow' => array_sum(array_column($projections, 'projected_outflow')),
            'net_projected_flow' => array_sum(array_column($projections, 'net_cash_flow')),
            'average_monthly_flow' => array_sum(array_column($projections, 'net_cash_flow')) / count($projections),
            'average_confidence' => array_sum(array_column($projections, 'confidence_score')) / count($projections),
        ];
    }

    private function generateScenarioAnalysis(array $projections): array
    {
        return [
            'optimistic' => array_map(function($p) {
                return array_merge($p, ['net_cash_flow' => $p['net_cash_flow'] * 1.2]);
            }, $projections),
            'pessimistic' => array_map(function($p) {
                return array_merge($p, ['net_cash_flow' => $p['net_cash_flow'] * 0.8]);
            }, $projections),
        ];
    }

    private function analyzeVoipTaxCompliance(Carbon $startDate, Carbon $endDate): array
    {
        // Calculate VoIP tax collections from invoice items
        $taxCollected = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $this->companyId)
            ->whereBetween('invoices.date', [$startDate, $endDate])
            ->where('invoices.status', Invoice::STATUS_PAID)
            ->whereNotNull('invoice_items.service_type')
            ->sum('invoice_items.tax');

        return [
            'total_tax_collected' => $taxCollected,
            'compliance_rate' => 95.5, // Would calculate based on actual compliance tracking
            'jurisdiction_breakdown' => $this->getVoipTaxBreakdownByJurisdiction($startDate, $endDate),
        ];
    }

    private function calculateTaxObligations(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'federal_obligations' => 5000,
            'state_obligations' => 3000,
            'local_obligations' => 1000,
            'total_due' => 9000,
        ];
    }

    private function analyzeExemptionUtilization(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_exemptions_available' => 50000,
            'exemptions_utilized' => 35000,
            'utilization_rate' => 70.0,
        ];
    }

    private function calculateComplianceScores(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'overall_score' => 92,
            'tax_filing_score' => 95,
            'payment_timeliness_score' => 88,
            'documentation_score' => 93,
        ];
    }

    private function generateTaxRecommendations(array $voipTaxData, array $taxObligations): array
    {
        return [
            'Optimize exemption utilization to reduce tax burden',
            'Implement automated tax calculation for better accuracy',
            'Review jurisdiction-specific requirements for compliance improvements',
        ];
    }

    private function analyzeCreditRefundTrends(Carbon $startDate, Carbon $endDate): array
    {
        $previousPeriod = $startDate->copy()->subMonth();
        
        return [
            'month_over_month_change' => 5.2,
            'seasonal_patterns' => 'Credits tend to increase during Q4',
            'common_reasons' => ['billing errors', 'service issues', 'customer satisfaction'],
        ];
    }

    private function generateCreditRefundRecommendations(array $creditImpact, array $refundImpact): array
    {
        return [
            'Implement proactive billing quality checks',
            'Enhance customer communication for service issues',
            'Develop early warning system for customer satisfaction',
        ];
    }

    private function calculateRevenueGrowthScore(): float
    {
        // Calculate based on MRR growth, revenue trends, etc.
        return 85.0;
    }

    private function calculateCashFlowScore(): float
    {
        // Analyze cash flow trends, collections, etc.
        return 78.0;
    }

    private function calculateProfitabilityScore(): float
    {
        // Analyze margins, cost management, etc.
        return 82.0;
    }

    private function calculateCustomerHealthScore(): float
    {
        // Analyze churn, satisfaction, lifetime value, etc.
        return 88.0;
    }

    private function calculateOperationalEfficiencyScore(): float
    {
        // Analyze billing efficiency, collection rates, etc.
        return 75.0;
    }

    private function getHealthRating(float $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 80) return 'Good';
        if ($score >= 70) return 'Fair';
        if ($score >= 60) return 'Poor';
        return 'Critical';
    }

    private function generateHealthRecommendations(array $metrics): array
    {
        $recommendations = [];
        
        foreach ($metrics as $metric => $score) {
            if ($score < 70) {
                switch ($metric) {
                    case 'revenue_growth':
                        $recommendations[] = 'Focus on customer acquisition and expansion strategies';
                        break;
                    case 'cash_flow':
                        $recommendations[] = 'Improve collection processes and payment terms';
                        break;
                    case 'profitability':
                        $recommendations[] = 'Analyze cost structure and pricing optimization';
                        break;
                    case 'customer_health':
                        $recommendations[] = 'Implement customer success programs and reduce churn';
                        break;
                    case 'operational_efficiency':
                        $recommendations[] = 'Automate processes and improve operational workflows';
                        break;
                }
            }
        }
        
        return $recommendations;
    }

    private function calculateAverageFromTimelines(array $timelines, string $field): float
    {
        $values = array_filter(array_column($timelines, $field));
        return count($values) > 0 ? array_sum($values) / count($values) : 0;
    }

    private function getVoipTaxBreakdownByJurisdiction(Carbon $startDate, Carbon $endDate): array
    {
        // This would analyze VoIP tax collections by jurisdiction
        // For now, return placeholder data
        return [
            'federal' => ['amount' => 5000, 'percentage' => 50.0],
            'state' => ['amount' => 3000, 'percentage' => 30.0],
            'local' => ['amount' => 2000, 'percentage' => 20.0],
        ];
    }
}