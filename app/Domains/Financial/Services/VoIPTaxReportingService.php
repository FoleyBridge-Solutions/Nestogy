<?php

namespace App\Domains\Financial\Services;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\InvoiceItem;
use App\Models\VoIPTaxRate;
use App\Models\TaxJurisdiction;
use App\Models\TaxCategory;
use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * VoIP Tax Reporting Service
 * 
 * Comprehensive reporting system for VoIP tax data analysis,
 * regulatory compliance, and business intelligence.
 */
class VoIPTaxReportingService
{
    protected int $companyId;
    protected array $config;

    public function __construct(int $companyId, array $config = [])
    {
        $this->companyId = $companyId;
        $this->config = array_merge([
            'currency_symbol' => '$',
            'date_format' => 'Y-m-d',
            'precision' => 2,
        ], $config);
    }

    /**
     * Generate comprehensive tax summary report.
     */
    public function generateTaxSummaryReport(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $report = [
            'report_type' => 'tax_summary',
            'company_id' => $this->companyId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'generated_at' => now()->toISOString(),
            'filters_applied' => $filters,
            'summary' => [],
            'breakdown_by_jurisdiction' => [],
            'breakdown_by_service_type' => [],
            'breakdown_by_tax_type' => [],
            'monthly_trends' => [],
            'top_clients' => [],
        ];

        // Get all invoices with VoIP services in the period
        $invoices = $this->getInvoicesForPeriod($startDate, $endDate, $filters);
        
        // Calculate overall summary
        $report['summary'] = $this->calculateOverallSummary($invoices);
        
        // Breakdown by jurisdiction
        $report['breakdown_by_jurisdiction'] = $this->getJurisdictionBreakdown($invoices);
        
        // Breakdown by service type
        $report['breakdown_by_service_type'] = $this->getServiceTypeBreakdown($invoices);
        
        // Breakdown by tax type
        $report['breakdown_by_tax_type'] = $this->getTaxTypeBreakdown($invoices);
        
        // Monthly trends (if period is more than one month)
        if ($startDate->diffInMonths($endDate) >= 1) {
            $report['monthly_trends'] = $this->getMonthlyTrends($startDate, $endDate, $filters);
        }
        
        // Top clients by tax paid
        $report['top_clients'] = $this->getTopClientsByTax($invoices);

        return $report;
    }

    /**
     * Generate jurisdiction-specific tax report.
     */
    public function generateJurisdictionReport(int $jurisdictionId, Carbon $startDate, Carbon $endDate): array
    {
        $jurisdiction = TaxJurisdiction::findOrFail($jurisdictionId);
        
        $report = [
            'report_type' => 'jurisdiction_specific',
            'company_id' => $this->companyId,
            'jurisdiction' => [
                'id' => $jurisdiction->id,
                'name' => $jurisdiction->name,
                'type' => $jurisdiction->jurisdiction_type,
                'authority' => $jurisdiction->authority_name,
                'filing_requirements' => $jurisdiction->filing_requirements,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'tax_rates' => [],
            'collections' => [],
            'exemptions' => [],
            'filing_summary' => [],
        ];

        // Get active tax rates for this jurisdiction
        $taxRates = VoIPTaxRate::where('company_id', $this->companyId)
            ->where('tax_jurisdiction_id', $jurisdictionId)
            ->active()
            ->with('category')
            ->get();

        $report['tax_rates'] = $taxRates->map(function ($rate) {
            return [
                'id' => $rate->id,
                'tax_name' => $rate->tax_name,
                'rate_type' => $rate->rate_type,
                'rate_value' => $rate->percentage_rate ?? $rate->fixed_amount,
                'formatted_rate' => $rate->getFormattedRate(),
                'category' => $rate->category->name,
                'effective_date' => $rate->effective_date->toDateString(),
                'service_types' => $rate->service_types,
            ];
        })->toArray();

        // Get tax collections for this jurisdiction
        $invoices = $this->getInvoicesForPeriod($startDate, $endDate);
        $collections = $this->getJurisdictionCollections($invoices, $jurisdiction->name);
        
        $report['collections'] = [
            'total_base_amount' => $collections['base_amount'],
            'total_tax_amount' => $collections['tax_amount'],
            'transaction_count' => $collections['transaction_count'],
            'by_service_type' => $collections['by_service_type'],
            'by_tax_type' => $collections['by_tax_type'],
        ];

        // Get exemptions used for this jurisdiction
        $exemptions = TaxExemptionUsage::where('company_id', $this->companyId)
            ->whereBetween('used_at', [$startDate, $endDate])
            ->whereHas('taxExemption', function ($query) use ($jurisdictionId) {
                $query->where('tax_jurisdiction_id', $jurisdictionId)
                      ->orWhere('is_blanket_exemption', true);
            })
            ->with(['taxExemption', 'client'])
            ->get();

        $report['exemptions'] = [
            'total_exempted_amount' => $exemptions->sum('exempted_amount'),
            'exemption_count' => $exemptions->count(),
            'by_exemption_type' => $exemptions->groupBy('taxExemption.exemption_type')
                ->map(function ($group, $type) {
                    return [
                        'type' => $type,
                        'count' => $group->count(),
                        'amount' => $group->sum('exempted_amount'),
                    ];
                })->values()->toArray(),
        ];

        // Generate filing summary if filing requirements exist
        if ($jurisdiction->filing_requirements) {
            $report['filing_summary'] = $this->generateFilingSummary(
                $jurisdiction,
                $collections,
                $exemptions,
                $startDate,
                $endDate
            );
        }

        return $report;
    }

    /**
     * Generate service type analysis report.
     */
    public function generateServiceTypeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $report = [
            'report_type' => 'service_type_analysis',
            'company_id' => $this->companyId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'service_types' => [],
            'comparative_analysis' => [],
            'trends' => [],
        ];

        $invoices = $this->getInvoicesForPeriod($startDate, $endDate);
        
        // Analyze each service type
        $serviceTypes = ['local', 'long_distance', 'international', 'voip_fixed', 'voip_nomadic', 'data', 'equipment'];
        
        foreach ($serviceTypes as $serviceType) {
            $analysis = $this->analyzeServiceType($invoices, $serviceType);
            if ($analysis['transaction_count'] > 0) {
                $report['service_types'][$serviceType] = $analysis;
            }
        }

        // Comparative analysis
        $report['comparative_analysis'] = $this->getServiceTypeComparison($report['service_types']);

        // Trends over time
        if ($startDate->diffInDays($endDate) > 30) {
            $report['trends'] = $this->getServiceTypeTrends($startDate, $endDate);
        }

        return $report;
    }

    /**
     * Generate exemption usage report.
     */
    public function generateExemptionReport(Carbon $startDate, Carbon $endDate): array
    {
        $report = [
            'report_type' => 'exemption_usage',
            'company_id' => $this->companyId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'summary' => [],
            'by_exemption_type' => [],
            'by_client' => [],
            'certificate_status' => [],
            'savings_analysis' => [],
        ];

        $exemptionUsage = TaxExemptionUsage::where('company_id', $this->companyId)
            ->whereBetween('used_at', [$startDate, $endDate])
            ->with(['taxExemption', 'client', 'invoice'])
            ->get();

        // Overall summary
        $report['summary'] = [
            'total_exemptions_used' => $exemptionUsage->count(),
            'total_amount_exempted' => round($exemptionUsage->sum('exempted_amount'), 2),
            'unique_clients' => $exemptionUsage->pluck('client_id')->unique()->count(),
            'average_exemption_amount' => round($exemptionUsage->avg('exempted_amount'), 2),
        ];

        // Breakdown by exemption type
        $report['by_exemption_type'] = $exemptionUsage
            ->groupBy('taxExemption.exemption_type')
            ->map(function ($group, $type) {
                return [
                    'exemption_type' => $type,
                    'usage_count' => $group->count(),
                    'total_exempted' => round($group->sum('exempted_amount'), 2),
                    'unique_clients' => $group->pluck('client_id')->unique()->count(),
                    'average_per_use' => round($group->avg('exempted_amount'), 2),
                ];
            })->values()->toArray();

        // Top clients by exemption usage
        $report['by_client'] = $exemptionUsage
            ->groupBy('client_id')
            ->map(function ($group) {
                $client = $group->first()->client;
                return [
                    'client_id' => $client->id,
                    'client_name' => $client->name ?? 'Unknown',
                    'usage_count' => $group->count(),
                    'total_exempted' => round($group->sum('exempted_amount'), 2),
                    'exemption_types' => $group->pluck('taxExemption.exemption_type')->unique()->values()->toArray(),
                ];
            })->sortByDesc('total_exempted')->take(10)->values()->toArray();

        // Certificate status analysis
        $allExemptions = TaxExemption::where('company_id', $this->companyId)->get();
        $report['certificate_status'] = [
            'total_certificates' => $allExemptions->count(),
            'active' => $allExemptions->where('status', TaxExemption::STATUS_ACTIVE)->count(),
            'expired' => $allExemptions->filter(fn($e) => $e->isExpired())->count(),
            'expiring_soon' => $allExemptions->filter(fn($e) => $e->isExpiringSoon())->count(),
            'needs_verification' => $allExemptions->where('verification_status', TaxExemption::VERIFICATION_PENDING)->count(),
        ];

        return $report;
    }

    /**
     * Generate tax rate effectiveness report.
     */
    public function generateRateEffectivenessReport(Carbon $startDate, Carbon $endDate): array
    {
        $report = [
            'report_type' => 'rate_effectiveness',
            'company_id' => $this->companyId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'generated_at' => now()->toISOString(),
            'rate_utilization' => [],
            'revenue_by_rate' => [],
            'rate_changes' => [],
            'recommendations' => [],
        ];

        // Get all tax rates and their usage
        $taxRates = VoIPTaxRate::where('company_id', $this->companyId)
            ->with(['jurisdiction', 'category'])
            ->get();

        $invoices = $this->getInvoicesForPeriod($startDate, $endDate);

        foreach ($taxRates as $rate) {
            $usage = $this->analyzeTaxRateUsage($rate, $invoices);
            if ($usage['applications'] > 0) {
                $report['rate_utilization'][] = $usage;
            }
        }

        // Sort by revenue generated
        usort($report['rate_utilization'], function ($a, $b) {
            return $b['total_tax_collected'] <=> $a['total_tax_collected'];
        });

        $report['revenue_by_rate'] = array_slice($report['rate_utilization'], 0, 10);

        // Rate changes analysis
        $report['rate_changes'] = $this->analyzeRateChanges($startDate, $endDate);

        // Generate recommendations
        $report['recommendations'] = $this->generateRateRecommendations($report['rate_utilization']);

        return $report;
    }

    /**
     * Generate dashboard summary data.
     */
    public function generateDashboardData(Carbon $startDate, Carbon $endDate): array
    {
        $invoices = $this->getInvoicesForPeriod($startDate, $endDate);
        $previousPeriodStart = $startDate->copy()->sub($startDate->diffAsCarbonInterval($endDate));
        $previousInvoices = $this->getInvoicesForPeriod($previousPeriodStart, $startDate->copy()->subDay());

        $current = $this->calculateOverallSummary($invoices);
        $previous = $this->calculateOverallSummary($previousInvoices);

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'metrics' => [
                'total_tax_collected' => [
                    'current' => $current['total_tax_collected'],
                    'previous' => $previous['total_tax_collected'],
                    'change_percent' => $this->calculatePercentChange($previous['total_tax_collected'], $current['total_tax_collected']),
                ],
                'total_base_amount' => [
                    'current' => $current['total_base_amount'],
                    'previous' => $previous['total_base_amount'],
                    'change_percent' => $this->calculatePercentChange($previous['total_base_amount'], $current['total_base_amount']),
                ],
                'invoice_count' => [
                    'current' => $current['invoice_count'],
                    'previous' => $previous['invoice_count'],
                    'change_percent' => $this->calculatePercentChange($previous['invoice_count'], $current['invoice_count']),
                ],
                'effective_tax_rate' => [
                    'current' => $current['effective_tax_rate'],
                    'previous' => $previous['effective_tax_rate'],
                    'change_percent' => $this->calculatePercentChange($previous['effective_tax_rate'], $current['effective_tax_rate']),
                ],
            ],
            'top_jurisdictions' => $this->getTopJurisdictions($invoices, 5),
            'recent_exemptions' => $this->getRecentExemptions(10),
            'compliance_alerts' => $this->getComplianceAlerts(),
        ];
    }

    /**
     * Get invoices with VoIP services for a period.
     */
    protected function getInvoicesForPeriod(Carbon $startDate, Carbon $endDate, array $filters = []): Collection
    {
        $query = Invoice::where('company_id', $this->companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('items', function ($q) {
                $q->whereNotNull('service_type');
            })
            ->with(['client', 'items' => function ($q) {
                $q->whereNotNull('service_type');
            }]);

        // Apply filters
        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['service_type'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('service_type', $filters['service_type']);
            });
        }

        return $query->get();
    }

    /**
     * Calculate overall summary statistics.
     */
    protected function calculateOverallSummary(Collection $invoices): array
    {
        $totalBaseAmount = 0;
        $totalTaxCollected = 0;
        $transactionCount = 0;

        foreach ($invoices as $invoice) {
            foreach ($invoice->voipItems as $item) {
                $baseAmount = $item->subtotal - $item->discount;
                $totalBaseAmount += $baseAmount;
                $totalTaxCollected += $item->tax;
                $transactionCount++;
            }
        }

        $effectiveTaxRate = $totalBaseAmount > 0 ? ($totalTaxCollected / $totalBaseAmount) * 100 : 0;

        return [
            'invoice_count' => $invoices->count(),
            'transaction_count' => $transactionCount,
            'total_base_amount' => round($totalBaseAmount, 2),
            'total_tax_collected' => round($totalTaxCollected, 2),
            'average_tax_per_transaction' => $transactionCount > 0 ? round($totalTaxCollected / $transactionCount, 2) : 0,
            'effective_tax_rate' => round($effectiveTaxRate, 2),
        ];
    }

    /**
     * Get breakdown by jurisdiction.
     */
    protected function getJurisdictionBreakdown(Collection $invoices): array
    {
        $breakdown = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->voipItems as $item) {
                if ($item->voip_tax_data && isset($item->voip_tax_data['tax_breakdown'])) {
                    foreach ($item->voip_tax_data['tax_breakdown'] as $tax) {
                        $jurisdiction = $tax['jurisdiction'] ?? 'Unknown';
                        
                        if (!isset($breakdown[$jurisdiction])) {
                            $breakdown[$jurisdiction] = [
                                'jurisdiction_name' => $jurisdiction,
                                'base_amount' => 0,
                                'tax_amount' => 0,
                                'transaction_count' => 0,
                            ];
                        }

                        $breakdown[$jurisdiction]['base_amount'] += $tax['base_amount'];
                        $breakdown[$jurisdiction]['tax_amount'] += $tax['tax_amount'];
                        $breakdown[$jurisdiction]['transaction_count']++;
                    }
                }
            }
        }

        // Sort by tax amount descending
        uasort($breakdown, function ($a, $b) {
            return $b['tax_amount'] <=> $a['tax_amount'];
        });

        return array_values($breakdown);
    }

    /**
     * Get breakdown by service type.
     */
    protected function getServiceTypeBreakdown(Collection $invoices): array
    {
        $breakdown = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->voipItems as $item) {
                $serviceType = $item->service_type;
                
                if (!isset($breakdown[$serviceType])) {
                    $breakdown[$serviceType] = [
                        'service_type' => $serviceType,
                        'base_amount' => 0,
                        'tax_amount' => 0,
                        'transaction_count' => 0,
                    ];
                }

                $baseAmount = $item->subtotal - $item->discount;
                $breakdown[$serviceType]['base_amount'] += $baseAmount;
                $breakdown[$serviceType]['tax_amount'] += $item->tax;
                $breakdown[$serviceType]['transaction_count']++;
            }
        }

        // Sort by base amount descending
        uasort($breakdown, function ($a, $b) {
            return $b['base_amount'] <=> $a['base_amount'];
        });

        return array_values($breakdown);
    }

    /**
     * Get breakdown by tax type.
     */
    protected function getTaxTypeBreakdown(Collection $invoices): array
    {
        $breakdown = [];

        foreach ($invoices as $invoice) {
            foreach ($invoice->voipItems as $item) {
                if ($item->voip_tax_data && isset($item->voip_tax_data['tax_breakdown'])) {
                    foreach ($item->voip_tax_data['tax_breakdown'] as $tax) {
                        $taxType = $tax['tax_type'] ?? 'unknown';
                        
                        if (!isset($breakdown[$taxType])) {
                            $breakdown[$taxType] = [
                                'tax_type' => $taxType,
                                'base_amount' => 0,
                                'tax_amount' => 0,
                                'transaction_count' => 0,
                            ];
                        }

                        $breakdown[$taxType]['base_amount'] += $tax['base_amount'];
                        $breakdown[$taxType]['tax_amount'] += $tax['tax_amount'];
                        $breakdown[$taxType]['transaction_count']++;
                    }
                }
            }
        }

        // Sort by tax amount descending
        uasort($breakdown, function ($a, $b) {
            return $b['tax_amount'] <=> $a['tax_amount'];
        });

        return array_values($breakdown);
    }

    /**
     * Get top clients by tax paid.
     */
    protected function getTopClientsByTax(Collection $invoices, int $limit = 10): array
    {
        $clients = [];

        foreach ($invoices as $invoice) {
            $clientId = $invoice->client_id;
            $clientName = $invoice->client->name ?? 'Unknown';

            if (!isset($clients[$clientId])) {
                $clients[$clientId] = [
                    'client_id' => $clientId,
                    'client_name' => $clientName,
                    'base_amount' => 0,
                    'tax_amount' => 0,
                    'invoice_count' => 0,
                ];
            }

            foreach ($invoice->voipItems as $item) {
                $clients[$clientId]['base_amount'] += $item->subtotal - $item->discount;
                $clients[$clientId]['tax_amount'] += $item->tax;
            }
            $clients[$clientId]['invoice_count']++;
        }

        // Sort by tax amount descending and take top N
        uasort($clients, function ($a, $b) {
            return $b['tax_amount'] <=> $a['tax_amount'];
        });

        return array_slice(array_values($clients), 0, $limit);
    }

    /**
     * Calculate percentage change between two values.
     */
    protected function calculatePercentChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get recent exemptions for dashboard.
     */
    protected function getRecentExemptions(int $limit = 10): array
    {
        return TaxExemptionUsage::where('company_id', $this->companyId)
            ->with(['taxExemption', 'client'])
            ->orderBy('used_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($usage) {
                return [
                    'client_name' => $usage->client->name ?? 'Unknown',
                    'exemption_name' => $usage->taxExemption->exemption_name ?? 'Unknown',
                    'exempted_amount' => $usage->exempted_amount,
                    'used_at' => $usage->used_at->toDateString(),
                ];
            })->toArray();
    }

    /**
     * Get compliance alerts.
     */
    protected function getComplianceAlerts(): array
    {
        $alerts = [];

        // Check for expired exemptions
        $expiredExemptions = TaxExemption::where('company_id', $this->companyId)
            ->where('status', TaxExemption::STATUS_EXPIRED)
            ->orWhere(function ($query) {
                $query->whereNotNull('expiry_date')
                      ->where('expiry_date', '<', now());
            })
            ->count();

        if ($expiredExemptions > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$expiredExemptions} expired tax exemption(s) need attention",
                'action' => 'Review and renew exemption certificates',
            ];
        }

        // Check for exemptions expiring soon
        $expiringSoon = TaxExemption::where('company_id', $this->companyId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->count();

        if ($expiringSoon > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$expiringSoon} tax exemption(s) expiring within 30 days",
                'action' => 'Prepare for renewal',
            ];
        }

        // Check for missing tax rates
        $jurisdictions = TaxJurisdiction::where('company_id', $this->companyId)->active()->count();
        $activeRates = VoIPTaxRate::where('company_id', $this->companyId)->active()->count();

        if ($activeRates === 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => 'No active tax rates configured',
                'action' => 'Configure tax rates for your jurisdictions',
            ];
        }

        return $alerts;
    }

    /**
     * Format currency value.
     */
    protected function formatCurrency(float $amount): string
    {
        return $this->config['currency_symbol'] . number_format($amount, $this->config['precision']);
    }

    // Additional helper methods would be implemented here...
    protected function getMonthlyTrends($startDate, $endDate, $filters) { return []; }
    protected function getJurisdictionCollections($invoices, $jurisdictionName) { return []; }
    protected function generateFilingSummary($jurisdiction, $collections, $exemptions, $startDate, $endDate) { return []; }
    protected function analyzeServiceType($invoices, $serviceType) { return []; }
    protected function getServiceTypeComparison($serviceTypes) { return []; }
    protected function getServiceTypeTrends($startDate, $endDate) { return []; }
    protected function analyzeTaxRateUsage($rate, $invoices) { return []; }
    protected function analyzeRateChanges($startDate, $endDate) { return []; }
    protected function generateRateRecommendations($utilization) { return []; }
    protected function getTopJurisdictions($invoices, $limit) { return []; }
}